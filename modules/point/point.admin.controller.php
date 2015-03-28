<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  pointAdminController
 * @author NAVER (developers@xpressengine.com)
 * @brief The admin controller class of the point module
 */
class pointAdminController extends point
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief Save the default configurations
	 */
	function procPointAdminInsertConfig()
	{
		// Get the configuration information
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('point');
		// Arrange variables
		$args = Context::getRequestVars();

		//if module IO config is off
		if($args->able_module == 'Y')
		{
			// Re-install triggers, if it was disabled.
			if($config->able_module == 'N')
			{
				$this->moduleUpdate();
			}

			//module IO config is on
			$config->able_module = 'Y';

			// Check the point name
			$config->point_name = $args->point_name;
			if(!$config->point_name) $config->point_name = 'point';
			// Specify the default points
			$config->signup_point = (int)$args->signup_point;
			$config->login_point = (int)$args->login_point;
			$config->insert_document = (int)$args->insert_document;
			$config->read_document = (int)$args->read_document;
			$config->insert_comment = (int)$args->insert_comment;
			$config->upload_file = (int)$args->upload_file;
			$config->download_file = (int)$args->download_file;
			$config->voted = (int)$args->voted;
			$config->blamed = (int)$args->blamed;
			// The highest level
			$config->max_level = $args->max_level;
			if($config->max_level>1000) $config->max_level = 1000;
			if($config->max_level<1) $config->max_level = 1;
			// Set the level icon
			$config->level_icon = $args->level_icon;
			// Check if downloads are not allowed
			if($args->disable_download == 'Y') $config->disable_download = 'Y';
			else $config->disable_download = 'N';
			// Check if reading a document is not allowed
			if($args->disable_read_document == 'Y') $config->disable_read_document = 'Y';
			else $config->disable_read_document = 'N';

			$oMemberModel = getModel('member');
			$group_list = $oMemberModel->getGroups();

			// Per-level group configurations
			foreach($group_list as $group)
			{
				// Admin group should not be connected to point.
				if($group->is_admin == 'Y' || $group->is_default == 'Y') continue;

				$group_srl = $group->group_srl;

				if(isset($args->{'point_group_'.$group_srl}))
				{
					//if group level is higher than max level, change to max level
					if($args->{'point_group_'.$group_srl} > $args->max_level)
					{
						$args->{'point_group_'.$group_srl} = $args->max_level;
					}

					//if group level is lower than 1, change to 1
					if($args->{'point_group_'.$group_srl} < 1)
					{
						$args->{'point_group_'.$group_srl} = 1;
					}
					$config->point_group[$group_srl] = $args->{'point_group_'.$group_srl};
				}
				else
				{
					unset($config->point_group[$group_srl]);
				}
			}

			$config->group_reset = $args->group_reset;
			// Per-level point configurations
			unset($config->level_step);
			for($i=1;$i<=$config->max_level;$i++)
			{
				$key = "level_step_".$i;
				$config->level_step[$i] = (int)$args->{$key};
			}
			// A function to calculate per-level points
			$config->expression = $args->expression;
		}
		else
		{
			//module IO config is OFF, Other settings will not be modified.
			$config->able_module = 'N';

			// Delete Triggers
			$oModuleController = getController('module');
			$oModuleController->deleteModuleTriggers('point');
		}
		// Save
		$oModuleController = getController('module');
		$oModuleController->insertModuleConfig('point', $config);

		$this->setMessage('success_updated');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispPointAdminConfig');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * @brief Save per-module configurations
	 */
	function procPointAdminInsertModuleConfig()
	{
		$args = Context::getRequestVars();

		$configTypeList = array('insert_document', 'insert_comment', 'upload_file', 'download_file', 'read_document', 'voted', 'blamed');
		foreach($configTypeList AS $config)
		{
			if(is_array($args->{$config}))
			{
				foreach($args->{$config} AS $key=>$value)
				{
					$module_config[$key][$config] = $value;
				}
			}
		}

		$oModuleController = getController('module');
		if(count($module_config))
		{
			foreach($module_config as $module_srl => $config)
			{
				$oModuleController->insertModulePartConfig('point',$module_srl,$config);
			}
		}

		$this->setMessage('success_updated');
		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispPointAdminModuleConfig');
			header('location:'.$returnUrl);
			return;
		}
	}

	/**
	 * @brief Save individual points per module
	 */
	function procPointAdminInsertPointModuleConfig()
	{
		$module_srl = Context::get('target_module_srl');
		if(!$module_srl) return new Object(-1, 'msg_invalid_request');
		// In case of batch configuration of several modules
		if(preg_match('/^([0-9,]+)$/',$module_srl)) $module_srl = explode(',',$module_srl);
		else $module_srl = array($module_srl);
		// Save configurations
		$oModuleController = getController('module');
		for($i=0;$i<count($module_srl);$i++)
		{
			$srl = trim($module_srl[$i]);
			if(!$srl) continue;
			unset($config);
			$config['insert_document'] = (int)Context::get('insert_document');
			$config['insert_comment'] = (int)Context::get('insert_comment');
			$config['upload_file'] = (int)Context::get('upload_file');
			$config['download_file'] = (int)Context::get('download_file');
			$config['read_document'] = (int)Context::get('read_document');
			$config['voted'] = (int)Context::get('voted');
			$config['blamed'] = (int)Context::get('blamed');
			$oModuleController->insertModulePartConfig('point', $srl, $config);
		}

		$this->setError(-1);
		$this->setMessage('success_updated', 'info');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispBoardAdminContent');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * @brief Change members points
	 */
	function procPointAdminUpdatePoint()
	{
		$member_srl = Context::get('member_srl');
		$point = Context::get('point');

		preg_match('/^(\+|-)?([1-9][0-9]*)$/', $point, $m);

		$action = '';
		switch($m[1])
		{
			case '+':
				$action = 'add';
				break;
			case '-':
				$action = 'minus';
				break;
			default:
				$action = 'update';
				break;
		}
		$point = $m[2];

		$oPointController = getController('point');
		$output = $oPointController->setPoint($member_srl, (int)$point, $action);

		$this->setError(-1);
		$this->setMessage('success_updated', 'info');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispPointAdminPointList');
		return $this->setRedirectUrl($returnUrl, $output);
	}

	/**
	 * @brief Recalculate points based on the list/comment/attachment and registration information. Granted only once a first-time login score.
	 */
	function procPointAdminReCal()
	{
		@set_time_limit(0);
		// Get per-module points information
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('point');

		$module_config = $oModuleModel->getModulePartConfigs('point');
		// A variable to store member's points
		$member = array();

		// Get member infomation
		$output = executeQueryArray('point.getMemberCount');
		if(!$output->toBool()) return $output;

		if($output->data)
		{
			foreach($output->data as $key => $val)
			{
				if(!$val->member_srl) continue;
				$member[$val->member_srl] = 0;
			}
		}

		// Get post information
		$output = executeQueryArray('point.getDocumentPoint');
		if(!$output->toBool()) return $output;

		if($output->data)
		{
			foreach($output->data as $key => $val)
			{
				if($module_config[$val->module_srl]['insert_document']) $insert_point = $module_config[$val->module_srl]['insert_document'];
				else $insert_point = $config->insert_document;

				if(!$val->member_srl) continue;
				$point = $insert_point * $val->count;
				$member[$val->member_srl] += $point;
			}
		}

		$output = null;
		// Get comments information
		$output = executeQueryArray('point.getCommentPoint');
		if(!$output->toBool()) return $output;

		if($output->data)
		{
			foreach($output->data as $key => $val)
			{
				if($module_config[$val->module_srl]['insert_comment']) $insert_point = $module_config[$val->module_srl]['insert_comment'];
				else $insert_point = $config->insert_comment;

				if(!$val->member_srl) continue;
				$point = $insert_point * $val->count;
				$member[$val->member_srl] += $point;
			}
		}
		$output = null;
		// Get the attached files' information
		$output = executeQueryArray('point.getFilePoint');
		if(!$output->toBool()) return $output;

		if($output->data)
		{
			foreach($output->data as $key => $val)
			{
				if($module_config[$val->module_srl]['upload_file']) $insert_point = $module_config[$val->module_srl]['upload_file'];
				else $insert_point = $config->upload_file;

				if(!$val->member_srl) continue;
				$point = $insert_point * $val->count;
				$member[$val->member_srl] += $point;
			}
		}
		$output = null;
		// Set all members' points to 0
		$output = executeQuery("point.initMemberPoint");
		if(!$output->toBool()) return $output;
		// Save the file temporarily
		
		$str = '';
		foreach($member as $key => $val)
		{
			$val += (int)$config->signup_point;
			$str .= $key.','.$val."\r\n";
		}

		@file_put_contents('./files/cache/pointRecal.txt', $str, LOCK_EX);

		$this->add('total', count($member));
		$this->add('position', 0);
		$this->setMessage( sprintf(Context::getLang('point_recal_message'), 0, $this->get('total')) );
	}

	/**
	 * @brief Apply member points saved by file to units of 5,000 people
	 */
	function procPointAdminApplyPoint()
	{
		$position = (int)Context::get('position');
		$total = (int)Context::get('total');

		if(!file_exists('./files/cache/pointRecal.txt')) return new Object(-1, 'msg_invalid_request');

		$idx = 0;
		$f = fopen("./files/cache/pointRecal.txt","r");
		while(!feof($f))
		{
			$str = trim(fgets($f, 1024));
			$idx ++;
			if($idx > $position)
			{
				list($member_srl, $point) = explode(',',$str);

				$args = new stdClass();
				$args->member_srl = $member_srl;
				$args->point = $point;
				$output = executeQuery('point.insertPoint',$args);
				if($idx%5000==0) break;
			}
		}

		if(feof($f))
		{
			FileHandler::removeFile('./files/cache/pointRecal.txt');
			$idx = $total;

			FileHandler::rename('./files/member_extra_info/point','./files/member_extra_info/point.old');

			FileHandler::removeDir('./files/member_extra_info/point.old');
		}
		fclose($f);

		$this->add('total', $total);
		$this->add('position', $idx);
		$this->setMessage(sprintf(Context::getLang('point_recal_message'), $idx, $total));

	}

	/**
	 * @brief Reset points for each module
	 */
	function procPointAdminReset()
	{
		$module_srl = Context::get('module_srls');
		if(!$module_srl) return new Object(-1, 'msg_invalid_request');
		// In case of batch configuration of several modules
		if(preg_match('/^([0-9,]+)$/',$module_srl)) $module_srl = explode(',',$module_srl);
		else $module_srl = array($module_srl);
		// Save configurations
		$oModuleController = getController('module');
		for($i=0;$i<count($module_srl);$i++)
		{
			$srl = trim($module_srl[$i]);
			if(!$srl) continue;
			$args = new stdClass();
			$args->module = 'point';
			$args->module_srl = $srl;
			executeQuery('module.deleteModulePartConfig', $args);
		}

		$oCacheHandler = CacheHandler::getInstance('object', null, true);
		if($oCacheHandler->isSupport())
		{
			$oCacheHandler->invalidateGroupKey('site_and_module');
		}

		$this->setMessage('success_updated');
	}

	/**
	 * @brief Save the cache files
	 * @deprecated
	 */
	function cacheActList()
	{
		return;
	}
}
/* End of file point.admin.controller.php */
/* Location: ./modules/point/point.admin.controller.php */
