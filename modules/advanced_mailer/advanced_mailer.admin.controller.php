<?php

/**
 * @file advanced_mailer.admin.controller.php
 * @author Kijin Sung <kijin@kijinsung.com>
 * @license GPLv2 or Later <https://www.gnu.org/licenses/gpl-2.0.html>
 * @brief Advanced Mailer Admin Controller
 */
class Advanced_MailerAdminController extends Advanced_Mailer
{
	/**
	 * Save the basic configuration.
	 */
	public function procAdvanced_MailerAdminInsertConfig()
	{
		// Get and validate the new configuration.
		$vars = Context::getRequestVars();
		
		// Update the current module's configuration.
		$config = $this->getConfig();
		$config->log_sent_mail = toBool($vars->log_sent_mail);
		$config->log_errors = toBool($vars->log_errors);
		$config->log_sent_sms = toBool($vars->log_sent_sms);
		$config->log_sms_errors = toBool($vars->log_sms_errors);
		$config->log_sent_push = toBool($vars->log_sent_push);
		$config->log_push_errors = toBool($vars->log_push_errors);
		$output = getController('module')->insertModuleConfig('advanced_mailer', $config);
		if ($output->toBool())
		{
			$this->setMessage('success_registed');
		}
		else
		{
			return $output;
		}
		
		if (Context::get('success_return_url'))
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdvanced_mailerAdminConfig'));
		}
	}
	
	/**
	 * Save the exception configuration.
	 */
	public function procAdvanced_MailerAdminInsertExceptions()
	{
		// Get the current configuration.
		$config = $this->getConfig();
		$sending_methods = Rhymix\Framework\Mail::getSupportedDrivers();
		
		// Get and validate the list of exceptions.
		$exceptions = array();
		for ($i = 1; $i <= 3; $i++)
		{
			$method = strval(Context::get('exception_' . $i . '_method'));
			$domains = trim(Context::get('exception_' . $i . '_domains'));
			if ($method !== '' && $domains !== '')
			{
				if ($method !== 'default' && !isset($sending_methods[$method]))
				{
					throw new Rhymix\Framework\Exception('msg_advanced_mailer_sending_method_is_invalid');
				}
				if ($method !== 'default')
				{
					foreach ($this->sending_methods[$method]['required'] as $conf_name)
					{
						if (!Rhymix\Framework\Config::get("mail.$method.$conf_name"))
						{
							throw new Rhymix\Framework\Exception(sprintf('msg_advanced_mailer_sending_method_is_not_configured', lang('cmd_advanced_mailer_sending_method_' . $method)));
						}
					}
				}
				$exceptions[$i]['method'] = $method;
				$exceptions[$i]['domains'] = array();
				
				$domains = array_map('trim', preg_split('/[,\n]/', $domains, null, PREG_SPLIT_NO_EMPTY));
				foreach ($domains as $domain)
				{
					if (strpos($domain, 'xn--') !== false)
					{
						$domain = Rhymix\Framework\URL::decodeIdna($domain);
					}
					$exceptions[$i]['domains'][] = $domain;
				}
			}
		}
		
		// Save the new configuration.
		$config->exceptions = $exceptions;
		$output = getController('module')->insertModuleConfig('advanced_mailer', $config);
		if ($output->toBool())
		{
			$this->setMessage('success_registed');
		}
		else
		{
			return $output;
		}
		
		if (Context::get('success_return_url'))
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdvanced_mailerAdminExceptions'));
		}
	}
	
	/**
	 * Check the DNS record of a domain.
	 */
	public function procAdvanced_MailerAdminCheckDNSRecord()
	{
		$check_config = Context::gets('hostname', 'record_type');
		if (!preg_match('/^[a-z0-9_.-]+$/', $check_config->hostname))
		{
			$this->add('record_content', false);
			return;
		}
		if (!defined('DNS_' . $check_config->record_type))
		{
			$this->add('record_content', false);
			return;
		}
		
		$records = @dns_get_record($check_config->hostname, constant('DNS_' . $check_config->record_type));
		if ($records === false)
		{
			$this->add('record_content', false);
			return;
		}
		
		$return_values = array();
		foreach ($records as $record)
		{
			if (isset($record[strtolower($check_config->record_type)]))
			{
				$return_values[] = $record[strtolower($check_config->record_type)];
			}
		}
		$this->add('record_content', implode("\n\n", $return_values));
		return;
	}
	
	/**
	 * Clear old mail sending log.
	 */
	public function procAdvanced_mailerAdminClearSentMail()
	{
		$status = Context::get('status');
		$clear_before_days = intval(Context::get('clear_before_days'));
		if (!in_array($status, array('success', 'error')))
		{
			$status = null;
		}
		if ($clear_before_days < 0)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		
		$obj = new stdClass();
		$obj->status = $status;
		$obj->regdate = date('YmdHis', time() - ($clear_before_days * 86400) + zgap());
		$output = executeQuery('advanced_mailer.deleteMailLogs', $obj);
		
		$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdvanced_mailerAdminMailLog', 'status', $status));
	}
	
	/**
	 * Clear old SMS sending log.
	 */
	public function procAdvanced_mailerAdminClearSentSMS()
	{
		$status = Context::get('status');
		$clear_before_days = intval(Context::get('clear_before_days'));
		if (!in_array($status, array('success', 'error')))
		{
			$status = null;
		}
		if ($clear_before_days < 0)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		
		$obj = new stdClass();
		$obj->status = $status;
		$obj->regdate = date('YmdHis', time() - ($clear_before_days * 86400) + zgap());
		$output = executeQuery('advanced_mailer.deleteSMSLogs', $obj);
		
		$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdvanced_mailerAdminSMSLog', 'status', $status));
	}
	
	/**
	 * Clear old Push sending log.
	 */
	public function procAdvanced_mailerAdminClearSentPush()
	{
		$status = Context::get('status');
		$clear_before_days = intval(Context::get('clear_before_days'));
		if (!in_array($status, array('success', 'error')))
		{
			$status = null;
		}
		if ($clear_before_days < 0)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		
		$obj = new stdClass();
		$obj->status = $status;
		$obj->regdate = date('YmdHis', time() - ($clear_before_days * 86400) + zgap());
		$output = executeQuery('advanced_mailer.deletePushLogs', $obj);
		
		$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdvanced_mailerAdminPushLog', 'status', $status));
	}
	
	/**
	 * Send a test mail.
	 */
	public function procAdvanced_MailerAdminTestSendMail()
	{
		$recipient_config = Context::gets('recipient_name', 'recipient_email');
		$recipient_name = $recipient_config->recipient_name;
		$recipient_email = $recipient_config->recipient_email;
		
		if (!$recipient_name)
		{
			$this->add('test_result', 'Error: ' . Context::getLang('msg_advanced_mailer_recipient_name_is_empty'));
			return;
		}
		if (!$recipient_email)
		{
			$this->add('test_result', 'Error: ' . Context::getLang('msg_advanced_mailer_recipient_email_is_empty'));
			return;
		}
		if (!Mail::isVaildMailAddress($recipient_email))
		{
			$this->add('test_result', 'Error: ' . Context::getLang('msg_advanced_mailer_recipient_email_is_invalid'));
			return;
		}
		
		$oAdvancedMailerController = getController('advanced_mailer');
		$sending_method = $oAdvancedMailerController->getSendingMethodForEmailAddress($recipient_email) ?: config('mail.type');
		
		try
		{
			$oMail = new Rhymix\Framework\Mail();
			$oMail->setTitle('Advanced Mailer Test : ' . strtoupper($sending_method));
			$oMail->setContent('<p>This is a <b>test email</b> from Advanced Mailer.</p><p>Thank you for trying Advanced Mailer.</p>' .
				'<p>고급 메일 발송 모듈 <b>테스트</b> 메일입니다.</p><p>메일이 정상적으로 발송되고 있습니다.</p>');
			$oMail->addTo($recipient_email, $recipient_name);
			$result = $oMail->send();
			
			if (!$result)
			{
				if (count($oMail->getErrors()))
				{
					if (config('mail.type') === 'smtp')
					{
						if (strpos(config('mail.smtp.smtp_host'), 'gmail.com') !== false && strpos(implode("\n", $oMail->getErrors()), 'code "535"') !== false)
						{
							$this->add('test_result', Context::getLang('msg_advanced_mailer_google_account_security'));
							return;
						}
						if (strpos(config('mail.smtp.smtp_host'), 'naver.com') !== false && strpos(implode("\n", $oMail->getErrors()), 'Failed to authenticate') !== false)
						{
							$this->add('test_result', Context::getLang('msg_advanced_mailer_naver_smtp_disabled'));
							return;
						}
					}
					
					$this->add('test_result', nl2br(htmlspecialchars(implode("\n", $oMail->getErrors()))));
					return;
				}
				else
				{
					$this->add('test_result', Context::getLang('msg_advanced_mailer_unknown_error'));
					return;
				}
			}
		}
		catch (Exception $e)
		{
			$this->add('test_result', nl2br(htmlspecialchars($e->getMessage())));
			return;
		}
		
		$this->add('test_result', Context::getLang('msg_advanced_mailer_test_success'));
		return;
	}
	
	/**
	 * Send a test SMS.
	 */
	public function procAdvanced_MailerAdminTestSendSMS()
	{
		$recipient_number = Context::get('recipient_number');
		$country_code = intval(Context::get('country_code'));
		$content = trim(Context::get('content'));
		
		if (!$recipient_number)
		{
			$this->add('test_result', 'Error: ' . Context::getLang('msg_advanced_mailer_recipient_number_is_empty'));
			return;
		}
		if (!$content)
		{
			$this->add('test_result', 'Error: ' . Context::getLang('msg_advanced_mailer_content_is_empty'));
			return;
		}
		
		try
		{
			$oSMS = new Rhymix\Framework\SMS();
			$oSMS->addTo($recipient_number, $country_code);
			$oSMS->setBody($content);
			$result = $oSMS->send();
			
			if (!$result)
			{
				if (count($oSMS->getErrors()))
				{
					$this->add('test_result', nl2br(htmlspecialchars(implode("\n", $oSMS->getErrors()))));
					return;
				}
				else
				{
					$this->add('test_result', Context::getLang('msg_advanced_mailer_unknown_error'));
					return;
				}
			}
		}
		catch (Exception $e)
		{
			$this->add('test_result', nl2br(htmlspecialchars($e->getMessage())));
			return;
		}
		
		$this->add('test_result', Context::getLang('msg_advanced_mailer_test_success_sms'));
		return;
	}
	
	/**
	 * Send a test Push Notification.
	 */
	public function procAdvanced_MailerAdminTestSendPush()
	{
		$recipient_user_id = Context::get('recipient_user_id');
		$subject = trim(Context::get('subject'));
		$content = trim(Context::get('content'));
		$url = trim(Context::get('url'));
		
		$member_info = MemberModel::getMemberInfoByUserID($recipient_user_id);
		if (!$member_info || !$member_info->member_srl)
		{
			$this->add('test_result', 'Error: ' . Context::getLang('msg_advanced_mailer_recipient_user_id_not_found'));
			return;
		}
		
		$args = new stdClass;
		$args->member_srl = $member_info->member_srl;
		$output = executeQueryArray('member.getMemberDeviceTokensByMemberSrl', $args);
		if (!$output->toBool() || !count($output->data))
		{
			$this->add('test_result', 'Error: ' . Context::getLang('msg_advanced_mailer_recipient_has_no_devices'));
			return;
		}
		
		if (!$subject)
		{
			$this->add('test_result', 'Error: ' . Context::getLang('msg_advanced_mailer_subject_is_empty'));
			return;
		}
		if (!$content)
		{
			$this->add('test_result', 'Error: ' . Context::getLang('msg_advanced_mailer_content_is_empty'));
			return;
		}
		if (!$url || !Rhymix\Framework\URL::isInternalURL($url))
		{
			$this->add('test_result', 'Error: ' . Context::getLang('msg_advanced_mailer_url_is_invalid'));
			return;
		}
		
		try
		{
			$oPush = new Rhymix\Framework\Push;
			$oPush->addTo($member_info->member_srl);
			$oPush->setSubject($subject);
			$oPush->setContent($content);
			$oPush->setURL($url);
			$result = $oPush->send();
			
			if (!$result)
			{
				if (count($oPush->getErrors()))
				{
					$this->add('test_result', nl2br(htmlspecialchars(implode("\n", $oPush->getErrors()))));
					return;
				}
				else
				{
					$this->add('test_result', Context::getLang('msg_advanced_mailer_unknown_error'));
					return;
				}
			}
		}
		catch (Exception $e)
		{
			$this->add('test_result', nl2br(htmlspecialchars($e->getMessage())));
			return;
		}
		
		$this->add('test_result', Context::getLang('msg_advanced_mailer_test_success_sms'));
		return;
	}
}
