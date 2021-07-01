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
		$oModuleController = getController('module');

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
		}
		else
		{
			//module IO config is OFF, Other settings will not be modified.
			$config->able_module = 'N';

			// Delete Triggers
			$oModuleController->deleteModuleTriggers('point');
		}

		// Check the point name
		$config->point_name = $args->point_name;
		if(!$config->point_name)
		{
			$config->point_name = 'point';
		}

		// Specify the default points
		$config->signup_point = (int)$args->signup_point;
		$config->login_point = (int)$args->login_point;
		$config->insert_document = (int)$args->insert_document;
		$config->insert_comment = (int)$args->insert_comment;
		$config->upload_file = (int)$args->upload_file;
		$config->download_file = (int)$args->download_file;
		$config->read_document = (int)$args->read_document;
		$config->voter = (int)$args->voter;
		$config->blamer = (int)$args->blamer;
		$config->voted = (int)$args->voted;
		$config->blamed = (int)$args->blamed;
		$config->download_file_author = (int)$args->download_file_author;
		$config->read_document_author = (int)$args->read_document_author;
		$config->voter_comment = (int)$args->voter_comment;
		$config->blamer_comment = (int)$args->blamer_comment;
		$config->voted_comment = (int)$args->voted_comment;
		$config->blamed_comment = (int)$args->blamed_comment;

		// Specify notice exceptions
		$config->read_document_except_notice = ($args->read_document_except_notice === 'Y');
		$config->read_document_author_except_notice = ($args->read_document_author_except_notice === 'Y');

		// Specify revert on delete
		$config->insert_document_revert_on_delete = ($args->insert_document_revert_on_delete === 'Y');
		$config->insert_comment_revert_on_delete = ($args->insert_comment_revert_on_delete === 'Y');
		$config->upload_file_revert_on_delete = ($args->upload_file_revert_on_delete === 'Y');

		// Specify time limits
		$config->insert_comment_limit = $config->no_point_date = (int)$args->insert_comment_limit;
		$config->read_document_limit = (int)$args->read_document_limit;
		$config->voter_limit = (int)$args->voter_limit;
		$config->blamer_limit = (int)$args->blamer_limit;
		$config->voted_limit = (int)$args->voted_limit;
		$config->blamed_limit = (int)$args->blamed_limit;
		$config->read_document_author_limit = (int)$args->read_document_author_limit;
		$config->voter_comment_limit = (int)$args->voter_comment_limit;
		$config->blamer_comment_limit = (int)$args->blamer_comment_limit;
		$config->voted_comment_limit = (int)$args->voted_comment_limit;
		$config->blamed_comment_limit = (int)$args->blamed_comment_limit;

		// The highest level
		$config->max_level = $args->max_level;
		if($config->max_level>10000) $config->max_level = 10000;
		if($config->max_level<1) $config->max_level = 1;

		// Set the level icon
		$config->level_icon = $args->level_icon;
		$config->level_icon_type = 'gif';
		$level_icon_dir = $this->module_path . '/icons/' . $config->level_icon;
		if (!file_exists($level_icon_dir))
		{
			return new BaseObject(-1, 'msg_level_icon_not_found');
		}
		if (!file_exists($level_icon_dir . '/1.gif'))
		{
			if (file_exists($level_icon_dir . '/1.png'))
			{
				$config->level_icon_type = 'png';
			}
			elseif (file_exists($level_icon_dir . '/1.svg'))
			{
				$config->level_icon_type = 'svg';
			}
			else
			{
				return new BaseObject(-1, 'msg_level_icon_not_found');
			}
		}

		// Check if downloads are not allowed
		$config->disable_download = ($args->disable_download === 'Y') ? 'Y' : 'N';

		// Check if reading a document is not allowed
		$config->disable_read_document = ($args->disable_read_document === 'Y') ? 'Y' : 'N';
		$config->disable_read_document_except_robots = ($args->disable_read_document_except_robots === 'Y') ? 'Y' : 'N';

		$oMemberModel = getModel('member');
		$group_list = $oMemberModel->getGroups();
		$config->point_group = array();

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
				if($args->{'point_group_'.$group_srl} && $args->{'point_group_'.$group_srl} < 1)
				{
					$args->{'point_group_'.$group_srl} = 1;
				}
				$config->point_group[$group_srl] = $args->{'point_group_'.$group_srl};
			}
		}

		$config->group_reset = $args->group_reset;
		$config->group_ratchet = $args->group_ratchet;

		// Per-level point configurations
		$level_step = array_map('intval', explode(',', $args->level_step ?: '0'));
		$config->level_step = array();
		for($i=1;$i<=$config->max_level;$i++)
		{
			$config->level_step[$i] = isset($level_step[$i - 1]) ? $level_step[$i - 1] : array_last($level_step);
		}

		// A function to calculate per-level points
		$config->expression = $args->expression;
		
		// Save
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

		$configTypeList = array(
			'insert_document', 'insert_comment', 'upload_file', 'download_file', 'read_document',
			'voter', 'blamer', 'voter_comment', 'blamer_comment',
			'download_file_author', 'read_document_author', 'voted', 'blamed', 'voted_comment', 'blamed_comment',
		);
		
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
		if(!$module_srl) throw new Rhymix\Framework\Exceptions\InvalidRequest;
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
			$config['voter'] = (int)Context::get('voter');
			$config['blamer'] = (int)Context::get('blamer');
			$config['voter_comment'] = (int)Context::get('voter_comment');
			$config['blamer_comment'] = (int)Context::get('blamer_comment');
			$config['download_file_author'] = (int)Context::get('download_file_author');
			$config['read_document_author'] = (int)Context::get('read_document_author');
			$config['voted'] = (int)Context::get('voted');
			$config['blamed'] = (int)Context::get('blamed');
			$config['voted_comment'] = (int)Context::get('voted_comment');
			$config['blamed_comment'] = (int)Context::get('blamed_comment');
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

		Rhymix\Framework\Storage::write(\RX_BASEDIR . 'files/cache/pointRecal.txt', $str);

		$this->add('total', count($member));
		$this->add('position', 0);
		$this->setMessage( sprintf(lang('point_recal_message'), 0, $this->get('total')) );
	}

	/**
	 * @brief Apply member points saved by file to units of 5,000 people
	 */
	function procPointAdminApplyPoint()
	{
		$position = (int)Context::get('position');
		$total = (int)Context::get('total');

		if(!file_exists('./files/cache/pointRecal.txt')) throw new Rhymix\Framework\Exceptions\InvalidRequest;

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
		$this->setMessage(sprintf(lang('point_recal_message'), $idx, $total));

	}

	/**
	 * @brief Reset points for each module
	 */
	function procPointAdminReset()
	{
		$module_srl = Context::get('module_srls');
		if(!$module_srl) throw new Rhymix\Framework\Exceptions\InvalidRequest;
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

		Rhymix\Framework\Cache::clearGroup('site_and_module');
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
