<?php

/**
 * @file advanced_mailer.class.php
 * @author Kijin Sung <kijin@kijinsung.com>
 * @license GPLv2 or Later <https://www.gnu.org/licenses/gpl-2.0.html>
 * @brief Advanced Mailer Main Class
 */
class Advanced_Mailer extends ModuleObject
{
	/**
	 * Get the configuration of the current module.
	 */
	public function getConfig()
	{
		$config = getModel('module')->getModuleConfig('advanced_mailer');
		if (!is_object($config))
		{
			$config = new stdClass();
		}
		
		if (isset($config->is_enabled) || isset($config->sending_method) || isset($config->send_type))
		{
			$config = $this->migrateConfig($config);
			getController('module')->insertModuleConfig('advanced_mailer', $config);
		}
		
		return $config;
	}
	
	/**
	 * Migrate from previous configuration format.
	 */
	public function migrateConfig($config)
	{
		$systemconfig = array();
		
		if (isset($config->sending_method))
		{
			$systemconfig['mail.type'] = $config->sending_method;
		}
		elseif (isset($config->send_type))
		{
			$systemconfig['mail.type'] = $config->send_type;
		}
		if ($systemconfig['mail.type'] === 'mail')
		{
			$systemconfig['mail.type'] = 'mailfunction';
		}
		
		if (isset($config->username))
		{
			if (in_array('username', $this->sending_methods[$config->sending_method]['conf']))
			{
				$config->{$config->sending_method . '_username'} = $config->username;
			}
			unset($config->username);
		}
		
		if (isset($config->password))
		{
			if (in_array('password', $this->sending_methods[$config->sending_method]['conf']))
			{
				$config->{$config->sending_method . '_password'} = $config->password;
			}
			unset($config->password);
		}
		
		if (isset($config->domain))
		{
			if (in_array('domain', $this->sending_methods[$config->sending_method]['conf']))
			{
				$config->{$config->sending_method . '_domain'} = $config->domain;
			}
			unset($config->domain);
		}
		
		if (isset($config->api_key))
		{
			if (in_array('api_key', $this->sending_methods[$config->sending_method]['conf']))
			{
				$config->{$config->sending_method . '_api_key'} = $config->api_key;
			}
			unset($config->api_key);
		}
		
		if (isset($config->account_type))
		{
			if (in_array('account_type', $this->sending_methods[$config->sending_method]['conf']))
			{
				$config->{$config->sending_method . '_account_type'} = $config->account_type;
			}
			unset($config->account_type);
		}
		
		if (isset($config->aws_region))
		{
			$config->ses_region = $config->aws_region;
			unset($config->aws_region);
		}
		
		if (isset($config->aws_access_key))
		{
			$config->ses_access_key = $config->aws_access_key;
			unset($config->aws_access_key);
		}
		
		if (isset($config->aws_secret_key))
		{
			$config->ses_secret_key = $config->aws_secret_key;
			unset($config->aws_secret_key);
		}
		
		$mail_drivers = Rhymix\Framework\Mail::getSupportedDrivers();
		foreach ($mail_drivers as $driver_name => $driver_definition)
		{
			foreach ($config as $key => $value)
			{
				if (strncmp($key, $driver_name . '_', strlen($driver_name) + 1) === 0)
				{
					$subkey = substr($key, strlen($driver_name) + 1);
					switch ($subkey)
					{
						case 'host':
						case 'port':
						case 'security':
							$systemconfig["mail.$driver_name.smtp_" . $subkey] = $value;
							break;
						case 'username':
						case 'password':
							$systemconfig["mail.$driver_name." . ($driver_name === 'smtp' ? 'smtp_' : 'api_') . substr($subkey, 0, 4)] = $value;
							break;
						case 'account_type':
						case 'region':
							$systemconfig["mail.$driver_name.api_type"] = $value;
							break;
						case 'access_key':
							$systemconfig["mail.$driver_name.api_key"] = $value;
							break;
						case 'secret_key':
							$systemconfig["mail.$driver_name.api_secret"] = $value;
							break;
						case 'domain':
							$systemconfig["mail.$driver_name.api_domain"] = $value;
							break;
						case 'api_key':
							$systemconfig["mail.$driver_name.api_token"] = $value;
							break;
						default:
							break;
					}
					unset($config->$key);
				}
			}
		}
		
		if (count($systemconfig))
		{
			foreach ($systemconfig as $key => $value)
			{
				Rhymix\Framework\Config::set($key, $value);
			}
			Rhymix\Framework\Config::save();
		}
		
		unset($config->is_enabled);
		unset($config->sending_method);
		unset($config->send_type);
		$config->log_sent_mail = toBool($config->log_sent_mail);
		$config->log_errors = toBool($config->log_errors);
		$config->force_sender = toBool($config->force_sender);
		if (!isset($config->exceptions))
		{
			$config->exceptions = array();
		}
		
		return $config;
	}
	
	/**
	 * Register triggers.
	 */
	public function registerTriggers()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');
		if ($oModuleModel->getTrigger('moduleHandler.init', 'advanced_mailer', 'model', 'triggerReplaceMailClass', 'before'))
		{
			$oModuleController->deleteTrigger('moduleHandler.init', 'advanced_mailer', 'model', 'triggerReplaceMailClass', 'before');
		}
		if (!$oModuleModel->getTrigger('mail.send', 'advanced_mailer', 'controller', 'triggerBeforeMailSend', 'before'))
		{
			$oModuleController->insertTrigger('mail.send', 'advanced_mailer', 'controller', 'triggerBeforeMailSend', 'before');
		}
		if (!$oModuleModel->getTrigger('mail.send', 'advanced_mailer', 'controller', 'triggerAfterMailSend', 'after'))
		{
			$oModuleController->insertTrigger('mail.send', 'advanced_mailer', 'controller', 'triggerAfterMailSend', 'after');
		}
		if (!$oModuleModel->getTrigger('sms.send', 'advanced_mailer', 'controller', 'triggerAfterSMSSend', 'after'))
		{
			$oModuleController->insertTrigger('sms.send', 'advanced_mailer', 'controller', 'triggerAfterSMSSend', 'after');
		}
		if (!$oModuleModel->getTrigger('push.send', 'advanced_mailer', 'controller', 'triggerAfterPushSend', 'after'))
		{
			$oModuleController->insertTrigger('push.send', 'advanced_mailer', 'controller', 'triggerAfterPushSend', 'after');
		}
	}
	
	/**
	 * Install.
	 */
	public function moduleInstall()
	{
		$this->registerTriggers();
	}
	
	/**
	 * Check update.
	 */
	public function checkUpdate()
	{
		$oModuleModel = getModel('module');
		if ($oModuleModel->getTrigger('moduleHandler.init', 'advanced_mailer', 'model', 'triggerReplaceMailClass', 'before'))
		{
			return true;
		}
		if (!$oModuleModel->getTrigger('mail.send', 'advanced_mailer', 'controller', 'triggerBeforeMailSend', 'before'))
		{
			return true;
		}
		if (!$oModuleModel->getTrigger('mail.send', 'advanced_mailer', 'controller', 'triggerAfterMailSend', 'after'))
		{
			return true;
		}
		if (!$oModuleModel->getTrigger('sms.send', 'advanced_mailer', 'controller', 'triggerAfterSMSSend', 'after'))
		{
			return true;
		}
		if (!$oModuleModel->getTrigger('push.send', 'advanced_mailer', 'controller', 'triggerAfterPushSend', 'after'))
		{
			return true;
		}
		return false;
	}
	
	/**
	 * Update.
	 */
	public function moduleUpdate()
	{
		$this->registerTriggers();
	}
	
	public function recompileCache()
	{
		// no-op
	}
}
