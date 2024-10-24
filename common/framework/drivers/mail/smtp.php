<?php

namespace Rhymix\Framework\Drivers\Mail;

/**
 * The SMTP mail driver.
 */
class SMTP extends Base implements \Rhymix\Framework\Drivers\MailInterface
{
	/**
	 * Get the list of configuration fields required by this mail driver.
	 *
	 * @return array
	 */
	public static function getRequiredConfig()
	{
		return array('smtp_host', 'smtp_port', 'smtp_security', 'smtp_user', 'smtp_pass');
	}

	/**
	 * Check if the current mail driver is supported on this server.
	 *
	 * This method returns true on success and false on failure.
	 *
	 * @return bool
	 */
	public static function isSupported()
	{
		return function_exists('proc_open');
	}

	/**
	 * Send a message.
	 *
	 * This method returns true on success and false on failure.
	 *
	 * @param object $message
	 * @return bool
	 */
	public function send(\Rhymix\Framework\Mail $message)
	{
		if ($this->_mailer === null)
		{
			if (isset($this->_config['smtp_security']) && in_array($this->_config['smtp_security'], ['ssl', 'tls']))
			{
				$security = $this->_config['smtp_security'];
			}
			else
			{
				$security = null;
			}

			$transport = new \Swift_SmtpTransport($this->_config['smtp_host'], $this->_config['smtp_port'], $security);
			$transport->setUsername($this->_config['smtp_user']);
			$transport->setPassword($this->_config['smtp_pass']);
			$local_domain = $transport->getLocalDomain();
			if (preg_match('/^\*\.(.+)$/', $local_domain, $matches))
			{
				$transport->setLocalDomain($matches[1]);
			}
			$this->_mailer = new \Swift_Mailer($transport);
		}

		try
		{
			$errors = [];
			$result = $this->_mailer->send($message->message, $errors);
		}
		catch(\Exception $e)
		{
			$message->errors[] = $e->getMessage();
			return false;
		}

		foreach ($errors as $error)
		{
			$message->errors[] = 'Failed to send to ' . $error;
		}
		return (bool)$result;
	}
}
