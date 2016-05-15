<?php

namespace Rhymix\Framework\Drivers\Mail;

/**
 * The Mandrill mail driver.
 */
class Mandrill extends SMTP implements \Rhymix\Framework\Drivers\MailInterface
{
	/**
	 * Direct invocation of the constructor is not permitted.
	 */
	protected function __construct(array $config)
	{
		$config['smtp_host'] = 'smtp.mandrillapp.com';
		$config['smtp_port'] = 465;
		$config['smtp_security'] = 'ssl';
		$config['smtp_user'] = $config['api_user'];
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
		return array('api_user', 'api_token');
	}
}
