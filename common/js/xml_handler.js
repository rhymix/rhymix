/**
 * Functions for sending AJAX requests to the server.
 */
(function($){
	
	"use strict";
	
	/**
	 * Set this variable to false to hide the "waiting for server response" layer.
	 */
	window.show_waiting_message = false;
	
	/**
	 * Set this variable to false to hide the "do you want to leave the page?" dialog.
	 */
	window.show_leaving_warning = false;
	
	/**
	 * This variable becomes true when the user tries to navigate away from the page.
	 */
	var page_unloading = false;
	
	/**
	 * This variable stores the .wfsr jQuery object.
	 */
	var waiting_obj = $(".wfsr");
	
	/**
	 * Function for compatibility with XE's exec_xml()
	 */
	window.exec_xml = $.exec_xml = function(module, act, params, callback_success, return_fields, callback_success_arg, fo_obj) {
		
		// Define callback functions.
		var successHandler, errorHandler, xmlHandler;
		
		// Convert params to object and fill in the module and act.
		params = params ? ($.isArray(params) ? arr2obj(params) : params) : {};
		params.module = module;
		params.act = act;
		params._rx_ajax_compat = 'XMLRPC';
		params._rx_csrf_token = getCSRFToken();
		
		// Decide whether or not to use SSL.
		var url = request_uri;
		
		// Check whether this is a cross-domain request. If so, use an alternative method.
		if (!isSameOrigin(location.href, url)) return send_by_form(url, params);
		
		// Delay the waiting message for 1 second to prevent rapid blinking.
		waiting_obj.css("opacity", 0.0);
		var wfsr_timeout = setTimeout(function() {
			if (show_waiting_message) {
				waiting_obj.css("opacity", "").show();
			}
		}, 1000);
		
		// Define the success handler.
		successHandler = function(data, textStatus, xhr) {
			
			// Hide the waiting message.
			clearTimeout(wfsr_timeout);
			waiting_obj.hide().trigger("cancel_confirm");
			
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
					if (typeof console == "object" && typeof console.log == "function") {
						console.log("DEPRECATED : $.exec_xml.onerror() is deprecated in Rhymix.");
					}
					return $.exec_xml.onerror(module, act, data, callback_success, return_fields, callback_success_arg, fo_obj);
				}
				// Display the error message, or a generic stub if there is no error message.
				if (data.message) {
					alert(data.message.replace(/\\n/g, "\n"));
				} else {
					alert("AJAX communication error while requesting " + params.module + "." + params.act);
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

			// Hide the waiting message and display an error notice.
			clearTimeout(wfsr_timeout);
			waiting_obj.hide().trigger("cancel_confirm");
			var error_info;
			
			if ($(".x_modal-body").size()) {
				error_info = xhr.status + " " + xhr.statusText + " (" + textStatus + ")" + "<br><br><pre>" + xhr.responseText + "</pre>";
				alert("AJAX communication error while requesting " + params.module + "." + params.act + "<br><br>" + error_info);
			} else {
				error_info = xhr.status + " " + xhr.statusText + " (" + textStatus + ")" + "\n\n" + xhr.responseText;
				alert("AJAX communication error while requesting " + params.module + "." + params.act + "\n\n" + error_info);
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
				url : url,
				type : "POST",
				dataType : "json",
				data : params,
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
		var request_info;
		if (action === 'raw') {
			request_info = 'RAW FORM SUBMISSION';
		} else {
			params = params ? ($.isArray(params) ? arr2obj(params) : params) : {};
			action = action.split(".");
			//if (action.length != 2) return;
			params.module = action[0];
			params.act = action[1];
			params._rx_ajax_compat = 'JSON';
			params._rx_csrf_token = getCSRFToken();
			request_info = params.module + "." + params.act;
		}
		
		// Delay the waiting message for 1 second to prevent rapid blinking.
		waiting_obj.css("opacity", 0.0);
		var wfsr_timeout = setTimeout(function() {
			if (show_waiting_message) {
				waiting_obj.css("opacity", "").show();
			}
		}, 1000);
		
		// Define the success handler.
		var successHandler = function(data, textStatus, xhr) {
			
			// Hide the waiting message.
			clearTimeout(wfsr_timeout);
			waiting_obj.hide().trigger("cancel_confirm");
			
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
				// Should not display the error message when the callback function returns false.
				if ($.isFunction(callback_error) && callback_error(data) === false) {
					return;
				}
				if(data.error == -1 && data.message == "admin.msg_is_not_administrator") {
					alert("You are not logged in as an administrator.");
					return;
				} else {
					if (data.message) {
						alert(data.message.replace(/\\n/g, "\n"));
					} else {
						alert("AJAX communication error while requesting " + request_info);
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
			
			// Hide the waiting message and display an error notice.
			clearTimeout(wfsr_timeout);
			waiting_obj.hide().trigger("cancel_confirm");
			var error_info;
			
			if ($(".x_modal-body").size()) {
				error_info = xhr.status + " " + xhr.statusText + " (" + textStatus + ")" + "<br><br><pre>" + xhr.responseText + "</pre>";
				alert("AJAX communication error while requesting " + params.module + "." + params.act + "<br><br>" + error_info);
			} else {
				error_info = xhr.status + " " + xhr.statusText + " (" + textStatus + ")" + "\n\n" + xhr.responseText;
				alert("AJAX communication error while requesting " + params.module + "." + params.act + "\n\n" + error_info);
			}
		};
		
		// Send the AJAX request.
		try {
			$.ajax({
				type: "POST",
				dataType: "json",
				url: request_uri,
				data: params,
				processData: (action !== 'raw'),
				success : successHandler,
				error : errorHandler
			});
		} catch(e) {
			alert(e);
			return;
		}
	};

	/**
	 * Function for compatibility with XE's exec_html()
	 */
	window.exec_html = $.fn.exec_html = function() {
		if (typeof console == "object" && typeof console.log == "function") {
			console.log("DEPRECATED : exec_html() is deprecated in Rhymix.");
		}
	};

	/**
	 * Function for AJAX submission of arbitrary forms.
	 */
	XE.ajaxForm = function(form, callback_success, callback_error) {
		// Abort if the form already has a 'target' attribute.
		form = $(form);
		// Get success and error callback functions.
		if (typeof callback_success === 'undefined') {
			callback_success = form.data('callback-success');
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
			callback_error = form.data('callback-error');
			if (callback_error && window[callback_error] && $.isFunction(window[callback_error])) {
				callback_error = window[callback_error];
			} else {
				callback_error = null;
			}
		}
		// Set _rx_ajax_form flag
		if (!form.find('input[name=_rx_ajax_form]').size()) {
			form.append('<input type="hidden" name="_rx_ajax_form" value="json" />');
			setTimeout(function() {
				form.find('input[name=_rx_ajax_form]').remove();
			}, 1000);
		}
		// If the form has file uploads, use a hidden iframe to submit. Otherwise use exec_json.
		var has_files = form.find('input[type=file][name!=Filedata]').size();
		if (has_files) {
			var iframe_id = '_rx_temp_iframe_' + (new Date()).getTime();
			$('<iframe id="' + iframe_id + '" name="' + iframe_id + '" style="display:none"></iframe>').appendTo($(document.body));
			form.attr('method', 'POST').attr('enctype', 'multipart/form-data').attr('target', iframe_id);
			form.find('input[name=_rx_ajax_form]').val(iframe_id);
			window.XE.handleIframeResponse = function(iframe_id, data) {
				if (data.error) {
					if (callback_error) {
						callback_error(data);
					} else {
						alert(data.message);
					}
				} else {
					callback_success(data);
				}
				if (iframe_id.match(/^_rx_temp_iframe_[0-9]+$/)) {
					$('iframe#' + iframe_id).remove();
				}
			};
			setTimeout(function() {
				form.removeAttr('target');
			}, 1000);
			form.submit();
		} else {
			window.exec_json('raw', form.serialize(), callback_success, callback_error);
		}
	};
	$(document).on('submit', 'form.rx_ajax', function(event) {
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
		waiting_obj = $('.wfsr');
		waiting_obj.text(waiting_obj.data('message'));
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
	if (typeof console == "object" && typeof console.log == "function") {
		console.log("DEPRECATED : send_by_form() is deprecated in Rhymix.");
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
