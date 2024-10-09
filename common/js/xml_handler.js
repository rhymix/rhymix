/**
 * Functions for sending AJAX requests to the server.
 */
(function($){

	"use strict";

	/**
	 * Set this variable to a list of HTTP status codes for which to show AJAX communication errors.
	 * To show all errors, include the string 'ALL'.
	 */
	window.show_ajax_errors = ['ALL'];

	/**
	 * Set this variable to true to show the "do you want to leave the page?" dialog.
	 * This may be useful on pages with important forms, but it is generally not recommended.
	 */
	window.show_leaving_warning = false;

	/**
	 * This variable becomes true when the user tries to navigate away from the page.
	 * It should not be manually edited.
	 */
	var page_unloading = false;

	/**
	 * Function for compatibility with XE's exec_xml()
	 */
	window.exec_xml = $.exec_xml = function(module, act, params, callback_success, return_fields, callback_success_arg, fo_obj) {

		// Display deprecation notice.
		if (typeof console == "object" && typeof console.warn == "function") {
			var msg = "DEPRECATED : exec_xml() is deprecated in Rhymix. Use exec_json() instead.";
			if (navigator.userAgent.match(/Firefox/)) {
				console.error(msg);
			} else {
				console.warn(msg);
			}
		}

		// Define callback functions.
		var successHandler, errorHandler, xmlHandler;

		// Convert params to object and fill in the module and act.
		params = params ? ($.isArray(params) ? arr2obj(params) : params) : {};
		params.module = module;
		params.act = act;

		// Decide whether or not to use SSL.
		var url = request_uri;

		// Check whether this is a cross-domain request. If so, use an alternative method.
		if (!isSameOrigin(location.href, url)) return send_by_form(url, params);

		// Define the success handler.
		successHandler = function(data, textStatus, xhr) {

			// Copy data to the result object.
			var result = {};
			$.each(data, function(key, val) {
				if ($.inArray(key, ["error", "message", "act", "redirect_url"]) >= 0 || $.inArray(key, return_fields) >= 0) {
					result[key] = val;
				}
			});

			// Add debug information.
			if (data._rx_debug) {
				data._rx_debug.page_title = "AJAX : " + params.module + "." + params.act;
				if (window.rhymix_debug_add_data) {
					window.rhymix_debug_add_data(data._rx_debug);
				} else {
					window.rhymix_debug_pending_data.push(data._rx_debug);
				}
			}

			// If the response contains an error, display the error message.
			if (data.error != "0") {
				// This way of calling an error handler is deprecated. Do not use it.
				if ($.isFunction($.exec_xml.onerror)) {
					if (typeof console == "object" && typeof console.warn == "function") {
						var msg = "DEPRECATED : $.exec_xml.onerror() is deprecated in Rhymix.";
						if (navigator.userAgent.match(/Firefox/)) {
							console.error(msg);
						} else {
							console.warn(msg);
						}
					}
					return $.exec_xml.onerror(module, act, data, callback_success, return_fields, callback_success_arg, fo_obj);
				}
				// Display the error message, or a generic stub if there is no error message.
				if (data.message) {
					var full_message = data.message.replace(/\\n/g, "\n");
					if (data.errorDetail) {
						full_message += "\n\n" + data.errorDetail;
					}
					alert(full_message);
				} else {
					var msg = "AJAX communication error while requesting " + params.module + "." + params.act;
					console.error(msg);
					if (window.show_ajax_errors.indexOf('ALL') >= 0 || window.show_ajax_errors.indexOf(xhr.status) >= 0) {
						alert(msg);
					}
				}
				return null;
			}

			// If the response contains a redirect URL, redirect immediately.
			if (data.redirect_url) {
				data.redirect_url = data.redirect_url.replace(/&amp;/g, "&");
			}
			if (data.redirect_url && !$.isFunction(callback_success)) {
				return redirect(data.redirect_url);
			}

			// If there was a success callback, call it.
			if ($.isFunction(callback_success)) {
				callback_success(result, return_fields, callback_success_arg, fo_obj);
			}
		};

		// Define the error handler.
		errorHandler = function(xhr, textStatus, doNotHandleXml) {

			// If the server has returned XML anyway, convert to JSON and call the success handler.
			if (textStatus === 'parsererror' && doNotHandleXml !== true && xhr.responseText && xhr.responseText.match(/<response/)) {
				return xmlHandler(xhr, textStatus);
			}

			// If the user is navigating away, don't do anything.
			if (xhr.status == 0 && page_unloading) {
				return;
			}

			var error_info, msg;

			if ($(".x_modal-body").size()) {
				if (xhr.status == 0) {
					error_info = 'Connection failed: ' + xhr.statusText + " (" + textStatus + ")" + "<br><br><pre>" + xhr.responseText + "</pre>";
				} else {
					error_info = xhr.status + " " + xhr.statusText + " (" + textStatus + ")" + "<br><br><pre>" + xhr.responseText + "</pre>";
				}
				msg = "AJAX communication error while requesting " + params.module + "." + params.act + "<br><br>" + error_info;
				console.error(msg.replace(/(<br>)+/g, "\n").trim());
				if (window.show_ajax_errors.indexOf('ALL') >= 0 || window.show_ajax_errors.indexOf(xhr.status) >= 0) {
					alert(msg);
				}
			} else {
				if (xhr.status == 0) {
					error_info = 'Connection failed: ' + xhr.statusText + " (" + textStatus + ")" + "\n\n" + xhr.responseText;
				} else {
					error_info = xhr.status + " " + xhr.statusText + " (" + textStatus + ")" + "\n\n" + xhr.responseText;
				}
				msg = "AJAX communication error while requesting " + params.module + "." + params.act + "\n\n" + error_info;
				console.error(msg.trim().replace(/\n+/g, "\n"));
				if (window.show_ajax_errors.indexOf('ALL') >= 0 || window.show_ajax_errors.indexOf(xhr.status) >= 0) {
					alert(msg);
				}
			}
		};

		// Define the legacy XML handler.
		xmlHandler = function(xhr, textStatus) {
			var parseXmlAndReturn = function() {
				var x2js = new X2JS();
				var data = x2js.xml_str2json($.trim(xhr.responseText));
				if (data && data.response) {
					return successHandler(data.response, textStatus, xhr);
				} else {
					return errorHandler(xhr, textStatus, true);
				}
			};
			if (window.X2JS) {
				parseXmlAndReturn();
			} else {
				$.ajax({
					url : request_uri + "common/js/xml2json.js",
					dataType : "script",
					cache : true,
					success : parseXmlAndReturn,
					error : function() {
						return errorHandler(xhr, textStatus, true);
					}
				});
			}
		};

		// Send the AJAX request.
		try {
			$.ajax({
				url : XE.URI(request_uri).pathname(),
				type : "POST",
				dataType : "json",
				data : params,
				headers : {
					'X-AJAX-Compat': 'XMLRPC',
					'X-CSRF-Token': getCSRFToken()
				},
				success : successHandler,
				error : errorHandler
			});
		} catch(e) {
			alert(e);
			return;
		}
	};


	/**
	 * Function for compatibility with XE's exec_json()
	 */
	window.exec_json = $.exec_json = function(action, params, callback_success, callback_error) {

		// Convert params to object and fill in the module and act.
		var url = XE.URI(request_uri).pathname();
		var action_parts = action.split('.');
		var request_info;

		if (params instanceof FormData) {
			request_info = (params.get('module') || params.get('mid')) + '.' + params.get('act');
		} else if (action === 'raw') {
			request_info = 'RAW FORM SUBMISSION';
		} else {
			params = params ? ($.isArray(params) ? arr2obj(params) : params) : {};
			params.module = action_parts[0];
			params.act = action_parts[1];
			request_info = params.module + "." + params.act;
		}

		// Define the success handler.
		var successHandler = function(data, textStatus, xhr) {

			// Add debug information.
			if (data._rx_debug) {
				data._rx_debug.page_title = "AJAX : " + request_info;
				if (window.rhymix_debug_add_data) {
					window.rhymix_debug_add_data(data._rx_debug);
				} else {
					window.rhymix_debug_pending_data.push(data._rx_debug);
				}
			}

			// If the response contains an error, display the error message.
			if(data.error != "0" && data.error > -1000) {
				// If this is a temporary CSRF error, retry with a new token.
				if (data.errorDetail === 'ERR_CSRF_CHECK_FAILED' && action !== 'member.getLoginStatus') {
					return window.exec_json('member.getLoginStatus', {}, function(data) {
						if (data.csrf_token) {
							setCSRFToken(data.csrf_token);
							window.exec_json(action, params, callback_success, callback_error);
						}
					});
				}
				// Should not display the error message when the callback function returns false.
				if ($.isFunction(callback_error) && callback_error(data, xhr) === false) {
					return;
				}
				if(data.error == -1 && data.message == "admin.msg_is_not_administrator") {
					alert("You are not logged in as an administrator.");
					return;
				} else {
					if (data.message) {
						var full_message = data.message.replace(/\\n/g, "\n");
						if (data.errorDetail) {
							full_message += "\n\n" + data.errorDetail;
						}
						alert(full_message);
					} else {
						var msg = "AJAX communication error while requesting " + request_info;
						console.error(msg);
						if (window.show_ajax_errors.indexOf('ALL') >= 0 || window.show_ajax_errors.indexOf(xhr.status) >= 0) {
							alert(msg);
						}
					}
					return;
				}
			}

			// If the response contains a redirect URL, redirect immediately.
			if (data.redirect_url) {
				data.redirect_url = data.redirect_url.replace(/&amp;/g, "&");
			}
			if (data.redirect_url && !$.isFunction(callback_success)) {
				return redirect(data.redirect_url);
			}

			// If there was a success callback, call it.
			if($.isFunction(callback_success)) {
				callback_success(data);
			}
		};

		// Define the error handler.
		var errorHandler = function(xhr, textStatus) {

			// If the user is navigating away, don't do anything.
			if (xhr.status == 0 && page_unloading) {
				return;
			}

			var error_info, msg;

			// If a callback function is defined, call it and check if it returns false.
			if ($.isFunction(callback_error)) {
				var data = { error: -3, message: textStatus };
				if (callback_error(data, xhr) === false) {
					return;
				}
			}

			// Otherwise, display a simple alert dialog.
			if ($(".x_modal-body").size()) {
				if (xhr.status == 0) {
					error_info = 'Connection failed: ' + url + ' (' + textStatus + ')' + "<br><br><pre>" + xhr.responseText + "</pre>";
				} else {
					error_info = xhr.status + " " + xhr.statusText + " (" + textStatus + ")" + "<br><br><pre>" + xhr.responseText + "</pre>";
				}
				msg = "AJAX communication error while requesting " + (params.act ? (params.module + "." + params.act) : url) + "<br><br>" + error_info;
				console.error(msg.replace(/(<br>)+/g, "\n").trim());
				if (window.show_ajax_errors.indexOf('ALL') >= 0 || window.show_ajax_errors.indexOf(xhr.status) >= 0) {
					alert(msg);
				}
			} else {
				if (xhr.status == 0) {
					error_info = 'Connection failed: ' + url + ' (' + textStatus + ')' + "\n\n" + xhr.responseText;
				} else {
					error_info = xhr.status + " " + xhr.statusText + " (" + textStatus + ")" + "\n\n" + xhr.responseText;
				}
				msg = "AJAX communication error while requesting " + (params.act ? (params.module + "." + params.act) : url) + "\n\n" + error_info;
				console.error(msg.trim().replace(/\n+/g, "\n"));
				if (window.show_ajax_errors.indexOf('ALL') >= 0 || window.show_ajax_errors.indexOf(xhr.status) >= 0) {
					alert(msg);
				}
			}
		};

		// Generate headers.
		var headers = {};
		if (action !== 'raw') {
			headers['X-CSRF-Token'] = getCSRFToken();
			if (!params['_rx_ajax_compat']) {
				headers['X-AJAX-Compat'] = 'JSON';
			}
		};

		// Send the AJAX request.
		try {
			var args = {
				type: "POST",
				dataType: "json",
				url: url,
				data: params,
				processData: (action !== 'raw'),
				headers : headers,
				success : successHandler,
				error : errorHandler
			};
			if (params instanceof FormData) {
				args.contentType = false;
			}
			$.ajax(args);
		} catch(e) {
			alert(e);
			return;
		}
	};

	/**
	 * Function for compatibility with XE's exec_html()
	 */
	window.exec_html = $.fn.exec_html = function() {
		if (typeof console == "object" && typeof console.warn == "function") {
			var msg = "DEPRECATED : exec_html() is obsolete in Rhymix.";
			if (navigator.userAgent.match(/Firefox/)) {
				console.error(msg);
			} else {
				console.warn(msg);
			}
		}
	};

	/**
	 * Function for AJAX submission of arbitrary forms.
	 */
	XE.ajaxForm = function(form, callback_success, callback_error) {
		form = $(form);
		// Get success and error callback functions.
		if (typeof callback_success === 'undefined') {
			callback_success = form.data('callbackSuccess');
			if (callback_success && window[callback_success] && $.isFunction(window[callback_success])) {
				callback_success = window[callback_success];
			} else {
				callback_success = function(data) {
					if (data.message && data.message !== 'success') {
						rhymix_alert(data.message, data.redirect_url);
					}
					if (data.redirect_url) {
						redirect(data.redirect_url);
					}
				};
			}
		}
		if (typeof callback_error === 'undefined') {
			callback_error = form.data('callbackError');
			if (callback_error && window[callback_error] && $.isFunction(window[callback_error])) {
				callback_error = window[callback_error];
			} else {
				callback_error = null;
			}
		}
		window.exec_json('raw', new FormData(form[0]), callback_success, callback_error);
	};
	$(document).on('submit', 'form.rx_ajax', function(event) {
		// Abort if the form already has a 'target' attribute.
		if (!$(this).attr('target')) {
			event.preventDefault();
			XE.ajaxForm(this);
		}
	});

	/**
	 * Empty placeholder for beforeUnload handler.
	 */
	var beforeUnloadHandler = function() {
		page_unloading = true;
		return "";
	};

	/**
	 * Register the beforeUnload handler.
	 */
	$(function() {
		if (show_leaving_warning) {
			$(document).ajaxStart(function() {
				$(window).bind("beforeunload", beforeUnloadHandler);
			}).bind("ajaxStop cancel_confirm", function() {
				$(window).unbind("beforeunload", beforeUnloadHandler);
			});
		} else {
			$(window).on('beforeunload', function() {
				page_unloading = true;
			});
		}
	});

})(jQuery);

/**
 * This function simulates AJAX requests with HTML forms.
 * It was meant for cross-domain requests, but should be replaced with JSONP.
 */
function send_by_form(url, params) {

	// This function is deprecated!
	if (typeof console == "object" && typeof console.warn == "function") {
		var msg = "DEPRECATED : send_by_form() is deprecated in Rhymix.";
		if (navigator.userAgent.match(/Firefox/)) {
			console.error(msg);
		} else {
			console.warn(msg);
		}
	}

	// Create the hidden iframe.
	var frame_id = "xeTmpIframe";
	if (!$("#" + frame_id).length) {
		$('<iframe name="%id%" id="%id%" style="position:absolute;left:-1px;top:1px;width:1px;height:1px"></iframe>'.replace(/%id%/g, frame_id)).appendTo(document.body);
	}

	// Create the hidden form.
	var form_id  = "xeVirtualForm";
	$("#" + form_id).remove();
	var form = $('<form id="%id%"></form>'.replace(/%id%/g, form_id)).attr({
		"id"     : form_id,
		"method" : "post",
		"action" : url,
		"target" : frame_id
	});

	// Add virtual XML parameters.
	params.xeVirtualRequestMethod = "xml";
	params.xeRequestURI           = location.href.replace(/#(.*)$/i, "");
	params.xeVirtualRequestUrl    = request_uri;

	// Add inputs to the form.
	$.each(params, function(key, value){
		$('<input type="hidden">').attr("name", key).attr("value", value).appendTo(form);
	});

	// Submit the hidden form.
	form.appendTo(document.body).submit();
}

/**
 * This function converts arrays into objects.
 */
function arr2obj(arr) {
	var ret = {};
	for (var key in arr) {
		if (arr.hasOwnProperty(key)) {
			ret[key] = arr[key];
		}
	}
	return ret;
}
