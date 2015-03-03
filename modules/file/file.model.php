<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * Model class of the file module
 * @author NAVER (developers@xpressengine.com)
 */
class fileModel extends file
{
	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{
	}

	/**
	 * Return a file list attached in the document
	 *
	 * It is used when a file list of the upload_target_srl is requested for creating/updating a document.
	 * Attempt to replace with sever-side session if upload_target_srl is not yet determined
	 *
	 * @return void
	 */
	function getFileList()
	{
		$oModuleModel = getModel('module');

		$mid = Context::get('mid');
		$editor_sequence = Context::get('editor_sequence');
		$upload_target_srl = Context::get('upload_target_srl');
		if(!$upload_target_srl) $upload_target_srl = $_SESSION['upload_info'][$editor_sequence]->upload_target_srl;

		if($upload_target_srl)
		{
			$tmp_files = $this->getFiles($upload_target_srl);
			$file_count = count($tmp_files);

			for($i=0;$i<$file_count;$i++)
			{
				$file_info = $tmp_files[$i];
				if(!$file_info->file_srl) continue;

				$obj = new stdClass;
				$obj->file_srl = $file_info->file_srl;
				$obj->source_filename = $file_info->source_filename;
				$obj->file_size = $file_info->file_size;
				$obj->disp_file_size = FileHandler::filesize($file_info->file_size);
				if($file_info->direct_download=='N') $obj->download_url = $this->getDownloadUrl($file_info->file_srl, $file_info->sid, $file_info->module_srl);
				else $obj->download_url = str_replace('./', '', $file_info->uploaded_filename);
				$obj->direct_download = $file_info->direct_download;
				$files[] = $obj;
				$attached_size += $file_info->file_size;
			}
		}
		else
		{
			$upload_target_srl = 0;
			$attached_size = 0;
			$files = array();
		}
		// Display upload status
		$upload_status = $this->getUploadStatus($attached_size);
		// Check remained file size until upload complete
		//$config = $oModuleModel->getModuleInfoByMid($mid);	//perhaps config varialbles not used

		$file_config = $this->getUploadConfig();
		$left_size = $file_config->allowed_attach_size*1024*1024 - $attached_size;
		// Settings of required information
		$attached_size = FileHandler::filesize($attached_size);
		$allowed_attach_size = FileHandler::filesize($file_config->allowed_attach_size*1024*1024);
		$allowed_filesize = FileHandler::filesize($file_config->allowed_filesize*1024*1024);
		$allowed_filetypes = $file_config->allowed_filetypes;
		$this->add("files",$files);
		$this->add("editor_sequence",$editor_sequence);
		$this->add("upload_target_srl",$upload_target_srl);
		$this->add("upload_status",$upload_status);
		$this->add("left_size",$left_size);
		$this->add('attached_size', $attached_size);
		$this->add('allowed_attach_size', $allowed_attach_size);
		$this->add('allowed_filesize', $allowed_filesize);
		$this->add('allowed_filetypes', $allowed_filetypes);
	}

	/**
	 * Return number of attachments which belongs to a specific document
	 *
	 * @param int $upload_target_srl The sequence to get a number of files
	 * @return int Returns a number of files
	 */
	function getFilesCount($upload_target_srl)
	{
		$args = new stdClass();
		$args->upload_target_srl = $upload_target_srl;
		$output = executeQuery('file.getFilesCount', $args);
		return (int)$output->data->count;
	}

	/**
	 * Get a download path
	 *
	 * @param int $file_srl The sequence of file to get url
	 * @param string $sid
	 * @return string Returns a url
	 */
	function getDownloadUrl($file_srl, $sid, $module_srl="")
	{
		return sprintf('?module=%s&amp;act=%s&amp;file_srl=%s&amp;sid=%s&amp;module_srl=%s', 'file', 'procFileDownload', $file_srl, $sid, $module_srl);
	}

	/**
	 * Get file configurations
	 *
	 * @param int $module_srl If set this, returns specific module's configuration. Otherwise returns global configuration.
	 * @return object Returns configuration.
	 */
	function getFileConfig($module_srl = null)
	{
		// Get configurations (using module model object)
		$oModuleModel = getModel('module');

		$file_module_config = $oModuleModel->getModuleConfig('file');

		if($module_srl) $file_config = $oModuleModel->getModulePartConfig('file',$module_srl);
		if(!$file_config) $file_config = $file_module_config;

		$config = new stdClass();

		if($file_config)
		{
			$config->allowed_filesize = $file_config->allowed_filesize;
			$config->allowed_attach_size = $file_config->allowed_attach_size;
			$config->allowed_filetypes = $file_config->allowed_filetypes;
			$config->download_grant = $file_config->download_grant;
			$config->allow_outlink = $file_config->allow_outlink;
			$config->allow_outlink_site = $file_config->allow_outlink_site;
			$config->allow_outlink_format = $file_config->allow_outlink_format;
		}
		// Property for all files comes first than each property
		if(!$config->allowed_filesize) $config->allowed_filesize = $file_module_config->allowed_filesize;
		if(!$config->allowed_attach_size) $config->allowed_attach_size = $file_module_config->allowed_attach_size;
		if(!$config->allowed_filetypes) $config->allowed_filetypes = $file_module_config->allowed_filetypes;
		if(!$config->allow_outlink) $config->allow_outlink = $file_module_config->allow_outlink;
		if(!$config->allow_outlink_site) $config->allow_outlink_site = $file_module_config->allow_outlink_site;
		if(!$config->allow_outlink_format) $config->allow_outlink_format = $file_module_config->allow_outlink_format;
		if(!$config->download_grant) $config->download_grant = $file_module_config->download_grant;
		// Default setting if not exists
		if(!$config->allowed_filesize) $config->allowed_filesize = '2';
		if(!$config->allowed_attach_size) $config->allowed_attach_size = '3';
		if(!$config->allowed_filetypes) $config->allowed_filetypes = '*.*';
		if(!$config->allow_outlink) $config->allow_outlink = 'Y';
		if(!$config->download_grant) $config->download_grant = array();

		return $config;
	}

	/**
	 * Get file information
	 *
	 * @param int $file_srl The sequence of file to get information
	 * @param array $columnList The list of columns to get from DB
	 * @return Object|object|array If error returns an instance of Object. If result set is one returns a object that contins file information. If result set is more than one returns array of object.
	 */
	function getFile($file_srl, $columnList = array())
	{
		$args = new stdClass();
		$args->file_srl = $file_srl;
		$output = executeQueryArray('file.getFile', $args, $columnList);
		if(!$output->toBool()) return $output;

		// old version compatibility
		if(count($output->data) == 1)
		{
			$file = $output->data[0];
			$file->download_url = $this->getDownloadUrl($file->file_srl, $file->sid, $file->module_srl);

			return $file;
		}
		else
		{
			$fileList = array();

			if(is_array($output->data))
			{
				foreach($output->data as $key=>$value)
				{
					$file = $value;
					$file->download_url = $this->getDownloadUrl($file->file_srl, $file->sid, $file->module_srl);
					$fileList[] = $file;
				}
			}
			return $fileList;
		}
	}

	/**
	 * Return all files which belong to a specific document
	 *
	 * @param int $upload_target_srl The sequence of target to get file list
	 * @param array $columnList The list of columns to get from DB
	 * @param string $sortIndex The column that used as sort index
	 * @return array Returns array of object that contains file information. If no result returns null.
	 */
	function getFiles($upload_target_srl, $columnList = array(), $sortIndex = 'file_srl', $ckValid = false)
	{
		$args = new stdClass();
		$args->upload_target_srl = $upload_target_srl;
		$args->sort_index = $sortIndex;
		if($ckValid) $args->isvalid = 'Y';
		$output = executeQuery('file.getFiles', $args, $columnList);
		if(!$output->data) return;

		$file_list = $output->data;

		if($file_list && !is_array($file_list)) $file_list = array($file_list);

		$file_count = count($file_list);
		for($i=0;$i<$file_count;$i++)
		{
			$file = $file_list[$i];
			$file->source_filename = stripslashes($file->source_filename);
			$file->download_url = $this->getDownloadUrl($file->file_srl, $file->sid, $file->module_srl);
			$file_list[$i] = $file;
		}

		return $file_list;
	}

	/**
	 * Return configurations of the attachement (it automatically checks if an administrator is)
	 *
	 * @return object Returns a file configuration of current module. If user is admin, returns PHP's max file size and allow all file types.
	 */
	function getUploadConfig()
	{
		$logged_info = Context::get('logged_info');

		$module_srl = Context::get('module_srl');
		// Get the current module if module_srl doesn't exist
		if(!$module_srl)
		{
			$current_module_info = Context::get('current_module_info');
			$module_srl = $current_module_info->module_srl;
		}
		$file_config = $this->getFileConfig($module_srl);

		if($logged_info->is_admin == 'Y')
		{
			$iniPostMaxSize = FileHandler::returnbytes(ini_get('post_max_size'));
			$iniUploadMaxSize = FileHandler::returnbytes(ini_get('upload_max_filesize'));
			$size = min($iniPostMaxSize, $iniUploadMaxSize) / 1048576;
			$file_config->allowed_attach_size = $size;
			$file_config->allowed_filesize = $size;
			$file_config->allowed_filetypes = '*.*';
		}
		return $file_config;
	}

	/**
	 * Return messages for file upload and it depends whether an admin is or not
	 *
	 * @param int $attached_size
	 * @return string
	 */
	function getUploadStatus($attached_size = 0)
	{
		$file_config = $this->getUploadConfig();
		// Display upload status
		$upload_status = sprintf(
			'%s : %s/ %s<br /> %s : %s (%s : %s)',
			Context::getLang('allowed_attach_size'),
			FileHandler::filesize($attached_size),
			FileHandler::filesize($file_config->allowed_attach_size*1024*1024),
			Context::getLang('allowed_filesize'),
			FileHandler::filesize($file_config->allowed_filesize*1024*1024),
			Context::getLang('allowed_filetypes'),
			$file_config->allowed_filetypes
		);
		return $upload_status;
	}

	/**
	 * Return file configuration of the module
	 *
	 * @param int $module_srl The sequence of module to get configuration
	 * @return object
	 */
	function getFileModuleConfig($module_srl)
	{
		return $this->getFileConfig($module_srl);
	}

	/**
	 * Returns a grant of file
	 *
	 * @param object $file_info The file information to get grant
	 * @param object $member_info The member information to get grant
	 * @return object Returns a grant of file
	 */
	function getFileGrant($file_info, $member_info)
	{
		if(!$file_info) return null;

		if($_SESSION['__XE_UPLOADING_FILES_INFO__'][$file_info->file_srl])
		{
			$file_grant->is_deletable = true;
			return $file_grant;
		}

		$oModuleModel = getModel('module');
		$grant = $oModuleModel->getGrant($oModuleModel->getModuleInfoByModuleSrl($file_info->module_srl), $member_info);

		$oDocumentModel = getModel('document');
		$oDocument = $oDocumentModel->getDocument($file_info->upload_target_srl);
		if($oDocument->isExists()) $document_grant = $oDocument->isGranted();

		$file_grant->is_deletable = ($document_grant || $member_info->is_admin == 'Y' || $member_info->member_srl == $file_info->member_srl || $grant->manager);

		return $file_grant;
	}
}
/* End of file file.model.php */
/* Location: ./modules/file/file.model.php */
