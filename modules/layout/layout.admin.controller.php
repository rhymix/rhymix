<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  layoutAdminController
 * @author NAVER (developers@xpressengine.com)
 * admin controller class of the layout module
 */
class LayoutAdminController extends Layout
{
	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{
	}

	/**
	 * Create a new layout
	 * Insert a title into "layouts" table in order to create a layout
	 * @deprecated
	 * @return void|Object (void : success, Object : fail)
	 */
	function procLayoutAdminInsert()
	{
		if(Context::get('layout') == 'faceoff') throw new Rhymix\Framework\Exception('not supported');

		// Get information to create a layout
		$site_module_info = Context::get('site_module_info');
		$args = new stdClass();
		$args->site_srl = (int)$site_module_info->site_srl;
		$args->layout_srl = getNextSequence();
		$args->layout = Context::get('layout');
		$args->title = Context::get('title');
		$args->layout_type = Context::get('_layout_type');
		if(!$args->layout_type) $args->layout_type = "P";

		// Insert into the DB
		$output = $this->insertLayout($args);
		if(!$output->toBool()) return $output;

		// initiate if it is faceoff layout
		// $this->initLayout($args->layout_srl, $args->layout);

		// update layout info
		Context::set('layout_srl', $args->layout_srl);
		$output = $this->procLayoutAdminUpdate();
		if (!$output->toBool()) return $output;

		return $this->setRedirectUrl(Context::get('success_return_url'), $output);
	}

	/**
	 * Insert layout information into the DB
	 * @param object $args layout information
	 * @return Object
	 */
	function insertLayout($args)
	{
		$output = executeQuery("layout.insertLayout", $args);
		return $output;
	}

	/**
	 * Initiate if it is faceoff layout
	 * @param int $layout_srl
	 * @param string $layout_name
	 * @return void
	 */
	function initLayout($layout_srl, $layout_name)
	{
		// Import a sample layout if it is faceoff
		$oLayoutModel = getModel('layout');
		if($oLayoutModel->useDefaultLayout($layout_name))
		{
			throw new Rhymix\Framework\Exceptions\FeatureDisabled();
		}
		else
		{
			FileHandler::removeDir($oLayoutModel->getUserLayoutPath($layout_srl));
		}
	}

	/**
	 * Update layout information
	 * Apply a title of the new layout and extra vars
	 * @return Object
	 */
	function procLayoutAdminUpdate()
	{
		// Consider the rest of items as extra vars, except module, act, layout_srl, layout, and title  .. Some gurida ..
		$extra_vars = Context::getRequestVars();
		unset($extra_vars->module);
		unset($extra_vars->module);
		unset($extra_vars->act);
		unset($extra_vars->layout_srl);
		unset($extra_vars->layout);
		unset($extra_vars->title);
		unset($extra_vars->apply_layout);
		unset($extra_vars->apply_mobile_view);
		unset($extra_vars->success_return_url);
		unset($extra_vars->error_return_url);
		unset($extra_vars->xe_validator_id);
		unset($extra_vars->ruleset);

		$is_sitemap = $extra_vars->is_sitemap;
		unset($extra_vars->is_sitemap);

		$args = Context::gets('layout_srl','title');
		// Get layout information
		$oLayoutModel = getModel('layout');
		$oMenuAdminModel = getAdminModel('menu');
		$layout_info = $oLayoutModel->getLayout($args->layout_srl);

		if($layout_info->menu)
		{
			$menus = get_object_vars($layout_info->menu);
			foreach($menus ?: [] as $menu_id => $val)
			{
				$menu_srl = Context::get($menu_id);
				if(!$menu_srl) continue;

				// if menu is -1, get default menu in site
				if($menu_srl == -1)
				{
					$oModuleModel = getModel('module');
					$start_module = $oModuleModel->getSiteInfo(0, $columnList);
					$tmpArgs = new stdClass;
					$tmpArgs->url = $start_module->mid;
					$tmpArgs->site_srl = 0;
					$output = executeQuery('menu.getMenuItemByUrl', $tmpArgs);
					if(!$output->toBool())
					{
						throw new Rhymix\Framework\Exception('fail_to_update');
					}

					$menu_srl = $output->data->menu_srl;
				}

				$output = $oMenuAdminModel->getMenu($menu_srl);

				$menu_srl_list[] = $menu_srl;
				$menu_name_list[$menu_srl] = $output->title;
			}
		}

		$tmpDir = sprintf('./files/attach/images/%d/tmp', $args->layout_srl);
		// Separately handle if a type of extra_vars is an image
		if($layout_info->extra_var)
		{
			foreach($layout_info->extra_var as $name => $vars)
			{
				if($vars->type!='image') continue;

				$fileName = $extra_vars->{$name};
				if($vars->value == $fileName)
				{
					continue;
				}

				FileHandler::removeFile($vars->value);

				if(!$fileName)
				{
					continue;
				}

				$pathInfo = pathinfo($fileName);
				$tmpFileName = sprintf('%s/tmp/%s', $pathInfo['dirname'], $pathInfo['basename']);

				if(!FileHandler::moveFile($tmpFileName, $fileName))
				{
					unset($extra_vars->{$name});
				}
			}
		}

		// Save header script into "config" of layout module
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');
		$layout_config = new stdClass();
		$layout_config->header_script = Context::get('header_script') ?? '';
		$layout_config->header_script = Rhymix\Modules\Admin\Models\Utility::cleanHeaderAndFooterScripts($layout_config->header_script);
		$oModuleController->insertModulePartConfig('layout',$args->layout_srl,$layout_config);
		// Save a title of the menu
		$extra_vars->menu_name_list = $menu_name_list;
		// Variable setting for DB insert
		$args->extra_vars = serialize($extra_vars);

		$output = $this->updateLayout($args);
		if(!$output->toBool())
		{
			return $output;
		}

		FileHandler::removeDir($tmpDir);

		if(!$is_sitemap)
		{
			return $this->setRedirectUrl(Context::get('error_return_url'), $output);
		}
		else
		{
			$context = Context::getInstance();
			$context->setRequestMethod('JSON');
			$this->setMessage('success');
		}
	}

	/**
	 * Update layout information into the DB
	 * @param object $args
	 * @return Object
	 */
	function updateLayout($args) {
		$output = executeQuery('layout.updateLayout', $args);
		if($output->toBool())
		{
			$oLayoutModel = getModel('layout');
			$cache_file = $oLayoutModel->getUserLayoutCache($args->layout_srl, Context::getLangType());
			FileHandler::removeFile($cache_file);
			Rhymix\Framework\Cache::delete('layout:' . $args->layout_srl);
		}

		return $output;
	}

	/**
	 * Delete Layout
	 * Delete xml cache file too when deleting a layout
	 * @return Object
	 */
	function procLayoutAdminDelete()
	{
		$layout_srl = Context::get('layout_srl');
		$this->setRedirectUrl(Context::get('error_return_url'));
		return $this->deleteLayout($layout_srl);
	}

	/**
	 * Delete layout xml cache file
	 * @param int $layout_srl
	 * @return Object
	 */
	function deleteLayout($layout_srl, $force = FALSE)
	{
		$oLayoutModel = getModel('layout');

		if($force)
		{
			$layoutInfo = $oLayoutModel->getLayout($layout_srl);
			if($layoutInfo)
			{
				$layoutList = $oLayoutModel->getLayoutInstanceList($layoutInfo->site_srl, $layoutInfo->layout_type, $layoutInfo->layout, array('layout_srl', 'layout'));
				if(count($layoutList) <= 1)
				{
					// Autoinstall integration has been removed.
				}
			}
		}

		$path = $oLayoutModel->getUserLayoutPath($layout_srl);
		FileHandler::removeDir($path);

		$layout_file = $oLayoutModel->getUserLayoutHtml($layout_srl);
		FileHandler::removeFile($layout_file);

		// Delete Layout
		$args = new stdClass();
		$args->layout_srl = $layout_srl;
		$output = executeQuery("layout.deleteLayout", $args);

		Rhymix\Framework\Cache::delete('layout:' . $args->layout_srl);

		if(!$output->toBool()) return $output;

		return new BaseObject(0,'success_deleted');
	}

	/**
	 * Adding Layout Code
	 * @return void|Object (void : success, Object : fail)
	 */
	function procLayoutAdminCodeUpdate()
	{
		$mode = Context::get('mode');
		if ($mode == 'reset')
		{
			return $this->procLayoutAdminCodeReset();
		}

		$layout_srl = Context::get('layout_srl');
		$code = Context::get('code');
		$code_css = Context::get('code_css');
		if (!$layout_srl || !$code || !\RX_POST)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		$layout_info = LayoutModel::getLayout($layout_srl);
		if (!$layout_info)
		{
			throw new Rhymix\Framework\Exceptions\TargetNotFound;
		}
		if (!$layout_info->is_edited)
		{
			return new BaseObject(-1, 'layout.layout_editing_deprecated_p1');
		}

		$oLayoutModel = getModel('layout');
		$layout_file = $oLayoutModel->getUserLayoutHtml($layout_srl);
		FileHandler::writeFile($layout_file, $code);

		$layout_css_file = $oLayoutModel->getUserLayoutCss($layout_srl);
		FileHandler::writeFile($layout_css_file, $code_css);

		$this->setRedirectUrl(Context::get('error_return_url'));
		$this->setMessage('success_updated');
	}

	/**
	 * Reset layout code
	 * @return void|Object (void : success, Object : fail)
	 */
	function procLayoutAdminCodeReset()
	{
		$layout_srl = Context::get('layout_srl');
		if(!$layout_srl) throw new Rhymix\Framework\Exceptions\InvalidRequest;

		// delete user layout file
		$oLayoutModel = getModel('layout');
		$layout_file = $oLayoutModel->getUserLayoutHtml($layout_srl);
		FileHandler::removeFile($layout_file);

		$info = $oLayoutModel->getLayout($layout_srl);

		// if face off delete, tmp file
		if($oLayoutModel->useDefaultLayout($info->layout))
		{
			$this->deleteUserLayoutTempFile($layout_srl);
			$faceoff_css = $oLayoutModel->getUserLayoutFaceOffCss($layout_srl);
			FileHandler::removeFile($faceoff_css);
		}

		$this->initLayout($layout_srl, $info->layout);
		$this->setMessage('success_reset');
		$this->setRedirectUrl(getNotEncodedUrl([
			'module' => 'admin',
			'act' => 'dispLayoutAdminAllInstanceList',
			'type' => starts_with('./m.layouts/', $info->path) ? 'M' : '',
		]));
	}

	/**
	 * Layout setting page -> Upload an image
	 * @return void
	 */
	function procLayoutAdminUserImageUpload()
	{
		if(!Context::isUploaded()) exit();

		$image = Context::get('user_layout_image');
		$layout_srl = Context::get('layout_srl');
		if(!is_uploaded_file($image['tmp_name'])) exit();

		$this->insertUserLayoutImage($layout_srl, $image);
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile("top_refresh.html");

		$this->setRedirectUrl(Context::get('error_return_url'));
	}

	/**
	 * insert image into user layout
	 * @param int $layout_srl
	 * @param object $source file data
	 * @return boolean (true : success, false : fail)
	 */
	function insertUserLayoutImage($layout_srl,$source)
	{
		$oLayoutModel = getModel('layout');
		$path = $oLayoutModel->getUserLayoutImagePath($layout_srl);
		if(!is_dir($path)) FileHandler::makeDir($path);

		$filename = strtolower($source['name']);
		$filename = Rhymix\Framework\Filters\FilenameFilter::clean($filename);
		if($filename != urlencode($filename))
		{
			$ext = substr(strrchr($filename,'.'),1);
			$filename = sprintf('%s.%s', md5($filename), $ext);
		}

		if(file_exists($path .'/'. $filename)) @unlink($path . $filename);
		if(!move_uploaded_file($source['tmp_name'], $path . $filename )) return false;
		return true;
	}

	/**
	 * Layout setting page -> Delete an image
	 * @return void
	 */
	function procLayoutAdminUserImageDelete()
	{
		$layout_srl = Context::get('layout_srl');
		if (!$layout_srl)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest();
		}

		$filename = Context::get('filename');
		if (preg_match('!(\.\.|[/\\\\])!', $filename))
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest();
		}

		$this->removeUserLayoutImage($layout_srl, $filename);
		$this->setMessage('success_deleted');
		$this->setRedirectUrl(Context::get('error_return_url'));
	}

	/**
	 * delete image into user layout
	 * @param int $layout_srl
	 * @param string $filename
	 * @return bool
	 */
	function removeUserLayoutImage($layout_srl,$filename)
	{
		$oLayoutModel = getModel('layout');
		$path = $oLayoutModel->getUserLayoutImagePath($layout_srl);
		$path = FileHandler::getRealPath($path . Rhymix\Framework\Filters\FilenameFilter::clean($filename));
		if (!Rhymix\Framework\Storage::exists($path))
		{
			throw new Rhymix\Framework\Exceptions\TargetNotFound();
		}

		return Rhymix\Framework\Storage::delete($path);
	}

	// deprecated
	/**
	 * Save layout configuration
	 * save in "ini" format for faceoff
	 * @deprecated
	 * @return void|Object (void : success, Object : fail)
	 */
	function procLayoutAdminUserValueInsert()
	{
		$oModuleModel = getModel('module');

		$mid = Context::get('mid');
		if(!$mid) throw new Rhymix\Framework\Exceptions\InvalidRequest;

		$site_module_info = Context::get('site_module_info');
		$columnList = array('layout_srl');
		$module_info = $oModuleModel->getModuleInfoByMid($mid, $site_module_info->site_srl, $columnList);
		$layout_srl = $module_info->layout_srl;
		if(!$layout_srl) throw new Rhymix\Framework\Exceptions\InvalidRequest;

		$oLayoutModel = getModel('layout');

		// save tmp?
		$temp = Context::get('saveTemp');
		if($temp =='Y')
		{
			$oLayoutModel->setUseUserLayoutTemp();
		}
		else
		{
			// delete temp files
			$this->deleteUserLayoutTempFile($layout_srl);
		}

		$this->add('saveTemp',$temp);

		// write user layout
		$extension_obj = Context::gets('e1','e2','neck','knee');

		$file = $oLayoutModel->getUserLayoutHtml($layout_srl);
		$content = FileHandler::readFile($file);
		$content = $this->addExtension($layout_srl,$extension_obj,$content);
		FileHandler::writeFile($file,$content);

		// write faceoff.css
		$css = stripslashes(Context::get('css'));

		$css_file = $oLayoutModel->getUserLayoutFaceOffCss($layout_srl);
		FileHandler::writeFile($css_file,$css);

		// write ini
		$obj = Context::gets('type','align','column');
		$obj = (array)$obj;
		$src = $oLayoutModel->getUserLayoutIniConfig($layout_srl);
		foreach($obj as $key => $val) $src[$key] = $val;
		$this->insertUserLayoutValue($layout_srl,$src);
	}

	/**
	 * Layout setting, save "ini"
	 * @param int $layout_srl
	 * @param object $arr layout ini
	 * @return void
	 */
	function insertUserLayoutValue($layout_srl,$arr)
	{
		$oLayoutModel = getModel('layout');
		$file = $oLayoutModel->getUserLayoutIni($layout_srl);
		FileHandler::writeIniFile($file, $arr);
	}

	/**
	 * Add the widget code for faceoff into user layout file
	 * @param int $layout_srl
	 * @param object $arg
	 * @param string $content
	 * @return string
	 */
	function addExtension($layout_srl,$arg,$content)
	{
		$oLayoutModel = getModel('layout');
		$reg = '/(<\!\-\- start\-e1 \-\->)(.*)(<\!\-\- end\-e1 \-\->)/i';
		$extension_content =  '\1' .stripslashes($arg->e1) . '\3';
		$content = preg_replace($reg,$extension_content,$content);

		$reg = '/(<\!\-\- start\-e2 \-\->)(.*)(<\!\-\- end\-e2 \-\->)/i';
		$extension_content =  '\1' .stripslashes($arg->e2) . '\3';
		$content = preg_replace($reg,$extension_content,$content);

		$reg = '/(<\!\-\- start\-neck \-\->)(.*)(<\!\-\- end\-neck \-\->)/i';
		$extension_content =  '\1' .stripslashes($arg->neck) . '\3';
		$content = preg_replace($reg,$extension_content,$content);

		$reg = '/(<\!\-\- start\-knee \-\->)(.*)(<\!\-\- end\-knee \-\->)/i';
		$extension_content =  '\1' .stripslashes($arg->knee) . '\3';
		$content = preg_replace($reg,$extension_content,$content);
		return $content;
	}

	/**
	 * Delete temp files for faceoff
	 * @param int $layout_srl
	 * @return void
	 */
	function deleteUserLayoutTempFile($layout_srl)
	{
		$oLayoutModel = getModel('layout');
		$file_list = $oLayoutModel->getUserLayoutTempFileList($layout_srl);
		foreach($file_list as $key => $file)
		{
			FileHandler::removeFile($file);
		}
	}

	/**
	 * export user layout
	 * @return void
	 */
	function procLayoutAdminUserLayoutExport()
	{
		throw new Rhymix\Framework\Exceptions\FeatureDisabled();
	}

	/**
	 * faceoff import
	 * @deprecated
	 * @return void
	 */
	function procLayoutAdminUserLayoutImport()
	{
		throw new Rhymix\Framework\Exceptions\FeatureDisabled();
	}

	/**
	 * layout copy
	 * @return void
	 */
	function procLayoutAdminCopyLayout()
	{
		$sourceArgs = Context::getRequestVars();
		if($sourceArgs->layout == 'faceoff')
		{
			throw new Rhymix\Framework\Exception('not supported');
		}

		if(!$sourceArgs->layout_srl)
		{
			throw new Rhymix\Framework\Exception('msg_empty_origin_layout');
		}

		$oLayoutModel = getModel('layout');
		$layout = $oLayoutModel->getLayout($sourceArgs->layout_srl);

		if(!$sourceArgs->title)
		{
			$sourceArgs->title = array($layout->title.'_'.$this->_makeRandomMid());
		}

		if(!is_array($sourceArgs->title) || count($sourceArgs->title) == 0)
		{
			throw new Rhymix\Framework\Exception('msg_empty_target_layout');
		}

		$output = $oLayoutModel->getLayoutRawData($sourceArgs->layout_srl, array('extra_vars'));
		$args = new stdClass();
		$args->extra_vars = $output->extra_vars;
		$extra_vars = unserialize($args->extra_vars);
		$image_list = array();

		if($layout->extra_var_count && $extra_vars)
		{
			$reg = "/^.\/files\/attach\/images\/([0-9]+)\/(.*)/";
			foreach($extra_vars as $key => $val)
			{
				if($layout->extra_var->{$key}->type == 'image')
				{
					if(!preg_match($reg, $val, $matches))
					{
						continue;
					}
					if(!isset($image_list[$key]))
					{
						$image_list[$key] = new stdClass;
					}
					$image_list[$key]->filename = $matches[2];
					$image_list[$key]->old_file = $val;
				}
			}
		}

		$oModuleController = getController('module');
		$layout_config = new stdClass();
		$layout_config->header_script = $extra_vars->header_script;

		// Get information to create a layout
		$args->site_srl = (int)$layout->site_srl;
		$args->layout = $layout->layout;
		$args->layout_type = $layout->layout_type;
		if(!$args->layout_type) $args->layout_type = "P";

		$oDB = DB::getInstance();
		$oDB->begin();

		if(is_array($sourceArgs->title))
		{
			foreach($sourceArgs->title AS $key=>$value)
			{
				if(!trim($value))
				{
					continue;
				}

				$args->layout_srl = getNextSequence();
				$args->title = $value;

				if($image_list)
				{
					foreach($image_list as $key => $val)
					{
						$new_file = sprintf("./files/attach/images/%d/%s", $args->layout_srl, $val->filename);
						FileHandler::copyFile($val->old_file, $new_file);
						$extra_vars->{$key} = $new_file;
					}
					$args->extra_vars = serialize($extra_vars);
				}

				// for header script
				$oModuleController->insertModulePartConfig('layout', $args->layout_srl, $layout_config);

				// Insert into the DB
				$output = $this->insertLayout($args);
				if(!$output->toBool())
				{
					$oDB->rollback();
					return $output;
				}

				// initiate if it is faceoff layout
				// $this->initLayout($args->layout_srl, $args->layout);

				// update layout info
				$output = $this->updateLayout($args);
				if (!$output->toBool())
				{
					$oDB->rollback();
					return $output;
				}

				$this->_copyLayoutFile($layout->layout_srl, $args->layout_srl);
			}
		}
		$oDB->commit();

		$this->setMessage('success_registed');
		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
			global $lang;
			htmlHeader();
			alertScript($lang->success_registed);
			reload(true);
			closePopupScript();
			htmlFooter();
			Context::close();
			exit;
		}
	}

	private function _makeRandomMid()
	{
		$time = $_SERVER['REQUEST_TIME'];
		$randomString = "";
		for($i=0;$i<4;$i++)
		{
			$doc = rand()%26+65;
			$randomString .= chr($doc);
		}

		return $randomString.substr($time, -4);
	}

	/**
	 * Layout file copy
	 * @param $sourceLayoutSrl origin layout number
	 * @param $targetLayoutSrl origin layout number
	 * @return void
	 */
	function _copyLayoutFile($sourceLayoutSrl, $targetLayoutSrl)
	{
		$oLayoutModel = getModel('layout');
		$sourceLayoutPath = FileHandler::getRealPath($oLayoutModel->getUserLayoutPath($sourceLayoutSrl));
		$targetLayoutPath = FileHandler::getRealPath($oLayoutModel->getUserLayoutPath($targetLayoutSrl));

		$sourceImagePath = $oLayoutModel->getUserLayoutImagePath($sourceLayoutSrl);
		$targetImagePath = $oLayoutModel->getUserLayoutImagePath($targetLayoutSrl);
		FileHandler::makeDir($targetImagePath);

		$sourceFileList = $oLayoutModel->getUserLayoutFileList($sourceLayoutSrl);
		foreach($sourceFileList as $key => $file)
		{
			if(is_readable($sourceLayoutPath.$file))
			{
				FileHandler::copyFile($sourceLayoutPath.$file, $targetLayoutPath.$file);
				if($file == 'layout.html' || $file == 'layout.css')
				{
					$this->_changeFilepathInSource($targetLayoutPath.$file, $sourceImagePath, $targetImagePath);
				}
			}
		}
	}

	/**
	 * Change resource file path in Layout file
	 * @param string $file
	 * @return void
	 */
	function _changeFilepathInSource($file, $source, $target)
	{
		$content = FileHandler::readFile($file);
		$content = str_replace($source, $target, $content);
		FileHandler::writeFile($file, $content);
	}

	/**
	 * import layout
	 * @param int $layout_srl
	 * @param string $source_file path of imported file
	 * @return void
	 */
	function importLayout($layout_srl, $source_file)
	{
		throw new Rhymix\Framework\Exceptions\FeatureDisabled();
	}

	/**
	 * Upload config image
	 */
	function procLayoutAdminConfigImageUpload()
	{
		$layoutSrl = Context::get('layout_srl');
		$name = Context::get('name');
		$img = Context::get('img');

		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile("after_upload_config_image.html");

		if(!$img['tmp_name'] || !is_uploaded_file($img['tmp_name']))
		{
			Context::set('msg', lang('upload failed'));
			return;
		}

		if(!preg_match('/\.(gif|jpe?g|png|svg|webp)$/i', $img['name']))
		{
			Context::set('msg', lang('msg_layout_image_target'));
			return;
		}

		$path = sprintf('./files/attach/images/%s/', $layoutSrl);
		$tmpPath = $path . 'tmp/';
		if(!FileHandler::makeDir($tmpPath))
		{
			Context::set('msg', lang('make directory failed'));
			return;
		}

		$ext = substr(strrchr($img['name'],'.'),1);
		$_fileName = md5(crypt(rand(1000000,900000), rand(0,100))).'.'.$ext;
		$fileName = $path . $_fileName;
		$tmpFileName = $tmpPath . $_fileName;

		if(!move_uploaded_file($img['tmp_name'], $tmpFileName))
		{
			Context::set('msg', lang('move file failed'));
			return;
		}

		Context::set('name', $name);
		Context::set('fileName', $fileName);
		Context::set('tmpFileName', $tmpFileName);
	}

	/**
	 * Delete config image
	 */
	function procLayoutAdminConfigImageDelete()
	{
		$layoutSrl = Context::get('layout_srl');
		$name = Context::get('name');

		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile("after_delete_config_image.html");

		$oModel = getModel('layout');
		$layoutInfo = $oModel->getLayout($layoutSrl);

		$newLayoutInfo = new stdClass;
		if($layoutInfo->extra_var_count)
		{
			foreach($layoutInfo->extra_var as $varId => $val)
			{
				$newLayoutInfo->{$varId} = $val->value;
			}
		}

		unset($newLayoutInfo->{$name});
		$args = new stdClass();
		$args->layout_srl = $layoutSrl;
		$args->extra_vars = serialize($newLayoutInfo);
		$output = $this->updateLayout($args);
		if(!$output->toBool())
		{
			Context::set('msg', lang($output->getMessage()));
			return $output;
		}

		FileHandler::removeFile($layoutInfo->extra_var->{$name}->value);
		Context::set('name', $name);
	}
}
/* End of file layout.admin.controller.php */
/* Location: ./modules/layout/layout.admin.controller.php */
