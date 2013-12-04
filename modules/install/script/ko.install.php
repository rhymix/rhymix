<?php

// ko/en/...
$lang = Context::getLangType();

// insertMenu
$oMenuAdminController = getAdminController('menu'); /* @var $oMenuAdminController menuAdminController */
$output = $oMenuAdminController->addMenu('Welcome menu');
if(!$output->toBool())
{
	return $output;
}
$menuSrl = $output->get('menuSrl');

// make home menu cache
$oMenuAdminController->makeHomemenuCacheFile($menuSrl);

// insertMenuItem
// create 1depth menuitem

// adhoc...
Context::set('parent_srl', $menuSrl, TRUE);
Context::set('menu_name', 'Welcome Page', TRUE);
Context::set('module_type', 'WIDGET', TRUE);
$output = $oMenuAdminController->procMenuAdminInsertItem();
if($output instanceof Object && !$output->toBool())
{
	return $output;
}
$menuItemSrl = $oMenuAdminController->get('menu_item_srl');

// create menu cache
$oMenuAdminController->makeXmlFile($menuSrl);

// create Layout
//extra_vars init
$extra_vars = new stdClass;
$extra_vars->GNB = $menuSrl;
$extra_vars->LAYOUT_TYPE = 'MAIN_PAGE';
$extra_vars->VISUAL_USE = 'YES';
$extra_vars->menu_name_list = array();
$extra_vars->menu_name_list[$menuSrl] = 'Welcome menu';

$args = new stdClass;
$args->site_srl = 0;
$layout_srl = $args->layout_srl = getNextSequence();
$args->layout = 'default';
$args->title = 'default';
$args->layout_type = 'P';

$oLayoutAdminController = getAdminController('layout'); /* @var $oLayoutAdminController layoutAdminController */
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

$oModuleModel = getModel('module'); /* @var $oModuleModel moduleModel */
foreach($skinTypes as $key => $dir)
{
	$skinType = $key == 'skin' ? 'P' : 'M';
	foreach($moduleList as $moduleName)
	{
		$designInfo->module->{$moduleName} = new stdClass;
		$designInfo->module->{$moduleName}->{$key} = $oModuleModel->getModuleDefaultSkin($moduleName, $skinType, 0, false);
	}
}

$oAdminController = getAdminController('admin'); /* @var $oAdminController adminAdminController */
$oAdminController->makeDefaultDesignFile($designInfo, 0);

// create page content
$moduleInfo = $oModuleModel->getModuleInfoByMenuItemSrl($menuItemSrl);
$module_srl = $moduleInfo->module_srl;

// insert PageContents - widget
$oTemplateHandler = TemplateHandler::getInstance();

$oDocumentModel = getModel('document'); /* @var $oDocumentModel documentModel */
$oDocumentController = getController('document'); /* @var $oDocumentController documentController */

$obj = new stdClass;
$obj->module_srl = $module_srl;
Context::set('version', __XE_VERSION__);
$obj->title = 'Welcome XE';

$obj->content = $oTemplateHandler->compile(_XE_PATH_ . 'modules/install/script/welcome_content', 'welcome_content_'.$lang);

$output = $oDocumentController->insertDocument($obj);
if(!$output->toBool()) return $output;

$document_srl = $output->get('document_srl');

unset($obj->document_srl);
$obj->title = 'Welcome mobile XE';
$output = $oDocumentController->insertDocument($obj);
if(!$output->toBool()) return $output;

// save PageWidget
$oModuleController = getController('module'); /* @var $oModuleController moduleController */
$mdocument_srl = $output->get('document_srl');
$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
$module_info->content = '<img hasContent="true" class="zbxe_widget_output" widget="widgetContent" style="width: 100%; float: left;" body="" document_srl="'.$document_srl.'" widget_padding_left="0" widget_padding_right="0" widget_padding_top="0" widget_padding_bottom="0"  />';
$module_info->mcontent = '<img hasContent="true" class="zbxe_widget_output" widget="widgetContent" style="width: 100%; float: left;" body="" document_srl="'.$mdocument_srl.'" widget_padding_left="0" widget_padding_right="0" widget_padding_top="0" widget_padding_bottom="0"  />';
$output = $oModuleController->updateModule($module_info);
if(!$output->toBool()) return $output;

// insertFirstModule
$site_args = new stdClass;
$site_args->site_srl = 0;
$site_args->index_module_srl = $module_srl;
$oModuleController->updateSite($site_args);

/* End of file ko.install.php */
/* Location: ./modules/install/script/ko.install.php */
