<?php

class reCAPTCHA
{
	protected static $verify = 'https://www.google.com/recaptcha/api/siteverify';
	protected static $config = null;
	protected static $script_added = false;
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
			return new Object(-1, lang('recaptcha.msg_recaptcha_invalid_response'));
		}
		
		$verify_request = \Requests::post(self::$verify, array(), array(
			'secret' => self::$config->secret_key,
			'response' => $response,
			'remoteip' => \RX_CLIENT_IP,
		));
		
		$verify = @json_decode($verify_request->body, true);
		if ($verify && isset($verify['error-codes']) && in_array('invalid-input-response', $verify['error-codes']))
		{
			return new Object(-1, lang('recaptcha.msg_recaptcha_invalid_response'));
		}
		elseif (!$verify || !$verify['success'] || (isset($verify['error-codes']) && $verify['error-codes']))
		{
			return new Object(-1, lang('recaptcha.msg_recaptcha_server_error'));
		}
		else
		{
			return true;
		}
	}
	
	public function __toString()
	{
		if (!self::$config)
		{
			return '';
		}
		
		if (!self::$script_added)
		{
			Context::loadFile(array('./addons/recaptcha/recaptcha.js', 'body'));
			Context::addHtmlFooter('<script src="https://www.google.com/recaptcha/api.js?render=explicit&onload=reCaptchaCallback" async defer></script>');
			self::$script_added = true;
		}
		
		$html = '<div id="recaptcha-instance-%d" class="g-recaptcha" data-sitekey="%s" data-theme="%s" data-size="%s"></div>';
		$html = sprintf($html, self::$sequence++, escape(self::$config->site_key), self::$config->theme ?: 'light', self::$config->size ?: 'normal');
		return $html;
	}
}
