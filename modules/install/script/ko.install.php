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
$item_args->is_shortcut = 'N';
$item_args->name = 'menu1';
$parent_srl = $item_args->menu_item_srl = getNextSequence();
$item_args->listorder = -1*$item_args->menu_item_srl;

$output = executeQuery('menu.insertMenuItem', $item_args);
if(!$output->toBool()) return $output;

// create 2depth menuitem
/*unset($item_args);
$item_args->menu_srl = $menu_srl;
$item_args->parent_srl = $parent_srl;
$item_args->url = 'welcome_page';
$item_args->name = 'menu1-1';
$item_args->menu_item_srl = getNextSequence();
$item_args->listorder = -1*$item_args->menu_item_srl;

$output = executeQuery('menu.insertMenuItem', $item_args);
if(!$output->toBool()) return $output;*/

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

//create mobile layout
$mlayout_srl = $args->layout_srl = getNextSequence();
$args->layout = 'default';
$args->title = 'welcome_mobile_layout';
$args->layout_type = 'M';

$output = $oLayoutAdminController->insertLayout($args);
if(!$output->toBool()) return $output;

// update Layout
$args->extra_vars = serialize($extra_vars);
$output = $oLayoutAdminController->updateLayout($args);
if(!$output->toBool()) return $output;

$siteDesignPath = _XE_PATH_.'files/site_design/';
FileHandler::makeDir($siteDesignPath);

$designInfo = new stdClass();
$designInfo->layout_srl = $layout_srl;
$designInfo->mlayout_srl = $mlayout_srl;

$moduleList = array('page');
$moutput = ModuleHandler::triggerCall('menu.getModuleListInSitemap', 'after', $moduleList);
if($moutput->toBool())
{
	$moduleList = array_unique($moduleList);
}

$skinTypes = array('skin'=>'skins/', 'mskin'=>'m.skins/');

$designInfo->module = new stdClass();

$oModuleModel = &getModel('module');
foreach($skinTypes as $key => $dir)
{
	$skinType = $key == 'skin' ? 'P' : 'M';
	foreach($moduleList as $moduleName)
	{
		$designInfo->module->{$moduleName}->{$key} = $oModuleModel->getModuleDefaultSkin($moduleName, $skinType, 0, false);
	}
}

$oAdminController = getAdminController('admin');
$oAdminController->makeDefaultDesignFile($designInfo, 0);

// insertPageModule
$page_args->layout_srl = $layout_srl;
$page_args->mlayout_srl = $mlayout_srl;
$page_args->menu_srl = $menu_srl;
$page_args->browser_title = 'welcome_page';
$page_args->module = 'page';
$page_args->mid = 'welcome_page';
$page_args->module_category_srl = 0;
$page_args->page_caching_interval = 0;
$page_args->page_type = 'ARTICLE';
$page_args->skin = 'default';
$page_args->use_mobile = 'Y';

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

unset($obj->document_srl);
$output = $oDocumentController->insertDocument($obj);
if(!$output->toBool()) return $output;

// save PageWidget
$mdocument_srl = $output->get('document_srl');
$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
$module_info->document_srl = $document_srl;
$module_info->mdocument_srl = $mdocument_srl;
$output = $oModuleController->updateModule($module_info);
if(!$output->toBool()) return $output;

// insertFirstModule
$site_args->site_srl = 0;
$site_args->index_module_srl = $module_srl;
$oModuleController->updateSite($site_args);

/* End of file ko.install.php */
/* Location: ./modules/install/script/ko.install.php */
