<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  layoutView
 * @author NAVER (developers@xpressengine.com)
 * admin view class of the layout module
 */
class layoutView extends layout
{
	/**
	 * Initialization
	 * @return void
	 */
	function init()
	{
		$this->setTemplatePath($this->module_path.'tpl');
	}

	/**
	 * Pop-up layout details(conf/info.xml)
	 * @return void
	 */
	function dispLayoutInfo()
	{
		// Get the layout information
		$oLayoutModel = getModel('layout');
		$layout_info = $oLayoutModel->getLayoutInfo(Context::get('selected_layout'));
		if(!$layout_info) exit();
		Context::set('layout_info', $layout_info);
		// Set the layout to be pop-up
		$this->setLayoutFile('popup_layout');
		// Set a template file
		$this->setTemplateFile('layout_detail_info');
	}

	/**
	 * Preview a layout with module.
	 * 
	 * @return Object
	 */
	public function dispLayoutPreviewWithModule()
	{
		$content = '';
		$layoutSrl = Context::get('layout_srl');

		$module = Context::get('module_name');
		$mid = Context::get('target_mid');
		$skin = Context::get('skin');
		$skinType = Context::get('skin_type');

		try
		{
			// admin check
			// this act is admin view but in normal view because do not load admin css/js files
			$logged_info = Context::get('logged_info');
			if($logged_info->is_admin != 'Y')
			{
				throw new Exception(Context::getLang('msg_invalid_request'));
			}

			// if module is 'ARTiCLE' and from site design setting, make content directly
			if($module == 'ARTICLE' && !$mid)
			{
				$oDocumentModel = getModel('document');
				$oDocument = $oDocumentModel->getDocument(0, true);

				$t = Context::getLang('article_preview_title');

				$c = '';
				for($i = 0; $i < 4; $i++)
				{
					$c .= '<p>';
					for($j = 0; $j < 20; $j++)
					{
						$c .= Context::getLang('article_preview_content') . ' ';
					}
					$c .= '</p>';
				}

				$attr = new stdClass();
				$attr->title = $t;
				$attr->content = $c;
				$attr->document_srl = -1;
				$oDocument->setAttribute($attr, FALSE);

				Context::set('oDocument', $oDocument);

				if ($skinType == 'M')
				{
					$templatePath = _XE_PATH_ . 'modules/page/m.skins/' . $skin;
					$templateFile = 'mobile';
				}
				else
				{
					$templatePath = _XE_PATH_ . 'modules/page/skins/' . $skin;
					$templateFile = 'content';
				}

				$oTemplate = TemplateHandler::getInstance();
				$content = $oTemplate->compile($templatePath, $templateFile);
			}

			// else use real module
			else
			{
				$content = $this->procRealModule($module, $mid, $skin, $skinType);
			}
			Context::set('content', $content);

			// find layout
			if($layoutSrl)
			{
				if($layoutSrl == -1)
				{
					$site_srl = ($oModule) ? $oModule->module_info->site_srl : 0;
					$designInfoFile = sprintf(_XE_PATH_ . 'files/site_design/design_%s.php', $site_srl);
					include($designInfoFile);

					if($skinType == 'M')
					{
						$layoutSrl = $designInfo->mlayout_srl;
					}
					else
					{
						$layoutSrl = $designInfo->layout_srl;
					}
				}

				$oLayoutModel = getModel('layout');
				$layoutInfo = $oLayoutModel->getLayout($layoutSrl);

				// If there is no layout, pass it.
				if($layoutInfo)
				{
					// Adhoc...

					// Input extra_vars into $layout_info
					if($layoutInfo->extra_var_count)
					{

						foreach($layoutInfo->extra_var as $var_id => $val)
						{
							if($val->type == 'image')
							{
								if(strncmp('./files/attach/images/', $val->value, 22) === 0)
								{
									$val->value = Context::getRequestUri() . substr($val->value, 2);
								}
							}
							$layoutInfo->{$var_id} = $val->value;
						}
					}

					// Set menus into context
					if($layoutInfo->menu_count)
					{
						foreach($layoutInfo->menu as $menu_id => $menu)
						{
							// set default menu set(included home menu)
							if(!$menu->menu_srl || $menu->menu_srl == -1)
							{
								$oMenuAdminController = getAdminController('menu');
								$homeMenuCacheFile = $oMenuAdminController->getHomeMenuCacheFile();

								if(file_exists($homeMenuCacheFile))
								{
									include($homeMenuCacheFile);
								}

								if(!$menu->menu_srl)
								{
									$menu->xml_file = str_replace('.xml.php', $homeMenuSrl . '.xml.php', $menu->xml_file);
									$menu->php_file = str_replace('.php', $homeMenuSrl . '.php', $menu->php_file);
									$layoutInfo->menu->{$menu_id}->menu_srl = $homeMenuSrl;
								}
								else
								{
									$menu->xml_file = str_replace($menu->menu_srl, $homeMenuSrl, $menu->xml_file);
									$menu->php_file = str_replace($menu->menu_srl, $homeMenuSrl, $menu->php_file);
								}
							}

							$menu->php_file = FileHandler::getRealPath($menu->php_file);
							if(FileHandler::exists($menu->php_file))
							{
								include($menu->php_file);
							}
							Context::set($menu_id, $menu);
						}
					}

					Context::set('layout_info', $layoutInfo);
				}
			}
		}
		catch(Exception $e)
		{
			$content = '<div class="message error"><p id="preview_error">' . $e->getMessage() . '</p></div>';
			Context::set('content', $content);
			$layoutSrl = 0;
		}

		// Compile
		$oTemplate = TemplateHandler::getInstance();
		Context::clearHtmlHeader();

		if($layoutInfo)
		{
			$layout_path = $layoutInfo->path;
			$editLayoutTPL = $this->getRealLayoutFile($layoutSrl);
			$editLayoutCSS = $this->getRealLayoutCSS($layoutSrl);
			if($editLayoutCSS != '')
			{
				Context::addCSSFile($editLayoutCSS);
			}
			$layout_file = 'layout';
			$oModuleModel = getModel('module');
			$part_config = $oModuleModel->getModulePartConfig('layout', $layoutSrl);
			Context::addHtmlHeader($part_config->header_script);
		}
		else
		{
			$layout_path = './common/tpl';
			$layout_file = 'default_layout';
		}

		$layout_tpl = $oTemplate->compile($layout_path, $layout_file, $editLayoutTPL);
		Context::set('layout','none');

		// Convert widgets and others
		$oContext = Context::getInstance();
		Context::set('layout_tpl', $layout_tpl);
		$this->setTemplatePath($this->module_path.'tpl');
		$this->setTemplateFile('layout_preview');
	}

	/**
	 * Get content of real module
	 *
	 * @param string $module module name
	 * @param string $mid module id
	 * @param string $skin skin name
	 * @param string $skinType PC(P) or mobile(M)
	 * @return string content of real module
	 */
	private function procRealModule($module, $mid, $skin, $skinType)
	{
		// if form site design and preview module, find target module
		if($module && !$mid)
		{
			$args = new stdClass();
			$args->module = $module;
			$output = executeQuery('layout.getOneModuleInstanceByModuleName', $args);
			if(!$output->toBool())
			{
				throw new Exception($output->getMessage());
			}

			// if there is no module instance, error...
			if(!$output->data)
			{
				throw new Exception(Context::getLang('msg_unabled_preview'));
			}
		
			$mid = current($output->data)->mid;
		}

		// if form site design and preview layout, find start module
		elseif(!$module && !$mid)
		{
			$oModuleModel = getModel('module');
			$columnList = array('modules.mid', 'sites.index_module_srl');
			$startModuleInfo = $oModuleModel->getSiteInfo(0, $columnList);
			$mid = $startModuleInfo->mid;
		}

		$oModuleHandler = new ModuleHandler('', '', $mid, '', '');

		// Adhoc...
		$oModuleHandler->act = '';

		$oModuleHandler->init();

		// Adhoc...
		$oModuleHandler->module_info->use_mobile = 'Y';
		$oModuleHandler->module_info->is_skin_fix = 'Y';
		$oModuleHandler->module_info->is_mskin_fix = 'Y';

		if($skinType == 'M')
		{
			Mobile::setMobile(TRUE);
			$oModuleHandler->module_info->mskin = $skin;
		}
		else
		{
			Mobile::setMobile(FALSE);
			$oModuleHandler->module_info->skin = $skin;
		}

		// Proc module
		$oModule = $oModuleHandler->procModule();
		if(!$oModule->toBool())
		{
			throw new Exception(Context::getLang('not_support_layout_preview'));
		}

		// get module html
		require_once(_XE_PATH_ . "classes/display/HTMLDisplayHandler.php");
		$handler = new HTMLDisplayHandler();
		return $handler->toDoc($oModule);
	}

	/**
	 * Preview a layout
	 * @return void|Object (void : success, Object : fail)
	 */
	function dispLayoutPreview()
	{
		if(!checkCSRF())
		{
			$this->stop('msg_invalid_request');
			return new Object(-1, 'msg_invalid_request');
		}

		// admin check
		// this act is admin view but in normal view because do not load admin css/js files
		$logged_info = Context::get('logged_info');
		if($logged_info->is_admin != 'Y') return $this->stop('msg_invalid_request');

		$layout_srl = Context::get('layout_srl');
		$code = Context::get('code');

		$code_css = Context::get('code_css');
		if(!$layout_srl || !$code) return new Object(-1, 'msg_invalid_request');
		// Get the layout information
		$oLayoutModel = getModel('layout');
		$layout_info = $oLayoutModel->getLayout($layout_srl);
		if(!$layout_info) return new Object(-1, 'msg_invalid_request');
		// Separately handle the layout if its type is faceoff
		if($layout_info && $layout_info->type == 'faceoff') $oLayoutModel->doActivateFaceOff($layout_info);
		// Apply CSS directly
		Context::addHtmlHeader("<style type=\"text/css\" charset=\"UTF-8\">".$code_css."</style>");
		// Set names and values of extra_vars to $layout_info
		if($layout_info->extra_var_count)
		{
			foreach($layout_info->extra_var as $var_id => $val)
			{
				$layout_info->{$var_id} = $val->value;
			}
		}
		// menu in layout information becomes an argument for Context:: set
		if($layout_info->menu_count)
		{
			foreach($layout_info->menu as $menu_id => $menu)
			{
				$menu->php_file = FileHandler::getRealPath($menu->php_file);
				if(FileHandler::exists($menu->php_file)) include($menu->php_file);

				Context::set($menu_id, $menu);
			}
		}

		Context::set('layout_info', $layout_info);
		Context::set('content', Context::getLang('layout_preview_content'));
		// Temporary save the codes
		$edited_layout_file = _XE_PATH_ . 'files/cache/layout/tmp.tpl';
		FileHandler::writeFile($edited_layout_file, $code);

		// Compile
		$oTemplate = &TemplateHandler::getInstance();

		$layout_path = $layout_info->path;
		$layout_file = 'layout';

		$layout_tpl = $oTemplate->compile($layout_path, $layout_file, $edited_layout_file);
		Context::set('layout','none');
		// Convert widgets and others
		$oContext = &Context::getInstance();
		Context::set('layout_tpl', $layout_tpl);
		// Delete Temporary Files
		FileHandler::removeFile($edited_layout_file);
		$this->setTemplateFile('layout_preview');
	}

	private function getRealLayoutFile($layoutSrl)
	{
		$oLayoutModel = getModel('layout');
		$layoutFile = $oLayoutModel->getUserLayoutHtml($layoutSrl);

		if(file_exists($layoutFile))
		{
			return $layoutFile;
		}
		else
		{
			return ''; 
		}

	}

	private function getRealLayoutCSS($layoutSrl)
	{
		$oLayoutModel = getModel('layout');
		$cssFile = $oLayoutModel->getUserLayoutCss($layoutSrl);

		if(file_exists($cssFile))
		{
			return $cssFile;
		}
		else
		{
			return ''; 
		}

	}



}
/* End of file layout.view.php */
/* Location: ./modules/layout/layout.view.php */
