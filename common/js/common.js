/**
 * @file common.js
 * @author NAVER (developers@xpressengine.com)
 * @brief 몇가지 유용한 & 기본적으로 자주 사용되는 자바스크립트 함수들 모음
 **/

(function($) {

	/* OS check */
	var UA = navigator.userAgent.toLowerCase();
	$.os = {
		Linux: /linux/.test(UA),
		Unix: /x11/.test(UA),
		Mac: /mac/.test(UA),
		Windows: /win/.test(UA)
	};
	$.os.name = ($.os.Windows) ? 'Windows' :
		($.os.Linux) ? 'Linux' :
		($.os.Unix) ? 'Unix' :
		($.os.Mac) ? 'Mac' : '';
	
	/* Intercept getScript error due to broken minified script URL */
	$(document).ajaxError(function(event, jqxhr, settings, thrownError) {
		if(settings.dataType === "script" && (jqxhr.status >= 400 || (jqxhr.responseText && jqxhr.responseText.length < 40))) {
			var match = /^(.+)\.min\.(css|js)($|\?)/.exec(settings.url);
			if(match) {
				$.getScript(match[1] + "." + match[2], settings.success);
			}
		}
	});
	
	/**
	 * @brief Check if two URLs belong to the same origin
	 */
	window.isSameOrigin = function(url1, url2) {
		if(!url1 || !url2) {
			return false;
		}
		if (url1.match(/^\.?\/[^\/]*/) || url2.match(/^\.?\/[^\/]*/)) {
			return true;
		}
		if (url1.match(/^(https?:)?\/\/[^\/]*[^a-z0-9\/.:_-]/i) || url2.match(/^(https?:)?\/\/[^\/]*[^a-z0-9\/.:_-]/i)) {
			return false;
		}
			
		url1 = window.XE.URI(url1).normalizePort().normalizeHostname().normalizePathname().origin();
		url2 = window.XE.URI(url2).normalizePort().normalizeHostname().normalizePathname().origin();
		return (url1 === url2) ? true : false;
	};

	/**
	 * @brief Get CSRF token for the document
	 */
	window.getCSRFToken = function() {
		return $("meta[name='csrf-token']").attr("content");
	};

	/* Intercept jQuery AJAX calls to add CSRF headers */
	$.ajaxPrefilter(function(options) {
		if (!isSameOrigin(location.href, options.url)) return;
		var token = getCSRFToken();
		if (token) {
			if (!options.headers) options.headers = {};
			options.headers["X-CSRF-Token"] = token;
		}
	});

	/* Add CSRF token to dynamically loaded forms */
	$.fn.addCSRFTokenToForm = function() {
		var token = getCSRFToken();
		if (token) {
			return $(this).each(function() {
				if ($(this).data("csrf-token-checked") === "Y") return;
				if ($(this).attr("action") && !isSameOrigin(location.href, $(this).attr("action"))) {
					return $(this).data("csrf-token-checked", "Y");
				}
				$("<input />").attr({ type: "hidden", name: "_rx_csrf_token", value: token }).appendTo($(this));
				return $(this).data("csrf-token-checked", "Y");
			});
		} else {
			return $(this);
		}
	};
	
	window.rhymix_alert_close = function() {
		if($('#rhymix_alert').is(':hidden')) {
			return;
		}
		$('#rhymix_alert').fadeOut(500, function() {
			$(this).empty();
		});
	};
	
	/**
	 * @brief display alert
	 */
	window.rhymix_alert = function(message, redirect_url, delay) {
		if(!delay) {
			delay = 2500;
		}
		if(!redirect_url) {
			$('#rhymix_alert').text(message).show();
			setTimeout(rhymix_alert_close, delay);
		}
		else if(isSameOrigin(location.href, redirect_url)) {
			Cookies.set('rhymix_alert_message', message, { expires: 1 / 1440, path: '' });
			Cookies.set('rhymix_alert_delay', delay, { expires: 1 / 1440, path: '' });
		}
		else {
			alert(message);
		}
	};
	
	$(document).ready(function() {
		if(Cookies.get('rhymix_alert_message')) {
			rhymix_alert(Cookies.get('rhymix_alert_message'), null, Cookies.get('rhymix_alert_delay'));
			Cookies.remove('rhymix_alert_message', { path: '' });
			Cookies.remove('rhymix_alert_delay', { path: '' });
		}
		$('#rhymix_alert').click(rhymix_alert_close);
	});
	
	/* Array for pending debug data */
	window.rhymix_debug_pending_data = [];

	/**
	 * @brief XE 공용 유틸리티 함수
	 * @namespace XE
	 */
	window.XE = {
		loaded_popup_menus : [],
		addedDocument : [],
		cookie : window.Cookies,
		URI : window.URI,
		URITemplate : window.URITemplate,
		SecondLevelDomains : window.SecondLevelDomains,
		IPv6 : window.IPv6,
		baseurl : null,
		
		/**
		 * @brief 특정 name을 가진 체크박스들의 checked 속성 변경
		 * @param [itemName='cart',][options={}]
		 */
		checkboxToggleAll : function(itemName) {
			if(!is_def(itemName)) itemName='cart';
			var obj;
			var options = {
				wrap : null,
				checked : 'toggle',
				doClick : false
			};

			switch(arguments.length) {
				case 1:
					if(typeof(arguments[0]) == "string") {
						itemName = arguments[0];
					} else {
						$.extend(options, arguments[0] || {});
						itemName = 'cart';
					}
					break;
				case 2:
					itemName = arguments[0];
					$.extend(options, arguments[1] || {});
			}

			if(options.doClick === true) options.checked = null;
			if(typeof(options.wrap) == "string") options.wrap ='#'+options.wrap;

			if(options.wrap) {
				obj = $(options.wrap).find('input[name="'+itemName+'"]:checkbox');
			} else {
				obj = $('input[name="'+itemName+'"]:checkbox');
			}

			if(options.checked == 'toggle') {
				obj.each(function() {
					$(this).attr('checked', ($(this).attr('checked')) ? false : true);
				});
			} else {
				if(options.doClick === true) {
					obj.click();
				} else {
					obj.attr('checked', options.checked);
				}
			}
		},

		/**
		 * @brief 문서/회원 등 팝업 메뉴 출력
		 */
		displayPopupMenu : function(ret_obj, response_tags, params) {
			var target_srl = params.target_srl;
			var menu_id = params.menu_id;
			var menus = ret_obj.menus;
			var html = "";

			if(this.loaded_popup_menus[menu_id]) {
				html = this.loaded_popup_menus[menu_id];

			} else {
				if(menus) {
					var item = menus.item;
					if(typeof(item.length)=='undefined' || item.length<1) item = new Array(item);
					if(item.length) {
						for(var i=0;i<item.length;i++) {
							var url = item[i].url;
							var str = item[i].str;
							var classname = item[i]['class'];
							var icon = item[i].icon;
							var target = item[i].target;

							var actmatch = url.match(/\bact=(\w+)/) || url.match(/\b((?:disp|proc)\w+)/);
							var act = actmatch ? actmatch[1] : null;
							var classText = 'class="' + (classname ? classname : (act ? (act + ' ') : ''));
							var styleText = "";
							var click_str = "";
							/* if(icon) styleText = " style=\"background-image:url('"+icon+"')\" "; */
							switch(target) {
								case "popup" :
										click_str = 'onclick="popopen(this.href, \''+target+'\'); return false;"';
										classText += 'popup ';
									break;
								case "javascript" :
										click_str = 'onclick="'+url+'; return false; "';
										classText += 'script ';
										url='#';
									break;
								default :
										click_str = 'target="_blank"';
									break;
							}
							classText = classText.trim() + '" ';

							html += '<li '+classText+styleText+'><a href="'+url+'" '+click_str+'>'+str+'</a></li> ';
						}
					}
				}
				this.loaded_popup_menus[menu_id] =  html;
			}

			/* 레이어 출력 */
			if(html) {
				var area = $('#popup_menu_area').html('<ul>'+html+'</ul>');
				var areaOffset = {top:params.page_y, left:params.page_x};

				if(area.outerHeight()+areaOffset.top > $(window).height()+$(window).scrollTop())
					areaOffset.top = $(window).height() - area.outerHeight() + $(window).scrollTop();
				if(area.outerWidth()+areaOffset.left > $(window).width()+$(window).scrollLeft())
					areaOffset.left = $(window).width() - area.outerWidth() + $(window).scrollLeft();

				area.css({ top:areaOffset.top, left:areaOffset.left }).show().focus();
			}
		},
		
		/* 동일 사이트 내 주소인지 판단 (프로토콜 제외) */
		isSameHost: function(url) {
			if (typeof url !== "string") {
				return false;
			}
			if (url.match(/^\.?\/[^\/]/)) {
				return true;
			}
			if (url.match(/^\w+:[^\/]*$/) || url.match(/^(https?:)?\/\/[^\/]*[^a-z0-9\/.:_-]/i)) {
				return false;
			}
			
			if (!window.XE.baseurl) {
				window.XE.baseurl = window.XE.URI(window.request_uri).normalizePort().normalizeHostname().normalizePathname();
				window.XE.baseurl = window.XE.baseurl.hostname() + window.XE.baseurl.directory();
			}
			
			try {
				var target_url = window.XE.URI(url).normalizePort().normalizeHostname().normalizePathname();
				if (target_url.is("urn")) {
					return false;
				}
				if (!target_url.hostname()) {
					target_url = target_url.absoluteTo(window.request_uri);
				}
				target_url = target_url.hostname() + target_url.directory();
				return target_url.indexOf(window.XE.baseurl) === 0;
			}
			catch(err) {
				return false;
			}
		},
		
		/* Format file size */
		filesizeFormat: function(size) {
			if (size < 2) return size + 'Byte';
			if (size < 1024) return size + 'Bytes';
			if (size < 1048576) return (size / 1024).toFixed(1) + 'KB';
			if (size < 1073741824) return (size / 1048576).toFixed(2) + 'MB';
			if (size < 1099511627776) return (size / 1073741824).toFixed(2) + 'GB';
			return (size / 1099511627776).toFixed(2) + 'TB';
		}
	};
	
})(jQuery);

/* jQuery(document).ready() */
jQuery(function($) {

	/* CSRF token */
	$("form[method]").filter(function() { return String($(this).attr("method")).toUpperCase() == "POST"; }).addCSRFTokenToForm();
	$(document).on("submit", "form[method='post']", $.fn.addCSRFTokenToForm);
	$(document).on("focus", "input,select,textarea", function() {
		$(this).parents("form[method]").filter(function() { return String($(this).attr("method")).toUpperCase() == "POST"; }).addCSRFTokenToForm();
	});
	
	/* Tabnapping protection, step 1 */
	$('a[target]').each(function() {
		var $this = $(this);
		var href = String($this.attr('href')).trim();
		var target = String($this.attr('target')).trim();
		if (!href || !target || target === '_top' || target === '_self' || target === '_parent') {
			return;
		}
		if (!window.XE.isSameHost(href)) {
			var rel = $this.attr('rel');
			rel = (typeof rel === 'undefined') ? '' : String(rel);
			if (!rel.match(/\bnoopener\b/)) {
				$this.attr('rel', $.trim(rel + ' noopener'));
			}
		}
	});

	/* Tabnapping protection, step 2 */
	$('body').on('click', 'a[target]', function(event) {
		var $this = $(this);
		var href = String($this.attr('href')).trim();
		var target = String($this.attr('target')).trim();
		if (!href || !target || target === '_top' || target === '_self' || target === '_parent') {
			return;
		}
		if (!window.XE.isSameHost(href)) {
			var rel = $this.attr('rel');
			rel = (typeof rel === 'undefined') ? '' : String(rel);
			if (!rel.match(/\bnoopener\b/)) {
				$this.attr('rel', $.trim(rel + ' noopener'));
			}
			var isChrome = navigator.userAgent.match(/Chrome\/([0-9]+)/);
			if (isChrome && parseInt(isChrome[1], 10) >= 72) {
				return;
			}
			event.preventDefault();
			blankshield.open(href);
		}
	});
	
	/* Detect color scheme */
	var color_scheme_cookie = XE.cookie.get('rx_color_scheme');
	var color_scheme_check = $('#rhymix_color_scheme').is(':visible') ? 'dark' : 'light';
	if (color_scheme_cookie && color_scheme_cookie !== color_scheme_check) {
		XE.cookie.set('rx_color_scheme', color_scheme_check, { path: window.XE.URI(default_url).pathname(), expires: 365 });
	} else if (color_scheme_check === 'dark') {
		XE.cookie.set('rx_color_scheme', color_scheme_check, { path: window.XE.URI(default_url).pathname(), expires: 365 });
		$('#rhymix_color_scheme').hide();
	}
	
	/* Editor preview replacement */
	$(".editable_preview").addClass("rhymix_content xe_content").attr("tabindex", 0);
	$(".editable_preview").on("click", function() {
		var input = $(this).siblings(".editable_preview_content");
		if (input.size()) {
			$(this).off("click").off("focus").hide();
			input = input.first();
			if (input.attr("type") !== "hidden") {
				input.hide();
			}
			var iframe = $('<iframe class="editable_preview_iframe"></iframe>');
			iframe.attr("src", current_url.setQuery("module", "editor").setQuery("act", "dispEditorFrame").setQuery("parent_input_id", input.attr("id")).replace(/^https?:/, ''));
			iframe.insertAfter(input);
		}
	});
	$(".editable_preview").on("focus", function() {
		$(this).triggerHandler("click");
	});
	
	/* select - option의 disabled=disabled 속성을 IE에서도 체크하기 위한 함수 */
	if(navigator.userAgent.match(/MSIE/)) {
		$('select').each(function(i, sels) {
			var disabled_exists = false;
			var first_enable = [];

			for(var j=0; j < sels.options.length; j++) {
				if(sels.options[j].disabled) {
					sels.options[j].style.color = '#CCCCCC';
					disabled_exists = true;
				}else{
					first_enable[i] = (first_enable[i] > -1) ? first_enable[i] : j;
				}
			}

			if(!disabled_exists) return;

			sels.oldonchange = sels.onchange;
			sels.onchange = function() {
				if(this.options[this.selectedIndex].disabled) {

					this.selectedIndex = first_enable[i];
					/*
					if(this.options.length<=1) this.selectedIndex = -1;
					else if(this.selectedIndex < this.options.length - 1) this.selectedIndex++;
					else this.selectedIndex--;
					*/

				} else {
					if(this.oldonchange) this.oldonchange();
				}
			};

			if(sels.selectedIndex >= 0 && sels.options[ sels.selectedIndex ].disabled) sels.onchange();

		});
	}

	/* enforce max filesize on file uploaeds */
	$(document).on('change', 'input[type=file]', function() {
		var max_filesize = $(this).data('max-filesize');
		if (!max_filesize) return;
		var files = $(this).get(0).files;
		if (!files || !files[0]) return;
		if (files[0].size > max_filesize) {
			var max_filesize_error = String($(this).data('max-filesize-error'));
			max_filesize_error = max_filesize_error.replace('%s', XE.filesizeFormat(max_filesize));
			this.value = '';
			alert(max_filesize_error);
		}
	});
	
	jQuery('input[type="submit"],button[type="submit"]').click(function(ev){
		var $el = jQuery(ev.currentTarget);

		setTimeout(function(){
			return function(){
				$el.attr('disabled', 'disabled');
			};
		}(), 0);

		setTimeout(function(){
			return function(){
				$el.removeAttr('disabled');
			};
		}(), 3000);
	});
});

(function($) { // String extension methods

	/**
	 * @brief location.href에서 특정 key의 값을 return
	 **/
	String.prototype.getQuery = function(key) {
		var queries = window.XE.URI(this).search(true);
		var result = queries[key];
		if(typeof result === 'undefined') {
			return '';
		} else {
			return result;
		}
	};

	/**
	 * @brief location.href에서 특정 key의 값을 return
	 **/
	String.prototype.setQuery = function(key, val) {
		var uri = window.XE.URI(this);
		if(typeof key !== 'undefined') {
			if(typeof val === "undefined" || val === '' || val === null) {
				uri.removeSearch(key);
			} else {
				uri.setSearch(key, String(val));
			}
		}
		return normailzeUri(uri).toString();
	};

	/**
	 * @brief string prototype으로 escape 함수 추가
	 **/
	String.prototype.escape = function(double_escape) {
		var map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
		var revmap = { '&amp;amp;': '&amp;', '&amp;lt;': '&lt;', '&amp;gt;': '&gt;', '&amp;quot;': '&quot;', "&amp;#039;": '&#039;' };
		var result = String(this).replace(/[&<>"']/g, function(m) { return map[m]; });
		if (double_escape === false) {
			return result.replace(/&amp;(amp|lt|gt|quot|#039);/g, function(m) { return revmap[m]; });
		} else {
			return result;
		}
	};

	/**
	 * @brief string prototype으로 unescape 함수 추가
	 **/
	String.prototype.unescape = function() {
		var map = { '&amp;': '&', '&lt;': '<', '&gt;': '>', '&quot;': '"', '&#039;': "'" };
		return String(this).replace(/&(amp|lt|gt|quot|#039);/g, function(m) { return map[m]; });
	};

	/**
	 * @brief string prototype으로 stripTags 함수 추가
	 **/
	String.prototype.stripTags = function() {
		return String(this).replace(/<\/?[a-z][^>]*>/ig, "");
	};

	/**
	 * @brief string prototype으로 trim 함수 추가
	 **/
	if (!String.prototype.trim) {
		String.prototype.trim = function() {
			return String(this).replace(/^[\s\uFEFF\xA0]+|[\s\uFEFF\xA0]+$/g, '');
		};
	}
	
	/**
	 * @brief Helper function for setQuery()
	 * 
	 * @param uri URI
	 * @return URI
	 */
	function normailzeUri(uri) {
		var protocol = window.enforce_ssl ? 'https' : 'http';
		var port = (protocol === 'http') ? window.http_port : window.https_port;
		var filename = uri.filename() || 'index.php';
		var queries = uri.search(true);

		if(window.XE.isSameHost(uri.toString()) && filename === 'index.php' && $.isEmptyObject(queries)) {
			filename = '';
		}
		
		if(protocol !== 'https' && queries.act && $.inArray(queries.act, window.ssl_actions) !== -1) {
			protocol = 'https';
		}

		return uri.protocol(protocol).port(port || null).normalizePort().filename(filename);
	}
	
})(jQuery);

/**
 * @brief xSleep(micro time)
 **/
function xSleep(sec) {
	sec = sec / 1000;
	var now = new Date();
	var sleep = new Date();
	while( sleep.getTime() - now.getTime() < sec) {
		sleep = new Date();
	}
}

/**
 * @brief 주어진 인자가 하나라도 defined되어 있지 않으면 false return
 **/
function isDef() {
	for(var i=0; i < arguments.length; ++i) {
		if(typeof(arguments[i]) == "undefined") return false;
	}
	return true;
}

/**
 * @brief 윈도우 오픈
 * 열려진 윈도우의 관리를 통해 window.focus()등을 FF에서도 비슷하게 구현함
 **/
var winopen_list = {};
function winopen(url, target, features) {
	try {
		if (target != '_blank' && winopen_list[target]) {
			winopen_list[target].close();
			winopen_list[target] = null;
		}
	} catch(e) {
		// no-op
	}

	if (typeof target == 'undefined') target = '_blank';
	if (typeof features == 'undefined') features = '';
	
	if (!window.XE.isSameHost(url)) {
		window.blankshield.open(url, target, features);
	} else {
		var win = window.open(url, target, features);
		win.focus();
		if (target != '_blank') {
			winopen_list[target] = win;
		}
	}
}

/**
 * @brief 팝업으로만 띄우기
 * common/tpl/popup_layout.html이 요청되는 XE내의 팝업일 경우에 사용
 **/
function popopen(url, target) {
	winopen(url, target, "width=800,height=600,scrollbars=yes,resizable=yes,toolbars=no");
}

/**
 * @brief 메일 보내기용
 **/
function sendMailTo(to) {
	location.href="mailto:"+to;
}

/**
 * @brief url이동 (Rhymix 개선된 버전)
 */
function redirect(url) {
	if (isCurrentPageUrl(url)) {
		window.location.href = url;
		window.location.reload();
	} else {
		window.location.href = url;
	}
}

function isCurrentPageUrl(url) {
	var absolute_url = window.location.href;
	var relative_url = window.location.pathname + window.location.search;
	return  url === absolute_url || url.indexOf(absolute_url.replace(/#.+$/, "") + "#") === 0 ||
			url === relative_url || url.indexOf(relative_url.replace(/#.+$/, "") + "#") === 0;
}

/**
 * @brief url이동 (open_window 값이 N 가 아니면 새창으로 띄움)
 **/
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
 * @brief 멀티미디어 출력용 (IE에서 플래쉬/동영상 주변에 점선 생김 방지용)
 **/
function displayMultimedia(src, width, height, options) {
	/*jslint evil: true */
	var html = _displayMultimedia(src, width, height, options);
	if(html) document.writeln(html);
}
function _displayMultimedia(src, width, height, options) {
	if(src.indexOf('files') === 0) src = request_uri + src;

	var defaults = {
		wmode : 'transparent',
		allowScriptAccess : 'never',
		quality : 'high',
		flashvars : '',
		autostart : false
	};

	var params = jQuery.extend(defaults, options || {});
	var autostart = (params.autostart && params.autostart != 'false') ? 'true' : 'false';
	delete(params.autostart);

	var clsid = "";
	var codebase = "";
	var html = "";
	width = parseInt(width, 10);
	height = parseInt(height, 10);

	if(/\.(gif|jpg|jpeg|bmp|png)$/i.test(src)){
		html = '<img src="'+src+'" width="'+width+'" height="'+height+'" class="thumb" />';
	} else {
		html = '<span style="position:relative;background:black;width:' + width + 'px;height:' + height + 'px" class="thumb">';
		html += '<img style="width:24px;height:24px;position:absolute;left:50%;top:50%;border:0;margin:-12px 0 0 -12px;padding:0" src="./common/img/play.png" alt="" />';
		html += '</span>';
	}
	return html;
}

/**
 * @brief 에디터에서 사용되는 내용 여닫는 코드 (고정, zbxe용)
 **/
function zbxe_folder_open(id) {
	jQuery("#folder_open_"+id).hide();
	jQuery("#folder_close_"+id).show();
	jQuery("#folder_"+id).show();
}
function zbxe_folder_close(id) {
	jQuery("#folder_open_"+id).show();
	jQuery("#folder_close_"+id).hide();
	jQuery("#folder_"+id).hide();
}

/**
 * @brief 팝업의 경우 내용에 맞춰 현 윈도우의 크기를 조절해줌
 * 팝업의 내용에 맞게 크기를 늘리는 것은... 쉽게 되지는 않음.. ㅡ.ㅜ
 * popup_layout 에서 window.onload 시 자동 요청됨.
 **/
function setFixedPopupSize() {
	var $ = jQuery, $win = $(window), $pc = $('body>.popup'), w, h, dw, dh, offset, scbw;

	var $outer = $('<div>').css({visibility: 'hidden', width: 100, overflow: 'scroll'}).appendTo('body'),
		widthWithScroll = $('<div>').css({width: '100%'}).appendTo($outer).outerWidth();
	$outer.remove();
	scbw = 100 - widthWithScroll;

	offset = $pc.css({overflow:'scroll'}).offset();

	w = $pc.width(10).height(10000).get(0).scrollWidth + offset.left*2;

	if(w < 800) w = 800 + offset.left*2;
	// Window 의 너비나 높이는 스크린의 너비나 높이보다 클 수 없다. 스크린의 너비나 높이와 내용의 너비나 높이를 비교해서 최소값을 이용한다.
	w = Math.min(w, window.screen.availWidth);

	h = $pc.width(w - offset.left*2).height(10).get(0).scrollHeight + offset.top*2;

	dw = $win.width();
	dh = $win.height();

	h = Math.min(h, window.screen.availHeight - 100);
	window.resizeBy(w - dw, h - dh);

	$pc.width('100%').css({overflow:'',height:'','box-sizing':'border-box'});

}

/**
 * @brief 추천/비추천,스크랩,신고기능등 특정 srl에 대한 특정 module/action을 호출하는 함수
 **/
function doCallModuleAction(module, action, target_srl) {
	var params = {
		target_srl : target_srl,
		cur_mid    : current_mid,
		mid        : current_mid
	};
	exec_xml(module, action, params, completeCallModuleAction);
}

function completeCallModuleAction(ret_obj, response_tags) {
	if(ret_obj.message!='success') alert(ret_obj.message);
	location.reload();
}

function completeMessage(ret_obj) {
	alert(ret_obj.message);
	location.reload();
}



/* 언어코드 (lang_type) 쿠키값 변경 */
function doChangeLangType(obj) {
	if(typeof(obj) == "string") {
		setLangType(obj);
	} else {
		var val = obj.options[obj.selectedIndex].value;
		setLangType(val);
	}
	if(location.href.match(/[?&]l=[a-z]+/)) {
		location.href = location.href.setQuery('l', '');
	} else {
		location.reload();
	}
}
function setLangType(lang_type) {
	XE.cookie.set("lang_type", lang_type, { path: "/", expires: 3650 });
}

/* 미리보기 */
function doDocumentPreview(obj) {
	var fo_obj = obj;
	while(fo_obj.nodeName != "FORM") {
		fo_obj = fo_obj.parentNode;
	}
	if(fo_obj.nodeName != "FORM") return;
	var editor_sequence = fo_obj.getAttribute('editor_sequence');

	var content = editorGetContent(editor_sequence);

	var win = window.open("", "previewDocument","toolbars=no,width=700px;height=800px,scrollbars=yes,resizable=yes");

	var dummy_obj = jQuery("#previewDocument");

	if(!dummy_obj.length) {
		jQuery(
			'<form id="previewDocument" target="previewDocument" method="post" action="'+request_uri+'">'+
			'<input type="hidden" name="_rx_csrf_token" value="' + getCSRFToken() + '" />'+
			'<input type="hidden" name="module" value="document" />'+
			'<input type="hidden" name="act" value="dispDocumentPreview" />'+
			'<input type="hidden" name="mid" value="' + current_mid +'" />'+
			'<input type="hidden" name="content" />'+
			'</form>'
		).appendTo(document.body);

		dummy_obj = jQuery("#previewDocument")[0];
	} else {
		dummy_obj = dummy_obj[0];
	}

	if(dummy_obj) {
		dummy_obj.content.value = content;
		dummy_obj.submit();
	}
}

/* 게시글 저장 */
function doDocumentSave(obj) {
	var editor_sequence = obj.form.getAttribute('editor_sequence');
	var prev_content = editorRelKeys[editor_sequence].content.value;
	if(typeof(editor_sequence)!='undefined' && editor_sequence && typeof(editorRelKeys)!='undefined' && typeof(editorGetContent)=='function') {
		var content = editorGetContent(editor_sequence);
		editorRelKeys[editor_sequence].content.value = content;
	}

	var params={}, responses=['error','message','document_srl'], elms=obj.form.elements, data=jQuery(obj.form).serializeArray();
	jQuery.each(data, function(i, field){
		var val = jQuery.trim(field.value);
		if(!val) return true;
		if(/\[\]$/.test(field.name)) field.name = field.name.replace(/\[\]$/, '');
		if(params[field.name]) params[field.name] += '|@|'+val;
		else params[field.name] = field.value;
	});

	exec_xml('document','procDocumentTempSave', params, completeDocumentSave, responses, params, obj.form);

	editorRelKeys[editor_sequence].content.value = prev_content;
	return false;
}

function completeDocumentSave(ret_obj) {
	jQuery('input[name=document_srl]').eq(0).val(ret_obj.document_srl);
	alert(ret_obj.message);
}

/* 저장된 게시글 불러오기 */
var objForSavedDoc = null;
function doDocumentLoad(obj) {
	// 저장된 게시글 목록 불러오기
	objForSavedDoc = obj.form;
	popopen(request_uri.setQuery('module','document').setQuery('act','dispTempSavedList'));
}

/* 저장된 게시글의 선택 */
function doDocumentSelect(document_srl, module) {
	if(!opener || !opener.objForSavedDoc) {
		window.close();
		return;
	}

	if(module===undefined) {
		module = 'document';
	}

	// 게시글을 가져와서 등록하기
	switch(module) {
		case 'page' :
			var url = opener.current_url;
			url = url.setQuery('document_srl', document_srl);

			if(url.getQuery('act') === 'dispPageAdminMobileContentModify')
			{
				url = url.setQuery('act', 'dispPageAdminMobileContentModify');
			}
			else
			{
				url = url.setQuery('act', 'dispPageAdminContentModify');
			}
			opener.location.href = url;
			break;
		default :
			opener.location.href = opener.current_url.setQuery('document_srl', document_srl).setQuery('act', 'dispBoardWrite');
			break;
	}
	window.close();
}


/* 스킨 정보 */
function viewSkinInfo(module, skin) {
	popopen("./?module=module&act=dispModuleSkinInfo&selected_module="+module+"&skin="+skin, 'SkinInfo');
}


/* 관리자가 문서를 관리하기 위해서 선택시 세션에 넣음 */
var addedDocument = [];
function doAddDocumentCart(obj) {
	var srl = obj.value;
	addedDocument[addedDocument.length] = srl;
	setTimeout(function() { callAddDocumentCart(addedDocument.length); }, 100);
}

function callAddDocumentCart(document_length) {
	if(addedDocument.length<1 || document_length != addedDocument.length) return;
	var params = [];
	params.srls = addedDocument.join(",");
	exec_xml("document","procDocumentAddCart", params, null);
	addedDocument = [];
}

/* ff의 rgb(a,b,c)를 #... 로 변경 */
function transRGB2Hex(value) {
	if(!value) return value;
	if(value.indexOf('#') > -1) return value.replace(/^#/, '');

	if(value.toLowerCase().indexOf('rgb') < 0) return value;
	value = value.replace(/^rgb\(/i, '').replace(/\)$/, '');
	value_list = value.split(',');

	var hex = '';
	for(var i = 0; i < value_list.length; i++) {
		var color = parseInt(value_list[i], 10).toString(16);
		if(color.length == 1) color = '0'+color;
		hex += color;
	}
	return hex;
}

/* 보안 로그인 모드로 전환 */
function toggleSecuritySignIn() {
	var href = location.href;
	if(/https:\/\//i.test(href)) location.href = href.replace(/^https/i,'http');
	else location.href = href.replace(/^http/i,'https');
}

function reloadDocument() {
	location.reload();
}


/**
*
* Base64 encode / decode
* http://www.webtoolkit.info/
*
**/

var Base64 = {

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






/* ----------------------------------------------
 * DEPRECATED
 * 하위호환용으로 남겨 놓음
 * ------------------------------------------- */

if(typeof(resizeImageContents) == 'undefined') {
	window.resizeImageContents = function() {};
}

if(typeof(activateOptionDisabled) == 'undefined') {
	window.activateOptionDisabled = function() {};
}

objectExtend = jQuery.extend;

/**
 * @brief 특정 Element의 display 옵션 토글
 **/
function toggleDisplay(objId) {
	jQuery('#'+objId).toggle();
}

/**
 * @brief 에디터에서 사용하되 내용 여닫는 코드 (zb5beta beta 호환용으로 남겨 놓음)
 **/
function svc_folder_open(id) {
	jQuery("#_folder_open_"+id).hide();
	jQuery("#_folder_close_"+id).show();
	jQuery("#_folder_"+id).show();
}
function svc_folder_close(id) {
	jQuery("#_folder_open_"+id).show();
	jQuery("#_folder_close_"+id).hide();
	jQuery("#_folder_"+id).hide();
}

/**
 * @brief 날짜 선택 (달력 열기)
 **/
function open_calendar(fo_id, day_str, callback_func) {
	if(typeof(day_str)=="undefined") day_str = "";

	var url = "./common/tpl/calendar.php?";
	if(fo_id) url+="fo_id="+fo_id;
	if(day_str) url+="&day_str="+day_str;
	if(callback_func) url+="&callback_func="+callback_func;

	popopen(url, 'Calendar');
}

var loaded_popup_menus = XE.loaded_popup_menus;
function createPopupMenu() {}
function chkPopupMenu() {}
function displayPopupMenu(ret_obj, response_tags, params) {
	XE.displayPopupMenu(ret_obj, response_tags, params);
}

function GetObjLeft(obj) {
	return jQuery(obj).offset().left;
}
function GetObjTop(obj) {
	return jQuery(obj).offset().top;
}

function replaceOuterHTML(obj, html) {
	jQuery(obj).replaceWith(html);
}

function getOuterHTML(obj) {
	return jQuery(obj).html().trim();
}

function setCookie(name, value, expires, path) {
	var options = {
		path: path ? path : "/",
		secure: cookies_ssl ? true : false
	};
	if (expires) {
		options.expires = expires;
	}
	XE.cookie.set(name, value, options);
}

function getCookie(name) {
	return XE.cookie.get(name);
}

function is_def(v) {
	return typeof(v) != 'undefined' && v !== null;
}

function ucfirst(str) {
	return str.charAt(0).toUpperCase() + str.slice(1);
}

function get_by_id(id) {
	return document.getElementById(id);
}

jQuery(function($){
	// display popup menu that contains member actions and document actions
	$(document).on('click', function(evt) {
		var $area = $('#popup_menu_area');
		if(!$area.length) $area = $('<div id="popup_menu_area" tabindex="0" style="display:none;" />').appendTo(document.body);

		// 이전에 호출되었을지 모르는 팝업메뉴 숨김
		$area.hide();

		var $target = $(evt.target).filter('a,div,span');
		if(!$target.length) $target = $(evt.target).closest('a,div,span');
		if(!$target.length) return;

		// 객체의 className값을 구함
		var cls = $target.attr('class'), match;
		if(cls) match = cls.match(new RegExp('(?:^| )((document|comment|member)_([1-9]\\d*))(?: |$)',''));
		if(!match) return;

		// mobile에서 touchstart에 의한 동작 시 pageX, pageY 위치를 구함
		if(evt.pageX===undefined || evt.pageY===undefined)
		{
			var touch = evt.originalEvent.touches[0];
			if(touch!==undefined || !touch)
			{
				touch = evt.originalEvent.changedTouches[0];
			}
			evt.pageX = touch.pageX;
			evt.pageY = touch.pageY;
		}

		var action = 'get'+ucfirst(match[2])+'Menu';
		var params = {
			mid        : current_mid,
			cur_mid    : current_mid,
			menu_id    : match[1],
			target_srl : match[3],
			cur_act    : current_url.getQuery('act'),
			page_x     : evt.pageX,
			page_y     : evt.pageY
		};
		var response_tags = 'error message menus'.split(' ');

		// prevent default action
		evt.preventDefault();
		evt.stopPropagation();

		if(is_def(XE.loaded_popup_menus[params.menu_id])) return XE.displayPopupMenu(params, response_tags, params);

		show_waiting_message = false;
		exec_xml('member', action, params, XE.displayPopupMenu, response_tags, params);
		show_waiting_message = true;
	});

	/**
	 * Create popup windows automatically.
	 * Find anchors that have the '_xe_popup' class, then add popup script to them.
	 */
	$('body').on('click', 'a._xe_popup', function(event) {
		var $this = $(this);
		var name = $this.attr('name');
		var href = $this.attr('href');
		if (!name) name = '_xe_popup_' + Math.floor(Math.random() * 1000);
		
		event.preventDefault();
		winopen(href, name, 'left=10,top=10,width=10,height=10,resizable=no,scrollbars=no,toolbars=no');
	});

	// date picker default settings
	if($.datepicker) {
		$.datepicker.setDefaults({
			dateFormat : 'yy-mm-dd'
		});
	}
});
