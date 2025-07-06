/**
 * Common JavaScript library for Rhymix, based on XpressEngine 1.x
 */

/**
 * =============
 * Rhymix object
 * =============
 */

const Rhymix = window.Rhymix = {
	addedDocument: [],
	langCodes: {},
	loadedPopupMenus: [],
	openWindowList: {},
	currentDebugData: null,
	pendingDebugData: [],
	showAjaxErrors: ['ALL'],
	unloading: false,
	modal: {},
	state: {}
};

/**
 * Check if the current device is a mobile device.
 *
 * @return bool
 */
Rhymix.isMobile = function() {
	return String(navigator.userAgent).match(/mobile/i);
};

/**
 * Get the current color scheme
 *
 * @return string
 */
Rhymix.getColorScheme = function() {
	if ($('body').hasClass('color_scheme_dark')) {
		return 'dark';
	} else {
		return 'light';
	}
};

/**
 * Set the color scheme
 *
 * @param string color_scheme
 * @return void
 */
Rhymix.setColorScheme = function(color_scheme) {
	if (color_scheme === 'dark' || color_scheme === 'light') {
		$('body').addClass('color_scheme_' + color_scheme).removeClass('color_scheme_' + (color_scheme === 'dark' ? 'light' : 'dark'));
		this.cookie.set('rx_color_scheme', color_scheme, { path: this.URI(default_url).pathname(), expires: 365 });
	} else {
		this.cookie.remove('rx_color_scheme', { path: this.URI(default_url).pathname() });
		color_scheme = (window.matchMedia && window.matchMedia('(prefers-color-scheme:dark)').matches) ? 'dark' : 'light';
		$('body').addClass('color_scheme_' + color_scheme).removeClass('color_scheme_' + (color_scheme === 'dark' ? 'light' : 'dark'));
	}
};

/**
 * Automatically detect the color scheme
 *
 * @return void
 */
Rhymix.detectColorScheme = function() {
	// Return if a color scheme is already selected.
	const body_element = $('body');
	if(body_element.hasClass('color_scheme_light') || body_element.hasClass('color_scheme_dark')) {
		return;
	}
	// Detect the cookie.
	let color_scheme = this.cookie.get('rx_color_scheme');
	// Detect the device color scheme.
	let match_media = window.matchMedia ? window.matchMedia('(prefers-color-scheme:dark)') : null;
	if (color_scheme !== 'light' && color_scheme !== 'dark') {
		color_scheme = (match_media && match_media.matches) ? 'dark' : 'light';
	}
	// Set the body class according to the detected color scheme.
	body_element.addClass('color_scheme_' + color_scheme);
	// Add an event listener to detect changes to the device color scheme.
	match_media && match_media.addListener && match_media.addListener(function(e) {
		if (e.matches) {
			body_element.removeClass('color_scheme_light').addClass('color_scheme_dark');
		} else {
			body_element.removeClass('color_scheme_dark').addClass('color_scheme_light');
		}
	});
};

/**
 * Get the language
 *
 * @return string
 */
Rhymix.getLangType = function() {
	return window.current_lang;
};

/**
 * Set the language
 *
 * @param string lang_type
 * @return void
 */
Rhymix.setLangType = function(lang_type) {
	const baseurl = this.getBaseUrl();
	if (baseurl !== '/') {
		this.cookie.remove('lang_type', { path: '/' });
	}
	this.cookie.set('lang_type', lang_type, { path: baseurl, expires: 365 });
};

/**
 * Get CSRF token for this document
 *
 * @return string|null
 */
Rhymix.getCSRFToken = function() {
	return $("meta[name='csrf-token']").attr("content");
};

/**
 * Set CSRF token for this document
 *
 * @param string token
 * @return void
 */
Rhymix.setCSRFToken = function(token) {
	$("meta[name='csrf-token']").attr("content", token);
};

/**
 * Get the current rewrite level
 *
 * @return int
 */
Rhymix.getRewriteLevel = function() {
	return window.rewrite_level;
};

/**
 * Get the base URL relative to the current origin
 *
 * @return string
 */
Rhymix.getBaseUrl = function() {
	if (!this.state.baseUrl) {
		this.state.baseUrl = this.URI(default_url).pathname();
	}
	return this.state.baseUrl;
};

/**
 * Get the full default URL
 *
 * @return string
 */
Rhymix.getDefaultUrl = function() {
	return window.default_url;
};

/**
 * Get the current page's long URL
 *
 * @return string
 */
Rhymix.getCurrentUrl = function() {
	return window.current_url;
};

/**
 * Get the current page prefix (mid)
 *
 * @return string
 */
Rhymix.getCurrentUrlPrefix = function() {
	return window.current_mid;
};

/**
 * Check if a URL is identical to the current page URL except for the hash
 *
 * @param string url
 * @return bool
 */
Rhymix.isCurrentUrl = function(url) {
	const absolute_url = window.location.href;
	const relative_url = window.location.pathname + window.location.search;
	return url === absolute_url || url === relative_url ||
		url.indexOf(absolute_url.replace(/#.+$/, "") + "#") === 0 ||
		url.indexOf(relative_url.replace(/#.+$/, "") + "#") === 0;
};

/**
 * Check if two URLs belong to the same origin
 *
 * @param string url1
 * @param string url2
 * @return bool
 */
Rhymix.isSameOrigin = function(url1, url2) {
	if(!url1 || !url2) {
		return false;
	}
	if (url1.match(/^\.?\/[^\/]*/) || url2.match(/^\.?\/[^\/]*/)) {
		return true;
	}
	if (url1.match(/^(https?:)?\/\/[^\/]*[^a-z0-9\/.:_-]/i) || url2.match(/^(https?:)?\/\/[^\/]*[^a-z0-9\/.:_-]/i)) {
		return false;
	}
	try {
		url1 = this.URI(url1).normalizePort().normalizeHostname().normalizePathname().origin();
		url2 = this.URI(url2).normalizePort().normalizeHostname().normalizePathname().origin();
		return (url1 === url2) ? true : false;
	} catch (err) {
		return false;
	}
}

/**
 * Check if a URL belongs to the same host as the current page
 *
 * Note that this function does not check the protocol.
 * It is therefore a weaker check than isSameOrigin().
 *
 * @param string url
 * @return bool
 */
Rhymix.isSameHost = function(url) {
	if (typeof url !== 'string') {
		return false;
	}
	if (url.match(/^\.?\/[^\/]/)) {
		return true;
	}
	if (url.match(/^\w+:[^\/]*$/) || url.match(/^(https?:)?\/\/[^\/]*[^a-z0-9\/.:_-]/i)) {
		return false;
	}
	if (!this.state.partialOrigin) {
		let uri = this.URI(window.request_uri).normalizePort().normalizeHostname().normalizePathname();
		this.state.partialOrigin = uri.hostname() + uri.directory();
	}
	try {
		let target_url = this.URI(url).normalizePort().normalizeHostname().normalizePathname();
		if (target_url.is('urn')) {
			return false;
		}
		if (!target_url.hostname()) {
			target_url = target_url.absoluteTo(window.request_uri);
		}
		target_url = target_url.hostname() + target_url.directory();
		return target_url.indexOf(this.state.partialOrigin) === 0;
	} catch(err) {
		return false;
	}
};

/**
 * Redirect to a URL, but reload instead if the target is the same as the current page
 *
 * @param string url
 * @param int delay
 * @return void
 */
Rhymix.redirectToUrl = function(url, delay) {
	const callback = function() {
		if (Rhymix.isCurrentUrl(url)) {
			window.location.href = url;
			window.location.reload();
		} else {
			window.location.href = url;
		}
	};
	if (delay) {
		this.pendingRedirect = setTimeout(callback, delay);
	} else {
		callback();
	}
};

/**
 * Cancel any pending redirect
 *
 * @return bool
 */
Rhymix.cancelPendingRedirect = function() {
	if (this.pendingRedirect) {
		clearTimeout(this.pendingRedirect);
		this.pendingRedirect = null;
		return true;
	} else {
		return false;
	}
};

/**
 * Open a new window and focus it
 *
 * @param string url
 * @param string target
 * @param string features
 * @return void
 */
Rhymix.openWindow = function(url, target, features) {

	// Fill default values
	if (typeof target === 'undefined') {
		target = '_blank';
	}
	if (typeof features === 'undefined') {
		features = '';
	}

	// Close any existing window with the same target name
	try {
		if (target !== '_blank' && this.openWindowList[target]) {
			this.openWindowList[target].close();
			delete this.openWindowList[target];
		}
	} catch(e) {}

	// Open using Blankshield if the target is a different site
	if (!this.isSameHost(url)) {
		window.blankshield.open(url, target, features);
	} else {
		const win = window.open(url, target, features);
		win.focus();
		if (target !== '_blank') {
			this.openWindowList[target] = win;
		}
	}
};

/**
 * Open a popup with standard features, for backward compatibility
 *
 * @param string url
 * @param string target
 * @return void
 */
Rhymix.openPopup = function(url, target) {
	const features = 'width=800,height=600,toolbars=no,scrollbars=yes,resizable=yes';
	this.openWindow(url, target, features);
};

/**
 * Save background scroll position
 *
 * @param bool pushState
 * @return void
 */
Rhymix.modal.saveBackgroundPosition = function(modal_id, pushState) {
	const body = $(document.body);
	if (!body.data('rx_scroll_position')) {
		body.data('rx_scroll_position', {
			left: $(window).scrollLeft(),
			top: $(window).scrollTop()
		});
	}
	body.addClass('rx_modal_open');
	if (pushState) {
		history.pushState({ modal: modal_id }, '', location.href);
	}
};

/**
 * Open an HTML element as a modal
 *
 * @param string id
 * @return void
 */
Rhymix.modal.open = function(id) {
	this.saveBackgroundPosition(id, true);
	$('#' + id).addClass('active');
};

/**
 * Open an iframe as a modal
 *
 * @param string url
 * @param string target
 * @return void
 */
Rhymix.modal.openIframe = function(url, target) {
	const iframe = document.createElement('iframe');
	const iframe_sequence = String(Date.now()) + Math.round(Math.random() * 1000000);
	const iframe_id = '_rx_iframe_' + iframe_sequence;
	iframe.setAttribute('id', iframe_id);
	iframe.setAttribute('class', 'rx_modal');
	iframe.setAttribute('name', target || ('_rx_iframe_' + iframe_sequence))
	iframe.setAttribute('src', url + '&iframe_sequence=' + iframe_sequence);
	iframe.setAttribute('width', '100%');
	iframe.setAttribute('height', '100%');
	iframe.setAttribute('style', 'position:fixed; top:0; left:0; width:100%; height:100%; border:0; z-index:999999999; background-color: #fff; overflow-y:auto');
	this.saveBackgroundPosition(iframe_id, true);
	$(document.body).append(iframe);
};

/**
 * Close currently open modal
 *
 * @param string id
 * @return void
 */
Rhymix.modal.close = function(id) {
	history.back();
	/*
	if (typeof id === 'string') {
		$('#' + id).remove();
	} else {
		$('.rx_modal').remove();
	}
	*/
};

/**
 * Make an AJAX request
 *
 * @param string action
 * @param object params
 * @param function callback_success
 * @param function callback_error
 * @return Promise
 */
Rhymix.ajax = function(action, params, callback_success, callback_error) {

	// Extract module and act
	let isFormData = params instanceof FormData;
	let module, act, url, promise;
	if (action) {
		if (typeof action === 'string' && action.match(/^[a-z0-9_]+\.[a-z0-9_]+$/i)) {
			let parts = action.split('.');
			params = params || {};
			params.module = module = parts[0];
			params.act = act = parts[1];
		} else {
			url = action;
			action = null;
		}
	} else {
		if (isFormData) {
			module = params.get('module');
			act = params.get('act');
			if (module && act) {
				action = module + '.' + act;
			} else if (act) {
				action = act;
			} else {
				action = null;
			}
		} else {
			action = null;
		}
	}

	// Add action to URL if the current rewrite level supports it
	if (!url) {
		url = this.URI(window.request_uri).pathname() + 'index.php';
		if (act) {
			url = url + '?act=' + act;
		}
		/*
		if (this.getRewriteLevel() >= 2 && action !== null) {
			url = url + '_' + action.replace('.', '/');
		} else {
			url = url + 'index.php';
		}
		*/
	}

	// Add a CSRF token to the header, and remove it from the parameters
	const headers = {
		'X-CSRF-Token': getCSRFToken()
	};
	if (isFormData && params.has('_rx_csrf_token') && params.get('_rx_csrf_token') === headers['X-CSRF-Token']) {
		params.delete('_rx_csrf_token');
	}
	if (typeof params._rx_csrf_token !== 'undefined' && params._rx_csrf_token === headers['X-CSRF-Token']) {
		delete params._rx_csrf_token;
	}

	// Create and return a Promise for this AJAX request
	return promise = new Promise(function(resolve, reject) {

		// Define the success wrapper.
		const successWrapper = function(data, textStatus, xhr) {

			// Add debug information.
			if (data._rx_debug) {
				data._rx_debug.page_title = "AJAX : " + action;
				if (Rhymix.addDebugData) {
					Rhymix.addDebugData(data._rx_debug);
				} else {
					Rhymix.pendingDebugData.push(data._rx_debug);
				}
			}

			// If the response contains a Rhymix error code, display the error message.
			if (typeof data.error !== 'undefined' && data.error != 0) {
				return errorWrapper(data, textStatus, xhr);
			}

			// If a success callback was defined, call it.
			if (typeof callback_success === 'function') {
				callback_success(data, xhr);
				resolve(data);
				return;
			}

			// If the response contains a redirect URL, follow the redirect.
			// This can be canceled by Rhymix.cancelPendingRedirect() within 100 milliseconds.
			if (data.redirect_url) {
				Rhymix.redirectToUrl(data.redirect_url.replace(/&amp;/g, '&'), 100);
			}

			// Resolve the promise with the response data.
			resolve(data);
		};

		// Define the error wrapper.
		const errorWrapper = function(data, textStatus, xhr) {

			// If an error callback is defined, call it.
			// The promise will still be rejected, but silently.
			if (typeof callback_error === 'function') {
				callback_error(data, xhr);
				promise.catch(function(dummy) { });
				let dummy = new Error('Rhymix.ajax() error already handled by callback function');
				dummy._rx_ajax_error = true;
				dummy.cause = data;
				dummy.details = '';
				dummy.xhr = xhr;
				reject(dummy);
				return;
			}

			// Otherwise, generate a generic error message.
			let error_message = 'AJAX error: ' + (action || 'form submission');
			let error_details = '';
			if (data.error != 0 && data.message) {
				error_message = data.message.replace(/\\n/g, "\n");
				if (data.errorDetail) {
					error_details = data.errorDetail;
				}
			} else if (xhr.status == 0) {
				error_details = 'Connection failed: ' + url + "\n\n" + (xhr.responseText || '');
			} else {
				error_details = (xhr.responseText || '');
			}
			if (error_details.length > 1000) {
				error_details = error_details.substring(0, 1000) + '...';
			}

			// Reject the promise with an error object.
			// If uncaught, this will be handled by the 'unhandledrejection' event listener.
			const err = new Error(error_message);
			err._rx_ajax_error = true;
			err.cause = data;
			err.details = error_details;
			err.xhr = xhr;
			reject(err);
		};

		// Pass off to jQuery with another wrapper around the success and error wrappers.
		// This allows us to handle HTTP 400+ error codes with valid JSON responses.
		$.ajax({
			type: 'POST',
			dataType: 'json',
			url: url,
			data: isFormData ? params : JSON.stringify(params),
			contentType: isFormData ? false : 'application/json; charset=UTF-8',
			processData: false,
			headers: headers,
			success: successWrapper,
			error: function(xhr, textStatus, errorThrown) {
				if (xhr.status == 0 && Rhymix.unloading) {
					return;
				}
				if (xhr.status >= 400 && xhr.responseText) {
					try {
						let data = JSON.parse(xhr.responseText);
						if (data) {
							successWrapper(data, textStatus, xhr);
							return;
						}
					} catch (e) { }
				}
				errorWrapper({ error: 0, message: textStatus }, textStatus, xhr);
			}
		});
	});
};

/**
 * Submit a form using AJAX instead of navigating away
 *
 * @param HTMLElement form
 * @param function callback_success
 * @param function callback_error
 * @return void
 */
Rhymix.ajaxForm = function(form, callback_success, callback_error) {
	const $form = $(form);
	// Get success and error callback functions.
	if (typeof callback_success === 'undefined') {
		callback_success = $form.data('callbackSuccess');
		if (callback_success && typeof callback_success === 'function') {
			// no-op
		} else if (callback_success && window[callback_success] && typeof window[callback_success] === 'function') {
			callback_success = window[callback_success];
		} else {
			callback_success = function(data) {
				if (data.message && data.message !== 'success') {
					rhymix_alert(data.message, data.redirect_url);
				}
				if (data.redirect_url) {
					Rhymix.redirectToUrl(data.redirect_url.replace(/&amp;/g, '&'));
				}
			};
		}
	}
	if (typeof callback_error === 'undefined') {
		callback_error = $form.data('callbackError');
		if (callback_error && typeof callback_error === 'function') {
			// no-op
		} else if (callback_error && window[callback_error] && typeof window[callback_error] === 'function') {
			callback_error = window[callback_error];
		} else {
			callback_error = null;
		}
	}
	this.ajax(null, new FormData($form[0]), callback_success, callback_error);
};

/**
 * Toggle all checkboxes that have the same name
 *
 * This is a legacy function. Do not write new code that relies on it.
 *
 * @param string name
 * @return void
 */
Rhymix.checkboxToggleAll = function(name) {
	if (typeof name === 'undefined') {
		name='cart';
	}
	let options = {
		wrap : null,
		checked : 'toggle',
		doClick : false
	};

	if (arguments.length == 1) {
		if(typeof(arguments[0]) == 'string') {
			name = arguments[0];
		} else {
			$.extend(options, arguments[0] || {});
			name = 'cart';
		}
	} else {
		name = arguments[0];
		$.extend(options, arguments[1] || {});
	}

	if (options.doClick === true) {
		options.checked = null;
	}
	if (typeof options.wrap  === 'string') {
		options.wrap = '#' + options.wrap;
	}

	let obj;
	if (options.wrap) {
		obj = $(options.wrap).find('input[name="'+name+'"]:checkbox');
	} else {
		obj = $('input[name="'+name+'"]:checkbox');
	}

	if (options.checked === 'toggle') {
		obj.each(function() {
			$(this).prop('checked', $(this).prop('checked') ? false : true);
		});
	} else {
		if(options.doClick === true) {
			obj.click();
		} else {
			obj.prop('checked', options.checked);
		}
	}
};

/**
 * Display a popup menu for members, documents, etc.
 *
 * @param object ret_obj
 * @param object response_tags
 * @param object params
 * @return void
 */
Rhymix.displayPopupMenu = function(ret_obj, response_tags, params) {
	const menu_id = params.menu_id;
	const menus = ret_obj.menus;
	let html = "";

	if (this.loadedPopupMenus[menu_id]) {
		html = this.loadedPopupMenus[menu_id];
	} else {
		if (menus) {
			let item = menus.item || menus;
			if (typeof item.length === 'undefined' || item.length < 1) {
				item = new Array(item);
			}
			if (item.length) {
				for (let i = 0; i < item.length; i++) {
					var url = item[i].url;
					var str = item[i].str;
					var classname = item[i]['class'];
					var icon = item[i].icon;
					var target = item[i].target;

					// Convert self to _self #2154
					if (target === 'self') {
						target = '_self';
					}

					var actmatch = url.match(/\bact=(\w+)/) || url.match(/\b((?:disp|proc)\w+)/);
					var act = actmatch ? actmatch[1] : null;
					var classText = 'class="' + (classname ? classname : (act ? (act + ' ') : ''));
					var styleText = "";
					var click_str = "";
					var matches = [];
					if (target === 'popup') {
						if (this.isMobile()) {
							click_str = 'onclick="openModalIframe(this.href, \''+target+'\'); return false;"';
						} else {
							click_str = 'onclick="popopen(this.href, \''+target+'\'); return false;"';
						}
						classText += 'popup ';
					} else if (target === 'javascript') {
						click_str = 'onclick="'+url+'; return false; "';
						classText += 'javascript ';
						url = '#';
					} else if (target.match(/^_(self|blank|parent|top)$/)) {
						click_str = 'target="' + target + '"';
						classText += 'frame_' + target + ' ';
					} else if (matches = target.match(/^i?frame:([a-zA-Z0-9_]+)$/)) {
						click_str = 'target="' + matches[1] + '"';
						classText += 'frame_' + matches[1] + ' ';
					} else {
						click_str = 'target="_blank"';
					}
					classText = classText.trim() + '" ';

					html += '<li '+classText+styleText+'><a href="'+url+'" '+click_str+'>'+str+'</a></li> ';
				}
			}
		}
		this.loadedPopupMenus[menu_id] =  html;
	}

	/* 레이어 출력 */
	if (html) {
		const area = $('#popup_menu_area').html('<ul>'+html+'</ul>');
		const areaOffset = {top:params.page_y, left:params.page_x};
		if (area.outerHeight()+areaOffset.top > $(window).height()+$(window).scrollTop()) {
			areaOffset.top = $(window).height() - area.outerHeight() + $(window).scrollTop();
		}
		if (area.outerWidth()+areaOffset.left > $(window).width()+$(window).scrollLeft()) {
			areaOffset.left = $(window).width() - area.outerWidth() + $(window).scrollLeft();
		}
		area.css({ top:areaOffset.top, left:areaOffset.left }).show().focus();
	}
};

/**
 * Format file size
 *
 * @param int size
 * @return string
 */
Rhymix.filesizeFormat = function(size) {
	if (size < 2) {
		return size + 'Byte';
	}
	if (size < 1024) {
		return size + 'Bytes';
	}
	if (size < 1048576) {
		return (size / 1024).toFixed(1) + 'KB';
	}
	if (size < 1073741824) {
		return (size / 1048576).toFixed(2) + 'MB';
	}
	if (size < 1099511627776) {
		return (size / 1073741824).toFixed(2) + 'GB';
	}
	return (size / 1099511627776).toFixed(2) + 'TB';
};

/**
 * Get or set a lang code
 *
 * @param string key
 * @param string val
 * @return string|void
 */
Rhymix.lang = function(key, val) {
	if (typeof val === 'undefined')	{
		return this.langCodes[key] || key;
	} else {
		return this.langCodes[key] = val;
	}
};

// Add aliases to loaded libraries
Rhymix.cookie = window.Cookies;
Rhymix.URI = window.URI;
Rhymix.URITemplate = window.URITemplate;
Rhymix.SecondLevelDomains = window.SecondLevelDomains;
Rhymix.IPv6 = window.IPv6;

// Set window properties for backward compatibility
const XE = window.XE = Rhymix;

/**
 * ============================
 * Document ready event handler
 * ============================
 */

$(function() {

	/**
	 * Inject CSRF token to all POST forms
	 */
	$('form[method]').filter(function() {
		return String($(this).attr('method')).toUpperCase() == 'POST';
	}).addCSRFTokenToForm();
	$(document).on('submit', 'form[method=post]', $.fn.addCSRFTokenToForm);
	$(document).on('focus', 'input,select,textarea', function() {
		$(this).parents('form[method]').filter(function() {
			return String($(this).attr('method')).toUpperCase() == 'POST';
		}).addCSRFTokenToForm();
	});

	/**
	 * Reverse tabnapping protection
	 *
	 * Automatically add rel="noopener" to any external link with target="_blank"
	 * This is not required in most modern browsers.
	 * https://caniuse.com/mdn-html_elements_a_implicit_noopener
	 */
	const noopenerRequired = (function() {
		const isChromeBased = navigator.userAgent.match(/Chrome\/([0-9]+)/);
		if (isChromeBased && parseInt(isChromeBased[1], 10) >= 72) {
			return false;
		}
		const isAppleWebKit = navigator.userAgent.match(/AppleWebKit\/([0-9]+)/);
		if (isAppleWebKit && parseInt(isAppleWebKit[1], 10) >= 605) {
			return false;
		}
		const isFirefox = navigator.userAgent.match(/Firefox\/([0-9]+)/);
		if (isFirefox && parseInt(isFirefox[1], 10) >= 79) {
			return false;
		}
		return true;
	})();
	$('a[target]').each(function() {
		const $this = $(this);
		const href = String($this.attr('href')).trim();
		const target = String($this.attr('target')).trim();
		if (!href || !target || target === '_top' || target === '_self' || target === '_parent') {
			return;
		}
		if (!Rhymix.isSameHost(href)) {
			let rel = $this.attr('rel');
			rel = (typeof rel === 'undefined') ? '' : String(rel);
			if (!rel.match(/\bnoopener\b/)) {
				$this.attr('rel', $.trim(rel + ' noopener'));
			}
		}
	});
	$(document).on('click', 'a[target]', function(event) {
		const $this = $(this);
		const href = String($this.attr('href')).trim();
		const target = String($this.attr('target')).trim();
		if (!href || !target || target === '_top' || target === '_self' || target === '_parent') {
			return;
		}
		if (!Rhymix.isSameHost(href)) {
			let rel = $this.attr('rel');
			rel = (typeof rel === 'undefined') ? '' : String(rel);
			if (!rel.match(/\bnoopener\b/)) {
				$this.attr('rel', $.trim(rel + ' noopener'));
			}
			if (noopenerRequired) {
				event.preventDefault();
				blankshield.open(href);
			}
		}
	});

	/**
	 * Enforce max filesize on file uploaeds
	 */
	$(document).on('change', 'input[type=file]', function() {
		const max_filesize = $(this).data('max-filesize');
		if (!max_filesize) {
			return;
		}
		const files = $(this).get(0).files;
		if (!files || !files[0]) {
			return;
		}
		if (files[0].size > max_filesize) {
			this.value = '';
			const error = String($(this).data('max-filesize-error'));
			alert(error.replace('%s', Rhymix.filesizeFormat(max_filesize)));
		}
	});

	/**
	 * Intercept form submission and handle them with AJAX
	 */
	$(document).on('submit', 'form.rx_ajax', function(event) {
		if (!$(this).attr('target')) {
			event.preventDefault();
			Rhymix.ajaxForm(this);
		}
	});

	/**
	 * Prevent repeated click on submit button
	 */
	$(document).on('click', 'input[type="submit"],button[type="submit"]', function(e) {
		const timeout = 3000;
		setTimeout(function() {
			$(this).prop('disabled', true);
		}, 100);
		setTimeout(function() {
			$(this).prop('disabled', false);
		}, timeout);
	});

	/**
	 * Display a popup menu for members, documents, etc.
	 */
	$(document).on('click', function(e) {
		var $area = $('#popup_menu_area');
		if (!$area.length) {
			$area = $('<div id="popup_menu_area" tabindex="0" style="display:none;" />').appendTo(document.body);
		}

		// 이전에 호출되었을지 모르는 팝업메뉴 숨김
		$area.hide();

		var $target = $(e.target).filter('a,div,span');
		if (!$target.length) {
			$target = $(e.target).closest('a,div,span');
		}
		if (!$target.length) {
			return;
		}

		// 객체의 className값을 구함
		var cls = $target.attr('class'), match;
		if (cls) {
			match = cls.match(new RegExp('(?:^| )((document|comment|member)_([1-9][0-9]*))(?: |$)',''));
		}
		if (!match) {
			return;
		}

		// mobile에서 touchstart에 의한 동작 시 pageX, pageY 위치를 구함
		if (e.pageX === undefined || e.pageY === undefined)
		{
			let touch = e.originalEvent.touches[0];
			if (touch !== undefined || !touch) {
				touch = e.originalEvent.changedTouches[0];
			}
			e.pageX = touch.pageX;
			e.pageY = touch.pageY;
		}

		var module = match[2];
		var action = 'get' + ucfirst(module) + 'Menu';
		var target_srl = match[3];
		var params = {
			mid        : current_mid,
			cur_mid    : current_mid,
			menu_id    : match[1],
			target_srl : target_srl,
			cur_act    : current_url.getQuery('act'),
			page_x     : e.pageX,
			page_y     : e.pageY
		};
		var response_tags = ['error', 'message', 'menus'];

		// prevent default action
		e.preventDefault();
		e.stopPropagation();

		if (Rhymix.loadedPopupMenus[params.menu_id]) {
			return Rhymix.displayPopupMenu(params, response_tags, params);
		}

		show_waiting_message = false;
		exec_json(module + '.' + action, params, function(data) {
			Rhymix.displayPopupMenu(data, response_tags, params);
			show_waiting_message = true;
		});
	});

	/**
	 * Create popup windows automatically for _xe_popup links
	 */
	$(document).on('click', 'a._xe_popup', function(e) {
		var $this = $(this);
		var name = $this.attr('name');
		var href = $this.attr('href');
		if (!name) {
			name = '_xe_popup_' + Math.floor(Math.random() * 1000);
		}
		e.preventDefault();
		winopen(href, name, 'left=10,top=10,width=10,height=10,resizable=no,scrollbars=no,toolbars=no');
	});

	/**
	 * Editor preview replacement
	 */
	const editable_previews = $('.editable_preview');
	editable_previews.addClass('rhymix_content xe_content').attr('tabindex', 0);
	editable_previews.on('click', function() {
		let input = $(this).siblings('.editable_preview_content');
		if (input.size()) {
			$(this).off('click').off('focus').hide();
			input = input.first();
			if (input.attr('type') !== 'hidden') {
				input.hide();
			}
			let iframe = $('<iframe class="editable_preview_iframe"></iframe>');
			iframe.attr('src', current_url.setQuery('module', 'editor').setQuery('act', 'dispEditorFrame').setQuery('parent_input_id', input.attr('id')).replace(/^https?:/, ''));
			iframe.insertAfter(input);
		}
	});
	editable_previews.on('focus', function() {
		$(this).triggerHandler('click');
	});

	/**
	 * Datepicker default settings
	 */
	if ($.datepicker) {
		$.datepicker.setDefaults({
			dateFormat : 'yy-mm-dd'
		});
	}

	/**
	 * Display any pending alert messages
	 */
	if(Cookies.get('rhymix_alert_message')) {
		rhymix_alert(Cookies.get('rhymix_alert_message'), null, Cookies.get('rhymix_alert_delay'));
		Cookies.remove('rhymix_alert_message', { path: '/' });
		Cookies.remove('rhymix_alert_delay', { path: '/' });
	}
	$('#rhymix_alert').on('click', rhymix_alert_close);

});

/**
 * ====================
 * Other event handlers
 * ====================
 */

// Intercept getScript error due to broken minified script URLs
$(document).ajaxError(function(event, jqxhr, settings, thrownError) {
	if(settings.dataType === "script" && (jqxhr.status >= 400 || (jqxhr.responseText && jqxhr.responseText.length < 40))) {
		const match = /^(.+)\.min\.(css|js)($|\?)/.exec(settings.url);
		if(match) {
			$.getScript(match[1] + "." + match[2], settings.success);
		}
	}
});

// General handler for page unload events
window.addEventListener('beforeunload', function() {
	Rhymix.unloading = true;
});

// General handler for unhandled Promise rejections
window.addEventListener('unhandledrejection', function(event) {
	if (event.reason && typeof event.reason['_rx_ajax_error'] === 'boolean') {
		event.preventDefault();
		const error_message = event.reason.message.trim();
		const error_details = event.reason.details || '';
		const error_xhr = event.reason.xhr || {};
		console.error(error_message.replace(/\n+/g, "\n" + error_details));
		if (Rhymix.showAjaxErrors.indexOf('ALL') >= 0 || Rhymix.showAjaxErrors.indexOf(error_xhr.status) >= 0) {
			alert(error_message.trim() + (error_details ? ("\n\n" + error_details) : ''));
		}
	}
});

// General handler for popstate events
window.addEventListener('popstate', function(event) {
	// Close modal if it is open
	if ($(document.body).hasClass('rx_modal_open')) {
		const body = $(document.body).removeClass('rx_modal_open');
		const scroll_position = body.data('rx_scroll_position');
		if (scroll_position) {
			$(window).scrollLeft(scroll_position.left);
			$(window).scrollTop(scroll_position.top);
			body.removeData('rx_scroll_position');
		}
		$('.rx_modal').each(function() {
			if (this.nodeName === 'IFRAME') {
				$(this).remove();
			} else {
				$(this).removeClass('active');
			}
		});
	}
});

// Fix for browsers that don't support the unhandledrejection event
if (typeof Promise._unhandledRejectionFn !== 'undefined') {
	Promise._unhandledRejectionFn = function(error) {
		if (error['_rx_ajax_error']) {
			alert(error.message.trim());
		}
	};
}

/**
 * =================
 * jQuery extensions
 * =================
 */

(function($) {

	// OS check
	const UA = navigator.userAgent.toLowerCase();
	$.os = {
		Windows: /win/.test(UA),
		Android: /android/.test(UA),
		iOS: /i(Phone|Pad)/.test(UA),
		Linux: /linux/.test(UA),
		Unix: /x11/.test(UA),
		Mac: /mac/.test(UA)
	};
	$.os.name = ($.os.Windows) ? 'Windows' :
		($.os.Android) ? 'Android' :
		($.os.iOS) ? 'iOS' :
		($.os.Linux) ? 'Linux' :
		($.os.Unix) ? 'Unix' :
		($.os.Mac) ? 'Mac' : '';

	// Add CSRF token to AJAX calls
	$.ajaxPrefilter(function(options) {
		if (!Rhymix.isSameOrigin(location.href, options.url)) {
			return;
		}
		const token = Rhymix.getCSRFToken();
		if (token) {
			if (!options.headers) {
				options.headers = {};
			}
			options.headers['X-CSRF-Token'] = token;
		}
	});

	// Add CSRF token to dynamically loaded forms
	$.fn.addCSRFTokenToForm = function() {
		const form = $(this);
		const token = Rhymix.getCSRFToken();
		if (token) {
			return form.each(function() {
				if (form.data('csrf-token-checked')) return;
				if (form.attr('action') && !Rhymix.isSameOrigin(location.href, form.attr('action'))) {
					return form.data('csrf-token-checked', true);
				}
				$('<input type="hidden" name="_rx_csrf_token" />').val(token).appendTo(form);
				return form.data('csrf-token-checked', true);
			});
		} else {
			return form;
		}
	};

})(jQuery);

/**
 * =================
 * String extensions
 * =================
 */

/**
 * Get a query parameter from a URL
 *
 * @param string key
 * @return string
 */
String.prototype.getQuery = function(key) {
	const queries = Rhymix.URI(this).search(true);
	const result = queries[key];
	if(typeof result === 'undefined') {
		return '';
	} else {
		return result;
	}
};

/**
 * Add or replace a query parameter in a URL
 *
 * @param string key
 * @param string val
 * @return string
 */
String.prototype.setQuery = function(key, val) {
	const uri = Rhymix.URI(this);
	const protocol = window.enforce_ssl ? 'https' : uri.protocol();
	const port = (protocol === 'http') ? window.http_port : window.https_port;
	let filename = uri.filename() || 'index.php';
	let queries = uri.search(true);

	if(typeof key !== 'undefined') {
		if(typeof val === "undefined" || val === '' || val === null) {
			uri.removeSearch(key);
		} else {
			uri.setSearch(key, String(val));
		}
	}

	if (Rhymix.isSameHost(uri.toString()) && filename === 'index.php' && $.isEmptyObject(queries)) {
		filename = '';
	}

	return uri.protocol(protocol).port(port || null).normalizePort().filename(filename).toString();
};

/**
 * Escape a string for HTML output
 *
 * @param bool double_escape
 * @return string
 */
String.prototype.escape = function(double_escape) {
	const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
	const revmap = { '&amp;amp;': '&amp;', '&amp;lt;': '&lt;', '&amp;gt;': '&gt;', '&amp;quot;': '&quot;', "&amp;#039;": '&#039;' };
	const result = String(this).replace(/[&<>"']/g, function(m) { return map[m]; });
	if (double_escape === false) {
		return result.replace(/&amp;(amp|lt|gt|quot|#039);/g, function(m) { return revmap[m]; });
	} else {
		return result;
	}
};

/**
 * Unescape a string from HTML output
 *
 * @return string
 */
String.prototype.unescape = function() {
	const map = { '&amp;': '&', '&lt;': '<', '&gt;': '>', '&quot;': '"', '&#039;': "'" };
	return String(this).replace(/&(amp|lt|gt|quot|#039);/g, function(m) { return map[m]; });
};

/**
 * Strip HTML tags from a string
 *
 * @return string
 */
String.prototype.stripTags = function() {
	return String(this).replace(/<\/?[a-z][^>]*>/ig, "");
};

/**
 * Trim whitespace from the beginning and end of a string
 *
 * @return string
 */
if (!String.prototype.trim) {
	String.prototype.trim = function() {
		return String(this).replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g, '');
	};
}

/**
 * ==============================================================
 * Global functions (most of these are aliases to Rhymix methods)
 * ==============================================================
 */

/**
 * Check if the current device is a mobile device.
 *
 * @return bool
 */
function isMobile() {
	return Rhymix.isMobile();
}

/**
 * Get the current color scheme
 *
 * @return string
 */
function getColorScheme() {
	return Rhymix.getColorScheme();
}

/**
 * Set the color scheme
 *
 * @param string color_scheme
 * @return void
 */
function setColorScheme(color_scheme) {
	return Rhymix.setColorScheme(color_scheme);
}

/**
 * Automatically detect the color scheme
 *
 * @return void
 */
function detectColorScheme() {
	return Rhymix.detectColorScheme();
}

/**
 * Get the language
 *
 * @return string
 */
function getLangType() {
	return Rhymix.getLangType();
}

/**
 * Set the language
 *
 * @param string lang_type
 * @return void
 */
function setLangType(lang_type) {
	return Rhymix.setLangType(lang_type);
}

/**
 * Get CSRF token for this document
 *
 * @return string|null
 */
function getCSRFToken() {
	return Rhymix.getCSRFToken();
}

/**
 * Set CSRF token for this document
 *
 * @param string token
 * @return void
 */
function setCSRFToken(token) {
	return Rhymix.setCSRFToken(token);
}

/**
 * Check if a URL is identical to the current page URL except for the hash
 *
 * @param string url
 * @return bool
 */
function isCurrentPageUrl(url) {
	return Rhymix.isCurrentUrl(url);
}

/**
 * Check if two URLs belong to the same origin
 *
 * @param string url1
 * @param string url2
 * @return bool
 */
function isSameOrigin(url1, url2) {
	return Rhymix.isSameOrigin(url1, url2);
}

/**
 * Redirect to a URL, but reload instead if the target is the same as the current page
 *
 * @param string url
 * @return void
 */
function redirect(url) {
	return Rhymix.redirectToUrl(url);
}

/**
 * Open an HTML element as a modal
 *
 * @param string id
 * @return void
 */
function openModal(id) {
	return Rhymix.modal.open(id);
}

/**
 * Open an iframe as a modal
 *
 * @param string url
 * @param string target
 * @return void
 */
function openModalIframe(url, target) {
	return Rhymix.modal.openIframe(url, target);
}

/**
 * Close currently open modal
 *
 * @param string id
 * @return void
 */
function closeModal(id) {
	return Rhymix.modal.close(id);
}

/**
 * Open a new window and focus it
 *
 * @param string url
 * @param string target
 * @param string features
 */
function winopen(url, target, features) {
	return Rhymix.openWindow(url, target, features);
}

/**
 * Open a popup window with standardized features
 *
 * @param string url
 * @param string target
 * @return void
 */
function popopen(url, target) {
	return Rhymix.openPopup(url, target);
}

/**
 * ===============================================
 * Legacy functions related to document management
 * ===============================================
 */

/**
 * Add a document to the cart for admin action.
 *
 * @param object obj
 * @return void
 */
function doAddDocumentCart(obj) {
	if (obj && obj.value) {
		Rhymix.addedDocument.push(obj.value);
	}
	setTimeout(function() {
		if (Rhymix.addedDocument.length > 0) {
			exec_json('document.procDocumentAddCart', {
				srls: Rhymix.addedDocument
			});
			Rhymix.addedDocument = [];
		}
	}, 100);
}

/**
 * Open a document preview
 *
 * @param object obj
 * @return void
 */
function doDocumentPreview(obj) {
	var fo_obj = obj;
	while (fo_obj.nodeName != "FORM") {
		fo_obj = fo_obj.parentNode;
	}
	if (fo_obj.nodeName != "FORM") {
		return;
	}

	var editor_sequence = fo_obj.getAttribute('editor_sequence');
	var content = editorGetContent(editor_sequence);
	var win = window.open("", "previewDocument","toolbars=no,width=700px;height=800px,scrollbars=yes,resizable=yes");
	var dummy_obj = $("#previewDocument");
	if (!dummy_obj.length) {
		$(
			'<form id="previewDocument" target="previewDocument" method="post" action="'+request_uri+'">'+
			'<input type="hidden" name="_rx_csrf_token" value="' + getCSRFToken() + '" />'+
			'<input type="hidden" name="module" value="document" />'+
			'<input type="hidden" name="act" value="dispDocumentPreview" />'+
			'<input type="hidden" name="mid" value="' + current_mid +'" />'+
			'<input type="hidden" name="content" />'+
			'</form>'
		).appendTo(document.body);
		dummy_obj = $("#previewDocument")[0];
	} else {
		dummy_obj = dummy_obj[0];
	}

	if(dummy_obj) {
		dummy_obj.content.value = content;
		dummy_obj.submit();
	}
}

/**
 * Temporarily save a document
 *
 * @param object obj
 * @returns
 */
function doDocumentSave(obj) {
	var editor_sequence = obj.form.getAttribute('editor_sequence');
	var prev_content = editorRelKeys[editor_sequence].content.value;
	if (typeof(editor_sequence) !== 'undefined' && editor_sequence && typeof(editorRelKeys) !== 'undefined' && typeof(editorGetContent) === 'function') {
		var content = editorGetContent(editor_sequence);
		editorRelKeys[editor_sequence].content.value = content;
	}

	var params={}, data=$(obj.form).serializeArray();
	$.each(data, function(i, field){
		var val = $.trim(field.value);
		if (!val) {
			return true;
		}
		if (/\[\]$/.test(field.name)) {
			field.name = field.name.replace(/\[\]$/, '');
		}
		if (params[field.name]) {
			params[field.name] += '|@|'+val;
		} else {
			params[field.name] = field.value;
		}
	});

	exec_json('document.procDocumentTempSave', params, function(ret_obj) {
		$('input[name=document_srl]').eq(0).val(ret_obj.document_srl);
		alert(ret_obj.message);
	});

	editorRelKeys[editor_sequence].content.value = prev_content;
	return false;
}

/**
 * Load the list of saved documents
 */
function doDocumentLoad(obj) {
	var popup_url = request_uri.setQuery('module','document').setQuery('act','dispTempSavedList');
	if (Rhymix.isMobile()) {
		openModalIframe(popup_url);
	} else {
		popopen(popup_url);
	}
}

/**
 * Select from the list of saved documents
 *
 * @param int document_srl
 * @param string module
 * @return void
 */
function doDocumentSelect(document_srl, module) {
	if (!opener) {
		window.close();
		return;
	}
	if (module === undefined) {
		module = 'document';
	}

	// 게시글을 가져와서 등록하기
	if (module === 'page') {
		var url = opener.current_url;
		url = url.setQuery('document_srl', document_srl);
		if (url.getQuery('act') === 'dispPageAdminMobileContentModify') {
			url = url.setQuery('act', 'dispPageAdminMobileContentModify');
		} else {
			url = url.setQuery('act', 'dispPageAdminContentModify');
		}
		opener.location.href = url;
	} else {
		opener.location.href = opener.current_url.setQuery('act', 'dispBoardWrite').setQuery('document_srl', document_srl);
	}
	window.close();
}

/**
 * ==================================================================
 * Deprecated functions (Please avoid using anything below this line)
 * ==================================================================
 */

/**
 * Get a cookie
 *
 * Use Rhymix.cookie.get() instead
 *
 * @deprecated
 * @param string name
 * @return string|null
 */
function getCookie(name) {
	return Rhymix.cookie.get(name);
}

/**
 * Set a cookie
 *
 * Use Rhymix.cookie.set() instead
 *
 * @deprecated
 * @param string name
 * @param string value
 * @param int expires
 * @param string path
 * @return void
 */
function setCookie(name, value, expires, path) {
	var options = {
		path: path ? path : '/',
		secure: cookies_ssl ? true : false
	};
	if (expires) {
		options.expires = expires;
	}
	Rhymix.cookie.set(name, value, options);
}

/**
 * Change the language and reload the page
 *
 * @deprecated
 * @param string|object obj
 * @return void
 */
function doChangeLangType(obj) {
	var msg = "DEPRECATED : doChangeLangType() is deprecated in Rhymix.";
	if (navigator.userAgent.match(/Firefox/)) {
		console.error(msg);
	} else {
		console.warn(msg);
	}

	if (typeof(obj) == 'string') {
		setLangType(obj);
	} else {
		setLangType(obj.options[obj.selectedIndex].value);
	}
	if (location.href.match(/[?&]l=[a-z]+/)) {
		location.href = location.href.setQuery('l', '');
	} else {
		location.reload();
	}
}

/**
 * Make an AJAX request with a given target_srl
 *
 * Please use Rhymix.ajax() instead, which is much more powerful.
 *
 * @deprecated
 * @param string module
 * @param string action
 * @param int target_rl
 * @return void
 */
function doCallModuleAction(module, action, target_srl) {
	var msg = "DEPRECATED : doCallModuleAction() is deprecated in Rhymix.";
	if (navigator.userAgent.match(/Firefox/)) {
		console.error(msg);
	} else {
		console.warn(msg);
	}

	const params = {
		target_srl: target_srl,
		cur_mid: current_mid,
		mid: current_mid
	};
	Rhymix.ajax(module + '.' + action, params, function(data) {
		if (data.message !== 'success') {
			alert(data.message);
		}
		location.reload();
	});
}

/**
 * Close an alert box
 *
 * @deprecated
 * @return void
 */
function rhymix_alert_close() {
	if ($('#rhymix_alert').is(':hidden')) {
		return;
	}
	$('#rhymix_alert').fadeOut(500, function() {
		$(this).empty();
	});
};

/**
 * Display an alert box
 *
 * @deprecated
 * @param string message
 * @param string redirect_url
 * @param int delay
 * @return void
 */
function rhymix_alert(message, redirect_url, delay) {
	if (!delay) {
		delay = 2500;
	}
	if (!redirect_url) {
		$('#rhymix_alert').text(message).show();
		setTimeout(rhymix_alert_close, delay);
	} else if (Rhymix.isSameOrigin(location.href, redirect_url)) {
		Cookies.set('rhymix_alert_message', message, { expires: 1 / 1440, path: '/' });
		Cookies.set('rhymix_alert_delay', delay, { expires: 1 / 1440, path: '/' });
	} else {
		alert(message);
	}
};

/**
 * Move to a URL
 *
 * @deprecated
 * @param string url
 * @param string open_window
 * @return bool
 */
function move_url(url, open_window) {
	if (!url) {
		return false;
	}
	if (/^\./.test(url)) {
		url = window.request_uri + url;
	}
	if (typeof open_window == 'undefined' || open_window == 'N') {
		redirect(url);
	} else {
		winopen(url);
	}
	return false;
}

/**
 * Resize a popup window according to the size of its contents
 *
 * @deprecated
 * @return void
 */
function setFixedPopupSize() {
	var $win = $(window);
	var $pc = $('body > .popup');
	var $outer = $('<div>').css({visibility: 'hidden', width: 100, overflow: 'scroll'}).appendTo('body');
	var widthWithScroll = $('<div>').css({width: '100%'}).appendTo($outer).outerWidth();
	$outer.remove();
	var scbw = 100 - widthWithScroll;
	var offset = $pc.css({overflow: 'scroll'}).offset();

	var w = $pc.width(10).height(10000).get(0).scrollWidth + offset.left*2;
	if (w < 800) {
		w = 800 + (offset.left * 2);
	}

	// window의 너비나 높이는 스크린의 너비나 높이보다 클 수 없다.
	// 스크린의 너비나 높이와 내용의 너비나 높이를 비교해서 최소값을 이용한다.
	w = Math.min(w, window.screen.availWidth);
	var h = $pc.width(w - offset.left*2).height(10).get(0).scrollHeight + offset.top*2;
	var dw = $win.width();
	var dh = $win.height();

	h = Math.min(h, window.screen.availHeight - 100);
	window.resizeBy(w - dw, h - dh);
	$pc.width('100%').css({
		overflow: '',
		height: '',
		'box-sizing': 'border-box'
	});
}

/**
 * Print a thumbnail for multimedia content
 *
 * @deprecated
 * @param string src
 * @param int width
 * @param int height
 * @param object options
 * @return void
 */
function displayMultimedia(src, width, height, options) {
	const html = _displayMultimedia(src, width, height, options);
	if (html) {
		document.writeln(html);
	}
}
function _displayMultimedia(src, width, height, options) {
	width = parseInt(width, 10);
	height = parseInt(height, 10);
	if (src.indexOf('files') === 0) {
		src = request_uri + src;
	}

	var html = '';
	var background = 'black';

	if (/\.(gif|jpe?g|bmp|png|webp)$/i.test(src)){
		html = '<img src="'+src+'" width="'+width+'" height="'+height+'" class="thumb" />';
	} else {
		if (options.thumbnail) {
			background += " url('" + options.thumbnail + "');background-size:cover;background-position:center center";
		}
		html = '<span style="position:relative;background:' + background + ';width:' + width + 'px;height:' + height + 'px" class="thumb">';
		html += '<img style="width:24px;height:24px;position:absolute;left:50%;top:50%;border:0;margin:-12px 0 0 -12px;padding:0" src="' + request_uri + 'common/img/play.png" alt="" />';
		html += '</span>';
	}
	return html;
}

/**
 * Convert rgb(r,g,b) to HEX
 *
 * @param string value
 * @return string
 */
function transRGB2Hex(value) {
	if (!value) {
		return value;
	}
	if (value.indexOf('#') > -1) {
		return value.replace(/^#/, '');
	}
	if (value.toLowerCase().indexOf('rgb') < 0) {
		return value;
	}
	value = value.replace(/^rgb\(/i, '').replace(/\)$/, '');
	value_list = value.split(',');

	var hex = '';
	for (var i = 0; i < value_list.length; i++) {
		var color = parseInt(String(value_list[i]).trim(), 10).toString(16);
		if (color.length == 1) {
			color = '0' + color;
		}
		hex += color;
	}
	return hex;
}

/**
 * Send an email
 *
 * @deprecated
 * @param string email_address
 * @return void
 */
function sendMailTo(email_address) {
	location.href = 'mailto:' + email_address;
}

/**
 * View skin information
 *
 * @deprecated
 * @param string module
 * @param string skin
 * @return void
 */
function viewSkinInfo(module, skin) {
	const url = './?module=module&act=dispModuleSkinInfo&selected_module=' + module + '&skin=' + skin;
	popopen(url, 'SkinInfo');
}

/**
 * Sleep for seconds
 *
 * @deprecated
 * @param float sec
 * @return void
 */
function xSleep(sec) {
	sec = sec / 1000;
	var now = new Date();
	var sleep = new Date();
	while (sleep.getTime() - now.getTime() < sec) {
		sleep = new Date();
	}
}

/**
 * Check if any argument is a defined variable
 *
 * @deprecated
 * @param mixed arguments
 * @return bool
 */
function isDef() {
	for (let i = 0; i < arguments.length; ++i) {
		if (typeof(arguments[i]) == 'undefined') {
			return false;
		}
	}
	return true;
}

/**
 * Check if a variable is defined and not null
 *
 * This seems to be similar to isset() in PHP
 *
 * @deprecated
 * @param mixed v
 * @return bool
 */
function is_def(v) {
	return typeof(v) != 'undefined' && v !== null;
}

/**
 * Convert the first character of a string to uppercase
 *
 * @deprecated
 * @param string str
 * @return string
 */
function ucfirst(str) {
	return str.charAt(0).toUpperCase() + str.slice(1);
}

/**
 * Get an element by ID
 *
 * @deprecated
 * @param string id
 * @return HTMLElement|null
 */
function get_by_id(id) {
	return document.getElementById(id);
}

/**
 * Get the left position of an object
 *
 * @deprecated
 * @param HTMLElement obj
 * @return int
 */
function GetObjLeft(obj) {
	return $(obj).offset().left;
}

/**
 * Get the top position of an object
 *
 * @deprecated
 * @param HTMLElement obj
 * @return int
 */
function GetObjTop(obj) {
	return $(obj).offset().top;
}

/**
 * Get the outer HTML of an object
 *
 * @deprecated
 * @param HTMLElement obj
 * @return string
 */
function getOuterHTML(obj) {
	return $(obj).html().trim();
}

/**
 * Replace the entire object with the given HTML
 *
 * @deprecated
 * @param HTMLElement obj
 * @param string html
 * @return void
 */
function replaceOuterHTML(obj, html) {
	$(obj).replaceWith(html);
}

/**
 * Show or hide an element
 *
 * @deprecated
 * @param string id
 * @return void
 */
function toggleDisplay(id) {
	$('#' + id).toggle();
}

/**
 * Toggle between HTTP and HTTPS
 *
 * @deprecated
 * @return void
 */
function toggleSecuritySignIn() {
	var href = location.href;
	if (/https:\/\//i.test(href)) {
		location.href = href.replace(/^https/i,'http');
	} else {
		location.href = href.replace(/^http/i,'https');
	}
}

/**
 * Display a message and reload the page
 *
 * @deprecated
 * @param object ret_obj
 * @return void
 */
function completeMessage(ret_obj) {
	alert(ret_obj.message);
	location.reload();
}

/**
 * Just reload the current page
 *
 * @deprecated
 * @return void
 */
function reloadDocument() {
	location.reload();
}

/**
 * Open a calendar popup
 *
 * @deprecated
 * @param string fo_id
 * @param string day_str
 * @param function callback_func
 * @return void
 */
function open_calendar(fo_id, day_str, callback_func) {
	console.warn('open_calendar() is a no-op in Rhymix');
}

/**
 * Display a popup menu
 *
 * @deprecated
 * @param object ret_obj
 * @param object response_tags
 * @param object params
 * @return void
 */
function displayPopupMenu(ret_obj, response_tags, params) {
	Rhymix.displayPopupMenu(ret_obj, response_tags, params);
}

/**
 * Create a popup menu
 *
 * @deprecated
 * @return void
 */
function createPopupMenu() {
	console.warn('createPopupMenu() is a no-op in Rhymix');
}

/**
 * Check (?) a popup menu
 *
 * @deprecated
 * @return void
 */
function chkPopupMenu() {
	console.warn('chkPopupMenu() is a no-op in Rhymix');
}

/**
 * These functions were used in xpresseditor
 */
function zbxe_folder_open(id) {
	$("#folder_open_" + id).hide();
	$("#folder_close_" + id).show();
	$("#folder_" + id).show();
}
function zbxe_folder_close(id) {
	$("#folder_open_" + id).show();
	$("#folder_close_" + id).hide();
	$("#folder_" + id).hide();
}
function svc_folder_open(id) {
	$("#_folder_open_" + id).hide();
	$("#_folder_close_" + id).show();
	$("#_folder_" + id).show();
}
function svc_folder_close(id) {
	$("#_folder_open_" + id).show();
	$("#_folder_close_" + id).hide();
	$("#_folder_" + id).hide();
}

/**
 * Shims for old variable names and functions
 */
var loaded_popup_menus = Rhymix.loadedPopupMenus;
var objectExtend = $.extend;
var ssl_actions = [];
if (typeof(resizeImageContents) == 'undefined') {
	window.resizeImageContents = function() {};
}
if (typeof(activateOptionDisabled) == 'undefined') {
	window.activateOptionDisabled = function() {};
}

/**
 * Shim for Modernizr if it is not loaded
 */
if (!window.Modernizr) {
	window.Modernizr = {
		audio: true,
		video: true,
		canvas: true,
		history: true,
		postmessage: true,
		geolocation: ('geolocation' in navigator),
		touch: ('ontouchstart' in window) || (navigator.maxTouchPoints > 0),
		webgl: !!window.WebGLRenderingContext
	};
}

/**
 * Shim for base64 encoding and decoding
 *
 * http://www.webtoolkit.info/
 */
const Base64 = {

	// private property
	_keyStr : "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=",

	// public method for encoding
	encode : function (input) {
		var output = "";
		var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
		var i = 0;

		input = Base64._utf8_encode(input);

		while (i < input.length) {

			chr1 = input.charCodeAt(i++);
			chr2 = input.charCodeAt(i++);
			chr3 = input.charCodeAt(i++);

			enc1 = chr1 >> 2;
			enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
			enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
			enc4 = chr3 & 63;

			if (isNaN(chr2)) {
				enc3 = enc4 = 64;
			} else if (isNaN(chr3)) {
				enc4 = 64;
			}

			output = output +
			this._keyStr.charAt(enc1) + this._keyStr.charAt(enc2) +
			this._keyStr.charAt(enc3) + this._keyStr.charAt(enc4);

		}

		return output;
	},

	// public method for decoding
	decode : function (input) {
		var output = "";
		var chr1, chr2, chr3;
		var enc1, enc2, enc3, enc4;
		var i = 0;

		input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");

		while (i < input.length) {
			enc1 = this._keyStr.indexOf(input.charAt(i++));
			enc2 = this._keyStr.indexOf(input.charAt(i++));
			enc3 = this._keyStr.indexOf(input.charAt(i++));
			enc4 = this._keyStr.indexOf(input.charAt(i++));

			chr1 = (enc1 << 2) | (enc2 >> 4);
			chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
			chr3 = ((enc3 & 3) << 6) | enc4;

			output = output + String.fromCharCode(chr1);

			if (enc3 != 64) {
				output = output + String.fromCharCode(chr2);
			}
			if (enc4 != 64) {
				output = output + String.fromCharCode(chr3);
			}
		}

		output = Base64._utf8_decode(output);

		return output;

	},

	// private method for UTF-8 encoding
	_utf8_encode : function (string) {
		string = string.replace(/\r\n/g,"\n");
		var utftext = "";

		for (var n = 0; n < string.length; n++) {
			var c = string.charCodeAt(n);

			if (c < 128) {
				utftext += String.fromCharCode(c);
			}
			else if((c > 127) && (c < 2048)) {
				utftext += String.fromCharCode((c >> 6) | 192);
				utftext += String.fromCharCode((c & 63) | 128);
			}
			else {
				utftext += String.fromCharCode((c >> 12) | 224);
				utftext += String.fromCharCode(((c >> 6) & 63) | 128);
				utftext += String.fromCharCode((c & 63) | 128);
			}
		}

		return utftext;
	},

	// private method for UTF-8 decoding
	_utf8_decode : function (utftext) {
		var string = "";
		var i = 0;
		var c = 0, c1 = 0, c2 = 0, c3 = 0;

		while ( i < utftext.length ) {
			c = utftext.charCodeAt(i);

			if (c < 128) {
				string += String.fromCharCode(c);
				i++;
			}
			else if((c > 191) && (c < 224)) {
				c2 = utftext.charCodeAt(i+1);
				string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
				i += 2;
			}
			else {
				c2 = utftext.charCodeAt(i+1);
				c3 = utftext.charCodeAt(i+2);
				string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
				i += 3;
			}
		}

		return string;
	}
};
