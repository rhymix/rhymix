<?php

namespace Rhymix\Framework\Drivers\Mail;

/**
 * The Postmark mail driver.
 */
class Postmark extends SMTP implements \Rhymix\Framework\Drivers\MailInterface
{
	/**
	 * Direct invocation of the constructor is not permitted.
	 */
	protected function __construct(array $config)
	{
		$config['smtp_host'] = 'smtp.postmarkapp.com';
		$config['smtp_port'] = 587;
		$config['smtp_security'] = 'tls';
		$config['smtp_user'] = $config['api_token'];
		$config['smtp_pass'] = $config['api_token'];
		parent::__construct($config);
	}
	
	/**
	 * Get the list of configuration fields required by this mail driver.
	 * 
	 * @return array
	 */
	public static function getRequiredConfig()
	{
		return array('api_token');
	}
	
	/**
	 * Get the SPF hint.
	 * 
	 * @return string
	 */
	public static function getSPFHint()
	{
		return 'include:spf.mtasv.net';
	}
	
	/**
	 * Get the DKIM hint.
	 * 
	 * @return string
	 */
	public static function getDKIMHint()
	{
		return '********.pm._domainkey';
	}
}
