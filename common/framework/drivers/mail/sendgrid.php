<?php

namespace Rhymix\Framework\Drivers\Mail;

/**
 * The SendGrid mail driver.
 */
class SendGrid extends SMTP implements \Rhymix\Framework\Drivers\MailInterface
{
	/**
	 * Direct invocation of the constructor is not permitted.
	 */
	protected function __construct(array $config)
	{
		$config['smtp_host'] = 'smtp.sendgrid.net';
		$config['smtp_port'] = 465;
		$config['smtp_security'] = 'ssl';
		$config['smtp_user'] = $config['api_user'];
		$config['smtp_pass'] = $config['api_pass'];
		parent::__construct($config);
	}
	
	/**
	 * Get the list of configuration fields required by this mail driver.
	 * 
	 * @return array
	 */
	public static function getRequiredConfig()
	{
		return array('api_user', 'api_pass');
	}
	
	/**
	 * Get the SPF hint.
	 * 
	 * @return string
	 */
	public static function getSPFHint()
	{
		return 'include:sendgrid.net';
	}
	
	/**
	 * Get the DKIM hint.
	 * 
	 * @return string
	 */
	public static function getDKIMHint()
	{
		return 'smtpapi._domainkey';
	}
}
