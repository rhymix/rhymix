<?php

namespace Rhymix\Modules\Spamfilter\Captcha;

use Context;
use Rhymix\Framework\Exception;
use Rhymix\Framework\HTTP;

class Turnstile
{
	protected static $verify_url = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';
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
			throw new Exception('msg_recaptcha_invalid_response');
		}

		$verify_request = HTTP::post(self::$verify_url, [
			'secret' => self::$config->secret_key,
			'response' => $response,
			'remoteip' => \RX_CLIENT_IP,
		]);
		if ($verify_request->getStatusCode() !== 200 || !$verify_request->getBody())
		{
			throw new Exception('msg_recaptcha_connection_error');
		}

		$verify = @json_decode($verify_request->getBody(), true);
		if (!$verify || !$verify['success'])
		{
			throw new Exception('msg_recaptcha_server_error');
		}
		if ($verify && isset($verify['error-codes']) && in_array('invalid-input-response', $verify['error-codes']))
		{
			throw new Exception('msg_recaptcha_invalid_response');
		}

		$_SESSION['recaptcha_authenticated'] = true;
	}

	public function addScripts()
	{
		if (!self::$scripts_added)
		{
			self::$scripts_added = true;
			Context::loadFile(array('./modules/spamfilter/tpl/js/turnstile.js', 'body'));
			Context::addHtmlFooter('<script src="https://challenges.cloudflare.com/turnstile/v0/api.js?compat=recaptcha&amp;render=explicit&amp;onload=turnstileCallback" async defer></script>');
			$html = '<div id="turnstile-config" data-sitekey="%s" data-theme="%s" data-size="%s" data-targets="%s"></div>';
			$html = sprintf($html, escape(self::$config->site_key), self::$config->theme ?: 'auto', self::$config->size ?: 'normal', implode(',', array_keys($this->_target_actions)));
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
		return sprintf('<div id="turnstile-instance-%d" class="turnstile-captcha"></div>', self::$instances_inserted++);
	}
}
