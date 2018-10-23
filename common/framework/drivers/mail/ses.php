<?php

namespace Rhymix\Framework\Drivers\Mail;

/**
 * The Amazon SES mail driver.
 */
class SES extends Base implements \Rhymix\Framework\Drivers\MailInterface
{
	/**
	 * Cache the message here for debug access.
	 */
	protected $_message;
	
	/**
	 * Direct invocation of the constructor is not permitted.
	 */
	protected function __construct(array $config)
	{
		$transport = new \Swift_AWSTransport($config['api_key'] ?: $config['api_user'], $config['api_secret'] ?: $config['api_pass']);
		$transport->setDebug(array($this, 'debugCallback'));
		$transport->setEndpoint('https://email.' . strtolower($config['api_type']) . '.amazonaws.com/');
		$this->mailer = new \Swift_Mailer($transport);
	}
	
	/**
	 * Get the list of configuration fields required by this mail driver.
	 * 
	 * @return array
	 */
	public static function getRequiredConfig()
	{
		return array('api_key', 'api_secret', 'api_type');
	}
	
	/**
	 * Get the list of API types supported by this mail driver.
	 * 
	 * @return array
	 */
	public static function getAPITypes()
	{
		return array('us-east-1', 'us-west-2', 'eu-west-1');
	}
	
	/**
	 * Get the SPF hint.
	 * 
	 * @return string
	 */
	public static function getSPFHint()
	{
		return '';
	}
	
	/**
	 * Get the DKIM hint.
	 * 
	 * @return string
	 */
	public static function getDKIMHint()
	{
		return '********._domainkey';
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
		$this->_message = $message;
		
		try
		{
			$result = $this->mailer->send($message->message, $errors);
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
	
	/**
	 * Debug callback function for SES transport.
	 * 
	 * @param string $msg
	 * @return void
	 */
	public function debugCallback($msg)
	{
		if ($this->_message)
		{
			$this->_message->errors[] = $msg;
		}
	}
}
