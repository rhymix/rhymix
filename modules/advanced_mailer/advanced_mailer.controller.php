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
		
		$first_recipient = array_first_key($mail->message->getTo());
		if ($exception_driver = $this->getSendingMethodForEmailAddress($first_recipient, $config))
		{
			$driver_class = '\\Rhymix\\Framework\\Drivers\Mail\\' . $exception_driver;
			if (class_exists($driver_class))
			{
				$mail->driver = $driver_class::getInstance(config("mail.$exception_driver"));
			}
		}
		
		if (!$mail->getFrom())
		{
			$mail->setFrom($config->sender_email, $config->sender_name ?: null);
		}
		elseif (toBool($config->force_sender))
		{
			if (stripos($mail->driver->getName(), 'woorimail') !== false && config('mail.woorimail.api_type') === 'free')
			{
				// no-op
			}
			else
			{
				$original_sender_email = array_first_key($mail->message->getFrom());
				$original_sender_name = array_first($mail->message->getFrom());
				if ($original_sender_email !== $config->sender_email)
				{
					$mail->setFrom($config->sender_email, $original_sender_name ?: $config->sender_name);
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
		
		if (toBool($config->log_sent_mail) || (toBool(self::$config->log_errors) && count($mail->errors)))
		{
			$obj = new \stdClass();
			$obj->mail_srl = getNextSequence();
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
			$obj->status = !count($mail->errors) ? 'success' : 'error';
			$obj->errors = count($mail->errors) ? implode("\n", $mail->errors) : null;
			$output = executeQuery('advanced_mailer.insertLog', $obj);
			if (!$output->toBool())
			{
				return $output;
			}
		}
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
}
