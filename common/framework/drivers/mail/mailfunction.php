<?php

namespace Rhymix\Framework\Drivers\Mail;

/**
 * The mail() function mail driver.
 */
class MailFunction extends Base implements \Rhymix\Framework\Drivers\MailInterface
{
	/**
	 * Get the human-readable name of this mail driver.
	 *
	 * @return string
	 */
	public static function getName()
	{
		return 'PHP mail() Function';
	}

	/**
	 * Get the SPF hint.
	 *
	 * @return string
	 */
	public static function getSPFHint()
	{
		return 'ip4:$SERVER_ADDR';
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
		return true;
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
			include_once \RX_BASEDIR . 'common/libraries/swift_mail.php';
			$this->_mailer = new \Swift_Mailer(new \Swift_MailTransport);
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
			$message->errors[] = $error;
		}
		return (bool)$result;
	}
}
