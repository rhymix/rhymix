<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  spamfilter
 * @author NAVER (developers@xpressengine.com)
 * @brief The parent class of the spamfilter module
 */
class Spamfilter extends ModuleObject
{
	/**
	 * @brief Additional tasks required to accomplish during the installation
	 */
	public function moduleInstall()
	{

	}

	/**
	 * @brief A method to check if the installation has been successful
	 */
	public function checkUpdate()
	{
		$oDB = DB::getInstance();
		if(!$oDB->isColumnExists('spamfilter_denied_word', 'hit')) return true;
		if(!$oDB->isColumnExists('spamfilter_denied_word', 'latest_hit')) return true;
		if(!$oDB->isColumnExists('spamfilter_denied_word', 'except_member')) return true;
		if(!$oDB->isColumnExists('spamfilter_denied_word', 'filter_html')) return true;
		if(!$oDB->isColumnExists('spamfilter_denied_word', 'is_regexp')) return true;
		if(!$oDB->isColumnExists('spamfilter_denied_word', 'description')) return true;
		if(!$oDB->isColumnExists('spamfilter_denied_ip', 'hit')) return true;
		if(!$oDB->isColumnExists('spamfilter_denied_ip', 'latest_hit')) return true;
		if(!$oDB->isColumnExists('spamfilter_denied_ip', 'except_member')) return true;
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
		$oDB = DB::getInstance();
		if(!$oDB->isColumnExists('spamfilter_denied_word', 'hit'))
		{
			$oDB->addColumn('spamfilter_denied_word', 'hit', 'number', null, 0, true, 'word');
			$oDB->addIndex('spamfilter_denied_word','idx_hit', 'hit');
		}
		if(!$oDB->isColumnExists('spamfilter_denied_word', 'latest_hit'))
		{
			$oDB->addColumn('spamfilter_denied_word', 'latest_hit', 'date', null, null, false, 'hit');
			$oDB->addIndex('spamfilter_denied_word','idx_latest_hit', 'latest_hit');
		}
		if(!$oDB->isColumnExists('spamfilter_denied_word', 'except_member'))
		{
			$oDB->addColumn('spamfilter_denied_word', 'except_member', 'char', 1, 'N', true, 'latest_hit');
		}
		if(!$oDB->isColumnExists('spamfilter_denied_word', 'filter_html'))
		{
			$oDB->addColumn('spamfilter_denied_word', 'filter_html', 'char', 1, 'N', true, 'except_member');
		}
		if(!$oDB->isColumnExists('spamfilter_denied_word', 'is_regexp'))
		{
			$oDB->addColumn('spamfilter_denied_word', 'is_regexp', 'char', 1, 'N', true, 'filter_html');
			$oDB->query('UPDATE spamfilter_denied_word SET is_regexp = ? WHERE word LIKE ?', ['Y', '/%/']);
		}
		if(!$oDB->isColumnExists('spamfilter_denied_word', 'description'))
		{
			$oDB->addColumn('spamfilter_denied_word', 'description', 'varchar', 191, null, false, 'is_regexp');
		}
		if(!$oDB->isColumnExists('spamfilter_denied_ip', 'hit'))
		{
			$oDB->addColumn('spamfilter_denied_ip', 'hit', 'number', null, 0, true, 'ipaddress');
			$oDB->addIndex('spamfilter_denied_ip','idx_hit', 'hit');
		}
		if(!$oDB->isColumnExists('spamfilter_denied_ip', 'latest_hit'))
		{
			$oDB->addColumn('spamfilter_denied_ip', 'latest_hit', 'date', null, null, false, 'hit');
			$oDB->addIndex('spamfilter_denied_ip','idx_latest_hit', 'latest_hit');
		}
		if(!$oDB->isColumnExists('spamfilter_denied_ip', 'except_member'))
		{
			$oDB->addColumn('spamfilter_denied_ip', 'except_member', 'char', 1, 'N', true, 'latest_hit');
		}
		if(!$oDB->isColumnExists('spamfilter_denied_ip', 'description'))
		{
			$oDB->addColumn('spamfilter_denied_ip', 'description', 'varchar', 191, null, false, 'except_member');
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

			$output = ModuleController::getInstance()->insertModuleConfig($this->module, $config);
			if (!$output->toBool())
			{
				return $output;
			}
		}
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

		$oAddonAdminController = AddonAdminController::getInstance();
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
