<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * The view class file module
 * @author NAVER (developers@xpressengine.com)
 */
class FileView extends File
{
	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{
	}

	/**
	 * This is for additional configuration for service module
	 * It only receives file configurations
	 *
	 * @param string $obj The html string of page of addition setup of module
	 * @return void
	 */
	function triggerDispFileAdditionSetup(&$obj)
	{
		$current_module_srl = Context::get('module_srl');
		if(!$current_module_srl)
		{
			// Get information of the current module
			$current_module_srl = Context::get('current_module_info')->module_srl ?? 0;
			if(!$current_module_srl)
			{
				return;
			}
		}

		// Get file configurations of the module
		$config = FileModel::getFileConfig($current_module_srl);
		if (!isset($config->use_default_file_config))
		{
			$config->use_default_file_config = 'Y';
		}
		if (!isset($config->use_image_default_file_config))
		{
			$config->use_image_default_file_config = 'Y';
		}
		if (!isset($config->use_video_default_file_config))
		{
			$config->use_video_default_file_config = 'Y';
		}
		Context::set('config', $config);
		Context::set('is_ffmpeg', function_exists('exec') && !empty($config->ffmpeg_command) && Rhymix\Framework\Storage::isExecutable($config->ffmpeg_command) && !empty($config->ffprobe_command) && Rhymix\Framework\Storage::isExecutable($config->ffprobe_command));
		Context::set('is_magick', function_exists('exec') && !empty($config->magick_command) && Rhymix\Framework\Storage::isExecutable($config->magick_command));

		// Get a permission for group setting
		$group_list = MemberModel::getGroups();
		Context::set('group_list', $group_list);

		// Set a template file
		$oTemplate = TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path . 'tpl', 'file_module_config');
		$obj .= $tpl;
	}
}
/* End of file file.view.php */
/* Location: ./modules/file/file.view.php */
