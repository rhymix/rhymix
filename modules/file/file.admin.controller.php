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

		$this->setMessage( sprintf(lang('msg_checked_file_is_deleted'), $file_count) );

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispFileAdminList');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * Save upload configuration
	 *
	 * @return Object
	 */
	function procFileAdminInsertUploadConfig()
	{
		// Update configuration
		$config = getModel('module')->getModuleConfig('file');
		$config->allowed_filesize = Context::get('allowed_filesize');
		$config->allowed_attach_size = Context::get('allowed_attach_size');
		$config->allowed_filetypes = Context::get('allowed_filetypes');
		$config->max_image_width = intval(Context::get('max_image_width')) ?: '';
		$config->max_image_height = intval(Context::get('max_image_height')) ?: '';
		$config->max_image_size_action = Context::get('max_image_size_action') ?: '';
		$config->max_image_size_quality = max(50, min(100, intval(Context::get('max_image_size_quality'))));
		$config->max_image_size_admin = Context::get('max_image_size_admin') === 'Y' ? 'Y' : 'N';
		$config->image_autoconv['bmp2jpg'] = Context::get('image_autoconv_bmp2jpg') === 'Y' ? true : false;
		$config->image_autoconv['png2jpg'] = Context::get('image_autoconv_png2jpg') === 'Y' ? true : false;
		$config->image_autoconv['webp2jpg'] = Context::get('image_autoconv_webp2jpg') === 'Y' ? true : false;
		$config->image_autoconv['gif2mp4'] = Context::get('image_autoconv_gif2mp4') === 'Y' ? true : false;
		$config->image_autoconv_quality = max(50, min(100, intval(Context::get('image_autoconv_quality'))));
		$config->ffmpeg_command = escape(utf8_trim(Context::get('ffmpeg_command'))) ?: '/usr/bin/ffmpeg';
		$config->image_autorotate = Context::get('image_autorotate') === 'Y' ? true : false;
		$config->image_autorotate_quality = max(50, min(100, intval(Context::get('image_autorotate_quality'))));
		
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
		$oModuleController = getController('module');
		$output = $oModuleController->insertModuleConfig('file',$config);

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispFileAdminUploadConfig');
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
		$config = getModel('module')->getModuleConfig('file');
		$config->allow_outlink = Context::get('allow_outlink');
		$config->allow_outlink_format = Context::get('allow_outlink_format');
		$config->allow_outlink_site = Context::get('allow_outlink_site');
		$config->inline_download_format = array_map('utf8_trim', Context::get('inline_download_format'));
		
		// Save and redirect
		$oModuleController = getController('module');
		$output = $oModuleController->insertModuleConfig('file',$config);
		if(!$output->toBool())
		{
			return $output;
		}

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispFileAdminDownloadConfig');
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
		$config = getModel('module')->getModuleConfig('file');
		$config->save_changelog = Context::get('save_changelog') === 'Y' ? 'Y' : 'N';
		
		// Save and redirect
		$oModuleController = getController('module');
		$output = $oModuleController->insertModuleConfig('file',$config);
		if(!$output->toBool())
		{
			return $output;
		}

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispFileAdminOtherConfig');
		return $this->setRedirectUrl($returnUrl, $output);
	}

	/**
	 * Add file information for each module
	 *
	 * @return void
	 */
	function procFileAdminInsertModuleConfig()
	{
		// Get variables
		$module_srl = Context::get('target_module_srl');
		// In order to configure multiple modules at once
		if(preg_match('/^([0-9,]+)$/',$module_srl)) $module_srl = explode(',',$module_srl);
		else $module_srl = array($module_srl);

		$file_config = new stdClass;
		$file_config->allowed_filesize = Context::get('allowed_filesize');
		$file_config->allowed_attach_size = Context::get('allowed_attach_size');
		$file_config->allowed_filetypes = Context::get('allowed_filetypes');
		$file_config->max_image_width = intval(Context::get('max_image_width')) ?: '';
		$file_config->max_image_height = intval(Context::get('max_image_height')) ?: '';
		$file_config->max_image_size_action = Context::get('max_image_size_action') ?: '';
		$file_config->max_image_size_quality = max(50, min(100, intval(Context::get('max_image_size_quality'))));
		$file_config->max_image_size_admin = Context::get('max_image_size_admin') === 'Y' ? 'Y' : 'N';
		$file_config->image_autoconv['bmp2jpg'] = Context::get('image_autoconv_bmp2jpg') === 'Y' ? true : false;
		$file_config->image_autoconv['png2jpg'] = Context::get('image_autoconv_png2jpg') === 'Y' ? true : false;
		$file_config->image_autoconv['webp2jpg'] = Context::get('image_autoconv_webp2jpg') === 'Y' ? true : false;
		$file_config->image_autoconv['gif2mp4'] = Context::get('image_autoconv_gif2mp4') === 'Y' ? true : false;
		$file_config->image_autoconv_quality = max(50, min(100, intval(Context::get('image_autoconv_quality'))));
		$file_config->ffmpeg_command = escape(utf8_trim(Context::get('ffmpeg_command'))) ?: '/usr/bin/ffmpeg';
		$file_config->image_autorotate = Context::get('image_autorotate') === 'Y' ? true : false;
		$file_config->image_autorotate_quality = max(50, min(100, intval(Context::get('image_autorotate_quality'))));

		// Check maximum file size
		if (PHP_INT_SIZE < 8)
		{
			if ($file_config->allowed_filesize > 2047 || $file_config->allowed_attach_size > 2047)
			{
				throw new Rhymix\Framework\Exception('msg_32bit_max_2047mb');
			}
		}
		
		// Simplify allowed_filetypes
		$file_config->allowed_extensions = strtr(strtolower(trim($file_config->allowed_filetypes)), array('*.' => '', ';' => ','));
		if ($file_config->allowed_extensions)
		{
			$file_config->allowed_extensions = array_map('trim', explode(',', $file_config->allowed_filetypes));
			$file_config->allowed_filetypes = implode(';', array_map(function($ext) {
				return '*.' . $ext;
			}, $file_config->allowed_extensions));
		}
		else
		{
			$file_config->allowed_extensions = array();
			$file_config->allowed_filetypes = '*.*';
		}
		
		// Use default config
		if(Context::get('use_default_file_config') === 'Y')
		{
			$file_config = new stdClass;
			$file_config->use_default_file_config = true;
		}
		
		// Check download grant
		$download_grant = Context::get('download_grant');
		if(!is_array($download_grant))
		{
			$file_config->download_grant = explode('|@|',$download_grant);
		}
		else
		{
			$file_config->download_grant = array_values($download_grant);
		}
		$oModuleController = getController('module');
		for($i=0;$i<count($module_srl);$i++)
		{
			$srl = trim($module_srl[$i]);
			if(!$srl) continue;
			$oModuleController->insertModulePartConfig('file',$srl,$file_config);
		}

		$this->setError(-1);
		$this->setMessage('success_updated', 'info');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispBoardAdminContent');
		$this->setRedirectUrl($returnUrl);
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
