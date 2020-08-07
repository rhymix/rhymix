$(function() {
	if (recaptcha_config['theme'] === 'system') {
		if (window.matchMedia) {
			recaptcha_config['theme'] = window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
		} else {
			recaptcha_config['theme'] = 'light';
		}
	}
	
	var recaptcha = {
		create: function(callback_success) {
			grecaptcha.ready(function() {
				recaptcha.clear();
				
				if (recaptcha_config['keytype'] === 'v3') {
					grecaptcha.execute(recaptcha_config['sitekey'], {action: 'submit'}).then(function(token) {
						callback_success(token);
						recaptcha.clear();
					});
				} else {
					$('body').css('overflow', 'hidden');
					$('body').append('<div id="recaptcha_container" class="recaptcha_' + recaptcha_config['theme'] + '"><div class="recaptcha_modal_background"></div><div class="recaptcha_modal_window"><div class="recaptcha_modal_title"><h3>reCAPTCHA</h3><p>' + recaptcha_config['message'] + '</p></div><div class="recaptcha_modal_content"><div class="g-recaptcha"></div></div><button class="recaptcha_modal_close" title="Close"></button></div></div>');
					$('.recaptcha_modal_close').click(recaptcha.clear);
					
					grecaptcha.render($('#recaptcha_container .g-recaptcha')[0], {
						sitekey: recaptcha_config['sitekey'],
						size: recaptcha_config['size'],
						theme: recaptcha_config['theme'],
						badge: 'inline',
						callback: function(token) {
							callback_success(token);
							recaptcha.clear();
						},
						'expired-callback': recaptcha.callback_error,
						'error-callback': recaptcha.callback_error
					});
					
					if (recaptcha_config['keytype'] === 'v2.invisible') {
						grecaptcha.execute();
					}
				}
			});
		},
		clear: function() {
			$('#recaptcha_container').remove();
			$('body').css('overflow', '');
		},
		callback_error: function() {
			alert('Please try again.');
			recaptcha.clear();
		}
	};
	
	$('form').submit(function(event) {
		var input_act = $(this).find('input[name="act"]');
		if (!input_act.size() || !input_act.val() || recaptcha_config['target_acts'].indexOf(input_act.val()) === -1) {
			return;
		}
		if ($(this).find('input[name="g-recaptcha-response"]').size() || $(this).hasClass('rx_ajax')) {
			return;
		}
		event.preventDefault();
		
		var $form = $(this);
		recaptcha.create(function(token) {
			$form.append($('<input>').attr({
				type: 'hidden',
				name: 'g-recaptcha-response',
				value: token
			}));
			setTimeout(function() {
				$form.find('input[name="g-recaptcha-response"]').remove();
			}, 1000);
			
			$form.submit();
		});
	});
	
	(function(proxied) {
		window.exec_xml = $.exec_xml = function(module, act, params, callback_success, return_fields, callback_success_arg, fo_obj) {
			if (!act || recaptcha_config['target_acts'].indexOf(act) === -1) {
				return proxied.apply(this, arguments);
			}
			if (params.hasOwnProperty('g-recaptcha-response')) {
				return proxied.apply(this, arguments);
			}
			
			recaptcha.create(function(token) {
				params['g-recaptcha-response'] = token;
				proxied(module, act, params, callback_success, return_fields, callback_success_arg, fo_obj);
			});
		};
	})(window.exec_xml);
	
	(function(proxied) {
		window.exec_json = $.exec_json = function(action, params, callback_success, callback_error) {
			var is_query_string = typeof params === 'string';
			var act = (is_query_string && params.match(/act=([^&]+)/)) ? params.match(/act=([^&]+)/)[1] : action.split('.')[1];
			
			if (!act || recaptcha_config['target_acts'].indexOf(act) === -1) {
				return proxied.apply(this, arguments);
			}
			if (is_query_string ? params.match(/g-recaptcha-response=/) : params.hasOwnProperty('g-recaptcha-response')) {
				return proxied.apply(this, arguments);
			}
			
			recaptcha.create(function(token) {
				if (is_query_string) {
					params = params + '&g-recaptcha-response=' + token;
				} else {
					params['g-recaptcha-response'] = token;
				}
				proxied(action, params, callback_success, callback_error);
			});
		};
	})(window.exec_json);
});

var recaptcha_callbackV2 = function() {
	if (recaptcha_config['keytype'] !== 'v2' || !$('.g-recaptcha').size()) {
		return;
	}
	
	$('.g-recaptcha').each(function() {
		var $form = $(this).closest('form');
		if (!$form.size()) {
			return;
		}
		
		var attr_onsubmit = $form.attr('onsubmit');
		var filter_pattern = /procFilter\(.+,\s*(?:window\.)?(.+)\s*\)/i;
		var filter_name = (attr_onsubmit && attr_onsubmit.match(filter_pattern)) ? attr_onsubmit.match(filter_pattern)[1] : '';
		if (filter_name) {
			if (filter_name === 'insert' || filter_name === 'insert_document') {
				if (recaptcha_config['target_acts'].indexOf('procBoardInsertDocument') === -1) {
					return;
				}
			} else if (filter_name === 'insert_comment') {
				if (recaptcha_config['target_acts'].indexOf('procBoardInsertComment') === -1) {
					return;
				}
			} else {
				return;
			}
		} else {
			var input_act = $form.find('input[name="act"]');
			if (!input_act.size() || !input_act.val() || recaptcha_config['target_acts'].indexOf(input_act.val()) === -1) {
				return;
			}
		}
		
		grecaptcha.render($(this)[0], {
			sitekey: recaptcha_config['sitekey'],
			size: recaptcha_config['size'],
			theme: recaptcha_config['theme']
		});
	});
}