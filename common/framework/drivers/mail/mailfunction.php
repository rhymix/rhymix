<?php

namespace Rhymix\Framework\Drivers\Mail;

/**
 * The mail() function mail driver.
 */
class MailFunction implements \Rhymix\Framework\Drivers\MailInterface
{
	/**
	 * The singleton instance is stored here.
	 */
	protected static $_instance = null;
	
	/**
	 * The mailer instance is stored here.
	 */
	protected $_mailer = null;
	
	/**
	 * Direct invocation of the constructor is not permitted.
	 */
	protected function __construct()
	{
		$this->mailer = \Swift_Mailer::newInstance(\Swift_MailTransport::newInstance());
	}
	
	/**
	 * Create a new instance of the current mail driver, using the given settings.
	 * 
	 * @param array $config
	 * @return void
	 */
	public static function getInstance(array $config)
	{
		if (self::$_instance === null)
		{
			self::$_instance = new self();
		}
		return self::$_instance;
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
		$result = $this->mailer->send($message->message, $errors);
		foreach ($errors as $error)
		{
			$message->errors[] = $error;
		}
		return (bool)$result;
	}
}
