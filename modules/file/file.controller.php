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
		$file_info = $_FILES['Filedata'];

		// An error appears if not a normally uploaded file
		if(!is_uploaded_file($file_info['tmp_name'])) exit();

		// Basic variables setting
		$oFileModel = getModel('file');
		$editor_sequence = Context::get('editor_sequence');
		$upload_target_srl = intval(Context::get('uploadTargetSrl'));
		if(!$upload_target_srl) $upload_target_srl = intval(Context::get('upload_target_srl'));
		$module_srl = $this->module_srl;
		// Exit a session if there is neither upload permission nor information
		if(!$_SESSION['upload_info'][$editor_sequence]->enabled) exit();
		// Extract from session information if upload_target_srl is not specified
		if(!$upload_target_srl) $upload_target_srl = $_SESSION['upload_info'][$editor_sequence]->upload_target_srl;
		// Create if upload_target_srl is not defined in the session information
		if(!$upload_target_srl) $_SESSION['upload_info'][$editor_sequence]->upload_target_srl = $upload_target_srl = getNextSequence();

		$output = $this->insertFile($file_info, $module_srl, $upload_target_srl);
		Context::setResponseMethod('JSON');
		if($output->error != '0') $this->stop($output->message);
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
		if($file_srl) $this->deleteFile($file_srl);

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
			return new Object(-1,'msg_invalid_request');
		}

		$oFileModel = getModel('file');
		$fileInfo = $oFileModel->getFile($file_srl);
		if(!$fileInfo || $fileInfo->direct_download != 'Y')
		{
			return new Object(-1,'msg_invalid_request');
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
			return new Object(-1,'msg_invalid_request');
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

		if(isset($this->grant->access) && $this->grant->access !== true) return new Object(-1, 'msg_not_permitted');

		$file_srl = Context::get('file_srl');
		$sid = Context::get('sid');
		$logged_info = Context::get('logged_info');
		// Get file information from the DB
		$columnList = array('file_srl', 'sid', 'isvalid', 'source_filename', 'module_srl', 'uploaded_filename', 'file_size', 'member_srl', 'upload_target_srl', 'upload_target_type');
		$file_obj = $oFileModel->getFile($file_srl, $columnList);
		// If the requested file information is incorrect, an error that file cannot be found appears
		if($file_obj->file_srl!=$file_srl || $file_obj->sid!=$sid) return $this->stop('msg_file_not_found');
		// Notify that file download is not allowed when standing-by(Only a top-administrator is permitted)
		if($logged_info->is_admin != 'Y' && $file_obj->isvalid!='Y') return $this->stop('msg_not_permitted_download');
		// File name
		$filename = $file_obj->source_filename;
		$file_module_config = $oFileModel->getFileModuleConfig($file_obj->module_srl);
		// Not allow the file outlink
		if($file_module_config->allow_outlink == 'N')
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
			if($file_module_config->allow_outlink != 'Y') return $this->stop('msg_not_allowed_outlink');
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
			if(!Context::get('is_logged')) return $this->stop('msg_not_permitted_download');
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
					if(!$is_permitted) return $this->stop('msg_not_permitted_download');
				}
			}
		}
		// Call a trigger (before)
		$output = ModuleHandler::triggerCall('file.downloadFile', 'before', $file_obj);
		if(!$output->toBool()) return $this->stop(($output->message)?$output->message:'msg_not_permitted_download');


		// 다운로드 후 (가상)
		// Increase download_count
		$args = new stdClass();
		$args->file_srl = $file_srl;
		executeQuery('file.updateFileDownloadCount', $args);
		// Call a trigger (after)
		$output = ModuleHandler::triggerCall('file.downloadFile', 'after', $file_obj);

		$random = new Password();
		$file_key = $_SESSION['__XE_FILE_KEY__'][$file_srl] = $random->createSecureSalt(32, 'hex');
		header('Location: '.getNotEncodedUrl('', 'act', 'procFileOutput','file_srl',$file_srl,'file_key',$file_key));
		Context::close();
		exit();

	}

	public function procFileOutput()
	{
		$oFileModel = getModel('file');
		$file_srl = Context::get('file_srl');
		$file_key = Context::get('file_key');
		if(strstr($_SERVER['HTTP_USER_AGENT'], "Android")) $is_android = true;

		if($is_android && $_SESSION['__XE_FILE_KEY_AND__'][$file_srl]) $session_key = '__XE_FILE_KEY_AND__';
		else $session_key = '__XE_FILE_KEY__';
		$columnList = array('source_filename', 'uploaded_filename', 'file_size');
		$file_obj = $oFileModel->getFile($file_srl, $columnList);

		$uploaded_filename = $file_obj->uploaded_filename;

		if(!file_exists($uploaded_filename)) return $this->stop('msg_file_not_found');

		if(!$file_key || $_SESSION[$session_key][$file_srl] != $file_key)
		{
			unset($_SESSION[$session_key][$file_srl]);
			return $this->stop('msg_invalid_request');
		}

		$file_size = $file_obj->file_size;
		$filename = $file_obj->source_filename;
		if(strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE') !== FALSE || (strpos($_SERVER['HTTP_USER_AGENT'], 'Windows') !== FALSE && strpos($_SERVER['HTTP_USER_AGENT'], 'Trident') !== FALSE && strpos($_SERVER['HTTP_USER_AGENT'], 'rv:') !== FALSE))
		{
			$filename = rawurlencode($filename);
			$filename = preg_replace('/\./', '%2e', $filename, substr_count($filename, '.') - 1);
		}

		if($is_android)
		{
			if($_SESSION['__XE_FILE_KEY__'][$file_srl]) $_SESSION['__XE_FILE_KEY_AND__'][$file_srl] = $file_key;
		}

		unset($_SESSION[$session_key][$file_srl]);

		Context::close();

		$fp = fopen($uploaded_filename, 'rb');
		if(!$fp) return $this->stop('msg_file_not_found');

		header("Cache-Control: ");
		header("Pragma: ");
		header("Content-Type: application/octet-stream");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

		header("Content-Length: " .(string)($file_size));
		header('Content-Disposition: attachment; filename="'.$filename.'"');
		header("Content-Transfer-Encoding: binary\n");

		// if file size is lager than 10MB, use fread function (#18675748)
		if(filesize($uploaded_filename) > 1024 * 1024)
		{
			while(!feof($fp)) echo fread($fp, 1024);
			fclose($fp);
		}
		else
		{
			fpassthru($fp);
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
		if(!Context::get('is_logged')) return new Object(-1,'msg_not_permitted');
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
		if(!$document_srl) return new Object();
		// Get numbers of attachments
		$oFileModel = getModel('file');
		$obj->uploaded_count = $oFileModel->getFilesCount($document_srl);

		return new Object();
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
		if(!$document_srl) return new Object();

		$output = $this->setFilesValid($document_srl);
		if(!$output->toBool()) return $output;

		return new Object();
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
		if(!$document_srl) return new Object();

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
		if(!$comment_srl) return new Object();
		// Get numbers of attachments
		$oFileModel = getModel('file');
		$obj->uploaded_count = $oFileModel->getFilesCount($comment_srl);

		return new Object();
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
		if(!$comment_srl || !$uploaded_count) return new Object();

		$output = $this->setFilesValid($comment_srl);
		if(!$output->toBool()) return $output;

		return new Object();
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
		if(!$comment_srl) return new Object();

		if($obj->isMoveToTrash) return new Object();

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
		if(!$module_srl) return new Object();

		$oFileController = getAdminController('file');
		return $oFileController->deleteModuleFiles($module_srl);
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
						return $this->stop('msg_not_allowed_filetype');
					}
				}

				$allowed_filesize = $config->allowed_filesize * 1024 * 1024;
				$allowed_attach_size = $config->allowed_attach_size * 1024 * 1024;
				// An error appears if file size exceeds a limit
				if($allowed_filesize < filesize($file_info['tmp_name'])) return new Object(-1, 'msg_exceeds_limit_size');
				// Get total file size of all attachements (from DB)
				$size_args = new stdClass;
				$size_args->upload_target_srl = $upload_target_srl;
				$output = executeQuery('file.getAttachedFileSize', $size_args);
				$attached_size = (int)$output->data->attached_size + filesize($file_info['tmp_name']);
				if($attached_size > $allowed_attach_size) return new Object(-1, 'msg_exceeds_limit_size');
			}
		}

		// https://github.com/xpressengine/xe-core/issues/1713
		$file_info['name'] = preg_replace('/\.(php|phtm|phar|html?|cgi|pl|exe|jsp|asp|inc)/i', '$0-x',$file_info['name']);
		$file_info['name'] = removeHackTag($file_info['name']);
		$file_info['name'] = str_replace(array('<','>'),array('%3C','%3E'),$file_info['name']);

		// Get random number generator
		$random = new Password();

		// Set upload path by checking if the attachement is an image or other kinds of file
		if(preg_match("/\.(jpe?g|gif|png|wm[va]|mpe?g|avi|swf|flv|mp[1-4]|as[fx]|wav|midi?|moo?v|qt|r[am]{1,2}|m4v)$/i", $file_info['name']))
		{
			$path = sprintf("./files/attach/images/%s/%s", $module_srl,getNumberingPath($upload_target_srl,3));

			// special character to '_'
			// change to random file name. because window php bug. window php is not recognize unicode character file name - by cherryfilter
			$ext = substr(strrchr($file_info['name'],'.'),1);
			//$_filename = preg_replace('/[#$&*?+%"\']/', '_', $file_info['name']);
			$_filename = $random->createSecureSalt(32, 'hex').'.'.$ext;
			$filename  = $path.$_filename;
			$idx = 1;
			while(file_exists($filename))
			{
				$filename = $path.preg_replace('/\.([a-z0-9]+)$/i','_'.$idx.'.$1',$_filename);
				$idx++;
			}
			$direct_download = 'Y';
		}
		else
		{
			$path = sprintf("./files/attach/binaries/%s/%s", $module_srl, getNumberingPath($upload_target_srl,3));
			$filename = $path.$random->createSecureSalt(32, 'hex');
			$direct_download = 'N';
		}
		// Create a directory
		if(!FileHandler::makeDir($path)) return new Object(-1,'msg_not_permitted_create');

		// Check uploaded file
		if(!checkUploadedFile($file_info['tmp_name']))  return new Object(-1,'msg_file_upload_error');

		// Get random number generator
		$random = new Password();
		
		// Move the file
		if($manual_insert)
		{
			@copy($file_info['tmp_name'], $filename);
			if(!file_exists($filename))
			{
				$filename = $path.$random->createSecureSalt(32, 'hex').'.'.$ext;
				@copy($file_info['tmp_name'], $filename);
			}
		}
		else
		{
			if(!@move_uploaded_file($file_info['tmp_name'], $filename))
			{
				$filename = $path.$random->createSecureSalt(32, 'hex').'.'.$ext;
				if(!@move_uploaded_file($file_info['tmp_name'], $filename))  return new Object(-1,'msg_file_upload_error');
			}
		}
		// Get member information
		$oMemberModel = getModel('member');
		$member_srl = $oMemberModel->getLoggedMemberSrl();
		// List file information
		$args = new stdClass;
		$args->file_srl = getNextSequence();
		$args->upload_target_srl = $upload_target_srl;
		$args->module_srl = $module_srl;
		$args->direct_download = $direct_download;
		$args->source_filename = $file_info['name'];
		$args->uploaded_filename = $filename;
		$args->download_count = $download_count;
		$args->file_size = @filesize($filename);
		$args->comment = NULL;
		$args->member_srl = $member_srl;
		$args->sid = $random->createSecureSalt(32, 'hex');

		$output = executeQuery('file.insertFile', $args);
		if(!$output->toBool()) return $output;
		// Call a trigger (after)
		$trigger_output = ModuleHandler::triggerCall('file.insertFile', 'after', $args);
		if(!$trigger_output->toBool()) return $trigger_output;

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
	 * @param int $file_srl Sequence of file to delete
	 * @return Object
	 */
	function deleteFile($file_srl)
	{
		if(!$file_srl) return;

		$srls = (is_array($file_srl)) ? $file_srl : explode(',', $file_srl);
		if(!count($srls)) return;

		$oDocumentController = getController('document');
		$documentSrlList = array();

		foreach($srls as $srl)
		{
			$srl = (int)$srl;
			if(!$srl) 
			{
				continue;
			}

			$args = new stdClass();
			$args->file_srl = $srl;
			$output = executeQuery('file.getFile', $args);

			if(!$output->toBool() || !$output->data) 
			{
				continue;
			}

			$file_info = $output->data;

			if($file_info->upload_target_srl)
			{
				$documentSrlList[] = $file_info->upload_target_srl;
			}

			$source_filename = $output->data->source_filename;
			$uploaded_filename = $output->data->uploaded_filename;

			// Call a trigger (before)
			$trigger_obj = $output->data;
			$output = ModuleHandler::triggerCall('file.deleteFile', 'before', $trigger_obj);
			if(!$output->toBool()) return $output;

			// Remove from the DB
			$output = executeQuery('file.deleteFile', $args);
			if(!$output->toBool()) return $output;

			// Call a trigger (after)
			$trigger_output = ModuleHandler::triggerCall('file.deleteFile', 'after', $trigger_obj);
			if(!$trigger_output->toBool()) return $trigger_output;

			// If successfully deleted, remove the file
			FileHandler::removeFile($uploaded_filename);
		}

		$oDocumentController->updateUploaedCount($documentSrlList);

		return $output;
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
		$columnList = array('file_srl', 'uploaded_filename', 'module_srl');
		$file_list = $oFileModel->getFiles($upload_target_srl, $columnList);
		// Success returned if no attachement exists
		if(!is_array($file_list)||!count($file_list)) return new Object();

		// Delete the file
		$path = array();
		$file_count = count($file_list);
		for($i=0;$i<$file_count;$i++)
		{
			$this->deleteFile($file_list[$i]->file_srl);

			$uploaded_filename = $file_list[$i]->uploaded_filename;
			$path_info = pathinfo($uploaded_filename);
			if(!in_array($path_info['dirname'], $path)) $path[] = $path_info['dirname'];
		}

		// Remove from the DB
		$args = new stdClass();
		$args->upload_target_srl = $upload_target_srl;
		$output = executeQuery('file.deleteFiles', $args);
		if(!$output->toBool()) return $output;
		
		// Remove a file directory of the document
		for($i=0, $c=count($path); $i<$c; $i++)
		{
			FileHandler::removeBlankDir($path[$i]);
		}

		return $output;
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
			if(preg_match("/\.(jpg|jpeg|gif|png|wmv|wma|mpg|mpeg|avi|swf|flv|mp1|mp2|mp3|mp4|asf|wav|asx|mid|midi|asf|mov|moov|qt|rm|ram|ra|rmm|m4v)$/i", $file_info->source_filename))
			{
				$path = sprintf("./files/attach/images/%s/%s/", $target_module_srl,$target_srl);
				$new_file = $path.$file_info->source_filename;
			}
			else
			{
				$path = sprintf("./files/attach/binaries/%s/%s/", $target_module_srl, $target_srl);
				$random = new Password();
				$new_file = $path.$random->createSecureSalt(32, 'hex');
			}
			// Pass if a target document to move is same
			if($old_file == $new_file) continue;
			// Create a directory
			FileHandler::makeDir($path);
			// Move the file
			FileHandler::rename($old_file, $new_file);
			// Update DB information
			$args = new stdClass;
			$args->file_srl = $file_info->file_srl;
			$args->uploaded_filename = $new_file;
			$args->module_srl = $file_info->module_srl;
			$args->upload_target_srl = $target_srl;
			executeQuery('file.updateFile', $args);
		}
	}

	public function procFileSetCoverImage()
	{
		$vars = Context::getRequestVars();
		$logged_info = Context::get('logged_info');

		if(!$vars->editor_sequence) return new Object(-1, 'msg_invalid_request');

		$upload_target_srl = $_SESSION['upload_info'][$vars->editor_sequence]->upload_target_srl;

		$oFileModel = getModel('file');
		$file_info = $oFileModel->getFile($vars->file_srl);

		if(!$file_info) return new Object(-1, 'msg_not_founded');

		if(!$this->manager && !$file_info->member_srl === $logged_info->member_srl) return new Object(-1, 'msg_not_permitted');

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

		$args->cover_image = 'Y';
		$output = executeQuery('file.updateCoverImage', $args);
		if(!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		$oDB->commit();

		// 썸네일 삭제
		$thumbnail_path = sprintf('files/thumbnails/%s', getNumberingPath($upload_target_srl, 3));
		Filehandler::removeFilesInDir($thumbnail_path);
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

