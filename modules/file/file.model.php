<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * Model class of the file module
 * @author NAVER (developers@xpressengine.com)
 */
class FileModel extends File
{
	/**
	 * Initialization
	 * @return void
	 */
	public function init()
	{
	}

	/**
	 * Return a file list attached in the document
	 *
	 * It is used when a file list of the upload_target_srl is requested for creating/updating a document.
	 * Attempt to replace with sever-side session if upload_target_srl is not yet determined
	 *
	 * @return object
	 */
	public function getFileList($editor_sequence = null, $upload_target_srl = null, $upload_target_type = null)
	{
		$file_list = [];
		$attached_size = 0;
		$editor_sequence = $editor_sequence ?? intval(Context::get('editor_sequence'));
		$upload_target_srl = $upload_target_srl ?? $_SESSION['upload_info'][$editor_sequence]->upload_target_srl ?? 0;

		// Get uploaded files
		if($upload_target_srl)
		{
			if (!$upload_target_type || $upload_target_type === 'doc' || $upload_target_type === 'document')
			{
				$oDocument = DocumentModel::getDocument($upload_target_srl);
			}
			else
			{
				$oDocument = null;
			}

			// Check permissions of the comment
			if(!$oDocument || !$oDocument->isExists())
			{
				if (!$upload_target_type || $upload_target_type === 'com' || $upload_target_type === 'comment')
				{
					$oComment = CommentModel::getComment($upload_target_srl);
					if($oComment->isExists())
					{
						if(!$oComment->isAccessible())
						{
							throw new Rhymix\Framework\Exceptions\NotPermitted;
						}
						$oDocument = DocumentModel::getDocument($oComment->get('document_srl'));
					}
				}
			}

			// Check permissions of the document
			if($oDocument && $oDocument->isExists() && !$oDocument->isAccessible())
			{
				throw new Rhymix\Framework\Exceptions\NotPermitted;
			}

			// Check permissions of the module
			$module_srl = isset($oComment) ? $oComment->get('module_srl') : ($oDocument ? $oDocument->get('module_srl') : 0);
			if ($module_srl)
			{
				$module_info = ModuleModel::getModuleInfoByModuleSrl($module_srl);
				if(empty($module_info->module_srl))
				{
					throw new Rhymix\Framework\Exceptions\NotPermitted;
				}
				$grant = ModuleModel::getGrant($module_info, Context::get('logged_info'));
				if(!$grant->access)
				{
					throw new Rhymix\Framework\Exceptions\NotPermitted;
				}
			}

			// Set file list
			$filter_type = $_SESSION['upload_info'][$editor_sequence]->upload_target_type ?? null;
			$files = self::getFiles($upload_target_srl, [], 'file_srl', false, $filter_type, true);
			foreach ($files as $file_info)
			{
				$obj = new stdClass;
				$obj->file_srl = $file_info->file_srl;
				$obj->source_filename = $file_info->source_filename;
				$obj->thumbnail_filename = $file_info->thumbnail_filename;
				$obj->file_size = $file_info->file_size;
				$obj->disp_file_size = FileHandler::filesize($file_info->file_size);
				$obj->mime_type = $file_info->mime_type;
				$obj->original_type = $file_info->original_type;
				$obj->width = $file_info->width;
				$obj->height = $file_info->height;
				$obj->duration = $file_info->duration;
				$obj->direct_download = $file_info->direct_download;
				$obj->cover_image = ($file_info->cover_image === 'Y') ? true : false;
				if($obj->direct_download === 'Y' && self::isDownloadable($file_info))
				{
					$obj->download_url = self::getDirectFileUrl($file_info->uploaded_filename);
				}
				else
				{
					$obj->download_url = self::getDirectFileUrl($file_info->download_url);
				}

				$file_list[] = $obj;
				$attached_size += $file_info->file_size;
			}
		}

		// Initialize return value
		$result = new \stdClass;
		$result->files = $file_list;
		$result->editor_sequence = $editor_sequence;
		$result->upload_target_srl = $upload_target_srl;
		$result->upload_status = self::getUploadStatus($attached_size);

		// Set upload config
		$upload_config = self::getUploadConfig();
		$result->attached_size = FileHandler::filesize($attached_size);
		$result->left_size = max(0, ($upload_config->allowed_attach_size * 1024 * 1024) - $attached_size);
		if($this->user->isAdmin())
		{
			$result->allowed_filesize = sprintf('%s (%s)', lang('common.unlimited'), lang('common.admin'));
			$result->allowed_attach_size = sprintf('%s (%s)', lang('common.unlimited'), lang('common.admin'));
			$result->allowed_extensions = [];
		}
		else
		{
			$result->allowed_filesize = FileHandler::filesize($upload_config->allowed_filesize * 1024 * 1024);
			$result->allowed_attach_size = FileHandler::filesize($upload_config->allowed_attach_size * 1024 * 1024);
			$result->allowed_extensions = $upload_config->allowed_extensions;
		}
		if(!$this->user->isAdmin())
		{
			if (isset($_SESSION['upload_info'][$editor_sequence]->allowed_filesize))
			{
				$result->allowed_filesize = FileHandler::filesize($_SESSION['upload_info'][$editor_sequence]->allowed_filesize);
				$result->allowed_attach_size = FileHandler::filesize($_SESSION['upload_info'][$editor_sequence]->allowed_filesize);
			}
			if (isset($_SESSION['upload_info'][$editor_sequence]->allowed_extensions))
			{
				$result->allowed_extensions = $_SESSION['upload_info'][$editor_sequence]->allowed_extensions;
			}
		}
		$result->allowed_filetypes = $upload_config->allowed_filetypes ?? null;

		// for compatibility
		foreach ($result as $key => $val)
		{
			$this->add($key, $val);
		}
		return $result;
	}

	/**
	 * Check if the file is downloadable
	 *
	 * @param object $file_info
	 * @param object $member_info
	 * @return bool
	 */
	public static function isDownloadable($file_info, $member_info = null)
	{
		if(!$member_info)
		{
			$member_info = Rhymix\Framework\Session::getMemberInfo();
		}
		if(self::isDeletable($file_info, $member_info))
		{
			return true;
		}

		// Check the validity
		if($file_info->isvalid !== 'Y')
		{
			return false;
		}

		// Check download groups
		$config = self::getFileConfig($file_info->module_srl);
		if($config->download_groups)
		{
			if(empty($member_info->member_srl))
			{
				return false;
			}
			if(!isset($member_info->group_list))
			{
				$member_info->group_list = MemberModel::getMemberGroups($member_info->member_srl);
			}
			$is_group = false;
			foreach($config->download_groups as $group_srl)
			{
				if(isset($member_info->group_list[$group_srl]))
				{
					$is_group = true;
					break;
				}
			}
			if(!$is_group)
			{
				return false;
			}
		}

		return true;
	}

	/**
	 * Check if the file is indexable
	 * @param string $filename
	 * @param object $file_module_config
	 * @return bool
	 */
	public static function isIndexable($filename, $file_module_config)
	{
		if($file_module_config->allow_indexing_format)
		{
			$allow_indexing_format_array = array();
			$allow_indexing_format_array = explode(',', $file_module_config->allow_indexing_format);
			if(!is_array($allow_indexing_format_array)) $allow_indexing_format_array[0] = $file_module_config->allow_indexing_format;

			foreach($allow_indexing_format_array as $val)
			{
				$val = trim($val);
				if(preg_match("/\.{$val}$/i", $filename))
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	 * Check if the file is deletable
	 *
	 * @param object $file_info
	 * @param object $member_info
	 * @return bool
	 */
	public static function isDeletable($file_info, $member_info = null)
	{
		if(!$member_info)
		{
			$member_info = Rhymix\Framework\Session::getMemberInfo();
		}
		if($member_info->is_admin === 'Y' || $member_info->member_srl == $file_info->member_srl)
		{
			return true;
		}
		if(isset($_SESSION['__XE_UPLOADING_FILES_INFO__'][$file_info->file_srl]))
		{
			return true;
		}

		// Check permissions of the module
		$module_info = ModuleModel::getModuleInfoByModuleSrl($file_info->module_srl);
		if(empty($module_info->module_srl))
		{
			return false;
		}
		$grant = ModuleModel::getGrant($module_info, $member_info);
		if($grant->manager)
		{
			return true;
		}

		// Check permissions of the document
		$oDocument = DocumentModel::getDocument($file_info->upload_target_srl);
		if($oDocument->isExists() && $oDocument->isGranted())
		{
			return true;
		}

		// Check permissions of the comment
		$oComment = CommentModel::getComment($file_info->upload_target_srl);
		if($oComment->isExists() && $oComment->isGranted())
		{
			return true;
		}

		return false;
	}

	/**
	 * Return number of attachments which belongs to a specific document
	 *
	 * @param int $upload_target_srl The sequence to get a number of files
	 * @param ?string $upload_target_type
	 * @param bool $include_null_target_type
	 * @return int Returns a number of files
	 */
	public static function getFilesCount($upload_target_srl, $upload_target_type = null, $include_null_target_type = false)
	{
		$args = new stdClass();
		$args->upload_target_srl = $upload_target_srl;
		if ($upload_target_type)
		{
			$args->upload_target_type = $upload_target_type;
			if ($include_null_target_type)
			{
				$args->include_null_target_type = true;
			}
		}
		$output = executeQuery('file.getFilesCount', $args);
		return (int)$output->data->count;
	}

	/**
	 * Get a download path
	 *
	 * @param int $file_srl The sequence of file to get url
	 * @param string $sid
	 * @param int $unused (unused)
	 * @param string $source_filename
	 * @return string Returns a url
	 */
	public static function getDownloadUrl($file_srl, $sid, $unused = 0, $source_filename = null)
	{
		if ($source_filename && config('use_rewrite') && self::getFileConfig()->download_short_url === 'Y')
		{
			return sprintf('files/download/link/%d/%s/%s', $file_srl, $sid, rawurlencode(preg_replace('/\.\.+/', '.', $source_filename)));
		}
		else
		{
			return sprintf('index.php?module=%s&amp;act=%s&amp;file_srl=%s&amp;sid=%s', 'file', 'procFileDownload', $file_srl, $sid);
		}
	}

	/**
	 * Return direct download file url
	 *
	 * @param string $path
	 * @return string
	 */
	public static function getDirectFileUrl($path)
	{
		return \RX_BASEURL . ltrim($path, './');
	}

	/**
	 * Get file configurations
	 *
	 * @param int $module_srl If set this, returns specific module's configuration. Otherwise returns global configuration.
	 * @return object Returns configuration.
	 */
	public static function getFileConfig($module_srl = null)
	{
		$config = ModuleModel::getModuleConfig('file');
		$config = is_object($config) ? clone $config : new stdClass();
		if($module_srl)
		{
			$module_config = ModuleModel::getModulePartConfig('file', $module_srl);
			foreach((array)$module_config as $key => $value)
			{
				$config->$key = $value;
			}
		}

		// Default setting if not exists
		$config->allowed_filesize = $config->allowed_filesize ?? '2';
		$config->allowed_attach_size = $config->allowed_attach_size ?? '3';
		$config->allowed_filetypes = $config->allowed_filetypes ?? '*.*';
		$config->allow_outlink = $config->allow_outlink ?? 'Y';
		$config->download_grant = $config->download_grant ?? [];
		$config->download_short_url = $config->download_short_url ?? 'N';
		$config->inline_download_format = $config->inline_download_format ?? [];
		$config->image_autoconv = $config->image_autoconv ?? [];
		$config->image_quality_adjustment = $config->image_quality_adjustment ?? 75;
		$config->video_mp4_gif_time = $config->video_mp4_gif_time ?? 0;
		$config->ffmpeg_command = $config->ffmpeg_command ?? '/usr/bin/ffmpeg';
		$config->ffprobe_command = $config->ffprobe_command ?? '/usr/bin/ffprobe';
		$config->magick_command = $config->magick_command ?? '';

		// Set allowed_extensions
		if(!isset($config->allowed_extensions))
		{
			$config->allowed_extensions = [];
			$config->allowed_filetypes = trim($config->allowed_filetypes);
			if($config->allowed_filetypes !== '*.*')
			{
				$config->allowed_extensions = array_map(function($ext) {
					return strtolower(substr(strrchr(trim($ext), '.'), 1));
				}, explode(';', $config->allowed_filetypes));
			}
		}

		// Set download_groups
		$config->download_groups = is_array($config->download_grant) ? array_filter($config->download_grant) : [];

		return $config;
	}

	/**
	 * Get file information
	 *
	 * @param int $file_srl The sequence of file to get information
	 * @param array $columnList The list of columns to get from DB
	 * @return object|array If error returns an instance of Object. If result set is one returns a object that contins file information. If result set is more than one returns array of object.
	 */
	public static function getFile($file_srl, $columnList = array())
	{
		$args = new stdClass();
		$args->file_srl = $file_srl;
		$output = executeQueryArray('file.getFile', $args, $columnList);
		if(!$output->toBool()) return $output;

		// old version compatibility
		if(count($output->data) == 1)
		{
			$file = $output->data[0];
			$file->download_url = self::getDownloadUrl($file->file_srl, $file->sid, 0, $file->source_filename);

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
					$file->download_url = self::getDownloadUrl($file->file_srl, $file->sid, 0, $file->source_filename);
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
	 * @param bool $valid_files_only
	 * @param ?string $upload_target_type
	 * @param bool $include_null_target_type
	 * @return array Returns array of object that contains file information. If no result returns null.
	 */
	public static function getFiles($upload_target_srl, $columnList = array(), $sortIndex = 'file_srl', $valid_files_only = false, $upload_target_type = null, $include_null_target_type = false)
	{
		$args = new stdClass();
		$args->upload_target_srl = $upload_target_srl;
		$args->sort_index = $sortIndex;
		if ($valid_files_only)
		{
			$args->isvalid = 'Y';
		}
		if ($upload_target_type)
		{
			$args->upload_target_type = $upload_target_type;
			if ($include_null_target_type)
			{
				$args->include_null_target_type = true;
			}
		}

		$output = executeQueryArray('file.getFiles', $args, $columnList);
		if(!$output->data)
		{
			return array();
		}

		$fileList = array();
		$nullList = array();
		foreach ($output->data as $file)
		{
			$file->source_filename = escape($file->source_filename, false);
			$file->download_url = self::getDownloadUrl($file->file_srl, $file->sid, 0, $file->source_filename);
			$fileList[] = $file;
			if ($file->upload_target_type === null)
			{
				$nullList[] = $file->file_srl;
			}
		}

		if (count($nullList) && $upload_target_type)
		{
			FileController::getInstance()->updateTargetType($nullList, $upload_target_type);
		}

		return $fileList;
	}

	/**
	 * Return configurations of the attachement (it automatically checks if an administrator is)
	 *
	 * @return object Returns a file configuration of current module. If user is admin, returns PHP's max file size and allow all file types.
	 */
	public static function getUploadConfig($module_srl = 0)
	{
		if (!$module_srl)
		{
			$module_srl = Context::get('module_srl') ?: (Context::get('current_module_info')->module_srl ?? 0);
		}
		$config = self::getFileConfig($module_srl);
		if (Rhymix\Framework\Session::isAdmin())
		{
			$module_config = ModuleModel::getModuleConfig('file');
			$config->allowed_filesize = max($config->allowed_filesize, $module_config->allowed_filesize);
			$config->allowed_attach_size = max($config->allowed_attach_size, $module_config->allowed_attach_size);
			$config->allowed_extensions = [];
			$config->allowed_filetypes = '*.*';
		}
		return $config;
	}

	/**
	 * Return messages for file upload and it depends whether an admin is or not
	 *
	 * @param int $attached_size
	 * @return string
	 */
	public static function getUploadStatus($attached_size = 0)
	{
		$file_config = self::getUploadConfig();
		if (Context::get('allow_chunks') === 'Y')
		{
			$allowed_filesize = $file_config->allowed_filesize * 1024 * 1024;
		}
		else
		{
			$allowed_filesize = min($file_config->allowed_filesize * 1024 * 1024, FileHandler::returnBytes(ini_get('upload_max_filesize')), FileHandler::returnBytes(ini_get('post_max_size')));
		}

		// Display upload status
		$upload_status = sprintf(
			'%s : %s/ %s<br /> %s : %s (%s : %s)',
			lang('allowed_attach_size'),
			FileHandler::filesize($attached_size),
			FileHandler::filesize($file_config->allowed_attach_size*1024*1024),
			lang('allowed_filesize'),
			FileHandler::filesize($allowed_filesize),
			lang('allowed_filetypes'),
			$file_config->allowed_filetypes
		);
		return $upload_status;
	}

	/**
	 * method for compatibility
	 *
	 * @deprecated
	 */
	public static function getFileModuleConfig($module_srl)
	{
		return self::getFileConfig($module_srl);
	}

	/**
	 * method for compatibility
	 *
	 * @deprecated
	 */
	public static function getFileGrant($file_info, $member_info)
	{
		return (object)['is_deletable' => self::isDeletable($file_info, $member_info)];
	}
}
/* End of file file.model.php */
/* Location: ./modules/file/file.model.php */
