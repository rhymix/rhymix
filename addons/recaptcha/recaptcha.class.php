<?php

class reCAPTCHA
{
	protected static $verify = 'https://www.google.com/recaptcha/api/siteverify';
	protected static $config = null;
	protected static $scripts_added = false;
	protected static $instances_inserted = 0;
	protected static $sequence = 1;
	
	public static function init($config)
	{
		self::$config = $config;
	}
	
	public static function check()
	{
		$response = Context::get('g-recaptcha-response');
		if (!$response)
		{
			throw new Rhymix\Framework\Exception('recaptcha.msg_recaptcha_invalid_response');
		}
		
		try
		{
			$verify_request = \Requests::post(self::$verify, array(), array(
				'secret' => self::$config->secret_key,
				'response' => $response,
				'remoteip' => \RX_CLIENT_IP,
			));
		}
		catch (\Requests_Exception $e)
		{
			throw new Rhymix\Framework\Exception('recaptcha.msg_recaptcha_connection_error');
		}
		
		$verify = @json_decode($verify_request->body, true);
		if ($verify && isset($verify['error-codes']) && in_array('invalid-input-response', $verify['error-codes']))
		{
			throw new Rhymix\Framework\Exception('recaptcha.msg_recaptcha_invalid_response');
		}
		elseif (!$verify || !$verify['success'] || (isset($verify['error-codes']) && $verify['error-codes']))
		{
			throw new Rhymix\Framework\Exception('recaptcha.msg_recaptcha_server_error');
		}
		else
		{
			$_SESSION['recaptcha_authenticated'] = true;
			return true;
		}
	}
	
	public function __construct()
	{
		if (!self::$scripts_added)
		{
			self::$scripts_added = true;
			Context::loadFile(array('./addons/recaptcha/recaptcha.js', 'body'));
			Context::addHtmlFooter('<script src="https://www.google.com/recaptcha/api.js?render=explicit&amp;onload=reCaptchaCallback" async defer></script>');
			$html = '<div id="recaptcha-config" data-sitekey="%s" data-theme="%s" data-size="%s"></div>';
			$html = sprintf($html, escape(self::$config->site_key), self::$config->theme ?: 'light', self::$config->size ?: 'normal');
			Context::addHtmlFooter($html);
		}
	}
	
	public function __toString()
	{
		return sprintf('<div id="recaptcha-instance-%d" class="g-recaptcha"></div>', self::$instances_inserted++);
	}
}
