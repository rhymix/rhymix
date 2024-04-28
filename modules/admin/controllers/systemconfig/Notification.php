<?php

namespace Rhymix\Modules\Admin\Controllers\SystemConfig;

use Context;
use ModuleModel;
use Rhymix\Framework\Config;
use Rhymix\Framework\Exception;
use Rhymix\Framework\Mail;
use Rhymix\Framework\Push;
use Rhymix\Framework\SMS;
use Rhymix\Framework\Storage;
use Rhymix\Modules\Admin\Controllers\Base;

class Notification extends Base
{
	/**
	 * Display Notification Settings page
	 */
	public function dispAdminConfigNotification()
	{
		// Load advanced mailer module (for lang).
		$oAdvancedMailerAdminView = \Advanced_mailerAdminView::getInstance();

		// Load advanced mailer config.
		$advanced_mailer_config = $oAdvancedMailerAdminView->getConfig();
		Context::set('advanced_mailer_config', $advanced_mailer_config);

		// Load member config.
		$member_config = ModuleModel::getModuleConfig('member');
		Context::set('member_config', $member_config);
		Context::set('webmaster_name', !empty($member_config->webmaster_name) ? $member_config->webmaster_name : 'webmaster');
		Context::set('webmaster_email', $member_config->webmaster_email ?? '');

		// Load module config.
		$module_config = ModuleModel::getModuleConfig('module');
		Context::set('module_config', $module_config);

		// Load mail drivers.
		$mail_drivers = Mail::getSupportedDrivers();
		uasort($mail_drivers, function($a, $b) {
			if ($a['name'] === 'Dummy') return -1;
			if ($b['name'] === 'Dummy') return 1;
			return strnatcasecmp($a['name'], $b['name']);
		});
		Context::set('mail_drivers', $mail_drivers);
		Context::set('mail_driver', config('mail.type') ?: 'mailfunction');

		// Load SMS drivers.
		$sms_drivers = SMS::getSupportedDrivers();
		uasort($sms_drivers, function($a, $b) {
			if ($a['name'] === 'Dummy') return -1;
			if ($b['name'] === 'Dummy') return 1;
			return strnatcasecmp($a['name'], $b['name']);
		});
		Context::set('sms_drivers', $sms_drivers);
		Context::set('sms_driver', config('sms.type') ?: 'dummy');

		// Load Push drivers.
		$push_drivers = Push::getSupportedDrivers();
		uasort($push_drivers, function($a, $b) { return strcmp($a['name'], $b['name']); });
		Context::set('push_drivers', $push_drivers);
		Context::set('push_config', config('push') ?: []);
		$apns_certificate = false;
		if ($apns_certificate_filename = config('push.apns.certificate'))
		{
			$apns_certificate = Storage::read($apns_certificate_filename);
		}
		Context::set('apns_certificate', $apns_certificate);
		$fcmv1_service_account = false;
		if ($fcmv1_service_account_filename = config('push.fcmv1.service_account'))
		{
			$fcmv1_service_account = Storage::read($fcmv1_service_account_filename);
		}
		Context::set('fcmv1_service_account', $fcmv1_service_account);

		// Workaround for compatibility with older version of Amazon SES driver.
		config('mail.ses.api_key', config('mail.ses.api_user'));
		config('mail.ses.api_secret', config('mail.ses.api_pass'));

		$this->setTemplateFile('config_notification');
	}

	/**
	 * Update notification configuration.
	 */
	public function procAdminUpdateNotification()
	{
		$vars = Context::getRequestVars();

		// Load advanced mailer module (for lang).
		$oAdvancedMailerAdminView = \Advanced_mailerAdminView::getInstance();

		// Validate the mail sender's information.
		if (!$vars->mail_default_name)
		{
			throw new Exception('msg_advanced_mailer_sender_name_is_empty');
		}
		if (!$vars->mail_default_from)
		{
			throw new Exception('msg_advanced_mailer_sender_email_is_empty');
		}
		if (!\Mail::isVaildMailAddress($vars->mail_default_from))
		{
			throw new Exception('msg_advanced_mailer_sender_email_is_invalid');
		}
		if ($vars->mail_default_reply_to && !\Mail::isVaildMailAddress($vars->mail_default_reply_to))
		{
			throw new Exception('msg_advanced_mailer_reply_to_is_invalid');
		}

		// Validate the mail driver.
		$mail_drivers = Mail::getSupportedDrivers();
		$mail_driver = $vars->mail_driver;
		if (!array_key_exists($mail_driver, $mail_drivers))
		{
			throw new Exception('msg_advanced_mailer_sending_method_is_invalid');
		}

		// Validate the mail driver settings.
		$mail_driver_config = array();
		foreach ($mail_drivers[$mail_driver]['required'] as $conf_name)
		{
			$conf_value = $vars->{'mail_' . $mail_driver . '_' . $conf_name} ?: null;
			if (!$conf_value)
			{
				throw new Exception('msg_advanced_mailer_smtp_host_is_invalid');
			}
			$mail_driver_config[$conf_name] = $conf_value;
		}

		// Validate the SMS driver.
		$sms_drivers = SMS::getSupportedDrivers();
		$sms_driver = $vars->sms_driver;
		if (!array_key_exists($sms_driver, $sms_drivers))
		{
			throw new Exception('msg_advanced_mailer_sending_method_is_invalid');
		}

		// Validate the SMS driver settings.
		$sms_driver_config = array();
		foreach ($sms_drivers[$sms_driver]['required'] as $conf_name)
		{
			$conf_value = $vars->{'sms_' . $sms_driver . '_' . $conf_name} ?: null;
			if (!$conf_value)
			{
				throw new Exception('msg_advanced_mailer_sms_config_invalid');
			}
			$sms_driver_config[$conf_name] = $conf_value;
		}
		foreach ($sms_drivers[$sms_driver]['optional'] as $conf_name)
		{
			$conf_value = $vars->{'sms_' . $sms_driver . '_' . $conf_name} ?: null;
			$sms_driver_config[$conf_name] = $conf_value;
		}

		// Validate the selected Push drivers.
		$push_config = array('types' => array());
		$push_config['allow_guest_device'] = $vars->allow_guest_device === 'Y' ? true : false;
		$push_drivers = Push::getSupportedDrivers();
		$push_driver_list = $vars->push_driver ?: [];
		foreach ($push_driver_list as $driver_name)
		{
			if (array_key_exists($driver_name, $push_drivers))
			{
				$push_config['types'][$driver_name] = true;
			}
			else
			{
				throw new Exception('msg_advanced_mailer_sending_method_is_invalid');
			}
		}

		// Validate the Push driver settings.
		foreach ($push_drivers as $driver_name => $driver_definition)
		{
			foreach ($push_drivers[$driver_name]['required'] as $conf_name)
			{
				$conf_value = utf8_trim($vars->{'push_' . $driver_name . '_' . $conf_name}) ?: null;
				if (!$conf_value && in_array($driver_name, $push_driver_list))
				{
					throw new Exception('msg_advanced_mailer_push_config_invalid');
				}
				$push_config[$driver_name][$conf_name] = $conf_value;

				// Validate the FCM service account.
				if ($conf_name === 'service_account' && $conf_value !== null)
				{
					$decoded_value = @json_decode($conf_value, true);
					if (!$decoded_value || !isset($decoded_value['project_id']) || !isset($decoded_value['private_key']))
					{
						throw new Exception('msg_advanced_mailer_invalid_fcm_json');
					}
				}

				// Save certificates in a separate file and only store the filename in config.php.
				if ($conf_name === 'certificate' || $conf_name === 'service_account')
				{
					$filename = Config::get('push.' . $driver_name . '.' . $conf_name);
					if (!$filename)
					{
						if ($conf_name === 'certificate')
						{
							$filename = './files/config/' . $driver_name . '/cert-' . \Rhymix\Framework\Security::getRandom(32) . '.pem';
						}
						else
						{
							$filename = './files/config/' . $driver_name . '/pkey-' . \Rhymix\Framework\Security::getRandom(32) . '.json';
						}
					}

					if ($conf_value !== null)
					{
						Storage::write($filename, $conf_value);
						Storage::write('./files/config/' . $driver_name . '/index.html', '<!-- Direct Access Not Allowed -->');
						$push_config[$driver_name][$conf_name] = $filename;
					}
					elseif (Storage::exists($filename))
					{
						Storage::delete($filename);
					}
				}
			}
			foreach ($push_drivers[$driver_name]['optional'] as $conf_name)
			{
				$conf_value = utf8_trim($vars->{'push_' . $driver_name . '_' . $conf_name}) ?: null;
				$push_config[$driver_name][$conf_name] = $conf_value;
			}
		}

		// Save advanced mailer config.
		getController('module')->updateModuleConfig('advanced_mailer', (object)array(
			'sender_name' => trim($vars->mail_default_name),
			'sender_email' => trim($vars->mail_default_from),
			'force_sender' => toBool($vars->mail_force_default_sender),
			'reply_to' => trim($vars->mail_default_reply_to),
		));

		// Save member config.
		getController('module')->updateModuleConfig('member', (object)array(
			'webmaster_name' => trim($vars->mail_default_name),
			'webmaster_email' => trim($vars->mail_default_from),
		));

		// Save system config.
		Config::set("mail.default_name", trim($vars->mail_default_name));
		Config::set("mail.default_from", trim($vars->mail_default_from));
		Config::set("mail.default_force", toBool($vars->mail_force_default_sender));
		Config::set("mail.default_reply_to", trim($vars->mail_default_reply_to));
		Config::set("mail.type", $mail_driver);
		Config::set("mail.$mail_driver", $mail_driver_config);
		Config::set("sms.default_from", trim($vars->sms_default_from));
		Config::set("sms.default_force", toBool($vars->sms_force_default_sender));
		Config::set("sms.type", $sms_driver);
		Config::set("sms.$sms_driver", $sms_driver_config);
		Config::set("sms.allow_split.sms", toBool($vars->allow_split_sms));
		Config::set("sms.allow_split.lms", toBool($vars->allow_split_lms));
		Config::set("push", $push_config);
		if (!Config::save())
		{
			throw new Exception('msg_failed_to_save_config');
		}

		$this->setMessage('success_updated');
		$this->setRedirectUrl(Context::get('success_return_url') ?: getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdminConfigNotification'));
	}
}
