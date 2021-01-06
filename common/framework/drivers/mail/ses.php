<?php

namespace Rhymix\Framework\Drivers\Mail;

/**
 * The Amazon SES mail driver.
 */
class SES extends SMTP implements \Rhymix\Framework\Drivers\MailInterface
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
		$config['smtp_host'] = sprintf('email-smtp.%s.amazonaws.com', $config['api_type']);
		$config['smtp_port'] = 587;
		$config['smtp_security'] = 'tls';
		parent::__construct($config);
	}
	
	/**
	 * Get the human-readable name of this mail driver.
	 * 
	 * @return string
	 */
	public static function getName()
	{
		return 'Amazon SES (SMTP)';
	}
	
	/**
	 * Get the list of configuration fields required by this mail driver.
	 * 
	 * @return array
	 */
	public static function getRequiredConfig()
	{
		return array('smtp_user', 'smtp_pass', 'api_type');
	}
	
	/**
	 * Get the list of API types supported by this mail driver.
	 * 
	 * @return array
	 */
	public static function getAPITypes()
	{
		return array(
			'us-east-1', 'us-east-2', 'us-west-2', 'us-gov-west-1',
			'eu-west-1', 'eu-west-2', 'eu-central-1', 'ca-central-1', 'sa-east-1',
			'ap-northeast-1', 'ap-northeast-2',
			'ap-southeast-1', 'ap-southeast-2', 'ap-south-1',
		);
	}
	
	/**
	 * Get the SPF hint.
	 * 
	 * @return string
	 */
	public static function getSPFHint()
	{
		return 'include:amazonses.com';
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
}
