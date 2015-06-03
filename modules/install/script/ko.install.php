<?php

// ko/en/...
$lang = Context::getLangType();
$logged_info = Context::get('logged_info');

$oMenuAdminController = getAdminController('menu');

// sitemap
$sitemap = array(
	'GNB' => array(
		'title' => 'Main menu',
		'list' => array(
			array(
				'menu_name' => 'Welcome Page',
				'module_type' => 'WIDGET',
				'module_id' => 'index',
			),
			array(
				'menu_name' => 'Board',
				'module_type' => 'board',
				'module_id' => 'board',
				'list' => array(
					array(
						'menu_name' => 'SAMPLE 1',
						'is_shortcut' => 'Y',
						'shortcut_target' => '#',
						'list' => array(
							array(
								'menu_name' => 'SAMPLE 1-1',
								'is_shortcut' => 'Y',
								'shortcut_target' => '#'
							),
						)
					),
					array(
						'menu_name' => 'SAMPLE 2',
						'is_shortcut' => 'Y',
						'shortcut_target' => '#'
					),
					array(
						'menu_name' => 'SAMPLE 3',
						'is_shortcut' => 'Y',
						'shortcut_target' => '#'
					),
				)
			),
			array(
				'menu_name' => 'XEIcon',
				'module_type' => 'WIDGET',
				'module_id' => 'xeicon',
			),
		)
	),
	'UNB' => array(
		'title' => 'Utility menu',
		'list' => array(
			array(
				'menu_name' => 'XE Official Site',
				'is_shortcut' => 'Y',
				'open_window' => 'Y',
				'shortcut_target' => 'http://www.xpressengine.com'
			),
			array(
				'menu_name' => 'GitHub',
				'is_shortcut' => 'Y',
				'open_window' => 'Y',
				'shortcut_target' => 'https://github.com/xpressengine'
			),
		)
	),
	'FNB' => array(
		'title' => 'Footer Menu',
		'list' => array(
			array(
				'menu_name' => 'Welcome Page',
				'is_shortcut' => 'Y',
				'shortcut_target' => 'index',
				'list' => array(
					array(
						'menu_name' => 'SAMPLE 1',
						'is_shortcut' => 'Y',
						'shortcut_target' => '#'
					),
					array(
						'menu_name' => 'SAMPLE 2',
						'is_shortcut' => 'Y',
						'shortcut_target' => '#'
					),
					array(
						'menu_name' => 'SAMPLE 3',
						'is_shortcut' => 'Y',
						'shortcut_target' => '#'
					)
				),
			),
			array(
				'menu_name' => 'Board',
				'is_shortcut' => 'Y',
				'shortcut_target' => 'board',
				'list' => array(
					array(
						'menu_name' => 'SAMPLE 1',
						'is_shortcut' => 'Y',
						'shortcut_target' => '#'
					),
					array(
						'menu_name' => 'SAMPLE 2',
						'is_shortcut' => 'Y',
						'shortcut_target' => '#'
					)
				)
			),
			array(
				'menu_name' => 'XEIcon',
				'is_shortcut' => 'Y',
				'shortcut_target' => 'xeicon',
				'list' => array(
					array(
						'menu_name' => 'SAMPLE 1',
						'is_shortcut' => 'Y',
						'shortcut_target' => '#'
					)
				)
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
		if($output instanceof Object && !$output->toBool())
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


$siteDesignPath = _XE_PATH_.'files/site_design/';
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
Context::set('version', __XE_VERSION__);
$obj->title = 'Welcome XE';

$obj->content = $oTemplateHandler->compile(_XE_PATH_ . 'modules/install/script/welcome_content', 'welcome_content_'.$lang);

$output = $oDocumentController->insertDocument($obj, true);
if(!$output->toBool()) return $output;

$document_srl = $output->get('document_srl');

unset($obj->document_srl);
$obj->title = 'Welcome mobile XE';
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
$site_args = new stdClass();
$site_args->site_srl = 0;
$site_args->index_module_srl = $module_srl;
$oModuleController->updateSite($site_args);


// XEIcon page
$moduleInfo = $oModuleModel->getModuleInfoByMenuItemSrl($sitemap['GNB']['list'][2]['menu_srl']);
$xeicon_module_srl = $moduleInfo->module_srl;

$xeicon_document_srl = array();
for($i = 1; $i <=4; $i++)
{
	unset($obj->document_srl);
	$obj->title = "XEIcon ({$i})";
	$obj->content = $oTemplateHandler->compile(_XE_PATH_ . 'modules/install/script/xeicon_content', 'xeicon_content_ko_' . $i);

	$output = $oDocumentController->insertDocument($obj, true);
	if(!$output->toBool()) return $output;

	$xeicon_document_srl[$i] = $output->get('document_srl');
}

// save PageWidget
$oModuleController = getController('module'); /* @var $oModuleController moduleController */
$module_info = $oModuleModel->getModuleInfoByModuleSrl($xeicon_module_srl);
$module_info->content = '<div widget="widgetBox" style="float:left;width:100%;" widget_padding_left="0" widget_padding_right="0" widget_padding_top="0" widget_padding_bottom="0" css_class="XEicon" ><div><div><img hasContent="true" class="zbxe_widget_output" widget="widgetContent" style="float:left;padding:none;margin:none;width:100%;" document_srl="'.$xeicon_document_srl[1].'" widget_padding_left="0" widget_padding_right="0" widget_padding_top="0" widget_padding_bottom="0" /><img hasContent="true" class="zbxe_widget_output" widget="widgetContent" style="float:left;padding:none;margin:none;width:100%;" document_srl="'.$xeicon_document_srl[2].'" widget_padding_left="0" widget_padding_right="0" widget_padding_top="0" widget_padding_bottom="0" /><img hasContent="true" class="zbxe_widget_output" widget="widgetContent" style="float:left;padding:none;margin:none;width:100%;" document_srl="'.$xeicon_document_srl[3].'" widget_padding_left="0" widget_padding_right="0" widget_padding_top="0" widget_padding_bottom="0" /><img hasContent="true" class="zbxe_widget_output" widget="widgetContent" style="float:left;padding:none;margin:none;width:100%;" document_srl="'.$xeicon_document_srl[4].'" widget_padding_left="0" widget_padding_right="0" widget_padding_top="0" widget_padding_bottom="0" /></div></div></div>';
$output = $oModuleController->updateModule($module_info);
if(!$output->toBool()) return $output;


// create menu cache
$oMenuAdminController->makeXmlFile($menuSrl);

/* End of file ko.install.php */
