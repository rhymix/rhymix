<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class  communicationAdminView
 * @author NAVER (developers@xpressengine.com)
 * communication module of the admin view class
 */
class communicationAdminView extends communication
{

	/**
	 * Initialization
	 */
	function init()
	{

	}

	/**
	 * configuration to manage messages and friends
	 * @return void
	 */
	function dispCommunicationAdminConfig()
	{
		// Creating an object
		$oEditorModel = getModel('editor');
		$oModuleModel = getModel('module');
		$oLayoutModel = getModel('layout');
		$oCommunicationModel = getModel('communication');

		// get the configurations of communication module
		Context::set('config', $oCommunicationModel->getConfig());

		// get a list of layout
		Context::set('layout_list', $oLayoutModel->getLayoutList());

		// get a list of editor skins
		Context::set('editor_skin_list', $oEditorModel->getEditorSkinList());

		// get a list of communication skins
		Context::set('skin_list', $oModuleModel->getSkins($this->module_path));

		// get a list of communication skins
		Context::set('mobile_skin_list', $oModuleModel->getSkins($this->module_path, 'm.skins'));

		// Get a layout list
		$layout_list = $oLayoutModel->getLayoutList();
		Context::set('layout_list', $layout_list);

		$mlayout_list = $oLayoutModel->getLayoutList(0, 'M');
		Context::set('mlayout_list', $mlayout_list);

		$security = new Security();
		$security->encodeHTML('config..');
		$security->encodeHTML('layout_list..');
		$security->encodeHTML('editor_skin_list..');
		$security->encodeHTML('skin_list..title');
		$security->encodeHTML('mobile_skin_list..title');

		$oMemberModel = getModel('member');
		$group_list = $oMemberModel->getGroups($this->site_srl);
		Context::set('group_list', $group_list);

		// specify a template
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('index');
	}
}
/* End of file communication.admin.view.php */
/* Location: ./modules/comment/communication.admin.view.php */
