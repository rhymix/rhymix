/**
 * Functions for sending AJAX requests to the server.
 */
(function($){

	/**
	 * Set this variable to false to hide the "waiting for server response" layer.
	 */
	window.show_waiting_message = true;
	
	/**
	 * Set this variable to false to hide the "do you want to leave the page?" dialog.
	 */
	window.show_leaving_warning = true;
	
	/**
	 * This variable stores the .wfsr jQuery object.
	 */
	var waiting_obj = $(".wfsr");
	
	/**
	 * Function for compatibility with XE's exec_xml()
	 */
	window.exec_xml = $.exec_xml = function(module, act, params, callback_success, return_fields, callback_success_arg, fo_obj) {
		
		// Convert params to object and fill in the module and act.
		params = params ? ($.isArray(params) ? arr2obj(params) : params) : {};
		params.module = module;
		params.act = act;
		params._rx_ajax_compat = 'XMLRPC';
		
		// Fill in the XE vid.
		if (typeof(xeVid) != "undefined") params.vid = xeVid;
		
		// Decide whether or not to use SSL.
		var url = request_uri;
		if ($.isArray(ssl_actions) && params.act && $.inArray(params.act, ssl_actions) >= 0) {
			url = default_url || request_uri;
			var port = window.https_port || 443;
			var _ul = $("<a>").attr("href", url)[0];
			var target = "https://" + _ul.hostname.replace(/:\d+$/, "");
			if (port != 443) target += ":" + port;
			if (_ul.pathname[0] != "/") target += "/";
			target += _ul.pathname;
			url = target.replace(/\/$/, "") + "/";
		}
		
		// Check whether this is a cross-domain request. If so, use an alternative method.
		var _u1 = $("<a>").attr("href", location.href)[0];
		var _u2 = $("<a>").attr("href", url)[0];
		if (_u1.protocol != _u2.protocol || _u1.port != _u2.port) return send_by_form(url, params);
		
		// Delay the waiting message for 1 second to prevent rapid blinking.
		waiting_obj.css("opacity", 0.0);
		var wfsr_timeout = setTimeout(function() {
			if (show_waiting_message) {
				waiting_obj.css("opacity", "").html(waiting_message).show();
			}
		}, 1000);
		
		// Define the success handler.
		var successHandler = function(data, textStatus, xhr) {
			
			// Hide the waiting message.
			clearTimeout(wfsr_timeout);
			waiting_obj.hide().trigger("cancel_confirm");
			
			// Copy data to the result object.
			var result = {};
			$.each(data, function(key, val) {
				if ($.inArray(key, ["error", "message", "act", "redirect_url"]) >= 0 || $.inArray(key, return_fields) >= 0) {
					if ($.isArray(val)) {
						result[key] = { item: val };
					} else {
						result[key] = val;
					}
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
			if (result.redirect_url) {
				window.location = result.redirect_url.replace(/&amp;/g, "&");
				return null;
			}
			
			// If there was a success callback, call it.
			if ($.isFunction(callback_success)) {
				callback_success(result, return_fields, callback_success_arg, fo_obj);
			}
		};
		
		// Define the error handler.
		var errorHandler = function(xhr, textStatus) {
			
			// If the server has returned XML anyway, convert to JSON and call the success handler.
			if (textStatus === 'parsererror' && xhr.responseText && xhr.responseText.match(/<response/)) {
				var xmldata = $.parseXML(xhr.responseText);
				if (xmldata) {
					var jsondata = $.parseJSON(xml2json(xmldata, false, false));
					if (jsondata && jsondata.response) {
						return successHandler(jsondata.response, textStatus, xhr);
					}
				}
			}
			
			// Hide the waiting message and display an error notice.
			clearTimeout(wfsr_timeout);
			waiting_obj.hide().trigger("cancel_confirm");
			var error_info = xhr.status + " " + xhr.statusText + " (" + textStatus + ")";
			alert("AJAX communication error while requesting " + params.module + "." + params.act + "\n\n" + error_info);
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
		params = params ? ($.isArray(params) ? arr2obj(params) : params) : {};
		action = action.split(".");
		if (action.length != 2) return;
		params.module = action[0];
		params.act = action[1];
		params._rx_ajax_compat = 'JSON';
		
		// Fill in the XE vid.
		if (typeof(xeVid) != "undefined") params.vid = xeVid;
		
		// Delay the waiting message for 1 second to prevent rapid blinking.
		waiting_obj.css("opacity", 0.0);
		var wfsr_timeout = setTimeout(function() {
			if (show_waiting_message) {
				waiting_obj.css("opacity", "").html(waiting_message).show();
			}
		}, 1000);
		
		// Define the success handler.
		var successHandler = function(data, textStatus, xhr) {
			
			// Hide the waiting message.
			clearTimeout(wfsr_timeout);
			waiting_obj.hide().trigger("cancel_confirm");
			
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
			if(data.error != "0" && data.error > -1000) {
				if(data.error == -1 && data.message == "msg_is_not_administrator") {
					alert("You are not logged in as an administrator.");
					if ($.isFunction(callback_error)) {
						callback_error(data);
					}
					return;
				} else {
					if (data.message) {
						alert(data.message.replace(/\\n/g, "\n"));
					} else {
						alert("AJAX communication error while requesting " + data.module + "." + data.act);
					}
					if ($.isFunction(callback_error)) {
						callback_error(data);
					}
					return;
				}
			}
			
			// If there was a success callback, call it.
			if($.isFunction(callback_success)) {
				callback_success(data);
			}
		};
		
		// Define the error handler.
		var errorHandler = function(xhr, textStatus) {
			clearTimeout(wfsr_timeout);
			waiting_obj.hide().trigger("cancel_confirm");
			var error_info = xhr.status + " " + xhr.statusText + " (" + textStatus + ")";
			alert("AJAX communication error while requesting " + params.module + "." + params.act + "\n\n" + error_info);
		};
		
		// Send the AJAX request.
		try {
			$.ajax({
				type: "POST",
				dataType: "json",
				url: request_uri,
				data: params,
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
	window.exec_html = $.fn.exec_html = function(action, params, type, callback_func, callback_args) {
		
		// Convert params to object and fill in the module and act.
		params = params ? ($.isArray(params) ? arr2obj(params) : params) : {};
		action = action.split(".");
		if (action.length != 2) return;
		params.module = action[0];
		params.act = action[1];
		
		// Fill in the XE vid.
		if (typeof(xeVid) != "undefined") params.vid = xeVid;
		
		// Determine the request type.
		if($.inArray(type, ["html", "append", "prepend"]) < 0) type = "html";
		var self = $(this);
		
		// Delay the waiting message for 1 second to prevent rapid blinking.
		waiting_obj.css("opacity", 0.0);
		var wfsr_timeout = setTimeout(function() {
			if (show_waiting_message) {
				waiting_obj.css("opacity", "").html(waiting_message).show();
			}
		}, 1000);
		
		// Define the success handler.
		var successHandler = function(data, textStatus, xhr) {
			clearTimeout(wfsr_timeout);
			waiting_obj.hide().trigger("cancel_confirm");
			if (self && self[type]) {
				self[type](html);
			}
			if ($.isFunction(callback_func)) {
				callback_func(callback_args);
			}
		};
		
		// Define the error handler.
		var errorHandler = function(xhr, textStatus) {
			clearTimeout(wfsr_timeout);
			waiting_obj.hide().trigger("cancel_confirm");
			var error_info = xhr.status + " " + xhr.statusText + " (" + textStatus + ")";
			alert("AJAX communication error while requesting " + params.module + "." + params.act + "\n\n" + error_info);
		};
		
		// Send the AJAX request.
		try {
			$.ajax({
				type: "POST",
				dataType: "html",
				url: request_uri,
				data: params,
				success: successHandler,
				error: errorHandler
			});
		} catch(e) {
			alert(e);
			return;
		}
	};

	/**
	 * Empty placeholder for beforeUnload handler.
	 */
	var beforeUnloadHandler = function() {
		return "";
	};
	
	/**
	 * Register the beforeUnload handler.
	 */
	$(function() {
		waiting_obj = $(".wfsr");
		if (show_leaving_warning) {
			$(document).ajaxStart(function() {
				$(window).bind("beforeunload", beforeUnloadHandler);
			}).bind("ajaxStop cancel_confirm", function() {
				$(window).unbind("beforeunload", beforeUnloadHandler);
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

/**
 * This work is licensed under Creative Commons GNU LGPL License.
 * License: http://creativecommons.org/licenses/LGPL/2.1/
 * Version: 0.9
 * Author:  Stefan Goessner/2006
 * Web:     http://goessner.net/
 **/
function xml2json(xml, tab, ignoreAttrib) {
	var X = {
		toObj: function(xml) {
			var o = {};
			if (xml.nodeType==1) { // element node ..
				if (ignoreAttrib && xml.attributes.length) { // element with attributes  ..
					for (var i=0; i<xml.attributes.length; i++) {
						o["@"+xml.attributes[i].nodeName] = (xml.attributes[i].nodeValue||"").toString();
					}
				}

				if (xml.firstChild) { // element has child nodes ..
					var textChild=0, cdataChild=0, hasElementChild=false;
					for (var n=xml.firstChild; n; n=n.nextSibling) {
						if (n.nodeType==1) {
							hasElementChild = true;
						} else if (n.nodeType==3 && n.nodeValue.match(/[^ \f\n\r\t\v]/)) {
							textChild++; // non-whitespace text
						}else if (n.nodeType==4) {
							cdataChild++; // cdata section node
						}
					}
					if (hasElementChild) {
						if (textChild < 2 && cdataChild < 2) { // structured element with evtl. a single text or/and cdata node ..
							X.removeWhite(xml);
							for (var n1=xml.firstChild; n1; n1=n1.nextSibling) {
								if (n1.nodeType == 3) { // text node
									o = X.escape(n1.nodeValue);
								} else if (n1.nodeType == 4) { // cdata node
									// o["#cdata"] = X.escape(n.nodeValue);
									o = X.escape(n1.nodeValue);
								} else if (o[n1.nodeName]) { // multiple occurence of element ..
									if (o[n1.nodeName] instanceof Array) {
										o[n1.nodeName][o[n1.nodeName].length] = X.toObj(n1);
									} else {
										o[n1.nodeName] = [o[n1.nodeName], X.toObj(n1)];
									}
								} else { // first occurence of element..
									o[n1.nodeName] = X.toObj(n1);
								}
							}
						}
						else { // mixed content
							if (!xml.attributes.length) {
								o = X.escape(X.innerXml(xml));
							} else {
								o["#text"] = X.escape(X.innerXml(xml));
							}
						}
					} else if (textChild) { // pure text
						if (!xml.attributes.length) {
							o = X.escape(X.innerXml(xml));
						} else {
							o["#text"] = X.escape(X.innerXml(xml));
						}
					} else if (cdataChild) { // cdata
						if (cdataChild > 1) {
							o = X.escape(X.innerXml(xml));
						} else {
							for (var n2=xml.firstChild; n2; n2=n2.nextSibling) {
								// o["#cdata"] = X.escape(n2.nodeValue);
								o = X.escape(n2.nodeValue);
							}
						}
					}
				}

				if (!xml.attributes.length && !xml.firstChild) {
					o = null;
				}
			} else if (xml.nodeType==9) { // document.node
				o = X.toObj(xml.documentElement);
			} else {
				alert("unhandled node type: " + xml.nodeType);
			}

			return o;
		},
		toJson: function(o, name, ind) {
			var json = name ? ("\""+name+"\"") : "";
			if (o instanceof Array) {
				for (var i=0,n=o.length; i<n; i++) {
					o[i] = X.toJson(o[i], "", ind+"\t");
				}
				json += (name?":[":"[") + (o.length > 1 ? ("\n"+ind+"\t"+o.join(",\n"+ind+"\t")+"\n"+ind) : o.join("")) + "]";
			} else if (o === null) {
				json += (name&&":") + "null";
			} else if (typeof(o) == "object") {
				var arr = [];
				for (var m in o) {
					arr[arr.length] = X.toJson(o[m], m, ind+"\t");
				}
				json += (name?":{":"{") + (arr.length > 1 ? ("\n"+ind+"\t"+arr.join(",\n"+ind+"\t")+"\n"+ind) : arr.join("")) + "}";
			} else if (typeof(o) == "string") {
				json += (name&&":") + "\"" + o.toString() + "\"";
			} else {
				json += (name&&":") + o.toString();
			}
			return json;
		},
		innerXml: function(node) {
			var s = "";

			if ("innerHTML" in node) {
				s = node.innerHTML;
			} else {
				var asXml = function(n) {
					var s = "";
					if (n.nodeType == 1) {
						s += "<" + n.nodeName;
						for (var i=0; i<n.attributes.length;i++) {
							s += " " + n.attributes[i].nodeName + "=\"" + (n.attributes[i].nodeValue||"").toString() + "\"";
						}
						if (n.firstChild) {
							s += ">";
							for (var c=n.firstChild; c; c=c.nextSibling) {
								s += asXml(c);
							}
							s += "</"+n.nodeName+">";
						} else {
							s += "/>";
						}
					} else if (n.nodeType == 3) {
						s += n.nodeValue;
					} else if (n.nodeType == 4) {
						s += "<![CDATA[" + n.nodeValue + "]]>";
					}

					return s;
				};

				for (var c=node.firstChild; c; c=c.nextSibling) {
					s += asXml(c);
				}
			}
			return s;
		},
		escape: function(txt) {
			return txt.replace(/[\\]/g, "\\\\")
				.replace(/[\"]/g, '\\"')
				.replace(/[\n]/g, '\\n')
				.replace(/[\r]/g, '\\r');
		},
		removeWhite: function(e) {
			e.normalize();
			for (var n3 = e.firstChild; n3; ) {
				if (n3.nodeType == 3) { // text node
					if (!n3.nodeValue.match(/[^ \f\n\r\t\v]/)) { // pure whitespace text node
						var nxt = n3.nextSibling;
						e.removeChild(n3);
						n3 = nxt;
					} else {
						n3 = n3.nextSibling;
					}
				} else if (n3.nodeType == 1) { // element node
					X.removeWhite(n3);
					n3 = n3.nextSibling;
				} else { // any other node
					n3 = n3.nextSibling;
				}
			}
			return e;
		}
	};

	// document node
	if (xml.nodeType == 9) xml = xml.documentElement;

	var json_obj = X.toObj(X.removeWhite(xml)), json_str;

	if (typeof(JSON)=='object' && jQuery.isFunction(JSON.stringify) && false) {
		var obj = {}; obj[xml.nodeName] = json_obj;
		json_str = JSON.stringify(obj);

		return json_str;
	} else {
		json_str = X.toJson(json_obj, xml.nodeName, "");

		return "{" + (tab ? json_str.replace(/\t/g, tab) : json_str.replace(/\t|\n/g, "")) + "}";
	}
}
