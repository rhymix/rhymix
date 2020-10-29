<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class  boardAdminController
 * @author NAVER (developers@xpressengine.com)
 * @brief  board module admin controller class
 **/

class boardAdminController extends board {

	/**
	 * @brief initialization
	 **/
	function init()
	{
		
	}

	/**
	 * @brief insert borad module
	 **/
	function procBoardAdminInsertBoard($args = null)
	{
		// generate module model/controller object
		$oModuleController = getController('module');
		$oModuleModel = getModel('module');

		// setup the board module infortmation
		$args = Context::getRequestVars();
		$args->module = 'board';
		$args->mid = $args->board_name;
		if(is_array($args->use_status)) $args->use_status = implode('|@|', $args->use_status);
		unset($args->board_name);

		// Get existing module info
		if($args->module_srl)
		{
			$module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
		}
		
		// setup extra_order_target
		$extra_order_target = array();
		if($args->module_srl)
		{
			$oDocumentModel = getModel('document');
			$module_extra_vars = $oDocumentModel->getExtraKeys($args->module_srl);
			foreach($module_extra_vars as $oExtraItem)
			{
				$extra_order_target[$oExtraItem->eid] = $oExtraItem->name;
			}
		}

		// setup other variables
		if($args->except_notice != 'Y') $args->except_notice = 'N';
		if($args->use_bottom_list != 'Y') $args->use_bottom_list = 'N';
		if($args->skip_bottom_list_for_olddoc != 'Y') $args->skip_bottom_list_for_olddoc = 'N';
		if($args->skip_bottom_list_for_robot != 'Y') $args->skip_bottom_list_for_robot = 'N';
		if($args->use_anonymous != 'Y') $args->use_anonymous = 'N';
		if($args->consultation != 'Y') $args->consultation = 'N';
		if($args->protect_content!= 'Y') $args->protect_content = 'N';
		if(!in_array($args->order_target,$this->order_target) && !array_key_exists($args->order_target, $extra_order_target)) $args->order_target = 'list_order';
		if(!in_array($args->order_type, array('asc', 'desc'))) $args->order_type = 'asc';
		
		$args->skip_bottom_list_days = max(0, intval($args->skip_bottom_list_days));
		$args->browser_title = trim(utf8_normalize_spaces($args->browser_title));
		$args->meta_keywords = $args->meta_keywords ? implode(', ', array_map('trim', explode(',', $args->meta_keywords))) : '';
		$args->meta_description = trim(utf8_normalize_spaces($args->meta_description));

		// if there is an existed module
		if ($args->module_srl && $module_info->module_srl != $args->module_srl)
		{
			unset($args->module_srl);
		}
		
		// preserve existing config
		if ($args->module_srl)
		{
			$args->include_modules = $module_info->include_modules;
		}

		// insert/update the board module based on module_srl
		if(!$args->module_srl) {
			$args->hide_category = 'N';
			$args->allow_no_category = 'N';
			$output = $oModuleController->insertModule($args);
			$msg_code = 'success_registed';
		} else {
			$args->hide_category = $module_info->hide_category;
			$args->allow_no_category = $module_info->allow_no_category;
			$output = $oModuleController->updateModule($args);
			$msg_code = 'success_updated';
		}

		if(!$output->toBool()) return $output;

		// setup list config
		$list = explode(',',Context::get('list'));
		if(count($list))
		{
			$list_arr = array();
			foreach($list as $val)
			{
				$val = trim($val);
				if(!$val) continue;
				if(substr($val,0,10)=='extra_vars') $val = substr($val,10);
				$list_arr[] = $val;
			}
			$oModuleController = getController('module');
			$oModuleController->insertModulePartConfig('board', $output->get('module_srl'), $list_arr);
		}

		$this->setMessage($msg_code);
		if (Context::get('success_return_url')){
			changeValueInUrl('mid', $args->mid, $module_info->mid);
			$this->setRedirectUrl(Context::get('success_return_url'));
		}else{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispBoardAdminBoardInfo', 'module_srl', $output->get('module_srl')));
		}
	}

	/**
	 * Board info update in basic setup page
	 * @return void
	 */
	public function procBoardAdminUpdateBoardFroBasic()
	{
		$args = Context::getRequestVars();

		// for board info
		$args->module = 'board';
		$args->mid = $args->board_name;
		if(is_array($args->use_status))
		{
			$args->use_status = implode('|@|', $args->use_status);
		}
		unset($args->board_name);

		if(!in_array($args->order_target, $this->order_target))
		{
			$args->order_target = 'list_order';
		}
		if(!in_array($args->order_type, array('asc', 'desc')))
		{
			$args->order_type = 'asc';
		}

		$oModuleController = getController('module');
		$output = $oModuleController->updateModule($args);

		// for grant info, Register Admin ID
		$oModuleController->deleteAdminId($args->module_srl);
		if($args->admin_member)
		{
			$admin_members = explode(',',$args->admin_member);
			for($i=0;$i<count($admin_members);$i++)
			{
				$admin_id = trim($admin_members[$i]);
				if(!$admin_id) continue;
				$oModuleController->insertAdminId($args->module_srl, $admin_id);
			}
		}
	}

	/**
	 * @brief delete the board module
	 **/
	function procBoardAdminDeleteBoard() {
		$module_srl = Context::get('module_srl');

		// get the current module
		$oModuleController = getController('module');
		$output = $oModuleController->deleteModule($module_srl);
		if(!$output->toBool()) return $output;

		$this->add('module','board');
		$this->add('page',Context::get('page'));
		$this->setMessage('success_deleted');
	}

	/**
	 * Save settings for combined board.
	 */
	public function procBoardAdminInsertCombinedConfig()
	{
		$vars = Context::getRequestVars();
		$module_srl = intval($vars->target_module_srl);
		$include_modules = array_map('intval', $vars->include_modules ?: []);
		
		$module_info = ModuleModel::getModuleInfoByModuleSrl($module_srl);
		if (!$module_info)
		{
			throw new Rhymix\Framework\Exceptions\TargetNotFound;
		}
		
		$module_info->include_modules = implode(',', array_filter($include_modules, function($item) {
			return $item > 0;
		}));
		
		$output = getController('module')->updateModule($module_info);
		if (!$output->toBool())
		{
			return $output;
		}
		
		$this->setMessage('success_updated');
		if (Context::get('success_return_url'))
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispBoardAdminBoardAdditionSetup', 'module_srl', $module_srl));
		}
	}

	function procBoardAdminSaveCategorySettings()
	{
		$module_srl = Context::get('module_srl');
		$mid = Context::get('mid');

		$oModuleModel = getModel('module');
		$module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
		if($module_info->mid != $mid)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}

		$module_info->hide_category = Context::get('hide_category') == 'Y' ? 'Y' : 'N';
		$module_info->allow_no_category = Context::get('allow_no_category') == 'Y' ? 'Y' : 'N';
		$oModuleController = getController('module'); /* @var $oModuleController moduleController */
		$output = $oModuleController->updateModule($module_info);
		if(!$output->toBool())
		{
			return $output;
		}

		$this->setMessage('success_updated');
		if (Context::get('success_return_url'))
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispBoardAdminCategoryInfo', 'module_srl', $output->get('module_srl')));
		}
	}
}
