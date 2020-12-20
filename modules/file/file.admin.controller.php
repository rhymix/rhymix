<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * admin controller class of the file module
 * @author NAVER (developers@xpressengine.com)
 */
class fileAdminController extends file
{
	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{
	}

	/**
	 * @deprecated move to fileController
	 * @return Object
	 */
	function deleteModuleFiles($module_srl)
	{
		return getController('file')->deleteModuleFiles($module_srl);
	}

	/**
	 * Delete selected files from the administrator page
	 *
	 * @return Object
	 */
	function procFileAdminDeleteChecked()
	{
		// An error appears if no document is selected
		$cart = Context::get('cart');
		if(!$cart) throw new Rhymix\Framework\Exception('msg_file_cart_is_null');
		if(!is_array($cart)) $file_srl_list= explode('|@|', $cart);
		else $file_srl_list = $cart;
		$file_count = count($file_srl_list);
		if(!$file_count) throw new Rhymix\Framework\Exception('msg_file_cart_is_null');

		$oFileController = getController('file');
		// Delete the post
		for($i=0;$i<$file_count;$i++)
		{
			$file_srl = trim($file_srl_list[$i]);
			if(!$file_srl) continue;

			$oFileController->deleteFile($file_srl);
		}
		
		$this->setMessage(sprintf(lang('msg_checked_file_is_deleted'), $file_count));
		$this->setRedirectUrl(Context::get('success_return_url') ?: getNotEncodedUrl('', 'module', 'admin', 'act', 'dispFileAdminList'));
	}

	/**
	 * Save upload configuration
	 *
	 * @return Object
	 */
	function procFileAdminInsertUploadConfig()
	{
		// Update configuration
		$config = getModel('module')->getModuleConfig('file') ?: new stdClass;
		$config->allowed_filesize = Context::get('allowed_filesize');
		$config->allowed_attach_size = Context::get('allowed_attach_size');
		$config->allowed_filetypes = Context::get('allowed_filetypes');
		$config->image_autoconv['bmp2jpg'] = Context::get('image_autoconv_bmp2jpg') === 'Y' ? true : false;
		$config->image_autoconv['png2jpg'] = Context::get('image_autoconv_png2jpg') === 'Y' ? true : false;
		$config->image_autoconv['webp2jpg'] = Context::get('image_autoconv_webp2jpg') === 'Y' ? true : false;
		$config->image_autoconv['gif2mp4'] = Context::get('image_autoconv_gif2mp4') === 'Y' ? true : false;
		$config->max_image_width = intval(Context::get('max_image_width')) ?: '';
		$config->max_image_height = intval(Context::get('max_image_height')) ?: '';
		$config->max_image_size_action = Context::get('max_image_size_action') ?: '';
		$config->max_image_size_admin = Context::get('max_image_size_admin') === 'Y' ? 'Y' : 'N';
		$config->image_quality_adjustment = max(50, min(100, intval(Context::get('image_quality_adjustment'))));
		$config->image_autorotate = Context::get('image_autorotate') === 'Y' ? true : false;
		$config->image_remove_exif_data = Context::get('image_remove_exif_data') === 'Y' ? true : false;
		$config->video_thumbnail = Context::get('video_thumbnail') === 'Y' ? true : false;
		$config->video_mp4_gif_time = intval(Context::get('video_mp4_gif_time'));
		if (RX_WINDOWS)
		{
			$config->ffmpeg_command = escape(Context::get('ffmpeg_command')) ?: 'C:\Program Files\ffmpeg\bin\ffmpeg.exe';
			$config->ffprobe_command = escape(Context::get('ffprobe_command')) ?: 'C:\Program Files\ffmpeg\bin\ffprobe.exe';
		}
		else
		{
			$config->ffmpeg_command = escape(utf8_trim(Context::get('ffmpeg_command'))) ?: '/usr/bin/ffmpeg';
			$config->ffprobe_command = escape(utf8_trim(Context::get('ffprobe_command'))) ?: '/usr/bin/ffprobe';
		}
		
		// Check maximum file size
		if (PHP_INT_SIZE < 8)
		{
			if ($config->allowed_filesize > 2047 || $config->allowed_attach_size > 2047)
			{
				throw new Rhymix\Framework\Exception('msg_32bit_max_2047mb');
			}
		}
		
		// Simplify allowed_filetypes
		$config->allowed_extensions = strtr(strtolower(trim($config->allowed_filetypes)), array('*.' => '', ';' => ','));
		if ($config->allowed_extensions)
		{
			$config->allowed_extensions = array_map('trim', explode(',', $config->allowed_filetypes));
			$config->allowed_filetypes = implode(';', array_map(function($ext) {
				return '*.' . $ext;
			}, $config->allowed_extensions));
		}
		else
		{
			$config->allowed_extensions = array();
			$config->allowed_filetypes = '*.*';
		}
		
		// Save and redirect
		$output = getController('module')->insertModuleConfig('file', $config);
		$returnUrl = Context::get('success_return_url') ?: getNotEncodedUrl('', 'module', 'admin', 'act', 'dispFileAdminUploadConfig');
		return $this->setRedirectUrl($returnUrl, $output);
	}

	/**
	 * Save download configuration
	 *
	 * @return Object
	 */
	function procFileAdminInsertDownloadConfig()
	{
		// Update configuration
		$config = getModel('module')->getModuleConfig('file') ?: new stdClass;
		$config->allow_outlink = Context::get('allow_outlink') === 'N' ? 'N' : 'Y';
		$config->allow_outlink_format = Context::get('allow_outlink_format');
		$config->allow_outlink_site = Context::get('allow_outlink_site');
		$config->allow_multimedia_direct_download = Context::get('allow_multimedia_direct_download') === 'Y' ? 'Y' : 'N';
		$config->download_short_url = Context::get('download_short_url') === 'Y' ? 'Y' : 'N';
		$config->inline_download_format = array_map('utf8_trim', Context::get('inline_download_format') ?: []);
		
		// Save and redirect
		$output = getController('module')->insertModuleConfig('file', $config);
		$returnUrl = Context::get('success_return_url') ?: getNotEncodedUrl('', 'module', 'admin', 'act', 'dispFileAdminDownloadConfig');
		return $this->setRedirectUrl($returnUrl, $output);
	}

	/**
	 * Save other configuration
	 *
	 * @return Object
	 */
	function procFileAdminInsertOtherConfig()
	{
		// Update configuration
		$config = getModel('module')->getModuleConfig('file') ?: new stdClass;
		$config->save_changelog = Context::get('save_changelog') === 'Y' ? 'Y' : 'N';
		
		// Save and redirect
		$output = getController('module')->insertModuleConfig('file', $config);
		$returnUrl = Context::get('success_return_url') ?: getNotEncodedUrl('', 'module', 'admin', 'act', 'dispFileAdminOtherConfig');
		return $this->setRedirectUrl($returnUrl, $output);
	}

	/**
	 * Add file information for each module
	 *
	 * @return void
	 */
	function procFileAdminInsertModuleConfig()
	{
		$config = new stdClass;
		
		// Default
		if(!Context::get('use_default_file_config'))
		{
			$config->use_default_file_config = 'N';
			$config->allowed_filesize = Context::get('allowed_filesize');
			$config->allowed_attach_size = Context::get('allowed_attach_size');
			$config->allowed_filetypes = Context::get('allowed_filetypes');
			
			// Check maximum file size
			if (PHP_INT_SIZE < 8)
			{
				if ($config->allowed_filesize > 2047 || $config->allowed_attach_size > 2047)
				{
					throw new Rhymix\Framework\Exception('msg_32bit_max_2047mb');
				}
			}
			
			// Simplify allowed_filetypes
			$config->allowed_extensions = strtr(strtolower(trim($config->allowed_filetypes)), array('*.' => '', ';' => ','));
			if ($config->allowed_extensions)
			{
				$config->allowed_extensions = array_map('trim', explode(',', $config->allowed_filetypes));
				$config->allowed_filetypes = implode(';', array_map(function($ext) {
					return '*.' . $ext;
				}, $config->allowed_extensions));
			}
			else
			{
				$config->allowed_extensions = array();
				$config->allowed_filetypes = '*.*';
			}
		}
		
		// Image
		if(!Context::get('use_image_default_file_config'))
		{
			$config->use_image_default_file_config = 'N';
			$config->image_autoconv['bmp2jpg'] = Context::get('image_autoconv_bmp2jpg') === 'Y' ? true : false;
			$config->image_autoconv['png2jpg'] = Context::get('image_autoconv_png2jpg') === 'Y' ? true : false;
			$config->image_autoconv['webp2jpg'] = Context::get('image_autoconv_webp2jpg') === 'Y' ? true : false;
			$config->image_autoconv['gif2mp4'] = Context::get('image_autoconv_gif2mp4') === 'Y' ? true : false;
			$config->max_image_width = intval(Context::get('max_image_width')) ?: '';
			$config->max_image_height = intval(Context::get('max_image_height')) ?: '';
			$config->max_image_size_action = Context::get('max_image_size_action') ?: '';
			$config->max_image_size_admin = Context::get('max_image_size_admin') === 'Y' ? 'Y' : 'N';
			$config->image_quality_adjustment = max(50, min(100, intval(Context::get('image_quality_adjustment'))));
			$config->image_autorotate = Context::get('image_autorotate') === 'Y' ? true : false;
			$config->image_remove_exif_data = Context::get('image_remove_exif_data') === 'Y' ? true : false;
		}
		
		// Video
		if(!Context::get('use_video_default_file_config'))
		{
			$config->use_video_default_file_config = 'N';
			$config->video_thumbnail = Context::get('video_thumbnail') === 'Y' ? true : false;
			$config->video_mp4_gif_time = intval(Context::get('video_mp4_gif_time'));
		}
		
		// Set download groups
		$download_grant = Context::get('download_grant');
		$config->download_grant = is_array($download_grant) ? array_values($download_grant) : array($download_grant);
		
		// Update
		$oModuleController = getController('module');
		foreach(explode(',', Context::get('target_module_srl')) as $module_srl)
		{
			$output = $oModuleController->insertModulePartConfig('file', trim($module_srl), $config);
			if(!$output->toBool())
			{
				return $output;
			}
		}
		
		$this->setError(-1);
		$this->setMessage('success_updated', 'info');
		$this->setRedirectUrl(Context::get('success_return_url') ?: getNotEncodedUrl('', 'module', 'admin', 'act', 'dispBoardAdminContent'));
	}

	/**
	 * Add to SESSION file srl
	 *
	 * @return Object
	 */
	function procFileAdminAddCart()
	{
		$file_srl = (int)Context::get('file_srl');
		//$fileSrlList = array(500, 502);

		$oFileModel = getModel('file');
		$output = $oFileModel->getFile($file_srl);
		//$output = $oFileModel->getFile($fileSrlList);

		if($output->file_srl)
		{
			if($_SESSION['file_management'][$output->file_srl]) unset($_SESSION['file_management'][$output->file_srl]);
			else $_SESSION['file_management'][$output->file_srl] = true;
		}
	}
}
/* End of file file.admin.controller.php */
/* Location: ./modules/file/file.admin.controller.php */
