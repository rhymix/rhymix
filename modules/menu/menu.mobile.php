<?php

class menuMobile extends moduleObject {
	var $result = array();

	function straightenMenu($menu_item, $depth)
	{
		if(!$menu_item['link']) return;
		$obj->href = $menu_item['href'];
		$obj->depth = $depth;
		$obj->link = $menu_item['link'];
		$this->result[] = $obj;
		if(!$menu_item['list']) return;
		foreach($menu_item['list'] as $item)
		{
			$this->straightenMenu($item, $depth+1);
		}
	}

	function dispMenuMenu() {
		$menu_srl = Context::get('menu_srl');
		$oAdminModel =& getAdminModel('menu');
		$menu_info = $oAdminModel->getMenu($menu_srl);
		if(file_exists($menu_info->php_file)) @include($menu_info->php_file);
		foreach($menu->list as $menu_item)
		{
			$this->straightenMenu($menu_item, 0);
		}

		Context::set('menu', $this->result);	

		$this->setTemplatePath(sprintf("%stpl/",$this->module_path));
		$this->setTemplateFile('menu.html');
		
	}
}
?>
