<?php

class spamfilter_reCAPTCHA
{
	protected static $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
	protected static $config = null;
	protected static $scripts_added = false;
	protected static $instances_inserted = 0;
	protected static $sequence = 1;
	protected $_target_actions = [];
	
	public static function init($config)
	{
		self::$config = $config;
	}
	
	public static function check()
	{
		$response = Context::get('g-recaptcha-response');
		if (!$response)
		{
			throw new Rhymix\Framework\Exception('msg_recaptcha_invalid_response');
		}
		
		try
		{
			$verify_request = \Requests::post(self::$verify_url, array(), array(
				'secret' => self::$config->secret_key,
				'response' => $response,
				'remoteip' => \RX_CLIENT_IP,
			));
		}
		catch (\Requests_Exception $e)
		{
			throw new Rhymix\Framework\Exception('msg_recaptcha_connection_error');
		}
		
        $verify = @json_decode($verify_request->body, true);
		if (!$verify || !$verify['success'])
		{
			throw new Rhymix\Framework\Exception('msg_recaptcha_server_error');
		}
        if ($verify && isset($verify['error-codes']) && in_array('invalid-input-response', $verify['error-codes']))
		{
			throw new Rhymix\Framework\Exception('msg_recaptcha_invalid_response');
        }
        
        $_SESSION['recaptcha_authenticated'] = true;
	}
	
	public function addScripts()
	{
		if (!self::$scripts_added)
		{
			self::$scripts_added = true;
			Context::loadFile(array('./modules/spamfilter/tpl/js/recaptcha.js', 'body'));
			Context::addHtmlFooter('<script src="https://www.google.com/recaptcha/api.js?render=explicit&amp;onload=reCaptchaCallback" async defer></script>');
			$html = '<div id="recaptcha-config" data-sitekey="%s" data-theme="%s" data-size="%s" data-targets="%s"></div>';
			$html = sprintf($html, escape(self::$config->site_key), self::$config->theme ?: 'light', self::$config->size ?: 'normal', implode(',', array_keys($this->_target_actions)));
			Context::addHtmlFooter($html);
		}
	}
	
	public function setTargetActions(array $target_actions)
	{
		$this->_target_actions = $target_actions;
	}
	
	public function isTargetAction(string $action): bool
	{
		return isset($this->_target_actions[$action]);
	}
	
	public function __toString()
	{
		return sprintf('<div id="recaptcha-instance-%d" class="g-recaptcha"></div>', self::$instances_inserted++);
	}
}
