<?php
namespace Addons;
class recaptcha
{
	private static $target_acts = null;
	private static $addon_info = null;
	private static $script_url = 'https://www.google.com/recaptcha/api.js';
	private static $verify_url = 'https://www.google.com/recaptcha/api/siteverify';
	
	public static function init($addon_info)
	{
		$addon_info->key_type = $addon_info->key_type ?? 'v2';
		$addon_info->theme = $addon_info->theme ?? 'light';
		$addon_info->size = $addon_info->size ?? 'normal';
		$addon_info->threshold = strlen($addon_info->threshold) ? (float)$addon_info->threshold : 0.5;
		$addon_info->extra_acts = array_map('trim', preg_split('/\R/', $addon_info->extra_acts, -1, PREG_SPLIT_NO_EMPTY) ?: []);
		$addon_info->use_captcha_var = $addon_info->use_captcha_var ?? 'Y';
		self::$addon_info = $addon_info;
		
		if (self::$addon_info->key_type === 'v2.invisible')
		{
			self::$addon_info->size = 'invisible';
		}
		elseif (self::$addon_info->key_type === 'v2' && self::$addon_info->use_captcha_var === 'Y')
		{
			\Context::set('captcha', '<div class="g-recaptcha"></div>');
		}
	}
	
	public static function setHTML()
	{
		if (!self::getTargetActs())
		{
			return;
		}
		
		if (self::$addon_info->key_type === 'v3')
		{
			$message = null;
			\Context::addHtmlFooter(sprintf('<script src="%s?render=%s"></script>', self::$script_url, escape(self::$addon_info->site_key)));
		}
		else
		{
			$parameter = 'render=explicit';
			if (self::$addon_info->key_type === 'v2.invisible')
			{
				$message = lang('recaptcha.msg_recaptcha_auto');
			}
			else
			{
				$message = lang('recaptcha.msg_recaptcha_request');
				if (self::$addon_info->use_captcha_var === 'Y')
				{
					$parameter .= '&onload=recaptcha_callbackV2';
				}
			}
			\Context::addHtmlFooter(sprintf('<script src="%s?%s" async defer></script>', self::$script_url, $parameter));
		}
		
		\Context::addHtmlFooter(sprintf('<script>var recaptcha_config = %s;</script>', json_encode([
			'keytype' => self::$addon_info->key_type,
			'sitekey' => self::$addon_info->site_key,
			'theme' => self::$addon_info->theme,
			'size' => self::$addon_info->size,
			'target_acts' => self::getTargetActs(),
			'message' => $message,
		])));
		
		\Context::loadFile('./addons/recaptcha/recaptcha.css');
		\Context::loadFile(['./addons/recaptcha/recaptcha.js', 'body']);
	}
	
	public static function verify($oModule)
	{
		if (!in_array($oModule->act, self::getTargetActs()))
		{
			return;
		}
		$response = \Context::get('g-recaptcha-response');
		if (!$response)
		{
			throw new \Rhymix\Framework\Exception('recaptcha.msg_recaptcha_invalid_response');
		}
		
		try
		{
			$verify_request = \Requests::post(self::$verify_url, [], [
				'secret' => self::$addon_info->secret_key,
				'response' => $response,
				'remoteip' => \RX_CLIENT_IP,
			]);
		}
		catch (\Requests_Exception $e)
		{
			throw new \Rhymix\Framework\Exception('recaptcha.msg_recaptcha_connection_error');
		}
		
		$result = @json_decode($verify_request->body, true);
		if (empty($result['success']))
		{
			if (isset($result['error-codes']) && in_array('invalid-input-response', $result['error-codes']))
			{
				throw new \Rhymix\Framework\Exception('recaptcha.msg_recaptcha_invalid_response');
			}
			else
			{
				throw new \Rhymix\Framework\Exception('recaptcha.msg_recaptcha_server_error');
			}
		}
		if (isset($result['score']) && $result['score'] <= self::$addon_info->threshold)
		{
			throw new \Rhymix\Framework\Exception('recaptcha.msg_recaptcha_blocked');
		}
		
		$_SESSION['recaptcha_authenticated'] = true;
		return true;
	}
	
	public static function getTargetActs()
	{
		if (self::$target_acts === null)
		{
			self::$target_acts = [];
			$is_logged = \Context::get('is_logged');
			
			if (!$is_logged)
			{
				if (self::$addon_info->use_login === 'Y')
				{
					self::$target_acts[] = 'procMemberLogin';
				}
				if (self::$addon_info->use_signup === 'Y')
				{
					self::$target_acts[] = 'procMemberInsert';
				}
				if (self::$addon_info->use_recovery === 'Y')
				{
					self::$target_acts[] = 'procMemberFindAccount';
					self::$target_acts[] = 'procMemberResendAuthMail';
				}
			}
			if (self::$addon_info->use_document === 'Y' || self::$addon_info->use_document === 'non-login' && !$is_logged)
			{
				self::$target_acts[] = 'procBoardInsertDocument';
			}
			if (self::$addon_info->use_comment === 'Y' || self::$addon_info->use_comment === 'non-login' && !$is_logged)
			{
				self::$target_acts[] = 'procBoardInsertComment';
			}
			
			self::$target_acts = array_merge(self::$target_acts, self::$addon_info->extra_acts);
		}
		return self::$target_acts;
	}
}