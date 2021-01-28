<?php

/**
 * @file advanced_mailer.controller.php
 * @author Kijin Sung <kijin@kijinsung.com>
 * @license GPLv2 or Later <https://www.gnu.org/licenses/gpl-2.0.html>
 * @brief Advanced Mailer Model
 */
class Advanced_MailerController extends Advanced_Mailer
{
	/**
	 * Before mail send trigger.
	 */
	public function triggerBeforeMailSend($mail)
	{
		$config = $this->getConfig();
		
		$recipients = $mail->message->getTo() ?: array();
		if ($recipients)
		{
			$first_recipient = array_first_key($recipients);
			if ($exception_driver = $this->getSendingMethodForEmailAddress($first_recipient, $config))
			{
				$driver_class = '\\Rhymix\\Framework\\Drivers\Mail\\' . $exception_driver;
				if (class_exists($driver_class))
				{
					$mail->driver = $driver_class::getInstance(config("mail.$exception_driver"));
				}
			}
		}
		
		if (!$mail->getFrom())
		{
			list($default_from, $default_name) = $this->getDefaultEmailIdentity();
			$mail->setFrom($default_from, $default_name);
			if ($replyTo = config('mail.default_reply_to'))
			{
				$mail->setReplyTo($replyTo);
			}
		}
		elseif (toBool($config->force_sender ?? 'N'))
		{
			if (stripos($mail->driver->getName(), 'woorimail') !== false && config('mail.woorimail.api_type') === 'free')
			{
				// no-op
			}
			else
			{
				$sender = $mail->message->getFrom();
				$original_sender_email = $sender ? array_first_key($sender) : null;
				$original_sender_name = $sender ? array_first($sender) : null;
				list($default_from, $default_name) = $this->getDefaultEmailIdentity();
				if ($original_sender_email !== $default_from)
				{
					$mail->setFrom($default_from, $original_sender_name ?: $default_name);
					$mail->setReplyTo($original_sender_email);
				}
			}
		}
	}
	
	/**
	 * After mail send trigger.
	 */
	public function triggerAfterMailSend($mail)
	{
		$config = $this->getConfig();
		
		if (toBool($config->log_sent_mail ?? 'N') || (toBool($config->log_errors ?? 'N') && count($mail->errors)))
		{
			$obj = new \stdClass();
			$obj->mail_from = '';
			$obj->mail_to = '';
			
			if ($real_sender = $mail->message->getFrom())
			{
				foreach($real_sender as $email => $name)
				{
					$obj->mail_from .= (strval($name) !== '' ? "$name <$email>" : $email) . "\n";
				}
			}
			
			if ($real_to = $mail->message->getTo())
			{
				foreach($real_to as $email => $name)
				{
					$obj->mail_to .= (strval($name) !== '' ? "$name <$email>" : $email) . "\n";
				}
			}
			
			if ($real_cc = $mail->message->getCc())
			{
				foreach($real_cc as $email => $name)
				{
					$obj->mail_to .= (strval($name) !== '' ? "$name <$email>" : $email) . "\n";
				}
			}
			
			if ($real_bcc = $mail->message->getBcc())
			{
				foreach($real_bcc as $email => $name)
				{
					$obj->mail_to .= (strval($name) !== '' ? "$name <$email>" : $email) . "\n";
				}
			}
			
			$obj->mail_from = trim($obj->mail_from);
			$obj->mail_to = trim($obj->mail_to);
			$obj->subject = $mail->message->getSubject();
			$obj->calling_script = $mail->getCaller();
			$obj->sending_method = strtolower(class_basename($mail->driver));
			$obj->status = !count($mail->getErrors()) ? 'success' : 'error';
			$obj->errors = count($mail->getErrors()) ? implode("\n", $mail->getErrors()) : null;
			$output = executeQuery('advanced_mailer.insertMailLog', $obj);
			if (!$output->toBool())
			{
				return $output;
			}
		}
	}
	
	/**
	 * Get the default identity for sending email.
	 * 
	 * @return array
	 */
	public function getDefaultEmailIdentity()
	{
		$email = config('mail.default_from');
		$name = config('mail.default_name');
		if (!$email)
		{
			$member_config = getModel('module')->getModuleConfig('member');
			$email = $member_config->webmaster_email;
			$name = $member_config->webmaster_name ?: 'webmaster';
		}
		
		return [$email, $name];
	}
	
	/**
	 * Check if an email address is on a list of exceptions.
	 * 
	 * @param string $email
	 * @param object $config (optional)
	 * @return string|null
	 */
	public function getSendingMethodForEmailAddress($email, $config = null)
	{
		if (!$config)
		{
			$config = $this->getConfig();
		}
		
		if (!isset($config->exceptions) || !is_array($config->exceptions) || !count($config->exceptions))
		{
			return null;
		}
		
		$email = Rhymix\Framework\URL::encodeIdna($email);
		
		foreach ($config->exceptions as $exception)
		{
			$domains = array();
			foreach ($exception['domains'] as $domain)
			{
				$domains[] = preg_quote($domain, '/');
			}
			if (count($domains) && preg_match('/\b(?:' . implode('|', $domains) . ')$/i', $email))
			{
				return $exception['method'];
			}
		}
		
		return null;
	}
	
	/**
	 * After SMS send trigger.
	 */
	public function triggerAfterSMSSend($sms)
	{
		$config = $this->getConfig();
		
		if (toBool($config->log_sent_sms ?? 'N') || (toBool($config->log_sms_errors ?? 'N') && count($sms->errors)))
		{
			$obj = new \stdClass();
			$obj->sms_from = $sms->getFrom();
			$obj->sms_to = array();
			foreach ($sms->getRecipientsWithCountry() as $to)
			{
				if ($to->country)
				{
					$obj->sms_to[] = '+' . $to->country . '.' . $to->number;
				}
				else
				{
					$obj->sms_to[] = $to->number;
				}
			}
			$obj->sms_to = implode(', ', $obj->sms_to);
			$obj->content = trim($sms->getSubject() . "\n" . $sms->getBody());
			$obj->calling_script = $sms->getCaller();
			$obj->sending_method = strtolower(class_basename($sms->driver));
			$obj->status = !count($sms->getErrors()) ? 'success' : 'error';
			$obj->errors = count($sms->getErrors()) ? implode("\n", $sms->getErrors()) : null;
			$output = executeQuery('advanced_mailer.insertSMSLog', $obj);
			if (!$output->toBool())
			{
				return $output;
			}
		}
	}
	
	/**
	 * After Push send trigger.
	 */
	public function triggerAfterPushSend($push)
	{
		$config = $this->getConfig();
		
		if (toBool($config->log_sent_push ?? 'N') || (toBool($config->log_push_errors ?? 'N') && count($push->getErrors())))
		{
			$obj = new \stdClass();
			$obj->push_from = $push->getFrom();
			$token_count = count($push->getSuccessTokens()) + count($push->getDeletedTokens()) + count($push->getUpdatedTokens());
			$obj->push_to = sprintf('%d members, %d devices', count($push->getRecipients()), $token_count);
			$obj->push_to .= "\n\n" . 'members: ' . implode(', ', $push->getRecipients());
			if (count($push->getSuccessTokens()))
			{
				$obj->push_to .= "\n\n" . 'success: ' . "\n";
				$obj->push_to .= implode("\n", array_keys($push->getSuccessTokens()));
			}
			if (count($push->getDeletedTokens()))
			{
				$obj->push_to .= "\n\n" . 'deleted: ' . "\n";
				$obj->push_to .= implode("\n", array_keys($push->getDeletedTokens()));
			}
			if (count($push->getUpdatedTokens()))
			{
				$obj->push_to .= "\n\n" . 'updated: ' . "\n";
				foreach ($push->getUpdatedTokens() as $from => $to)
				{
					$obj->push_to .= $from . ' => ' . $to . "\n";
				}
			}
			$obj->subject = trim($push->getSubject());
			$obj->content = trim($push->getContent());
			$obj->calling_script = $push->getCaller();
			$obj->success_count = count($push->getSuccessTokens());
			$obj->deleted_count = count($push->getDeletedTokens());
			$obj->updated_count = count($push->getUpdatedTokens());
			$obj->status = $push->isSent() ? 'success' : 'error';
			$obj->errors = count($push->getErrors()) ? implode("\n", $push->getErrors()) : null;
			$output = executeQuery('advanced_mailer.insertPushLog', $obj);
			if (!$output->toBool())
			{
				return $output;
			}
		}
	}
}
