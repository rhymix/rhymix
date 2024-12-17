<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * admin controller class of the file module
 * @author NAVER (developers@xpressengine.com)
 */
class FileAdminController extends File
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

		$redirect_url = $_SERVER['HTTP_REFERER'] ?? '';
		if (!$redirect_url || !Rhymix\Framework\URL::isInternalURL($redirect_url))
		{
			$redirect_url = Context::get('success_return_url') ?: getNotEncodedUrl('', 'module', 'admin', 'act', 'dispFileAdminList');
		}
		$this->setRedirectUrl($redirect_url);
	}

	/**
	 * Save upload configuration
	 *
	 * @return Object
	 */
	function procFileAdminInsertUploadConfig()
	{
		// Default settings
		$config = getModel('module')->getModuleConfig('file') ?: new stdClass;
		$config->allowed_filesize = Context::get('allowed_filesize');
		$config->allowed_attach_size = Context::get('allowed_attach_size');
		$config->allowed_filetypes = Context::get('allowed_filetypes');

		// Image settings
		$config->image_autoconv = [];
		foreach (Context::get('image_autoconv') ?: [] as $source_type => $target_type)
		{
			if (in_array($target_type, ['Y', 'N']))
			{
				$config->image_autoconv[$source_type] = tobool($target_type);
			}
			elseif (in_array($target_type, ['', 'jpg', 'png', 'webp']))
			{
				$config->image_autoconv[$source_type] = $target_type;
			}
		}
		$config->max_image_width = intval(Context::get('max_image_width')) ?: '';
		$config->max_image_height = intval(Context::get('max_image_height')) ?: '';
		$config->max_image_size_action = Context::get('max_image_size_action') ?: '';
		$config->max_image_size_same_format = strval(Context::get('max_image_size_same_format'));
		$config->max_image_size_admin = Context::get('max_image_size_admin') === 'Y' ? 'Y' : 'N';
		$config->image_quality_adjustment = max(50, min(100, intval(Context::get('image_quality_adjustment'))));
		$config->image_autorotate = Context::get('image_autorotate') === 'Y' ? true : false;
		$config->image_remove_exif_data = Context::get('image_remove_exif_data') === 'Y' ? true : false;

		// Video settings
		$config->max_video_width = intval(Context::get('max_video_width')) ?: '';
		$config->max_video_height = intval(Context::get('max_video_height')) ?: '';
		$config->max_video_size_action = Context::get('max_video_size_action') ?: '';
		$config->max_video_size_admin = Context::get('max_video_size_admin') === 'Y' ? 'Y' : 'N';
		$config->max_video_duration = intval(Context::get('max_video_duration')) ?: '';
		$config->max_video_duration_action = Context::get('max_video_duration_action') ?: '';
		$config->max_video_duration_admin = Context::get('max_video_duration_admin') === 'Y' ? 'Y' : 'N';
		$config->video_autoconv['any2mp4'] = Context::get('video_autoconv_any2mp4') === 'Y' ? true : false;
		$config->video_always_reencode = Context::get('video_always_reencode') === 'Y' ? true : false;
		$config->video_thumbnail = Context::get('video_thumbnail') === 'Y' ? true : false;
		$config->video_mp4_gif_time = intval(Context::get('video_mp4_gif_time'));

		// Path to ffmpeg, ffprobe, magick
		if (RX_WINDOWS)
		{
			$config->ffmpeg_command = escape(Context::get('ffmpeg_command')) ?: 'C:\Program Files\ffmpeg\bin\ffmpeg.exe';
			$config->ffprobe_command = escape(Context::get('ffprobe_command')) ?: 'C:\Program Files\ffmpeg\bin\ffprobe.exe';
			$config->magick_command = escape(Context::get('magick_command')) ?: '';
		}
		else
		{
			$config->ffmpeg_command = escape(utf8_trim(Context::get('ffmpeg_command'))) ?: '/usr/bin/ffmpeg';
			$config->ffprobe_command = escape(utf8_trim(Context::get('ffprobe_command'))) ?: '/usr/bin/ffprobe';
			$config->magick_command = escape(utf8_trim(Context::get('magick_command'))) ?: '';
		}

		// Check maximum file size (probably not necessary anymore)
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
			$config->allowed_extensions = array_filter(array_map('trim', explode(',', $config->allowed_extensions)), function($str) {
				return $str !== '*';
			});
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
		$config->allow_indexing_format = Context::get('allow_indexing_format');
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
				$config->allowed_extensions = array_filter(array_map('trim', explode(',', $config->allowed_extensions)), function($str) {
					return $str !== '*';
				});
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
			$config->max_image_size_same_format = Context::get('max_image_size_same_format') === 'Y' ? 'Y' : 'N';
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

	/**
	 * Edit filename
	 */
	public function procFileAdminEditFileName()
	{
		$file_srl = Context::get('file_srl');
		if (!$file_srl)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		$file = FileModel::getFile($file_srl);
		if (!$file)
		{
			throw new Rhymix\Framework\Exceptions\TargetNotFound;
		}
		$file_name = trim(utf8_normalize_spaces(utf8_clean(Context::get('file_name'))));
		$file_name = Rhymix\Framework\Filters\FilenameFilter::clean($file_name);
		if ($file_name === '')
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		$output = executeQuery('file.updateFileName', [
			'file_srl' => $file_srl,
			'source_filename' => $file_name,
		]);
		if (!$output->toBool())
		{
			return $output;
		}

		$this->setMessage('success_updated');
		$this->setRedirectUrl(Context::get('success_return_url') ?: getNotEncodedUrl(['module' => 'admin', 'act' => 'dispFileAdminEdit', 'file_srl' => $file_srl]));
	}

	/**
	 * Edit image
	 */
	public function procFileAdminEditImage()
	{
		$file_srl = Context::get('file_srl');
		if (!$file_srl)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		$file = FileModel::getFile($file_srl);
		if (!$file)
		{
			throw new Rhymix\Framework\Exceptions\TargetNotFound;
		}

		// Validate user input.
		$width = intval(Context::get('new_width'));
		$height = intval(Context::get('new_height'));
		$format = Context::get('format');
		$quality = intval(Context::get('quality'));
		if ($width <= 0 || $height <= 0 || !in_array($format, ['jpg', 'png', 'webp']) || $quality <= 0 || $quality > 100)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		// Generate filenames.
		$uploaded_filename = FileHandler::getRealPath($file->uploaded_filename);
		$temp_filename = \RX_BASEDIR . 'files/cache/temp/' . Rhymix\Framework\Security::getRandom(32, 'hex') . '.' . $format;
		$new_filename = preg_replace('/\.[a-z]+$/', '.' . $format, $file->source_filename);
		$del_filename = null;

		// Should this file be moved from binaries to images?
		if (str_starts_with($uploaded_filename, \RX_BASEDIR . 'files/attach/binaries/'))
		{
			$del_filename = $uploaded_filename;
			$uploaded_filename = preg_replace('!/files/attach/binaries/!', '/files/attach/images/', $uploaded_filename, 1) . '.' . $format;
		}

		// Resize the image using GD or ImageMagick.
		$config = FileModel::getFileConfig();
		$result = FileHandler::createImageFile(FileHandler::getRealPath($file->uploaded_filename), $temp_filename, $width, $height, $format, 'fill', $quality);
		if (!$result && !empty($config->magick_command))
		{
			$command = vsprintf('%s %s -resize %dx%d -quality %d %s %s %s', [
				\RX_WINDOWS ? escapeshellarg($config->magick_command) : $config->magick_command,
				escapeshellarg(FileHandler::getRealPath($file->uploaded_filename)),
				$width, $height, $quality,
				'-auto-orient -strip',
				'-limit memory 64MB -limit map 128MB -limit disk 1GB',
				escapeshellarg($temp_filename),
			]);
			@exec($command, $output, $return_var);
			$result = $return_var === 0 ? true : false;
		}

		// If successfully resized, replace original file and update the image size in DB.
		if ($result && Rhymix\Framework\Storage::exists($temp_filename) && filesize($temp_filename) > 0)
		{
			$moved = Rhymix\Framework\Storage::move($temp_filename, $uploaded_filename);
			if (!$moved)
			{
				throw new Rhymix\Framework\Exception(lang('file.msg_image_conversion_failed'));
			}
			if ($del_filename)
			{
				Rhymix\Framework\Storage::delete($del_filename);
			}

			clearstatcache(true, $uploaded_filename);
			$filesize = filesize($uploaded_filename);
			$relative_path = preg_replace('!^' . preg_quote(\RX_BASEDIR, '!') . '!', './', $uploaded_filename, 1);

			$updated = executeQuery('file.updateFile', [
				'file_srl' => $file_srl,
				'module_srl' => $file->module_srl,
				'upload_target_srl' => $file->upload_target_srl,
				'source_filename' => $new_filename,
				'uploaded_filename' => $relative_path,
				'direct_download' => 'Y',
				'mime_type' => Rhymix\Framework\MIME::getTypeByFilename($new_filename),
				'original_type' => $file->original_type ?: $file->mime_type,
				'is_cover' => $file->cover_image,
				'file_size' => $filesize,
				'width' => $width,
				'height' => $height,
			]);
			if (!$updated->toBool())
			{
				return $updated;
			}

			if (isset($config->save_changelog) && $config->save_changelog === 'Y')
			{
				$changelog1 = executeQuery('file.insertFileChangelog', [
					'change_type' => 'D',
					'file_srl' => $file->file_srl,
					'file_size' => $file->file_size,
					'uploaded_filename' => $file->uploaded_filename,
				]);
				if (!$changelog1->toBool())
				{
					return $changelog1;
				}

				$changelog2 = executeQuery('file.insertFileChangelog', [
					'change_type' => 'I',
					'file_srl' => $file->file_srl,
					'file_size' => $filesize,
					'uploaded_filename' => $relative_path,
				]);
				if (!$changelog2->toBool())
				{
					return $changelog2;
				}
			}
		}
		else
		{
			throw new Rhymix\Framework\Exception(lang('file.msg_image_conversion_failed'));
		}

		$this->setMessage(sprintf(lang('file.msg_image_converted'), FileHandler::filesize($file->file_size), FileHandler::filesize($filesize)));
		$this->setRedirectUrl(Context::get('success_return_url') ?: getNotEncodedUrl(['module' => 'admin', 'act' => 'dispFileAdminEdit', 'file_srl' => $file_srl]));
	}
}
/* End of file file.admin.controller.php */
/* Location: ./modules/file/file.admin.controller.php */
