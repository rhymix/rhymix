<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * Controller class of the file module
 * @author NAVER (developers@xpressengine.com)
 */
class FileController extends File
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
		if (!$file_info || !is_uploaded_file($file_info['tmp_name']))
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest();
		}

		// Validate editor_sequence and module_srl.
		$editor_sequence = Context::get('editor_sequence');
		$module_srl = intval($this->module_srl);
		if (empty($_SESSION['upload_info'][$editor_sequence]->enabled))
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest(sprintf(lang('file.msg_invalid_upload_info'), 'editor_sequence'));
		}
		if ($_SESSION['upload_info'][$editor_sequence]->module_srl !== $module_srl)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest(sprintf(lang('file.msg_invalid_upload_info'), 'module_srl'));
		}

		// Validate upload_target_srl.
		$upload_target_srl = intval($_SESSION['upload_info'][$editor_sequence]->upload_target_srl);
		$submitted_upload_target_srl = intval(Context::get('uploadTargetSrl')) ?: intval(Context::get('upload_target_srl'));
		if ($submitted_upload_target_srl && $submitted_upload_target_srl !== $upload_target_srl)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest(sprintf(lang('file.msg_invalid_upload_info'), 'upload_target_srl'));
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
			$temp_key = hash_hmac('sha1', sprintf('%d:%d:%d:%s:%s', $editor_sequence, $upload_target_srl, $module_srl, $file_info['name'], session_id()), config('crypto.authentication_key'));
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
				if (isset($_SESSION['upload_info'][$editor_sequence]->allowed_filesize))
				{
					$allowed_attach_size = $allowed_filesize = $_SESSION['upload_info'][$editor_sequence]->allowed_filesize;
				}
				else
				{
					$module_config = FileModel::getFileConfig($module_srl);
					$allowed_attach_size = $module_config->allowed_attach_size * 1024 * 1024;
					$allowed_filesize = $module_config->allowed_filesize * 1024 * 1024;
				}
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
					if (!Rhymix\Framework\Filters\FileContentFilter::check($temp_filename, $file_info['name']))
					{
						throw new Rhymix\Framework\Exception('msg_security_violation');
					}
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
		$output = $this->insertFile($file_info, $module_srl, $upload_target_srl, 0, false, $editor_sequence);
		if($output->error != '0')
		{
			throw new Rhymix\Framework\Exception($output->message);
		}

		// Create the response
		Context::setResponseMethod('JSON');
		$this->add('file_srl', $output->get('file_srl'));
		$this->add('upload_target_srl', $output->get('upload_target_srl'));
		$this->add('source_filename', $output->get('source_filename'));
		$this->add('thumbnail_filename', $output->get('thumbnail_filename'));
		$this->add('file_size', $output->get('file_size'));
		$this->add('disp_file_size', FileHandler::filesize($output->get('file_size')));
		$this->add('mime_type', $output->get('mime_type'));
		$this->add('original_type', $output->get('original_type'));
		$this->add('width', $output->get('width'));
		$this->add('height', $output->get('height'));
		$this->add('duration', $output->get('duration'));
		$this->add('direct_download', $output->get('direct_download'));
		if ($output->get('direct_download') === 'Y')
		{
			$this->add('download_url', FileModel::getDirectFileUrl($output->get('uploaded_filename')));
		}
		else
		{
			$this->add('download_url', FileModel::getDownloadUrl($output->get('file_srl'), $output->get('sid'), 0, $output->get('source_filename')));
		}

		// Add upload status (getFileList)
		try
		{
			$file_list = FileModel::getInstance()->getFileList($editor_sequence);
			foreach ($file_list as $key => $val)
			{
				if (!isset($this->variables[$key]))
				{
					$this->add($key, $val);
				}
			}
		}
		catch (Exception $e)
		{
			// pass
		}
	}

	/**
	 * Iframe upload attachments
	 *
	 * @return void
	 */
	function procFileIframeUpload()
	{
		// Basic variables setting
		$callback = Context::get('callback');

		// Validate editor_sequence and module_srl.
		$editor_sequence = Context::get('editor_sequence');
		$module_srl = intval($this->module_srl);
		if (empty($_SESSION['upload_info'][$editor_sequence]->enabled))
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest(sprintf(lang('file.msg_invalid_upload_info'), 'editor_sequence'));
		}
		if ($_SESSION['upload_info'][$editor_sequence]->module_srl !== $module_srl)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest(sprintf(lang('file.msg_invalid_upload_info'), 'module_srl'));
		}

		// Get upload_target_srl
		$upload_target_srl = intval($_SESSION['upload_info'][$editor_sequence]->upload_target_srl);
		$submitted_upload_target_srl = intval(Context::get('uploadTargetSrl')) ?: intval(Context::get('upload_target_srl'));
		if ($submitted_upload_target_srl && $submitted_upload_target_srl !== $upload_target_srl)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest(sprintf(lang('file.msg_invalid_upload_info'), 'upload_target_srl'));
		}
		if (!$upload_target_srl)
		{
			$upload_target_srl = getNextSequence();
			$_SESSION['upload_info'][$editor_sequence]->upload_target_srl = $upload_target_srl;
		}

		// Delete and then attempt to re-upload if file_srl is requested
		$file_srl = Context::get('file_srl');
		if($file_srl)
		{
			$file_info = FileModel::getFile($file_srl);
			if($file_info->file_srl == $file_srl && $file_info->upload_target_srl == $upload_target_srl && FileModel::isDeletable($file_info))
			{
				$this->deleteFile($file_srl);
			}
		}

		$file_info = Context::get('Filedata');
		// An error appears if not a normally uploaded file
		if(is_uploaded_file($file_info['tmp_name'])) {
			$output = $this->insertFile($file_info, $module_srl, $upload_target_srl, 0, false, $editor_sequence);
			Context::set('uploaded_fileinfo',$output);
			Context::set('module_srl', $module_srl);
		}

		Context::set('layout','none');

		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('iframe');
	}

	/**
	 * Image resize
	 *
	 * @deprecated
	 */
	function procFileImageResize()
	{
		throw new Rhymix\Framework\Exceptions\FeatureDisabled;
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
		if(isset($this->grant->access) && $this->grant->access !== true)
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}

		$file_srl = Context::get('file_srl');
		$sid = Context::get('sid');
		$filename_arg = Context::get('filename');

		// Get file information from the DB
		$file_obj = FileModel::getFile($file_srl);
		$filename = preg_replace('/\.\.+/', '.', $file_obj->source_filename);

		// If the requested file information is incorrect, an error that file cannot be found appears
		if($file_obj->file_srl != $file_srl || $file_obj->sid !== $sid)
		{
			throw new Rhymix\Framework\Exceptions\TargetNotFound('msg_file_not_found');
		}
		if ($filename_arg !== null && $filename_arg !== $filename)
		{
			throw new Rhymix\Framework\Exceptions\TargetNotFound('msg_file_not_found');
		}

		// Not allow the file outlink
		$file_module_config = FileModel::getFileConfig($file_obj->module_srl);
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

		// Check if the file is downloadable
		if(!FileModel::isDownloadable($file_obj))
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted('msg_not_permitted_download');
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
		$file_key_timestamp = \RX_TIME;
		$file_key_data = sprintf('%d:%d:%s:%s', $file_obj->file_srl, $file_key_timestamp, $file_obj->uploaded_filename, \RX_CLIENT_IP);
		$file_key_sig = \Rhymix\Framework\Security::createSignature($file_key_data);
		$file_key = dechex($file_key_timestamp) . $file_key_sig;

		// Use short URL or long URL
		if ($file_module_config->download_short_url === 'Y' && config('use_rewrite'))
		{
			$url = RX_BASEURL . sprintf('files/download/%d/%s/%s', $file_srl, $file_key, rawurlencode(preg_replace('/\.\.+/', '.', $filename)));
		}
		else
		{
			$url = getNotEncodedUrl('', 'module', 'file', 'act', 'procFileOutput', 'file_srl', $file_srl, 'file_key', $file_key, 'force_download', Context::get('force_download') === 'Y' ? 'Y' : null);
		}

		if (!FileModel::isIndexable($filename, $file_module_config))
		{
			header('X-Robots-Tag: noindex');
		}

		header('Location: ' . $url);
		Context::close();
		exit();
	}

	public function procFileOutput()
	{
		// Get requsted file info
		$file_srl = Context::get('file_srl');
		$file_key = Context::get('file_key');
		$filename_arg = Context::get('filename');

		$columnList = array('source_filename', 'uploaded_filename', 'file_size');
		$file_obj = FileModel::getFile($file_srl, $columnList);
		$file_config = FileModel::getFileConfig($file_obj->module_srl ?: null);
		$filesize = $file_obj->file_size;
		$filename = preg_replace('/\.\.+/', '.', $file_obj->source_filename);
		$etag = md5($file_srl . $file_key . \RX_CLIENT_IP);

		// Check file key
		if(strlen($file_key) != 48 || !ctype_xdigit(substr($file_key, 0, 8)))
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		$file_key_timestamp = hexdec(substr($file_key, 0, 8));
		if ($file_key_timestamp < \RX_TIME - 300)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest('msg_file_key_expired');
		}
		$file_key_data = sprintf('%d:%d:%s:%s', $file_srl, $file_key_timestamp, $file_obj->uploaded_filename, \RX_CLIENT_IP);
		if (!\Rhymix\Framework\Security::verifySignature($file_key_data, substr($file_key, 8)))
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		// Check filename if given
		if ($filename_arg !== null && $filename_arg !== $filename)
		{
			throw new Rhymix\Framework\Exceptions\TargetNotFound('msg_file_not_found');
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
		if ($filename_arg !== null && $filename_arg === $filename)
		{
			$filename_param = '';
		}
		else
		{
			$filename_param = '; ' . Rhymix\Framework\UA::encodeFilenameForDownload($filename);
		}

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
		header('Content-Type: ' . $mime_type);
		header('Content-Disposition: ' . $download_type . $filename_param);

		// Set cache headers
		header('Cache-Control: private; max-age=3600');
		header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
		header('Pragma: ');

		// Set other headers
		header('Content-Length: ' . $range_length);
		header('Accept-Ranges: bytes');
		header('Etag: "' . $etag . '"');

		if (!FileModel::isIndexable($filename, $file_config))
		{
			header('X-Robots-Tag: noindex' . false);
		}

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
	 * @return void
	 */
	function procFileDelete()
	{
		// Basic variable setting(upload_target_srl and module_srl set)
		$editor_sequence = Context::get('editor_sequence');
		$file_srl = Context::get('file_srl');
		$file_srls = Context::get('file_srls');
		if($file_srls) $file_srl = $file_srls;

		// Exit a session if there is neither upload permission nor information
		if (!$_SESSION['upload_info'][$editor_sequence]->enabled)
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}
		$upload_target_srl = $_SESSION['upload_info'][$editor_sequence]->upload_target_srl;
		if (!$upload_target_srl)
		{
			throw new Rhymix\Framework\Exceptions\TargetNotFound;
		}
		$module_srl = $_SESSION['upload_info'][$editor_sequence]->module_srl ?? 0;

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
			if(!$file_info || $file_info->upload_target_srl != $upload_target_srl) continue;
			//if($module_srl && $file_info->module_srl != $module_srl) continue;
			if(!FileModel::isDeletable($file_info)) continue;
			$output = $this->deleteFile($file_srl);
		}

		// Add upload status (getFileList)
		try
		{
			$file_list = FileModel::getInstance()->getFileList($editor_sequence);
			foreach ($file_list as $key => $val)
			{
				if (!isset($this->variables[$key]))
				{
					$this->add($key, $val);
				}
			}
		}
		catch (Exception $e)
		{
			// pass
		}
	}

	/**
	 * get file list
	 *
	 * @return void
	 */
	function procFileGetList()
	{
		if(!Context::get('is_logged'))
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}

		$logged_info = Context::get('logged_info');
		if($logged_info->is_admin !== 'Y' && !ModuleModel::isSiteAdmin($logged_info))
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}

		$fileSrls = Context::get('file_srls');
		if($fileSrls) $fileSrlList = explode(',', $fileSrls);

		global $lang;
		if(count($fileSrlList) > 0)
		{
			$fileList = FileModel::getFile($fileSrlList);
			$fileSizeTotal = 0;
			if(!is_array($fileList)) $fileList = array($fileList);

			if(is_array($fileList))
			{
				foreach($fileList AS $key=>$value)
				{
					$value->human_file_size = FileHandler::filesize($value->file_size);
					if($value->isvalid=='Y') $value->validName = $lang->is_valid;
					else $value->validName = $lang->is_stand_by;
					$fileSizeTotal += $value->file_size;
				}
			}
		}
		else
		{
			$fileList = array();
			$fileSizeTotal = 0;
			$this->setMessage($lang->no_files);
		}

		$this->add('file_list', $fileList);
		$this->add('file_size_total', $fileSizeTotal);
		$this->add('file_size_total_human', FileHandler::filesize($fileSizeTotal));
	}

	/**
	 * A trigger to delete the attachment in the upload_target_srl (document_srl)
	 *
	 * @param object $obj Trigger object
	 * @return BaseObject
	 */
	function triggerDeleteAttached(&$obj)
	{
		$document_srl = $obj->document_srl;
		if(!$document_srl) return;

		$output = $this->deleteFiles($document_srl);
		return $output;
	}

	/**
	 * A trigger to delete the attachment in the upload_target_srl (comment_srl)
	 *
	 * @param object $obj Trigger object
	 * @return BaseObject
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
	 * @return BaseObject
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
	 * @param int $module_srl
	 * @param array $config
	 * @return int
	 */
	public static function setUploadInfo($editor_sequence = 0, $upload_target_srl = 0, $module_srl = 0, array $config = [])
	{
		if(!$editor_sequence)
		{
			if(!isset($_SESSION['_editor_sequence_']))
			{
				$_SESSION['_editor_sequence_'] = 1;
			}
			$editor_sequence = ++$_SESSION['_editor_sequence_'];
		}
		if(!$module_srl)
		{
			$current_module_info = Context::get('current_module_info');
			if (!empty($current_module_info->module_srl))
			{
				$module_srl = $current_module_info->module_srl;
			}
		}
		if(!isset($_SESSION['upload_info']) || !is_array($_SESSION['upload_info']))
		{
			$_SESSION['upload_info'] = array();
		}
		if(count($_SESSION['upload_info']) > 200)
		{
			$_SESSION['upload_info'] = array_slice($_SESSION['upload_info'], 100, null, true);
		}
		if(!isset($_SESSION['upload_info'][$editor_sequence]))
		{
			$_SESSION['upload_info'][$editor_sequence] = new stdClass();
		}
		$_SESSION['upload_info'][$editor_sequence]->enabled = true;
		$_SESSION['upload_info'][$editor_sequence]->upload_target_srl = (int)$upload_target_srl;
		$_SESSION['upload_info'][$editor_sequence]->module_srl = (int)$module_srl;
		if (!$module_srl)
		{
			trigger_error('No module_srl supplied to setUploadInfo(), and cannot determine automatically', E_USER_WARNING);
		}
		if ($config)
		{
			foreach ($config as $key => $val)
			{
				$_SESSION['upload_info'][$editor_sequence]->$key = $val;
			}
		}

		return $editor_sequence;
	}

	/**
	 * Set the attachements of the upload_target_srl to be valid
	 * By changing its state to valid when a document is inserted, it prevents from being considered as a unnecessary file
	 *
	 * @param int $upload_target_srl
	 * @param ?string $upload_target_type
	 * @param ?array $file_srl
	 * @return BaseObject
	 */
	function setFilesValid($upload_target_srl, $upload_target_type = null, $file_srl = null)
	{
		$args = new stdClass();
		$args->upload_target_srl = $upload_target_srl;
		$args->old_isvalid = 'N';
		if ($upload_target_type)
		{
			$args->upload_target_type = $upload_target_type;
		}
		if ($file_srl)
		{
			$args->file_srl = $file_srl;
		}
		$output = executeQuery('file.updateFileValid', $args);
		$output->add('updated_file_count', intval(DB::getInstance()->getAffectedRows()));
		return $output;
	}

	/**
	 * Update upload target type
	 *
	 * @param int|array $file_srl
	 * @param string $upload_target_type
	 * @return BaseObject
	 */
	public function updateTargetType($file_srl, $upload_target_type)
	{
		$args = new stdClass;
		$args->file_srl = $file_srl;
		$args->upload_target_type = $upload_target_type;
		return executeQuery('file.updateFileTargetType', $args);
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
	 * @param array $file_info PHP file information array
	 * @param int $module_srl Sequence of module to upload file
	 * @param int $upload_target_srl Sequence of target to upload file
	 * @param int $download_count Initial download count
	 * @param bool $manual_insert If set true, pass validation check
	 * @param int $editor_sequence Optional
	 * @return BaseObject
	 */
	function insertFile($file_info, $module_srl, $upload_target_srl, $download_count = 0, $manual_insert = false, $editor_sequence = 0)
	{
		// Set base information
		$file_info['name'] = Rhymix\Framework\Filters\FilenameFilter::clean($file_info['name']);
		$file_info['type'] = Rhymix\Framework\MIME::getContentType($file_info['tmp_name']);
		$file_info['original_type'] = $file_info['type'];
		$file_info['extension'] = strtolower(array_pop(explode('.', $file_info['name'])));
		$file_info['original_extension'] = $file_info['extension'];
		$file_info['width'] = null;
		$file_info['height'] = null;
		$file_info['duration'] = null;
		$file_info['thumbnail'] = null;
		$file_info['save_path'] = null;
		$file_info['converted'] = false;

		// Correct extension
		if($file_info['extension'])
		{
			$type_by_extension = Rhymix\Framework\MIME::getTypeByExtension($file_info['extension']);
			if(!in_array($type_by_extension, [$file_info['type'], 'application/octet-stream']))
			{
				$extension_by_type = Rhymix\Framework\MIME::getExtensionByType($file_info['type']);
				if($extension_by_type && preg_match('@^(?:image|audio|video)/@m', $file_info['type'] . PHP_EOL . $type_by_extension))
				{
					$file_info['extension'] = $extension_by_type;
				}
			}
		}

		// Call a trigger (before)
		$trigger_obj = new stdClass;
		$trigger_obj->file_info = &$file_info;
		$trigger_obj->module_srl = $module_srl;
		$trigger_obj->upload_target_srl = $upload_target_srl;
		$output = ModuleHandler::triggerCall('file.insertFile', 'before', $trigger_obj);
		if(!$output->toBool()) return $output;

		// Get file module configuration
		$config = FileModel::getFileConfig($module_srl);

		// Check file extension
		if(!$manual_insert && !$this->user->isAdmin())
		{
			if (isset($_SESSION['upload_info'][$editor_sequence]->allowed_extensions))
			{
				if (!in_array($file_info['extension'], $_SESSION['upload_info'][$editor_sequence]->allowed_extensions))
				{
					throw new Rhymix\Framework\Exception('msg_not_allowed_filetype');
				}
			}
			elseif($config->allowed_extensions && !in_array($file_info['extension'], $config->allowed_extensions))
			{
				throw new Rhymix\Framework\Exception('msg_not_allowed_filetype');
			}
		}

		// Adjust
		if(!$manual_insert)
		{
			// image
			if(in_array($file_info['extension'], ['gif', 'jpg', 'jpeg', 'jfif', 'png', 'webp', 'bmp', 'avif', 'heic', 'heif']))
			{
				$file_info = $this->adjustUploadedImage($file_info, $config);
			}

			// video
			if(in_array($file_info['extension'], ['mp4', 'webm', 'ogv', 'avi', 'mkv', 'mov', 'mpg', 'mpe', 'mpeg', 'wmv', 'm4v', 'flv']))
			{
				$file_info = $this->adjustUploadedVideo($file_info, $config);
			}
		}

		// Check file size
		if(!$manual_insert && !$this->user->isAdmin())
		{
			$file_size = filesize($file_info['tmp_name']);
			if (isset($_SESSION['upload_info'][$editor_sequence]->allowed_filesize))
			{
				$allowed_attach_size = $allowed_filesize = $_SESSION['upload_info'][$editor_sequence]->allowed_filesize;
			}
			else
			{
				$allowed_filesize = $config->allowed_filesize * 1024 * 1024;
				$allowed_attach_size = $config->allowed_attach_size * 1024 * 1024;
			}
			if($allowed_filesize < $file_size)
			{
				throw new Rhymix\Framework\Exception('msg_exceeds_limit_size');
			}

			$size_args = new stdClass;
			$size_args->upload_target_srl = $upload_target_srl;
			$output = executeQuery('file.getAttachedFileSize', $size_args);
			if($allowed_attach_size < intval($output->data->attached_size) + $file_size)
			{
				throw new Rhymix\Framework\Exception('msg_exceeds_limit_size');
			}
		}

		$args = new stdClass;
		$args->file_srl = getNextSequence();
		$args->regdate = date('YmdHis');
		$args->module_srl = $module_srl;
		$args->upload_target_srl = $upload_target_srl;
		$args->download_count = $download_count;
		$args->member_srl = Rhymix\Framework\Session::getMemberSrl() ?: 0;
		$args->source_filename = $file_info['name'];
		$args->sid = Rhymix\Framework\Security::getRandom(32, 'hex');
		$args->mime_type = $file_info['type'];
		$args->width = $file_info['width'];
		$args->height = $file_info['height'];
		$args->duration = $file_info['duration'];

		// Set original type
		$args->original_type = null;
		if($file_info['type'] !== $file_info['original_type'])
		{
			$args->original_type = $file_info['original_type'];
		}

		// Add changed extension
		if($file_info['extension'] && $file_info['extension'] !== $file_info['original_extension'])
		{
			$args->source_filename .= '.' . $file_info['extension'];
		}

		// Set storage path by checking if the attachement is an image or other kinds of file
		if(!empty($file_info['save_path']))
		{
			$storage_path = dirname(FileHandler::getRealPath($file_info['save_path']));
			$uploaded_filename = basename($file_info['save_path']);
		}
		elseif($direct = Rhymix\Framework\Filters\FilenameFilter::isDirectDownload($args->source_filename))
		{
			$storage_path = $this->getStoragePath('images', $args->file_srl, $module_srl, $upload_target_srl, $args->regdate);
			$uploaded_filename = null;
		}
		else
		{
			$storage_path = $this->getStoragePath('binaries', $args->file_srl, $module_srl, $upload_target_srl, $args->regdate);
			$uploaded_filename = null;
		}

		// Set direct download option
		if(isset($config->allow_multimedia_direct_download) && $config->allow_multimedia_direct_download !== 'N')
		{
			$args->direct_download = $direct ? 'Y' : 'N';
		}
		else
		{
			$args->direct_download = Rhymix\Framework\Filters\FilenameFilter::isDirectDownload($args->source_filename, false) ? 'Y' : 'N';
		}

		// Create a directory
		if(!Rhymix\Framework\Storage::isDirectory($storage_path) && !Rhymix\Framework\Storage::createDirectory($storage_path))
		{
			throw new Rhymix\Framework\Exception('msg_not_permitted_create');
		}

		// Set move type and uploaded filename
		$move_type = $manual_insert ? 'copy' : '';
		if($file_info['converted'] || starts_with(RX_BASEDIR . 'files/attach/chunks/', $file_info['tmp_name']))
		{
			$move_type = 'move';
		}
		if(!$uploaded_filename)
		{
			$extension = ($direct && $file_info['extension']) ? ('.' . $file_info['extension']) : '';
			$uploaded_filename = $storage_path . Rhymix\Framework\Security::getRandom(32, 'hex') . $extension;
			while(file_exists($uploaded_filename))
			{
				$uploaded_filename = $storage_path . Rhymix\Framework\Security::getRandom(32, 'hex') . $extension;
			}
		}

		// Move the uploaded file
		if(!Rhymix\Framework\Storage::moveUploadedFile($file_info['tmp_name'], $uploaded_filename, $move_type))
		{
			throw new Rhymix\Framework\Exception('msg_file_upload_error');
		}
		clearstatcache(true, $uploaded_filename);
		$args->uploaded_filename = './' . substr($uploaded_filename, strlen(RX_BASEDIR));
		$args->file_size = Rhymix\Framework\Storage::getSize($uploaded_filename) ?: 0;

		// Move the generated thumbnail image
		$args->thumbnail_filename = null;
		if($file_info['thumbnail'])
		{
			$thumbnail_filename = $storage_path . Rhymix\Framework\Security::getRandom(32, 'hex') . '.jpg';
			while(file_exists($thumbnail_filename))
			{
				$thumbnail_filename = $storage_path . Rhymix\Framework\Security::getRandom(32, 'hex') . '.jpg';
			}
			if(Rhymix\Framework\Storage::moveUploadedFile($file_info['thumbnail'], $thumbnail_filename, 'move'))
			{
				$args->thumbnail_filename = './' . substr($thumbnail_filename, strlen(RX_BASEDIR));
			}
		}

		// Set upload target type
		if ($editor_sequence && isset($_SESSION['upload_info'][$editor_sequence]->upload_target_type))
		{
			$args->upload_target_type = strval($_SESSION['upload_info'][$editor_sequence]->upload_target_type);
		}

		$oDB = DB::getInstance();
		$oDB->begin();

		// Insert file information
		$output = executeQuery('file.insertFile', $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			$this->deleteFile(array($args));
			return $output;
		}

		// Insert changelog
		if(isset($config->save_changelog) && $config->save_changelog === 'Y')
		{
			$clargs = new stdClass;
			$clargs->change_type = 'I';
			$clargs->file_srl = $args->file_srl;
			$clargs->file_size = $args->file_size;
			$clargs->uploaded_filename = $args->uploaded_filename;
			$clargs->regdate = $args->regdate;
			$output = executeQuery('file.insertFileChangelog', $clargs);
			if(!$output->toBool())
			{
				$oDB->rollback();
				$this->deleteFile(array($args));
				return $output;
			}
		}

		$oDB->commit();

		// Call a trigger (after)
		ModuleHandler::triggerCall('file.insertFile', 'after', $args);

		$_SESSION['__XE_UPLOADING_FILES_INFO__'][$args->file_srl] = true;

		$output->add('file_srl', $args->file_srl);
		$output->add('file_size', $args->file_size);
		$output->add('upload_target_srl', $upload_target_srl);
		$output->add('direct_download', $args->direct_download);
		$output->add('source_filename', $args->source_filename);
		$output->add('uploaded_filename', $args->uploaded_filename);
		$output->add('thumbnail_filename', $args->thumbnail_filename);
		$output->add('mime_type', $args->mime_type);
		$output->add('original_type', $args->original_type);
		$output->add('width', $args->width);
		$output->add('height', $args->height);
		$output->add('duration', $args->duration);
		$output->add('sid', $args->sid);

		return $output;
	}

	/**
	 * Adjust uploaded image
	 */
	public function adjustUploadedImage($file_info, $config)
	{
		// Get image information
		if (in_array($file_info['extension'], ['avif', 'heic', 'heif']) && !empty($config->magick_command))
		{
			$command = \RX_WINDOWS ? escapeshellarg($config->magick_command) : $config->magick_command;
			$command .= ' identify ' . escapeshellarg($file_info['tmp_name']);
			@exec($command, $output, $return_var);
			if ($return_var === 0 && preg_match('/([A-Z]+) ([0-9]+)x([0-9]+)/', substr(array_last($output), strlen($file_info['tmp_name'])), $matches))
			{
				$image_info = [
					'width' => (int)$matches[2],
					'height' => (int)$matches[3],
					'type' => strtolower($matches[1]),
				];
			}
			else
			{
				$image_info = false;
			}
		}
		else
		{
			$image_info = Rhymix\Framework\Image::getImageInfo($file_info['tmp_name']);
		}

		// Return if image cannot be converted
		if (!$image_info)
		{
			return $file_info;
		}

		// Set image size
		$file_info['width'] = $image_info['width'];
		$file_info['height'] = $image_info['height'];

		// Set base information
		$force = false;
		$adjusted = [
			'width' => $image_info['width'],
			'height' => $image_info['height'],
			'type' => $image_info['type'],
			'quality' => $config->image_quality_adjustment ?: 75,
			'rotate' => 0,
		];
		$is_animated = Rhymix\Framework\Image::isAnimatedGIF($file_info['tmp_name']);

		// Adjust image type
		if ($config->image_autoconv['gif2mp4'] && $is_animated && function_exists('exec') && Rhymix\Framework\Storage::isExecutable($config->ffmpeg_command))
		{
			$adjusted['type'] = 'mp4';
		}
		elseif (!empty($config->image_autoconv[$image_info['type']]))
		{
			$adjusted['type'] = $config->image_autoconv[$image_info['type']];
		}
		elseif (!empty($config->image_autoconv[$image_info['type'] . '2jpg']))
		{
			$adjusted['type'] = 'jpg';
		}
		elseif ($image_info['type'] === 'avif' || $image_info['type'] === 'heic')
		{
			return $file_info;
		}

		// Adjust image rotation
		if ($config->image_autorotate && $image_info['type'] === 'jpg')
		{
			$rotate = FileHandler::checkImageRotation($file_info['tmp_name']);
			if ($rotate)
			{
				if ($rotate === 90 || $rotate === 270)
				{
					$adjusted['width'] = $image_info['height'];
					$adjusted['height'] = $image_info['width'];
				}
				$adjusted['rotate'] = $rotate;
			}
		}

		// Adjust image size
		if ($config->max_image_size_action && ($config->max_image_width || $config->max_image_height) && (!$this->user->isAdmin() || $config->max_image_size_admin === 'Y'))
		{
			$exceeded = 0;
			$resize_width = $adjusted['width'];
			$resize_height = $adjusted['height'];
			if ($config->max_image_width > 0 && $adjusted['width'] > $config->max_image_width)
			{
				$resize_width = $config->max_image_width;
				$resize_height = $adjusted['height'] * ($config->max_image_width / $adjusted['width']);
				$exceeded++;
			}
			if ($config->max_image_height > 0 && $resize_height > $config->max_image_height)
			{
				$resize_width = $resize_width * ($config->max_image_height / $resize_height);
				$resize_height = $config->max_image_height;
				$exceeded++;
			}

			if ($exceeded)
			{
				// Block upload
				if ($config->max_image_size_action === 'block')
				{
					if ($config->max_image_width && $config->max_image_height)
					{
						$message = sprintf(lang('msg_exceeds_max_image_size'), $config->max_image_width, $config->max_image_height);
					}
					elseif ($config->max_image_width)
					{
						$message = sprintf(lang('msg_exceeds_max_image_width'), $config->max_image_width);
					}
					else
					{
						$message = sprintf(lang('msg_exceeds_max_image_height'), $config->max_image_height);
					}
					throw new Rhymix\Framework\Exception($message);
				}

				$adjusted['width'] = (int)$resize_width;
				$adjusted['height'] = (int)$resize_height;
				if (!$is_animated && $adjusted['type'] === $image_info['type'] && $config->max_image_size_same_format !== 'Y')
				{
					$adjusted['type'] = $config->max_image_size_same_format ?: 'jpg';
				}
			}
		}

		// Set force for remove EXIF data
		if($config->image_remove_exif_data && $image_info['type'] === 'jpg' && function_exists('exif_read_data'))
		{
			if(!isset($exif))
			{
				$exif = @exif_read_data($file_info['tmp_name']);
			}
			if($exif && (isset($exif['Model']) || isset($exif['Software']) || isset($exif['GPSVersion'])))
			{
				$force = true;
			}
		}

		// Convert image if adjusted
		if ($adjusted['width'] !== $image_info['width'] ||
			$adjusted['height'] !== $image_info['height'] ||
			$adjusted['type'] !== $image_info['type'] ||
			$adjusted['rotate'] !== 0 || $force
		)
		{
			$output_name = $file_info['tmp_name'] . '.converted.' . $adjusted['type'];

			// Generate an output file
			if ($adjusted['type'] === 'mp4')
			{
				// Width and height must be even
				$adjusted['width'] -= $adjusted['width'] % 2;
				$adjusted['height'] -= $adjusted['height'] % 2;

				// Convert using ffmpeg
				$command = \RX_WINDOWS ? escapeshellarg($config->ffmpeg_command) : $config->ffmpeg_command;
				$command .= ' -nostdin -i ' . escapeshellarg($file_info['tmp_name']);
				$command .= ' -movflags +faststart -pix_fmt yuv420p -c:v libx264 -crf 23';
				$command .= sprintf(' -vf "scale=%d:%d"', $adjusted['width'], $adjusted['height']);
				$command .= ' ' . escapeshellarg($output_name);
				@exec($command, $output, $return_var);
				$result = $return_var === 0 ? true : false;

				// Generate a thumbnail image
				if ($result)
				{
					$thumbnail_name = $file_info['tmp_name'] . '.thumbnail.jpg';
					if (FileHandler::createImageFile($file_info['tmp_name'], $thumbnail_name, $adjusted['width'], $adjusted['height'], 'jpg', 'fill', $adjusted['quality']))
					{
						$file_info['thumbnail'] = $thumbnail_name;
					}
				}
			}
			elseif ($image_info['type'] === 'avif' || $image_info['type'] === 'heic')
			{
				// Width and height must be even
				$adjusted['width'] -= $adjusted['width'] % 2;
				$adjusted['height'] -= $adjusted['height'] % 2;

				// Convert using magick
				$command = vsprintf('%s %s -resize %dx%d -quality %d %s %s %s', [
					\RX_WINDOWS ? escapeshellarg($config->magick_command) : $config->magick_command,
					escapeshellarg($file_info['tmp_name']),
					$adjusted['width'],
					$adjusted['height'],
					intval($adjusted['quality'] ?: 75),
					'-auto-orient -strip',
					'-limit memory 64MB -limit map 128MB -limit disk 1GB',
					escapeshellarg($output_name),
				]);
				@exec($command, $output, $return_var);
				$result = $return_var === 0 ? true : false;
			}
			else
			{
				// Try resizing with GD.
				$result = FileHandler::createImageFile($file_info['tmp_name'], $output_name, $adjusted['width'], $adjusted['height'], $adjusted['type'], 'fill', $adjusted['quality'], $adjusted['rotate']);

				// If the image cannot be resized using GD, try ImageMagick.
				if (!$result && !empty($config->magick_command))
				{
					$command = vsprintf('%s %s -resize %dx%d -quality %d %s %s %s', [
						\RX_WINDOWS ? escapeshellarg($config->magick_command) : $config->magick_command,
						escapeshellarg($file_info['tmp_name']),
						$adjusted['width'],
						$adjusted['height'],
						intval($adjusted['quality'] ?: 75),
						'-auto-orient -strip',
						'-limit memory 64MB -limit map 128MB -limit disk 1GB',
						escapeshellarg($output_name),
					]);
					@exec($command, $output, $return_var);
					$result = $return_var === 0 ? true : false;
				}
			}

			// Change to information in the output file
			if ($result && file_exists($output_name))
			{
				$file_info['tmp_name'] = $output_name;
				$file_info['size'] = filesize($output_name);
				$file_info['type'] = Rhymix\Framework\MIME::getContentType($output_name);
				$file_info['extension'] = $adjusted['type'];
				$file_info['width'] = $adjusted['width'];
				$file_info['height'] = $adjusted['height'];
				$file_info['converted'] = true;
			}
		}

		return $file_info;
	}

	/**
	 * Adjust uploaded video
	 */
	public function adjustUploadedVideo($file_info, $config)
	{
		if (!function_exists('exec') || !Rhymix\Framework\Storage::isExecutable($config->ffmpeg_command) || !Rhymix\Framework\Storage::isExecutable($config->ffprobe_command))
		{
			return $file_info;
		}

		// Analyze video file
		$command = \RX_WINDOWS ? escapeshellarg($config->ffprobe_command) : $config->ffprobe_command;
		$command .= ' -v quiet -print_format json -show_streams';
		$command .= ' ' . escapeshellarg($file_info['tmp_name']);
		@exec($command, $output, $return_var);
		if ($return_var !== 0 || !$output = json_decode(implode('', $output), true))
		{
			return $file_info;
		}

		// Get stream information
		$stream_info = [];
		foreach ($output['streams'] as $info)
		{
			$stream_info[$info['codec_type']] = $info;
		}
		if (empty($stream_info['video']))
		{
			return $file_info;
		}

		// Check if video needs to be rotated
		$rotate = false;
		if (isset($stream_info['video']['tags']['rotate']) && in_array($stream_info['video']['tags']['rotate'], [90, 270]))
		{
			$rotate = true;
		}
		elseif (isset($stream_info['video']['side_data_list']) && is_array($stream_info['video']['side_data_list']))
		{
			foreach ($stream_info['video']['side_data_list'] as $side_data)
			{
				if (isset($side_data['rotation']) && in_array(abs($side_data['rotation']), [90, 270]))
				{
					$rotate = true;
				}
			}
		}

		// Get video size and duration
		$file_info['width'] = intval($rotate ? $stream_info['video']['height'] : $stream_info['video']['width']);
		$file_info['height'] = intval($rotate ? $stream_info['video']['width'] : $stream_info['video']['height']);
		$file_info['duration'] = round($stream_info['video']['duration']);
		$adjusted = [
			'width' => $file_info['width'],
			'height' => $file_info['height'],
			'duration' => $file_info['duration'],
			'type' => $file_info['extension'],
			'force' => false,
		];

		// Check video size
		if (!empty($config->max_video_size_action) && ($config->max_video_width || $config->max_video_height) && (!$this->user->isAdmin() || $config->max_video_size_admin === 'Y'))
		{
			$exceeded = 0;
			$resize_width = $adjusted['width'];
			$resize_height = $adjusted['height'];
			if ($config->max_video_width > 0 && $adjusted['width'] > $config->max_video_width)
			{
				$resize_width = $config->max_video_width;
				$resize_height = $adjusted['height'] * ($config->max_video_width / $adjusted['width']);
				$exceeded++;
			}
			if ($config->max_video_height > 0 && $resize_height > $config->max_video_height)
			{
				$resize_width = $resize_width * ($config->max_video_height / $resize_height);
				$resize_height = $config->max_video_height;
				$exceeded++;
			}

			if ($exceeded)
			{
				// Block upload
				if ($config->max_video_size_action === 'block')
				{
					if ($config->max_video_width && $config->max_video_height)
					{
						$message = sprintf(lang('msg_exceeds_max_video_size'), $config->max_video_width, $config->max_video_height);
					}
					elseif ($config->max_video_width)
					{
						$message = sprintf(lang('msg_exceeds_max_video_width'), $config->max_video_width);
					}
					else
					{
						$message = sprintf(lang('msg_exceeds_max_video_height'), $config->max_video_height);
					}
					throw new Rhymix\Framework\Exception($message);
				}

				// Resize
				if ($config->max_video_size_action === 'resize')
				{
					$adjusted['width'] = (int)$resize_width;
					$adjusted['height'] = (int)$resize_height;
					$adjusted['type'] = 'mp4';
				}
			}
		}

		// Check video duration
		if (!empty($config->max_video_duration_action) && $config->max_video_duration && $adjusted['duration'] > $config->max_video_duration && (!$this->user->isAdmin() || $config->max_video_duration_admin === 'Y'))
		{
			// Block upload
			if ($config->max_video_duration_action === 'block')
			{
				$message = sprintf(lang('msg_exceeds_max_video_duration'), $config->max_video_duration);
				throw new Rhymix\Framework\Exception($message);
			}

			// Cut video
			if ($config->max_video_duration_action === 'cut')
			{
				$adjusted['duration'] = $config->max_video_duration;
				$adjusted['type'] = 'mp4';
			}
		}

		// Check if this video should be force-converted to MP4
		if (isset($config->video_autoconv['any2mp4']) && $config->video_autoconv['any2mp4'] && $file_info['extension'] !== 'mp4')
		{
			$adjusted['type'] = 'mp4';
		}

		// Check if this video should be reencoded anyway
		if (isset($config->video_always_reencode) && $config->video_always_reencode)
		{
			$adjusted['force'] = true;
			$adjusted['type'] = 'mp4';
		}

		// Convert
		if ($adjusted['width'] !== $file_info['width'] ||
			$adjusted['height'] !== $file_info['height'] ||
			$adjusted['duration'] !== $file_info['duration'] ||
			$adjusted['type'] !== $file_info['extension'] ||
			$adjusted['force']
		)
		{
			$output_name = $file_info['tmp_name'] . '.converted.mp4';

			// Width and height of video must be even
			$adjusted['width'] -= $adjusted['width'] % 2;
			$adjusted['height'] -= $adjusted['height'] % 2;

			// Convert using ffmpeg
			$command = \RX_WINDOWS ? escapeshellarg($config->ffmpeg_command) : $config->ffmpeg_command;
			$command .= ' -nostdin -i ' . escapeshellarg($file_info['tmp_name']);
			if ($adjusted['duration'] !== $file_info['duration'])
			{
				$command .= sprintf(' -t %d', $adjusted['duration']);
			}
			$command .= ' -movflags +faststart -pix_fmt yuv420p -c:v libx264 -crf 23';
			$command .= empty($stream_info['audio']) ? ' -an' : ' -acodec aac';
			$command .= sprintf(' -vf "scale=%d:%d"', $adjusted['width'], $adjusted['height']);
			$command .= ' ' . escapeshellarg($output_name);
			@exec($command, $output, $return_var);
			$result = $return_var === 0 ? true : false;

			// Update file info
			if ($result)
			{
				$file_info['tmp_name'] = $output_name;
				$file_info['size'] = filesize($output_name);
				$file_info['type'] = Rhymix\Framework\MIME::getContentType($output_name);
				$file_info['extension'] = $adjusted['type'];
				$file_info['width'] = $adjusted['width'];
				$file_info['height'] = $adjusted['height'];
				$file_info['converted'] = true;
			}
		}

		// Set original type to GIF if this video is short and has no audio stream.
		if (isset($config->video_mp4_gif_time) && $config->video_mp4_gif_time)
		{
			if ($file_info['extension'] === 'mp4' && $file_info['duration'] <= $config->video_mp4_gif_time && empty($stream_info['audio']))
			{
				$file_info['original_type'] = 'image/gif';
			}
		}

		// Generate a thumbnail image
		if ($config->video_thumbnail)
		{
			$thumbnail_name = $file_info['tmp_name'] . '.thumbnail.jpeg';
			$command = \RX_WINDOWS ? escapeshellarg($config->ffmpeg_command) : $config->ffmpeg_command;
			$command .= sprintf(' -ss 00:00:00.%d -i %s -vframes 1', mt_rand(0, 99), escapeshellarg($file_info['tmp_name']));
			$command .= ' -nostdin ' . escapeshellarg($thumbnail_name);
			@exec($command, $output, $return_var);
			if ($return_var === 0)
			{
				$file_info['thumbnail'] = $thumbnail_name;
			}
		}

		return $file_info;
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
	 * @return BaseObject
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

		$config = FileModel::getFileConfig();
		$oDB = DB::getInstance();
		$oDB->begin();

		foreach($file_list as $file)
		{
			if(!is_object($file))
			{
				if(!$file_srl = (int) $file)
				{
					continue;
				}
				$file = FileModel::getFile($file_srl);
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
			if(!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}

			if($config->save_changelog === 'Y')
			{
				$clargs = new stdClass;
				$clargs->change_type = 'D';
				$clargs->file_srl = $file->file_srl;
				$clargs->file_size = $file->file_size;
				$clargs->uploaded_filename = $file->uploaded_filename;
				$output = executeQuery('file.insertFileChangelog', $clargs);
				if(!$output->toBool())
				{
					$oDB->rollback();
					return $output;
				}
			}

			// If successfully deleted, remove the file
			Rhymix\Framework\Storage::delete(FileHandler::getRealPath($file->uploaded_filename));

			// Call a trigger (after)
			ModuleHandler::triggerCall('file.deleteFile', 'after', $file);

			// Remove empty directories
			Rhymix\Framework\Storage::deleteEmptyDirectory(dirname(FileHandler::getRealPath($file->uploaded_filename)), true);

			// Remove thumbnail
			if ($file->thumbnail_filename)
			{
				Rhymix\Framework\Storage::delete(FileHandler::getRealPath($file->thumbnail_filename));
				Rhymix\Framework\Storage::deleteEmptyDirectory(dirname(FileHandler::getRealPath($file->thumbnail_filename)), true);
			}
		}

		$oDB->commit();
		return new BaseObject();
	}

	/**
	 * Delete all attachments of a particular document
	 *
	 * @param int $upload_target_srl Upload target srl to delete files
	 * @return BaseObject
	 */
	function deleteFiles($upload_target_srl)
	{
		// Get a list of attachements
		$file_list = FileModel::getFiles($upload_target_srl);

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
	 * @return BaseObject
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
	 * @return ?BaseObject
	 */
	function moveFile($source_srl, $target_module_srl, $target_srl)
	{
		if($source_srl == $target_srl) return;

		$file_list = FileModel::getFiles($source_srl);
		if(!$file_list) return;

		$config = FileModel::getFileConfig();
		$oDB = DB::getInstance();
		$oDB->begin();

		foreach($file_list as $i => $file_info)
		{
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
			$output = executeQuery('file.updateFile', $args);

			if($config->save_changelog === 'Y')
			{
				$clargs = new stdClass;
				$clargs->change_type = 'M';
				$clargs->file_srl = $file_info->file_srl;
				$clargs->file_size = $file_info->file_size;
				$clargs->uploaded_filename = $new_file;
				$clargs->previous_filename = $old_file;
				$output = executeQuery('file.insertFileChangelog', $clargs);
				if(!$output->toBool())
				{
					$oDB->rollback();
					return $output;
				}
			}
		}

		$oDB->commit();
		return new BaseObject();
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
			$source_file_list = FileModel::getFiles($source_file_list, array(), 'file_srl', true);
		}

		foreach($source_file_list as $source_file)
		{
			$this->copyFile($source_file, $module_srl, $upload_target_srl, $content);
		}
	}

	public function procFileSetCoverImage()
	{
		$vars = Context::getRequestVars();

		// Exit a session if there is neither upload permission nor information
		$editor_sequence = $vars->editor_sequence ?? 0;
		if (!$vars->editor_sequence)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		if (!$_SESSION['upload_info'][$editor_sequence]->enabled)
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}
		$upload_target_srl = $_SESSION['upload_info'][$editor_sequence]->upload_target_srl;
		if (!$upload_target_srl)
		{
			throw new Rhymix\Framework\Exceptions\TargetNotFound;
		}

		$file_info = FileModel::getFile($vars->file_srl);
		if (!$file_info || $file_info->upload_target_srl != $upload_target_srl)
		{
			throw new Rhymix\Framework\Exceptions\TargetNotFound;
		}
		if(!$this->grant->manager && $file_info->member_srl != $this->user->member_srl)
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}

		$args =  new stdClass();
		$args->file_srl = $vars->file_srl;
		$args->upload_target_srl = $upload_target_srl;

		$oDB = DB::getInstance();
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
	public static function getStoragePath($file_type, $file_srl, $module_srl = 0, $upload_target_srl = 0, $regdate = '', $absolute_path = true)
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
			return sprintf('%sfiles/attach/%s/%04d/%02d/%02d/', $prefix, $file_type, substr($regdate, 0, 4), substr($regdate, 4, 2), substr($regdate, 6, 2));
		}
		// 1 or 0: module_srl 및 업로드 대상 번호에 따라 3자리씩 끊어서 정리
		else
		{
			$components = $upload_target_srl ? getNumberingPath($upload_target_srl, 3) : '';
			return sprintf('%sfiles/attach/%s/%d/%s', $prefix, $file_type, $module_srl, $components);
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
		$fileConfig = ModuleModel::getModulePartConfig('file', $obj->originModuleSrl);

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

