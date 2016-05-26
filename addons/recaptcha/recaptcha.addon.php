<?php

if (!defined('RX_BASEDIR') || !$addon_info->site_key || !$addon_info->secret_key || $called_position !== 'before_module_init')
{
	return;
}

if (preg_match('/^dispMemberSignUp/i', Context::get('act')))
{
	getController('module')->addTriggerFunction('moduleObject.proc', 'after', function() use($addon_info) {
		$html = '<div class="g-recaptcha" data-sitekey="%s" data-theme="%s" data-size="%s"></div>';
		$html = sprintf($html, escape($addon_info->site_key), $addon_info->theme ?: 'light', $addon_info->size ?: 'normal');
		Context::addHtmlHeader('<script src="https://www.google.com/recaptcha/api.js" async defer></script>');
		Context::getInstance()->formTags[] = (object)array(
			'name' => 'recaptcha',
			'title' => 'reCAPTCHA',
			'inputTag' => $html,
		);
	});
}

if (preg_match('/^procMemberInsert/i', Context::get('act')))
{
	getController('module')->addTriggerFunction('moduleObject.proc', 'before', function() use($addon_info) {
		$response = Context::get('g-recaptcha-response');
		if (!$response)
		{
			return new Object(-1, lang('recaptcha.msg_recaptcha_invalid_response'));
		}
		
		$verify_url = 'https://www.google.com/recaptcha/api/siteverify';
		$verify_request = \Requests::post($recaptcha_verify_url, array(), array(
			'secret' => $addon_info->secret_key,
			'response' => $recaptcha_response,
			'remoteip' => \RX_CLIENT_IP,
		));
		
		$verify = @json_decode($verify_request->body, true);
		var_dump($verify);exit;
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
	});
}
