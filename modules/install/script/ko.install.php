<?php

// ko/en/...
$lang = Context::getLangType();
$logged_info = Context::get('logged_info');

$oMenuAdminController = getAdminController('menu');

// sitemap
$sitemap = array(
	'GNB' => array(
		'title' => 'Main Menu',
		'list' => array(
			array(
				'menu_name' => 'Welcome',
				'module_type' => 'WIDGET',
				'module_id' => 'index',
			),
			array(
				'menu_name' => 'Free Board',
				'module_type' => 'board',
				'module_id' => 'board',
			),
			array(
				'menu_name' => 'Q&A',
				'module_type' => 'board',
				'module_id' => 'qna',
			),
			array(
				'menu_name' => 'Notice',
				'module_type' => 'board',
				'module_id' => 'notice',
			),
		),
	),
	'UNB' => array(
		'title' => 'Utility Menu',
		'list' => array(
			array(
				'menu_name' => 'Rhymix Official Site',
				'is_shortcut' => 'Y',
				'open_window' => 'Y',
				'shortcut_target' => 'https://rhymix.org/',
			),
			array(
				'menu_name' => 'Rhymix GitHub',
				'is_shortcut' => 'Y',
				'open_window' => 'Y',
				'shortcut_target' => 'https://github.com/rhymix',
			),
		),
	),
	'FNB' => array(
		'title' => 'Footer Menu',
		'list' => array(
			array(
				'menu_name' => 'Terms of Service',
				'module_type' => 'ARTICLE',
				'module_id' => 'terms',
			),
			array(
				'menu_name' => 'Privacy Policy',
				'module_type' => 'ARTICLE',
				'module_id' => 'privacy',
			),
		),
	),
);

function __makeMenu(&$list, $parent_srl)
{
	$oMenuAdminController = getAdminController('menu');
	foreach($list as $idx => &$item)
	{
		Context::set('parent_srl', $parent_srl, TRUE);
		Context::set('menu_name', $item['menu_name'], TRUE);
		Context::set('module_type', $item['module_type'], TRUE);
		Context::set('module_id', $item['module_id'], TRUE);
		if($item['is_shortcut'] === 'Y')
		{
			Context::set('is_shortcut', $item['is_shortcut'], TRUE);
			Context::set('shortcut_target', $item['shortcut_target'], TRUE);
		}
		else
		{
			Context::set('is_shortcut', 'N', TRUE);
			Context::set('shortcut_target', null, TRUE);
		}

		$output = $oMenuAdminController->procMenuAdminInsertItem();
		if($output instanceof BaseObject && !$output->toBool())
		{
			return $output;
		}
		$menu_srl = $oMenuAdminController->get('menu_item_srl');
		$item['menu_srl'] = $menu_srl;

		if($item['list']) __makeMenu($item['list'], $menu_srl);
	}
}


// 사이트맵 생성
foreach($sitemap as $id => &$val)
{
	$output = $oMenuAdminController->addMenu($val['title']);
	if(!$output->toBool())
	{
		return $output;
	}
	$val['menu_srl'] = $output->get('menuSrl');

	__makeMenu($val['list'], $val['menu_srl']);

	$oMenuAdminController->makeHomemenuCacheFile($val['menu_srl']);
}

// create Layout
//extra_vars init
$extra_vars = new stdClass();
$extra_vars->use_demo = 'Y';
$extra_vars->use_ncenter_widget = 'Y';
$extra_vars->content_fixed_width = 'Y';
$extra_vars->GNB = $sitemap['GNB']['menu_srl'];
$extra_vars->UNB = $sitemap['UNB']['menu_srl'];
$extra_vars->FNB = $sitemap['FNB']['menu_srl'];

$args = new stdClass();
$layout_srl = $args->layout_srl = getNextSequence();
$args->site_srl = 0;
$args->layout = 'xedition';
$args->title = 'XEDITION';
$args->layout_type = 'P';
$oLayoutAdminController = getAdminController('layout');
$output = $oLayoutAdminController->insertLayout($args);
if(!$output->toBool()) return $output;

// update Layout (PC)
$args->extra_vars = serialize($extra_vars);
$output = $oLayoutAdminController->updateLayout($args);
if(!$output->toBool()) return $output;

//create mobile layout
$mlayout_srl = $args->layout_srl = getNextSequence();
$args->layout = 'default';
$args->title = 'welcome_mobile_layout';
$args->layout_type = 'M';
$extra_vars->main_menu = $sitemap['GNB']['menu_srl'];

$output = $oLayoutAdminController->insertLayout($args);
if(!$output->toBool()) return $output;

// update mobile Layout
$args->extra_vars = serialize($extra_vars);
$output = $oLayoutAdminController->updateLayout($args);
if(!$output->toBool()) return $output;

$siteDesignPath = RX_BASEDIR.'files/site_design/';
FileHandler::makeDir($siteDesignPath);

$designInfo = new stdClass();
$designInfo->layout_srl = $layout_srl;
$designInfo->mlayout_srl = $mlayout_srl;

$moduleList = array('page', 'board', 'editor');
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
		$designInfo->module->{$moduleName} = new stdClass();
		$designInfo->module->{$moduleName}->{$key} = $oModuleModel->getModuleDefaultSkin($moduleName, $skinType, 0, false);
	}
}
$designInfo->module->board->skin = 'xedition';
$designInfo->module->editor->skin = 'ckeditor';

$oAdminController = getAdminController('admin'); /* @var $oAdminController adminAdminController */
$oAdminController->makeDefaultDesignFile($designInfo, 0);

// create page content
$moduleInfo = $oModuleModel->getModuleInfoByMenuItemSrl($sitemap['GNB']['list'][0]['menu_srl']);
$module_srl = $moduleInfo->module_srl;

// insert PageContents - widget
$oTemplateHandler = TemplateHandler::getInstance();

$oDocumentModel = getModel('document'); /* @var $oDocumentModel documentModel */
$oDocumentController = getController('document'); /* @var $oDocumentController documentController */

$obj = new stdClass();

$obj->member_srl = $logged_info->member_srl;
$obj->user_id = htmlspecialchars_decode($logged_info->user_id);
$obj->user_name = htmlspecialchars_decode($logged_info->user_name);
$obj->nick_name = htmlspecialchars_decode($logged_info->nick_name);
$obj->email_address = $logged_info->email_address;

$obj->module_srl = $module_srl;
Context::set('version', RX_VERSION);
$obj->title = 'Welcome to Rhymix';

$obj->content = $oTemplateHandler->compile(RX_BASEDIR . 'modules/install/script/welcome_content', 'welcome_content');

$output = $oDocumentController->insertDocument($obj, true);
if(!$output->toBool()) return $output;

$document_srl = $output->get('document_srl');

unset($obj->document_srl);
$obj->title = 'Welcome to Mobile Rhymix';
$output = $oDocumentController->insertDocument($obj, true);
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
$domain_args = new stdClass();
$domain_args->domain_srl = 0;
$domain_args->index_module_srl = $module_srl;
executeQuery('module.updateDomain', $domain_args);

// insert admin favorites
foreach(['advanced_mailer', 'ncenterlite'] as $module_name)
{
	$oAdminController->_insertFavorite(0, $module_name);
}

// create menu cache
$oMenuAdminController->makeXmlFile($menuSrl);

/* End of file ko.install.php */
