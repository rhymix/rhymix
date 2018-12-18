<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * Controller class of the file module
 * @author NAVER (developers@xpressengine.com)
 */
class fileController extends file
{
	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{
	}

	/**
	 * Upload attachments in the editor
	 *
	 * Determine the upload target srl from editor_sequence and uploadTargetSrl variables.
	 * Create and return the UploadTargetSrl if not exists so that UI can use the value
	 * for sync.
	 *
	 * @return void
	 */
	function procFileUpload()
	{
		Context::setRequestMethod('JSON');
		$file_info = Context::get('Filedata');

		// An error appears if not a normally uploaded file
		if(!$file_info || !is_uploaded_file($file_info['tmp_name'])) exit();

		// Basic variables setting
		$oFileModel = getModel('file');
		$editor_sequence = Context::get('editor_sequence');
		$module_srl = $this->module_srl;
		
		// Exit a session if there is neither upload permission nor information
		if(!$_SESSION['upload_info'][$editor_sequence]->enabled)
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}
		
		// Get upload_target_srl
		$upload_target_srl = intval(Context::get('uploadTargetSrl')) ?: intval(Context::get('upload_target_srl'));
		if (!$upload_target_srl)
		{
			$upload_target_srl = $_SESSION['upload_info'][$editor_sequence]->upload_target_srl;
		}
		if (!$upload_target_srl)
		{
			$upload_target_srl = getNextSequence();
			$_SESSION['upload_info'][$editor_sequence]->upload_target_srl = $upload_target_srl;
		}
		
		// Handle chunking
		if (preg_match('!^bytes (\d+)-(\d+)/(\d+)$!', $_SERVER['HTTP_CONTENT_RANGE'], $matches))
		{
			// Check basic sanity
			$chunk_start = intval($matches[1]);
			$chunk_size = ($matches[2] - $matches[1]) + 1;
			$total_size = intval($matches[3]);
			if ($chunk_start < 0 || $chunk_size < 0 || $total_size < 0 || $chunk_start + $chunk_size > $total_size || $chunk_size != $file_info['size'])
			{
				throw new Rhymix\Framework\Exception('msg_upload_invalid_chunk');
			}
			$this->add('chunk_current_size', $chunk_size);
			$this->add('chunk_uploaded_size', $chunk_start);
			
			// Check existing chunks
			$nonce = Context::get('nonce');
			$temp_key = hash_hmac('sha1', sprintf('%d:%d:%d:%s:%s', $editor_sequence, $upload_target_srl, $module_srl, $file_info['name'], $nonce), config('crypto.authentication_key'));
			$temp_filename = RX_BASEDIR . 'files/attach/chunks/' . $temp_key;
			if ($chunk_start == 0 && Rhymix\Framework\Storage::isFile($temp_filename))
			{
				Rhymix\Framework\Storage::delete($temp_filename);
				$this->add('chunk_status', 11);
				throw new Rhymix\Framework\Exception('msg_upload_invalid_chunk');
			}
			if ($chunk_start != 0 && (!Rhymix\Framework\Storage::isFile($temp_filename) || Rhymix\Framework\Storage::getSize($temp_filename) != $chunk_start))
			{
				Rhymix\Framework\Storage::delete($temp_filename);
				$this->add('chunk_status', 12);
				throw new Rhymix\Framework\Exception('msg_upload_invalid_chunk');
			}
			
			// Check size limit
			$is_admin = (Context::get('logged_info')->is_admin === 'Y');
			if (!$is_admin)
			{
				$module_config = getModel('file')->getFileConfig($module_srl);
				$allowed_attach_size = $module_config->allowed_attach_size * 1024 * 1024;
				$allowed_filesize = $module_config->allowed_filesize * 1024 * 1024;
				if ($total_size > $allowed_filesize)
				{
					$this->add('chunk_status', 21);
					throw new Rhymix\Framework\Exception('msg_exceeds_limit_size');
				}
				$output = executeQuery('file.getAttachedFileSize', (object)array('upload_target_srl' => $upload_target_srl));
				if (intval($output->data->attached_size) + $total_size > $allowed_attach_size)
				{
					$this->add('chunk_status', 22);
					throw new Rhymix\Framework\Exception('msg_exceeds_limit_size');
				}
			}
			
			// Append to chunk
			$fp = fopen($file_info['tmp_name'], 'r');
			$success = Rhymix\Framework\Storage::write($temp_filename, $fp, 'a');
			if ($success && Rhymix\Framework\Storage::getSize($temp_filename) == $chunk_start + $chunk_size)
			{
				$this->add('chunk_status', 0);
				$this->add('chunk_uploaded_size', $chunk_start + $chunk_size);
				if ($chunk_start + $chunk_size == $total_size)
				{
					$file_info['tmp_name'] = $temp_filename;
					$file_info['size'] = Rhymix\Framework\Storage::getSize($temp_filename);
				}
				else
				{
					return;
				}
			}
			else
			{
				Rhymix\Framework\Storage::delete($temp_filename);
				$this->add('chunk_status', 40);
				throw new Rhymix\Framework\Exception('msg_upload_invalid_chunk');
			}
		}
		else
		{
			$this->add('chunk_status', -1);
		}
		
		// Save the file
		$output = $this->insertFile($file_info, $module_srl, $upload_target_srl);
		
		Context::setResponseMethod('JSON');
		$this->add('file_srl', $output->get('file_srl'));
		$this->add('file_size', $output->get('file_size'));
		$this->add('direct_download', $output->get('direct_download'));
		$this->add('source_filename', $output->get('source_filename'));
		$this->add('upload_target_srl', $output->get('upload_target_srl'));
		$this->add('download_url', $oFileModel->getDirectFileUrl($output->get('uploaded_filename')));
		
		if($output->error != '0')
		{
			throw new Rhymix\Framework\Exception($output->message);
		}
	}

	/**
	 * Iframe upload attachments
	 *
	 * @return Object
	 */
	function procFileIframeUpload()
	{
		// Basic variables setting
		$editor_sequence = Context::get('editor_sequence');
		$callback = Context::get('callback');
		$module_srl = $this->module_srl;
		$upload_target_srl = intval(Context::get('uploadTargetSrl'));
		if(!$upload_target_srl) $upload_target_srl = intval(Context::get('upload_target_srl'));

		// Exit a session if there is neither upload permission nor information
		if(!$_SESSION['upload_info'][$editor_sequence]->enabled) exit();
		// Extract from session information if upload_target_srl is not specified
		if(!$upload_target_srl) $upload_target_srl = $_SESSION['upload_info'][$editor_sequence]->upload_target_srl;
		// Create if upload_target_srl is not defined in the session information
		if(!$upload_target_srl) $_SESSION['upload_info'][$editor_sequence]->upload_target_srl = $upload_target_srl = getNextSequence();
		// Delete and then attempt to re-upload if file_srl is requested
		$file_srl = Context::get('file_srl');
		if($file_srl)
		{
			$oFileModel = getModel('file');
			$logged_info = Context::get('logged_info');
			$file_info = $oFileModel->getFile($file_srl);
			if($file_info->file_srl == $file_srl && $oFileModel->getFileGrant($file_info, $logged_info)->is_deletable)
			{
				$this->deleteFile($file_srl);
			}
		}

		$file_info = Context::get('Filedata');
		// An error appears if not a normally uploaded file
		if(is_uploaded_file($file_info['tmp_name'])) {
			$output = $this->insertFile($file_info, $module_srl, $upload_target_srl);
			Context::set('uploaded_fileinfo',$output);
		}

		Context::set('layout','none');

		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('iframe');
	}

	/**
	 * Image resize
	 *
	 * @return Object
	 */
	function procFileImageResize()
	{
		$file_srl = Context::get('file_srl');
		$width = Context::get('width');
		$height = Context::get('height');

		if(!$file_srl || !$width)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		$oFileModel = getModel('file');
		$fileInfo = $oFileModel->getFile($file_srl);
		if(!$fileInfo || $fileInfo->direct_download != 'Y')
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		$source_src = $fileInfo->uploaded_filename;
		$output_src = $source_src . '.resized' . strrchr($source_src,'.');

		if(!$height) $height = $width-1;

		if(FileHandler::createImageFile($source_src,$output_src,$width,$height,'','ratio'))
		{
			$output = new stdClass();
			$output->info = getimagesize($output_src);
			$output->src = $output_src;
		}
		else
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		$this->add('resized_info',$output);
	}

	/**
	 * Download Attachment
	 *
	 * <pre>
	 * Receive a request directly
	 * file_srl: File sequence
	 * sid : value in DB for comparison, No download if not matched
	 *
	 * This method call trigger 'file.downloadFile'.
	 * before, after.
	 * Trigger object contains:
	 * - download_url
	 * - file_srl
	 * - upload_target_srl
	 * - upload_target_type
	 * - sid
	 * - module_srl
	 * - member_srl
	 * - download_count
	 * - direct_download
	 * - source_filename
	 * - uploaded_filename
	 * - file_size
	 * - comment
	 * - isvalid
	 * - regdate
	 * - ipaddress
	 * </pre>
	 *
	 * return void
	 */
	function procFileDownload()
	{
		$oFileModel = getModel('file');

		if(isset($this->grant->access) && $this->grant->access !== true)
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}

		$file_srl = Context::get('file_srl');
		$sid = Context::get('sid');
		$logged_info = Context::get('logged_info');
		// Get file information from the DB
		$columnList = array('file_srl', 'sid', 'isvalid', 'source_filename', 'module_srl', 'uploaded_filename', 'file_size', 'member_srl', 'upload_target_srl', 'upload_target_type');
		$file_obj = $oFileModel->getFile($file_srl, $columnList);
		// If the requested file information is incorrect, an error that file cannot be found appears
		if($file_obj->file_srl != $file_srl || $file_obj->sid !== $sid)
		{
			throw new Rhymix\Framework\Exceptions\TargetNotFound('msg_file_not_found');
		}
		// Notify that file download is not allowed when standing-by(Only a top-administrator is permitted)
		if($logged_info->is_admin != 'Y' && $file_obj->isvalid != 'Y')
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted('msg_not_permitted_download');
		}
		// File name
		$filename = $file_obj->source_filename;
		$file_module_config = $oFileModel->getFileModuleConfig($file_obj->module_srl);
		// Not allow the file outlink
		if($file_module_config->allow_outlink == 'N' && $_SERVER["HTTP_REFERER"])
		{
			// Handles extension to allow outlink
			if($file_module_config->allow_outlink_format)
			{
				$allow_outlink_format_array = array();
				$allow_outlink_format_array = explode(',', $file_module_config->allow_outlink_format);
				if(!is_array($allow_outlink_format_array)) $allow_outlink_format_array[0] = $file_module_config->allow_outlink_format;

				foreach($allow_outlink_format_array as $val)
				{
					$val = trim($val);
					if(preg_match("/\.{$val}$/i", $filename))
					{
						$file_module_config->allow_outlink = 'Y';
						break;
					}
				}
			}
			// Sites that outlink is allowed
			if($file_module_config->allow_outlink != 'Y')
			{
				$referer = parse_url($_SERVER["HTTP_REFERER"]);
				if($referer['host'] != $_SERVER['HTTP_HOST'])
				{
					if($file_module_config->allow_outlink_site)
					{
						$allow_outlink_site_array = array();
						$allow_outlink_site_array = explode("\n", $file_module_config->allow_outlink_site);
						if(!is_array($allow_outlink_site_array)) $allow_outlink_site_array[0] = $file_module_config->allow_outlink_site;

						foreach($allow_outlink_site_array as $val)
						{
							$site = parse_url(trim($val));
							if($site['host'] == $referer['host'])
							{
								$file_module_config->allow_outlink = 'Y';
								break;
							}
						}
					}
				}
				else $file_module_config->allow_outlink = 'Y';
			}
			if($file_module_config->allow_outlink != 'Y')
			{
				throw new Rhymix\Framework\Exceptions\NotPermitted('msg_not_allowed_outlink');
			}
		}

		// Check if a permission for file download is granted
		$downloadGrantCount = 0;
		if(is_array($file_module_config->download_grant))
		{
			foreach($file_module_config->download_grant AS $value)
				if($value) $downloadGrantCount++;
		}

		if(is_array($file_module_config->download_grant) && $downloadGrantCount>0)
		{
			if(!Context::get('is_logged'))
			{
				throw new Rhymix\Framework\Exceptions\NotPermitted('msg_not_permitted_download');
			}
			
			$logged_info = Context::get('logged_info');
			if($logged_info->is_admin != 'Y')
			{
				$oModuleModel =& getModel('module');
				$columnList = array('module_srl', 'site_srl');
				$module_info = $oModuleModel->getModuleInfoByModuleSrl($file_obj->module_srl, $columnList);

				if(!$oModuleModel->isSiteAdmin($logged_info, $module_info->site_srl))
				{
					$oMemberModel =& getModel('member');
					$member_groups = $oMemberModel->getMemberGroups($logged_info->member_srl, $module_info->site_srl);

					$is_permitted = false;
					for($i=0;$i<count($file_module_config->download_grant);$i++)
					{
						$group_srl = $file_module_config->download_grant[$i];
						if($member_groups[$group_srl])
						{
							$is_permitted = true;
							break;
						}
					}
					if(!$is_permitted)
					{
						throw new Rhymix\Framework\Exceptions\NotPermitted('msg_not_permitted_download');
					}
				}
			}
		}

		// Call a trigger (before)
		$output = ModuleHandler::triggerCall('file.downloadFile', 'before', $file_obj);
		if(!$output->toBool())
		{
			if ($output->message)
			{
				throw new Rhymix\Framework\Exception($output->message);
			}
			else
			{
				throw new Rhymix\Framework\Exceptions\NotPermitted('msg_not_permitted_download');
			}
		}

		// Increase download_count
		$args = new stdClass();
		$args->file_srl = $file_srl;
		executeQuery('file.updateFileDownloadCount', $args);

		// Call a trigger (after)
		ModuleHandler::triggerCall('file.downloadFile', 'after', $file_obj);

		// Redirect to procFileOutput using file key
		if(!isset($_SESSION['__XE_FILE_KEY__']) || !is_string($_SESSION['__XE_FILE_KEY__']) || strlen($_SESSION['__XE_FILE_KEY__']) != 32)
		{
			$_SESSION['__XE_FILE_KEY__'] = Rhymix\Framework\Security::getRandom(32, 'hex');
		}
		$file_key_data = $file_obj->file_srl . $file_obj->file_size . $file_obj->uploaded_filename . $_SERVER['REMOTE_ADDR'];
		$file_key = substr(hash_hmac('sha256', $file_key_data, $_SESSION['__XE_FILE_KEY__']), 0, 32);
		header('Location: '.getNotEncodedUrl('', 'act', 'procFileOutput', 'file_srl', $file_srl, 'file_key', $file_key, 'force_download', Context::get('force_download') === 'Y' ? 'Y' : null));
		Context::close();
		exit();
	}

	public function procFileOutput()
	{
		// Get requsted file info
		$oFileModel = getModel('file');
		$file_srl = Context::get('file_srl');
		$file_key = Context::get('file_key');

		$columnList = array('source_filename', 'uploaded_filename', 'file_size');
		$file_obj = $oFileModel->getFile($file_srl, $columnList);
		$file_config = $oFileModel->getFileConfig($file_obj->module_srl ?: null);
		$filesize = $file_obj->file_size;
		$filename = $file_obj->source_filename;
		$etag = md5($file_srl . $file_key . $_SERVER['HTTP_USER_AGENT']);

		// Check file key
		if(strlen($file_key) != 32 || !isset($_SESSION['__XE_FILE_KEY__']) || !is_string($_SESSION['__XE_FILE_KEY__']))
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		$file_key_data = $file_srl . $file_obj->file_size . $file_obj->uploaded_filename . $_SERVER['REMOTE_ADDR'];
		$file_key_compare = substr(hash_hmac('sha256', $file_key_data, $_SESSION['__XE_FILE_KEY__']), 0, 32);
		if($file_key !== $file_key_compare)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		
		// Check if file exists
		$uploaded_filename = $file_obj->uploaded_filename;
		if(!file_exists($uploaded_filename))
		{
			throw new Rhymix\Framework\Exceptions\TargetNotFound('msg_file_not_found');
		}

		// If client sent an If-None-Match header with the correct ETag, do not download again
		if(isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim(trim($_SERVER['HTTP_IF_NONE_MATCH']), '\'"') === $etag)
		{
			header('HTTP/1.1 304 Not Modified');
			exit(); 
		}

		// If client sent an If-Modified-Since header with a recent modification date, do not download again
		if(isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) > filemtime($uploaded_filename))
		{
			header('HTTP/1.1 304 Not Modified');
			exit();
		}

		// Encode the filename.
		$filename_param = Rhymix\Framework\UA::encodeFilenameForDownload($filename);

		// Close context to prevent blocking the session
		Context::close();

		// Open file
		$fp = fopen($uploaded_filename, 'rb');
		if(!$fp)
		{
			throw new Rhymix\Framework\Exceptions\TargetNotFound('msg_file_not_found');
		}

		// Take care of pause and resume
		if(isset($_SERVER['HTTP_RANGE']) && preg_match('/^bytes=(\d+)-(\d+)?/', $_SERVER['HTTP_RANGE'], $matches))
		{
			$range_start = $matches[1];
			$range_end = $matches[2] ? $matches[2] : ($filesize - 1);
			$range_length = $range_end - $range_start + 1;
			if($range_length < 1 || $range_start < 0 || $range_start >= $filesize || $range_end >= $filesize)
			{
				header('HTTP/1.1 416 Requested Range Not Satisfiable');
				fclose($fp);
				exit();
			}
            fseek($fp, $range_start);
			header('HTTP/1.1 206 Partial Content');
			header('Content-Range: bytes ' . $range_start . '-' . $range_end . '/' . $filesize);
		}
		else
		{
			$range_start = 0;
			$range_length = $filesize - $range_start;
		}

		// Determine download type
		$download_type = 'attachment';
		$mime_type = Rhymix\Framework\MIME::getTypeByFilename($filename);
		if (starts_with('image/', $mime_type) && in_array('image', $file_config->inline_download_format))
		{
			$download_type = 'inline';
		}
		if (starts_with('audio/', $mime_type) && in_array('audio', $file_config->inline_download_format))
		{
			$download_type = 'inline';
		}
		if (starts_with('video/', $mime_type) && in_array('video', $file_config->inline_download_format))
		{
			$download_type = 'inline';
		}
		if (starts_with('text/', $mime_type) && ($mime_type !== 'text/html') && in_array('text', $file_config->inline_download_format))
		{
			$download_type = 'inline';
		}
		if ($mime_type === 'application/pdf' && in_array('pdf', $file_config->inline_download_format))
		{
			$download_type = 'inline';
		}
		if (Context::get('force_download') === 'Y')
		{
			$download_type = 'attachment';
		}
		
		// Clear buffer
		while(ob_get_level()) ob_end_clean();
		
		// Set filename headers
		header('Content-Type: ' . ($download_type === 'inline' ? $mime_type : 'application/octet-stream'));
		header('Content-Disposition: ' . $download_type . '; ' . $filename_param);
		
		// Set cache headers
		header('Cache-Control: private; max-age=3600');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Pragma: ');
		
		// Set other headers
		header('Content-Transfer-Encoding: binary');
		header('Content-Length: ' . $range_length);
		header('Accept-Ranges: bytes');
		header('Etag: "' . $etag . '"');

		// Print the file contents
		for($offset = 0; $offset < $range_length; $offset += 4096)
		{
			$buffer_size = min(4096, $range_length - $offset);
			echo fread($fp, $buffer_size);
			flush();
		}

		exit();
	}

	/**
	 * Delete an attachment from the editor
	 *
	 * @return Object
	 */
	function procFileDelete()
	{
		// Basic variable setting(upload_target_srl and module_srl set)
		$editor_sequence = Context::get('editor_sequence');
		$file_srl = Context::get('file_srl');
		$file_srls = Context::get('file_srls');
		if($file_srls) $file_srl = $file_srls;
		// Exit a session if there is neither upload permission nor information
		if(!$_SESSION['upload_info'][$editor_sequence]->enabled) exit();

		$upload_target_srl = $_SESSION['upload_info'][$editor_sequence]->upload_target_srl;

		$logged_info = Context::get('logged_info');
		$oFileModel = getModel('file');

		$srls = explode(',',$file_srl);
		if(!count($srls)) return;

		for($i=0;$i<count($srls);$i++)
		{
			$srl = (int)$srls[$i];
			if(!$srl) continue;

			$args = new stdClass;
			$args->file_srl = $srl;
			$output = executeQuery('file.getFile', $args);
			if(!$output->toBool()) continue;

			$file_info = $output->data;
			if(!$file_info) continue;

			$file_grant = $oFileModel->getFileGrant($file_info, $logged_info);

			if(!$file_grant->is_deletable) continue;

			if($upload_target_srl && $file_srl) $output = $this->deleteFile($file_srl);
		}
	}

	/**
	 * get file list
	 *
	 * @return Object
	 */
	function procFileGetList()
	{
		if(!Context::get('is_logged'))
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}
		
		$logged_info = Context::get('logged_info');
		if($logged_info->is_admin !== 'Y' && !getModel('module')->isSiteAdmin($logged_info))
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}
		
		$fileSrls = Context::get('file_srls');
		if($fileSrls) $fileSrlList = explode(',', $fileSrls);

		global $lang;
		if(count($fileSrlList) > 0)
		{
			$oFileModel = getModel('file');
			$fileList = $oFileModel->getFile($fileSrlList);
			if(!is_array($fileList)) $fileList = array($fileList);

			if(is_array($fileList))
			{
				foreach($fileList AS $key=>$value)
				{
					$value->human_file_size = FileHandler::filesize($value->file_size);
					if($value->isvalid=='Y') $value->validName = $lang->is_valid;
					else $value->validName = $lang->is_stand_by;
				}
			}
		}
		else
		{
			$fileList = array();
			$this->setMessage($lang->no_files);
		}

		$this->add('file_list', $fileList);
	}
	/**
	 * A trigger to return numbers of attachments in the upload_target_srl (document_srl)
	 *
	 * @param object $obj Trigger object
	 * @return Object
	 */
	function triggerCheckAttached(&$obj)
	{
		$document_srl = $obj->document_srl;
		if(!$document_srl) return;
		
		// Get numbers of attachments
		$oFileModel = getModel('file');
		$obj->uploaded_count = $oFileModel->getFilesCount($document_srl);
		// TODO: WTF are we doing with uploaded_count anyway?
	}

	/**
	 * A trigger to link the attachment with the upload_target_srl (document_srl)
	 *
	 * @param object $obj Trigger object
	 * @return Object
	 */
	function triggerAttachFiles(&$obj)
	{
		$document_srl = $obj->document_srl;
		if(!$document_srl) return;

		$output = $this->setFilesValid($document_srl);
		if(!$output->toBool()) return $output;
	}

	/**
	 * A trigger to delete the attachment in the upload_target_srl (document_srl)
	 *
	 * @param object $obj Trigger object
	 * @return Object
	 */
	function triggerDeleteAttached(&$obj)
	{
		$document_srl = $obj->document_srl;
		if(!$document_srl) return;

		$output = $this->deleteFiles($document_srl);
		return $output;
	}

	/**
	 * A trigger to return numbers of attachments in the upload_target_srl (comment_srl)
	 *
	 * @param object $obj Trigger object
	 * @return Object
	 */
	function triggerCommentCheckAttached(&$obj)
	{
		$comment_srl = $obj->comment_srl;
		if(!$comment_srl) return;
		// Get numbers of attachments
		$oFileModel = getModel('file');
		$obj->uploaded_count = $oFileModel->getFilesCount($comment_srl);
	}

	/**
	 * A trigger to link the attachment with the upload_target_srl (comment_srl)
	 *
	 * @param object $obj Trigger object
	 * @return Object
	 */
	function triggerCommentAttachFiles(&$obj)
	{
		$comment_srl = $obj->comment_srl;
		$uploaded_count = $obj->uploaded_count;
		if(!$comment_srl || !$uploaded_count) return;

		$output = $this->setFilesValid($comment_srl);
		if(!$output->toBool()) return $output;
	}

	/**
	 * A trigger to delete the attachment in the upload_target_srl (comment_srl)
	 *
	 * @param object $obj Trigger object
	 * @return Object
	 */
	function triggerCommentDeleteAttached(&$obj)
	{
		$comment_srl = $obj->comment_srl;
		if(!$comment_srl) return;

		if($obj->isMoveToTrash) return;

		$output = $this->deleteFiles($comment_srl);
		return $output;
	}

	/**
	 * A trigger to delete all the attachements when deleting the module
	 *
	 * @param object $obj Trigger object
	 * @return Object
	 */
	function triggerDeleteModuleFiles(&$obj)
	{
		$module_srl = $obj->module_srl;
		if(!$module_srl) return;
		
		return $this->deleteModuleFiles($module_srl);
	}

	/**
	 * Upload enabled
	 *
	 * @param int $editor_sequence
	 * @param int $upload_target_srl
	 * @return void
	 */
	function setUploadInfo($editor_sequence, $upload_target_srl=0)
	{
		if(!isset($_SESSION['upload_info']) || !is_array($_SESSION['upload_info']))
		{
			$_SESSION['upload_info'] = array();
		}
		if(!isset($_SESSION['upload_info'][$editor_sequence]))
		{
			$_SESSION['upload_info'][$editor_sequence] = new stdClass();
		}
		$_SESSION['upload_info'][$editor_sequence]->enabled = true;
		$_SESSION['upload_info'][$editor_sequence]->upload_target_srl = $upload_target_srl;
	}

	/**
	 * Set the attachements of the upload_target_srl to be valid
	 * By changing its state to valid when a document is inserted, it prevents from being considered as a unnecessary file
	 *
	 * @param int $upload_target_srl
	 * @return Object
	 */
	function setFilesValid($upload_target_srl)
	{
		$args = new stdClass();
		$args->upload_target_srl = $upload_target_srl;
		return executeQuery('file.updateFileValid', $args);
	}

	/**
	 * Add an attachement
	 *
	 * <pre>
	 * This method call trigger 'file.insertFile'.
	 *
	 * Before trigger object contains:
	 * - module_srl
	 * - upload_target_srl
	 *
	 * After trigger object contains:
	 * - file_srl
	 * - upload_target_srl
	 * - module_srl
	 * - direct_download
	 * - source_filename
	 * - uploaded_filename
	 * - donwload_count
	 * - file_size
	 * - comment
	 * - member_srl
	 * - sid
	 * </pre>
	 *
	 * @param object $file_info PHP file information array
	 * @param int $module_srl Sequence of module to upload file
	 * @param int $upload_target_srl Sequence of target to upload file
	 * @param int $download_count Initial download count
	 * @param bool $manual_insert If set true, pass validation check
	 * @return Object
	 */
	function insertFile($file_info, $module_srl, $upload_target_srl, $download_count = 0, $manual_insert = false)
	{
		// Call a trigger (before)
		$trigger_obj = new stdClass;
		$trigger_obj->file_info = $file_info;
		$trigger_obj->module_srl = $module_srl;
		$trigger_obj->upload_target_srl = $upload_target_srl;
		$output = ModuleHandler::triggerCall('file.insertFile', 'before', $trigger_obj);
		if(!$output->toBool()) return $output;

		// A workaround for Firefox upload bug
		if(preg_match('/^=\?UTF-8\?B\?(.+)\?=$/i', $file_info['name'], $match))
		{
			$file_info['name'] = base64_decode(strtr($match[1], ':', '/'));
		}

		if(!$manual_insert)
		{
			// Get the file configurations
			$logged_info = Context::get('logged_info');
			if($logged_info->is_admin != 'Y')
			{
				$oFileModel = getModel('file');
				$config = $oFileModel->getFileConfig($module_srl);

				// check file type
				if(isset($config->allowed_filetypes) && $config->allowed_filetypes !== '*.*')
				{
					$filetypes = explode(';', $config->allowed_filetypes);
					$ext = array();
					foreach($filetypes as $item) {
						$item = explode('.', $item);
						$ext[] = strtolower($item[1]);
					}
					$uploaded_ext = explode('.', $file_info['name']);
					$uploaded_ext = strtolower(array_pop($uploaded_ext));

					if(!in_array($uploaded_ext, $ext))
					{
						throw new Rhymix\Framework\Exception('msg_not_allowed_filetype');
					}
				}

				$allowed_filesize = $config->allowed_filesize * 1024 * 1024;
				$allowed_attach_size = $config->allowed_attach_size * 1024 * 1024;
				// An error appears if file size exceeds a limit
				if($allowed_filesize < filesize($file_info['tmp_name'])) throw new Rhymix\Framework\Exception('msg_exceeds_limit_size');
				// Get total file size of all attachements (from DB)
				$size_args = new stdClass;
				$size_args->upload_target_srl = $upload_target_srl;
				$output = executeQuery('file.getAttachedFileSize', $size_args);
				$attached_size = (int)$output->data->attached_size + filesize($file_info['tmp_name']);
				if($attached_size > $allowed_attach_size) throw new Rhymix\Framework\Exception('msg_exceeds_limit_size');
			}
		}

		// Sanitize filename
		$file_info['name'] = Rhymix\Framework\Filters\FilenameFilter::clean($file_info['name']);
		
		// Get file_srl
		$file_srl = getNextSequence();
		$file_regdate = date('YmdHis');

		// Set upload path by checking if the attachement is an image or other kinds of file
		if(Rhymix\Framework\Filters\FilenameFilter::isDirectDownload($file_info['name']))
		{
			$path = $this->getStoragePath('images', $file_srl, $module_srl, $upload_target_srl, $file_regdate);

			// change to random file name. because window php bug. window php is not recognize unicode character file name - by cherryfilter
			$ext = substr(strrchr($file_info['name'],'.'),1);
			$filename = $path . Rhymix\Framework\Security::getRandom(32, 'hex') . '.' . $ext;
			while(file_exists($filename))
			{
				$filename = $path . Rhymix\Framework\Security::getRandom(32, 'hex') . '.' . $ext;
			}
			$direct_download = 'Y';
		}
		else
		{
			$path = $this->getStoragePath('binaries', $file_srl, $module_srl, $upload_target_srl, $file_regdate);
			$filename = $path . Rhymix\Framework\Security::getRandom(32, 'hex');
			while(file_exists($filename))
			{
				$filename = $path . Rhymix\Framework\Security::getRandom(32, 'hex');
			}
			$direct_download = 'N';
		}

		// Create a directory
		if(!Rhymix\Framework\Storage::isDirectory($path) && !Rhymix\Framework\Storage::createDirectory($path))
		{
			throw new Rhymix\Framework\Exception('msg_not_permitted_create');
		}
		
		// Move the file
		if($manual_insert)
		{
			@copy($file_info['tmp_name'], $filename);
			if(!file_exists($filename))
			{
				@copy($file_info['tmp_name'], $filename);
				if(!file_exists($filename))
				{
					throw new Rhymix\Framework\Exception('msg_file_upload_error');
				}
			}
		}
		elseif(starts_with(RX_BASEDIR . 'files/attach/chunks/', $file_info['tmp_name']))
		{
			if (!Rhymix\Framework\Storage::move($file_info['tmp_name'], $filename))
			{
				if (!Rhymix\Framework\Storage::move($file_info['tmp_name'], $filename))
				{
					throw new Rhymix\Framework\Exception('msg_file_upload_error');
				}
			}
		}
		else
		{
			if(!@move_uploaded_file($file_info['tmp_name'], $filename))
			{
				if(!@move_uploaded_file($file_info['tmp_name'], $filename))
				{
					throw new Rhymix\Framework\Exception('msg_file_upload_error');
				}
			}
		}
		
		// Get member information
		$oMemberModel = getModel('member');
		$member_srl = $oMemberModel->getLoggedMemberSrl();
		// List file information
		$args = new stdClass;
		$args->file_srl = $file_srl;
		$args->upload_target_srl = $upload_target_srl;
		$args->module_srl = $module_srl;
		$args->direct_download = $direct_download;
		$args->source_filename = $file_info['name'];
		$args->uploaded_filename = './' . substr($filename, strlen(RX_BASEDIR));
		$args->download_count = $download_count;
		$args->file_size = @filesize($filename);
		$args->comment = NULL;
		$args->member_srl = $member_srl;
		$args->regdate = $file_regdate;
		$args->sid = Rhymix\Framework\Security::getRandom(32, 'hex');

		$output = executeQuery('file.insertFile', $args);
		if(!$output->toBool()) return $output;
		
		// Call a trigger (after)
		ModuleHandler::triggerCall('file.insertFile', 'after', $args);

		$_SESSION['__XE_UPLOADING_FILES_INFO__'][$args->file_srl] = true;

		$output->add('file_srl', $args->file_srl);
		$output->add('file_size', $args->file_size);
		$output->add('sid', $args->sid);
		$output->add('direct_download', $args->direct_download);
		$output->add('source_filename', $args->source_filename);
		$output->add('upload_target_srl', $upload_target_srl);
		$output->add('uploaded_filename', $args->uploaded_filename);
		return $output;
	}

	/**
	 * Delete the attachment
	 *
	 * <pre>
	 * This method call trigger 'file.deleteFile'.
	 * Before, after trigger object contains:
	 * - download_url
	 * - file_srl
	 * - upload_target_srl
	 * - upload_target_type
	 * - sid
	 * - module_srl
	 * - member_srl
	 * - download_count
	 * - direct_download
	 * - source_filename
	 * - uploaded_filename
	 * - file_size
	 * - comment
	 * - isvalid
	 * - regdate
	 * - ipaddress
	 * </pre>
	 *
	 * @param array|int $file_list or $file_srl
	 * @return Object
	 */
	function deleteFile($file_list)
	{
		if(!is_array($file_list))
		{
			$file_list = explode(',', $file_list);
		}
		
		if(empty($file_list))
		{
			return new BaseObject();
		}
		
		foreach($file_list as $file)
		{
			if(!is_object($file))
			{
				if(!$file_srl = (int) $file)
				{
					continue;
				}
				$file = getModel('file')->getFile($file_srl);
			}
			
			if(empty($file->file_srl))
			{
				continue;
			}
			
			// Call a trigger (before)
			$output = ModuleHandler::triggerCall('file.deleteFile', 'before', $file);
			if(!$output->toBool()) return $output;
			
			// Remove from the DB
			$output = executeQuery('file.deleteFile', $file);
			if(!$output->toBool()) return $output;
			
			// If successfully deleted, remove the file
			Rhymix\Framework\Storage::delete(FileHandler::getRealPath($file->uploaded_filename));
			
			// Call a trigger (after)
			ModuleHandler::triggerCall('file.deleteFile', 'after', $file);
			
			// Remove empty directories
			Rhymix\Framework\Storage::deleteEmptyDirectory(dirname(FileHandler::getRealPath($file->uploaded_filename)), true);
		}
		
		return new BaseObject();
	}
	
	/**
	 * Delete all attachments of a particular document
	 *
	 * @param int $upload_target_srl Upload target srl to delete files
	 * @return Object
	 */
	function deleteFiles($upload_target_srl)
	{
		// Get a list of attachements
		$oFileModel = getModel('file');
		$file_list = $oFileModel->getFiles($upload_target_srl);
		
		// Success returned if no attachement exists
		if(empty($file_list))
		{
			return new BaseObject();
		}
		
		// Delete the file
		return $this->deleteFile($file_list);
	}
	
	/**
	 * Delete the attachment of a particular module
	 *
	 * @param int $module_srl Sequence of module to delete files
	 * @return Object
	 */
	function deleteModuleFiles($module_srl)
	{
		// Get a full list of attachments
		$args = new stdClass;
		$args->module_srl = $module_srl;
		$output = executeQueryArray('file.getModuleFiles', $args);
		if(!$output->toBool() || empty($file_list = $output->data))
		{
			return $output;
		}
		
		// Delete the file
		return $this->deleteFile($file_list);
	}
	
	/**
	 * Move an attachement to the other document
	 *
	 * @param int $source_srl Sequence of target to move
	 * @param int $target_module_srl New squence of module
	 * @param int $target_srl New sequence of target
	 * @return void
	 */
	function moveFile($source_srl, $target_module_srl, $target_srl)
	{
		if($source_srl == $target_srl) return;

		$oFileModel = getModel('file');
		$file_list = $oFileModel->getFiles($source_srl);
		if(!$file_list) return;

		$file_count = count($file_list);

		for($i=0;$i<$file_count;$i++)
		{
			unset($file_info);
			$file_info = $file_list[$i];
			$old_file = $file_info->uploaded_filename;
			// Determine the file path by checking if the file is an image or other kinds
			if (Rhymix\Framework\Filters\FilenameFilter::isDirectDownload($file_info->source_filename))
			{
				$path = $this->getStoragePath('images', $file_info->file_srl, $target_module_srl, $target_srl, $file_info->regdate);
				$ext = substr(strrchr($file_info->source_filename,'.'), 1);
				$random_filename = basename($file_info->uploaded_filename) ?: Rhymix\Framework\Security::getRandom(32, 'hex') . '.' . $ext;
				$new_file = $path . $random_filename;
			}
			else
			{
				$path = $this->getStoragePath('binaries', $file_info->file_srl, $target_module_srl, $target_srl, $file_info->regdate);
				$random_filename = basename($file_info->uploaded_filename) ?: Rhymix\Framework\Security::getRandom(32, 'hex');
				$new_file = $path . $random_filename;
			}
			// Pass if a target document to move is same
			if($old_file === $new_file) continue;
			// Create a directory
			FileHandler::makeDir($path);
			// Move the file
			FileHandler::rename($old_file, $new_file);
			// Delete old path
			Rhymix\Framework\Storage::deleteEmptyDirectory(dirname(FileHandler::getRealPath($old_file)), true);
			// Update DB information
			$args = new stdClass;
			$args->file_srl = $file_info->file_srl;
			$args->uploaded_filename = $new_file;
			$args->module_srl = $file_info->module_srl;
			$args->upload_target_srl = $target_srl;
			executeQuery('file.updateFile', $args);
		}
	}
	
	function copyFile($source_file, $module_srl, $upload_target_srl, &$content = null)
	{
		$file_info = array();
		$file_info['name'] = $source_file->source_filename;
		$file_info['tmp_name'] = $source_file->uploaded_filename;
		$copied_file = $this->insertFile($file_info, $module_srl, $upload_target_srl, 0, true);
		
		if($content)
		{
			// if image/video files
			if($source_file->direct_download == 'Y')
			{
				$source_filename = substr($source_file->uploaded_filename, 2);
				$copied_filename = substr($copied_file->get('uploaded_filename'), 2);
				$content = str_replace($source_filename, $copied_filename, $content);
			}
			// if binary file
			else
			{
				$content = str_replace('file_srl=' . $source_file->file_srl, 'file_srl=' . $copied_file->get('file_srl'), $content);
				$content = str_replace('sid=' . $source_file->sid, 'sid=' . $copied_file->get('sid'), $content);
			}
		}
		
		return $copied_file;
	}
	
	function copyFiles($source_file_list, $module_srl, $upload_target_srl, &$content = null)
	{
		if(!is_array($source_file_list))
		{
			$source_file_list = getModel('file')->getFiles($source_file_list, array(), 'file_srl', true);
		}
		
		foreach($source_file_list as $source_file)
		{
			$this->copyFile($source_file, $module_srl, $upload_target_srl, $content);
		}
	}
	
	public function procFileSetCoverImage()
	{
		$vars = Context::getRequestVars();
		$logged_info = Context::get('logged_info');

		if(!$vars->editor_sequence) throw new Rhymix\Framework\Exceptions\InvalidRequest;

		$upload_target_srl = $_SESSION['upload_info'][$vars->editor_sequence]->upload_target_srl;

		$oFileModel = getModel('file');
		$file_info = $oFileModel->getFile($vars->file_srl);

		if(!$file_info) throw new Rhymix\Framework\Exceptions\TargetNotFound;

		if(!$this->manager && !$file_info->member_srl === $logged_info->member_srl) throw new Rhymix\Framework\Exceptions\NotPermitted;

		$args =  new stdClass();
		$args->file_srl = $vars->file_srl;
		$args->upload_target_srl = $upload_target_srl;

		$oDB = &DB::getInstance();
		$oDB->begin();
		
		$args->cover_image = 'N';
		$output = executeQuery('file.updateClearCoverImage', $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		if($file_info->cover_image != 'Y')
		{

			$args->cover_image = 'Y';
			$output = executeQuery('file.updateCoverImage', $args);
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}

		}

		$oDB->commit();

		$this->add('is_cover',$args->cover_image);

		// 썸네일 삭제
		$thumbnail_path = sprintf('files/thumbnails/%s', getNumberingPath($upload_target_srl, 3));
		Filehandler::removeFilesInDir($thumbnail_path);
	}
	
	/**
	 * Determine storage path based on file.folder_structure configuration.
	 * 
	 * @param string $file_type images or binary
	 * @param int $file_srl
	 * @param int $module_srl
	 * @param int $upload_target_srl
	 * @param string $regdate
	 * @param bool $absolute_path
	 * @return string
	 */
	public function getStoragePath($file_type, $file_srl, $module_srl = 0, $upload_target_srl = 0, $regdate = '', $absolute_path = true)
	{
		// 변수 확인 및 넘어오지 않은 변수 기본값 지정
		$file_srl = intval($file_srl);
		$module_srl = intval($module_srl);
		$upload_target_srl = $upload_target_srl ?: $file_srl;
		$regdate = $regdate ?: date('YmdHis');
		
		// 시스템 설정 참고 (기존 사용자는 1, 신규 설치시 2가 기본값임)
		$folder_structure = config('file.folder_structure');
		
		// 기본 경로 지정
		$prefix = $absolute_path ? \RX_BASEDIR : './';
		
		// 2: 년월일 단위로 정리
		if ($folder_structure == 2)
		{
			return  sprintf('%sfiles/attach/%s/%04d/%02d/%02d/', $prefix, $file_type, substr($regdate, 0, 4), substr($regdate, 4, 2), substr($regdate, 6, 2));
		}
		
		// 1 or 0: module_srl 및 업로드 대상 번호에 따라 3자리씩 끊어서 정리
		else
		{
			return sprintf('%sfiles/attach/%s/%d/%s', $prefix, $file_type, $module_srl, getNumberingPath($upload_target_srl, 3));
		}
	}

	/**
	 * Find the attachment where a key is upload_target_srl and then return java script code
	 *
	 * @deprecated
	 * @param int $editor_sequence
	 * @param int $upload_target_srl
	 * @return void
	 */
	function printUploadedFileList($editor_sequence, $upload_target_srl)
	{
		return;
	}
	
	function triggerMoveDocument($obj)
	{
		$obj->upload_target_srls = $obj->document_srls;
		executeQuery('file.updateFileModule', $obj);
		executeQuery('file.updateFileModuleComment', $obj);
	}
	
	function triggerAddCopyDocument(&$obj)
	{
		if(!$obj->source->uploaded_count)
		{
			return;
		}
		
		$this->copyFiles($obj->source->document_srl, $obj->copied->module_srl, $obj->copied->document_srl, $obj->copied->content);
	}
	
	function triggerAddCopyCommentByDocument(&$obj)
	{
		if(!$obj->source->uploaded_count)
		{
			return;
		}
		
		$this->copyFiles($obj->source->comment_srl, $obj->copied->module_srl, $obj->copied->comment_srl, $obj->copied->content);
	}
	
	function triggerCopyModule(&$obj)
	{
		$oModuleModel = getModel('module');
		$fileConfig = $oModuleModel->getModulePartConfig('file', $obj->originModuleSrl);

		$oModuleController = getController('module');
		if(is_array($obj->moduleSrlList))
		{
			foreach($obj->moduleSrlList AS $key=>$moduleSrl)
			{
				$oModuleController->insertModulePartConfig('file', $moduleSrl, $fileConfig);
			}
		}
	}
}
/* End of file file.controller.php */
/* Location: ./modules/file/file.controller.php */

