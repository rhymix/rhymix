<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  spamfilter
 * @author NAVER (developers@xpressengine.com)
 * @brief The parent class of the spamfilter module
 */
class spamfilter extends ModuleObject
{
	protected static $_insert_triggers = array(
		array('document.insertDocument', 'before', 'controller', 'triggerInsertDocument'),
		array('document.updateDocument', 'before', 'controller', 'triggerInsertDocument'),
		array('document.manage', 'before', 'controller', 'triggerManageDocument'),
		array('comment.insertComment', 'before', 'controller', 'triggerInsertComment'),
		array('comment.updateComment', 'before', 'controller', 'triggerInsertComment'),
		array('communication.sendMessage', 'before', 'controller', 'triggerSendMessage'),
		array('moduleObject.proc', 'before', 'controller', 'triggerCheckCaptcha'),
	);
	
	protected static $_delete_triggers = array(
		array('trackback.insertTrackback', 'before', 'controller', 'triggerInsertTrackback'),
	);
	
	/**
	 * Register all triggers.
	 * 
	 * @return object
	 */
	public function registerTriggers()
	{
		$oModuleController = getController('module');
		foreach (self::$_insert_triggers as $trigger)
		{
			if (!ModuleModel::getTrigger($trigger[0], $this->module, $trigger[2], $trigger[3], $trigger[1]))
			{
				$oModuleController->insertTrigger($trigger[0], $this->module, $trigger[2], $trigger[3], $trigger[1]);
			}
		}
		foreach (self::$_delete_triggers as $trigger)
		{
			if (ModuleModel::getTrigger($trigger[0], $this->module, $trigger[2], $trigger[3], $trigger[1]))
			{
				$oModuleController->deleteTrigger($trigger[0], $this->module, $trigger[2], $trigger[3], $trigger[1]);
			}
		}
		return new BaseObject(0, 'success_updated');
	}
	
	/**
	 * @brief Additional tasks required to accomplish during the installation
	 */
	public function moduleInstall()
	{
		return $this->registerTriggers();
	}

	/**
	 * @brief A method to check if the installation has been successful
	 */
	public function checkUpdate()
	{
		foreach (self::$_insert_triggers as $trigger)
		{
			if (!ModuleModel::getTrigger($trigger[0], $this->module, $trigger[2], $trigger[3], $trigger[1]))
			{
				return true;
			}
		}
		foreach (self::$_delete_triggers as $trigger)
		{
			if (ModuleModel::getTrigger($trigger[0], $this->module, $trigger[2], $trigger[3], $trigger[1]))
			{
				return true;
			}
		}
		
		$oDB = DB::getInstance();
		if(!$oDB->isColumnExists('spamfilter_denied_word', 'hit')) return true;
		if(!$oDB->isColumnExists('spamfilter_denied_word', 'latest_hit')) return true;
		if(!$oDB->isColumnExists('spamfilter_denied_ip', 'hit')) return true;
		if(!$oDB->isColumnExists('spamfilter_denied_ip', 'latest_hit')) return true;
		if(!$oDB->isColumnExists('spamfilter_denied_ip', 'description')) return true;
		
		$config = ModuleModel::getModuleConfig('spamfilter') ?: new stdClass;
		if (!isset($config->captcha))
		{
			return true;
		}
		
		return false;
	}

	/**
	 * @brief Execute update
	 */
	public function moduleUpdate()
	{
		$output = $this->registerTriggers();
		if (!$output->toBool())
		{
			return $output;
		}
		
		$oDB = DB::getInstance();
		if(!$oDB->isColumnExists('spamfilter_denied_word', 'hit'))
		{
			$oDB->addColumn('spamfilter_denied_word','hit','number',12,0,true);
			$oDB->addIndex('spamfilter_denied_word','idx_hit', 'hit');
		}
		if(!$oDB->isColumnExists('spamfilter_denied_word', 'latest_hit'))
		{
			$oDB->addColumn('spamfilter_denied_word','latest_hit','date');
			$oDB->addIndex('spamfilter_denied_word','idx_latest_hit', 'latest_hit');
		}
		if(!$oDB->isColumnExists('spamfilter_denied_ip', 'hit'))
		{
			$oDB->addColumn('spamfilter_denied_ip','hit','number',12,0,true);
			$oDB->addIndex('spamfilter_denied_ip','idx_hit', 'hit');
		}
		if(!$oDB->isColumnExists('spamfilter_denied_ip', 'latest_hit'))
		{
			$oDB->addColumn('spamfilter_denied_ip','latest_hit','date');
			$oDB->addIndex('spamfilter_denied_ip','idx_latest_hit', 'latest_hit');
		}
		if(!$oDB->isColumnExists('spamfilter_denied_ip', 'description'))
		{
			$oDB->addColumn('spamfilter_denied_ip','description','varchar', 250);
		}
		
		$config = ModuleModel::getModuleConfig('spamfilter') ?: new stdClass;
		if (!isset($config->captcha))
		{
			$config = is_object($config) ? $config : new stdClass;
			$recaptcha_config = AddonModel::getAddonConfig('recaptcha');
			if ($recaptcha_config)
			{
				$config->captcha = $this->_importRecaptchaConfig($recaptcha_config);
			}
			else
			{
				$config->captcha = new stdClass;
				$config->captcha->type = 'none';
			}
			
			$output = getController('module')->insertModuleConfig($this->module, $config);
			if (!$output->toBool())
			{
				return $output;
			}
		}
	}

	/**
	 * @brief Re-generate the cache file
	 */
	public function recompileCache()
	{
		
	}
	
	/**
	 * Import configuration from reCAPTCHA addon.
	 */
	protected function _importRecaptchaConfig($config)
	{
		$output = new stdClass;
		$output->type = 'none';
		if (!isset($config->site_key) || !isset($config->secret_key))
		{
			return $output;
		}
		
		if ($config->use_pc === 'Y' || $config->use_mobile === 'Y')
		{
			$output->type = 'recaptcha';
		}
		$output->site_key = $config->site_key;
		$output->secret_key = $config->secret_key;
		$output->theme = $config->theme;
		$output->size = $config->size;
		$output->target_devices = [
			'pc' => $config->use_pc === 'Y',
			'mobile' => $config->use_mobile === 'Y',
		];
		$output->target_users = $config->target_users;
		$output->target_frequency = $config->target_frequency;
		$output->target_actions = [];
		foreach (['signup', 'login', 'recovery', 'document', 'comment'] as $action)
		{
			$output->target_actions[$action] = ($config->{'use_' . $action} === 'Y') ? true : false;
		}
		$output->target_modules = [];
		foreach ($config->mid_list as $mid)
		{
			$module_srl = ModuleModel::getModuleInfoByMid($mid)->module_srl;
			$output->target_modules[$module_srl] = true;
		}
		$output->target_modules_type = ($config->xe_run_method === 'run_selected') ? '+' : '-';
		
		$oAddonAdminController = getAdminController('addon');
		if ($output->target_devices['pc'])
		{
			$oAddonAdminController->doDeactivate('recaptcha', 0, 'pc');
			$oAddonAdminController->makeCacheFile(0, 'pc');
		}
		if ($output->target_devices['mobile'])
		{
			$oAddonAdminController->doDeactivate('recaptcha', 0, 'mobile');
			$oAddonAdminController->makeCacheFile(0, 'mobile');
		}
		
		return $output;
	}
}
/* End of file spamfilter.class.php */
/* Location: ./modules/spamfilter/spamfilter.class.controller.php */
