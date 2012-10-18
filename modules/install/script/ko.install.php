<?php
	// ko/en/...
	$lang = Context::getLangType();

	// insertMenu
	$menu_args->site_srl = 0;
	$menu_args->title = 'welcome_menu';
	$menu_srl = $menu_args->menu_srl = getNextSequence();
	$menu_args->listorder = $menu_srl * -1;

	$output = executeQuery('menu.insertMenu', $menu_args);
	if(!$output->toBool()) return $output;

	// insertMenuItem
		// create 1depth menuitem
	$item_args->menu_srl = $menu_srl;
	$item_args->url = 'welcome_page';
	$item_args->name = 'menu1';
	$parent_srl = $item_args->menu_item_srl = getNextSequence();
	$item_args->listorder = -1*$item_args->menu_item_srl;

	$output = executeQuery('menu.insertMenuItem', $item_args);
	if(!$output->toBool()) return $output;

		// create 2depth menuitem
	unset($item_args);
	$item_args->menu_srl = $menu_srl;
	$item_args->parent_srl = $parent_srl;
	$item_args->url = 'welcome_page';
	$item_args->name = 'menu1-1';
	$item_args->menu_item_srl = getNextSequence();
	$item_args->listorder = -1*$item_args->menu_item_srl;

	$output = executeQuery('menu.insertMenuItem', $item_args);
	if(!$output->toBool()) return $output;

		// XML 파일을 갱신
	$oMenuAdminController = &getAdminController('menu');
	$oMenuAdminController->makeXmlFile($menu_srl);

	// create Layout
		//extra_vars init
	$extra_vars->colorset = 'default';
	$extra_vars->main_menu = $menu_srl;
	$extra_vars->bottom_menu = $menu_srl;
	$extra_vars->menu_name_list = array();
	$extra_vars->menu_name_list[$menu_srl] = 'welcome_menu';

	$args->site_srl = 0;
	$layout_srl = $args->layout_srl = getNextSequence();
	$args->layout = 'xe_official';
	$args->title = 'welcome_layout';
	$args->layout_type = 'P';

	$oLayoutAdminController = &getAdminController('layout');
	$output = $oLayoutAdminController->insertLayout($args);
	if(!$output->toBool()) return $output;

	// update Layout
	$args->extra_vars = serialize($extra_vars);
	$output = $oLayoutAdminController->updateLayout($args);
	if(!$output->toBool()) return $output;

	$siteDesignPath = _XE_PATH_.'files/site_design/';
	FileHandler::makeDir($siteDesignPath);
	$siteDesignFile = _XE_PATH_.'files/site_design/design_0.php';
	$buff = sprintf('$designInfo->layout_srl = %s;', $layout_srl);

	// after trigger
	$moduleList = array('page');
	$moutput = ModuleHandler::triggerCall('menu.getModuleListInSitemap', 'after', $moduleList);
	if($moutput->toBool())
	{
		$moduleList = array_unique($moduleList);
	}

	$skinTypes = array('skin'=>'skins/', 'mskin'=>'m.skins/');

	foreach($skinTypes as $key => $dir)
	{
		foreach($moduleList as $moduleName)
		{
			$moduleSkinPath = ModuleHandler::getModulePath($moduleName).$dir;
			$skinName = 'default';
			
			$defualtSkinPath = $moduleSkinPath.$skinName;

			if(!is_dir($defualtSkinPath))
			{
				$skins = FileHandler::readDir($moduleSkinPath);
				if(count($skins) > 0)
				{
					$skinName = $skins[0];
				}
				else
				{
					$skinName = NULL;
				}
			}

			if($skinName)
			{
				$buff .= sprintf('$designInfo->module->%s->%s = \'%s\';', $moduleName, $key, $skinName);
			}
		}
	}

	$buff = sprintf('<?php if(!defined("__ZBXE__")) exit(); if(!defined("__XE__")) exit(); %s ?>', $buff);
	FileHandler::writeFile($siteDesignFile, $buff);


	// insertPageModule
	$page_args->layout_srl = $layout_srl;
	$page_args->menu_srl = $menu_srl;
	$page_args->browser_title = 'welcome_page';
	$page_args->module = 'page';
	$page_args->mid = 'welcome_page';
	$page_args->module_category_srl = 0;
	$page_args->page_caching_interval = 0;
	$page_args->page_type = 'ARTICLE';
	$page_args->skin = 'default';
	
	$oModuleController = &getController('module');
	$output = $oModuleController->insertModule($page_args);

	if(!$output->toBool()) return $output;

	$module_srl = $output->get('module_srl');

	// insert PageContents - widget
	$oTemplateHandler = &TemplateHandler::getInstance();

	$oDocumentModel = &getModel('document');
	$oDocumentController = &getController('document');

	$obj->module_srl = $module_srl;
	Context::set('version', __ZBXE_VERSION__);
	$obj->title = 'Welcome XE';

	$obj->content = $oTemplateHandler->compile('./modules/install/script/welcome_content', 'welcome_content_'.$lang);

	$output = $oDocumentController->insertDocument($obj);
	if(!$output->toBool()) return $output;
	
	$document_srl = $output->get('document_srl');

	// save PageWidget
	$oModuleModel = &getModel('module');
	$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
	$module_info->document_srl = $document_srl;
	$output = $oModuleController->updateModule($module_info);
	if(!$output->toBool()) return $output;

	// insertFirstModule
	$site_args->site_srl = 0;
	$site_args->index_module_srl = $module_srl;
	$oModuleController->updateSite($site_args);

?>
