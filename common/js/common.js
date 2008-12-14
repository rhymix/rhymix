/**
 * @file common.js
 * @author zero (zero@nzeo.com)
 * @brief 몇가지 유용한 & 기본적으로 자주 사용되는 자바스크립트 함수들 모음
 **/

/* jQuery 참조변수($) 제거 */
if(jQuery) jQuery.noConflict();

;(function($) {
    /**
     * @brief XE 공용 유틸리티 함수
     * @namespace XE
     */
    window.XE = {
        loaded_popup_menus : new Array(),
        addedDocument : new Array(),
        /**
         * @brief 특정 name을 가진 체크박스들의 checked 속성 변경
         * @param [itemName='cart',][options={}]
         */
        checkboxToggleAll : function() {
            var itemName='cart';
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
                    }
                    break;
                case 2:
                    itemName = arguments[0];
                    $.extend(options, arguments[1] || {});
            }

            if(options.doClick == true) options.checked = null;
            if(typeof(options.wrap) == "string") options.wrap ='#'+options.wrap;

            if(options.wrap) {
                var obj = $(options.wrap).find('input[name='+itemName+']:checkbox');
            } else {
                var obj = $('input[name='+itemName+']:checkbox');
            }

            if(options.checked == 'toggle') {
                obj.each(function() {
                    $(this).attr('checked', ($(this).attr('checked')) ? false : true);
                });
            } else {
                (options.doClick == true) ? obj.click() : obj.attr('checked', options.checked);
            }
        },

        /**
         * @brief 문서/회원 등 팝업 메뉴 출력
         */
        displayPopupMenu : function(ret_obj, response_tags, params) {
            var target_srl = params["target_srl"];
            var menu_id = params["menu_id"];
            var menus = ret_obj['menus'];
            var html = "";

            if(this.loaded_popup_menus[menu_id]) {
                html = this.loaded_popup_menus[menu_id];

            } else {
                if(menus) {
                    var item = menus['item'];
                    if(typeof(item.length)=='undefined' || item.length<1) item = new Array(item);
                    if(item.length) {
                        for(var i=0;i<item.length;i++) {
                            var url = item[i].url;
                            var str = item[i].str;
                            var icon = item[i].icon;
                            var target = item[i].target;

                            var styleText = "";
                            var click_str = "";
                            if(icon) styleText = " style=\"background-image:url('"+icon+"')\" ";
                            switch(target) {
                                case "popup" :
                                        click_str = " onclick=\"popopen(this.href,'"+target+"'); return false;\"";
                                    break;
                                case "self" :
                                        //click_str = " onclick=\"location.href='"+url+"' return false;\"";
                                    break;
                                case "javascript" :
                                        click_str = " onclick=\""+url+"; return false; \"";
                                        url="#";
                                    break;
                                default :
                                        click_str = " onclick=\"window.open(this.href); return false;\"";
                                    break;
                            }

                            html += '<li '+styleText+'><a href="'+url+'"'+click_str+'>'+str+'</a></li> ';
                        }
                    }
                }
                this.loaded_popup_menus[menu_id] =  html;
            }

            // 레이어 출력
            if(html) {
                var area = jQuery("#popup_menu_area").html('<ul>'+html+'</ul>');
                var areaOffset = {top:params['page_y'], left:params['page_x']};

                if(area.outerHeight()+areaOffset.top > jQuery(window).height()+jQuery(window).scrollTop())
                    areaOffset.top = jQuery(window).height() - area.outerHeight() + jQuery(window).scrollTop();
                if(area.outerWidth()+areaOffset.left > jQuery(window).width()+jQuery(window).scrollLeft())
                    areaOffset.left = jQuery(window).width() - area.outerWidth() + jQuery(window).scrollLeft();

                if($.browser.safari) {
                    areaOffset.top -= 16;
                    areaOffset.left -= 16;
                }

                area.css({ visibility:"visible", top:areaOffset.top, left:areaOffset.left });
            }
        }
    }

    /**
     * jQuery 플러그인 로드
     * 시험중
     */
    $.getPlugin = function(name, options) {
        $.loaded_plugin = new Array();
        var version = '';
        var defaults = {
            version:'',
            prefix:'jquery.',
            path:'./common/js/jquery/'
        };
        var options = $.extend(defaults, options || {});
        if(options.version) version = '-' + options.version;

        $.ajax({
            type: 'GET',
            url : options.path + options.prefix + name + version + '.js',
            cache : true,
            async:false,
            success : function() {
                $.loaded_plugin[name] = {'version':options.version};
            },
            dataType : 'script'
        });

    }

}) (jQuery);

/* jQuery(document).ready() */
jQuery(function($) {
    /* 팝업메뉴 레이어 생성 */
    if(!$('#popup_menu_area').length) {
        var menuObj = $('<div>')
            .attr('id', 'popup_menu_area')
            .css({visibility:'hidden', zIndex:9999});
        $(document.body).append(menuObj);
    }

    $(document).click(function(evt) {
        var area = jQuery("#popup_menu_area");
        if(!area.length) return;

        // 이전에 호출되었을지 모르는 팝업메뉴 숨김
        area.css('visibility', 'hidden');

        var targetObj = $(evt.target);
        if(!targetObj.length) return;

        // obj의 nodeName이 div나 span이 아니면 나올대까지 상위를 찾음
        if(targetObj.length && jQuery.inArray(targetObj.attr('nodeName'), ['DIV', 'SPAN', 'A']) == -1) targetObj = targetObj.parent();
        if(!targetObj.length || jQuery.inArray(targetObj.attr('nodeName'), ['DIV', 'SPAN', 'A']) == -1) return;

        // 객체의 className값을 구함
        var class_name = targetObj.attr('className');
        if(class_name.indexOf('_') <= 0) return;
        // className을 분리
        var class_name_list = class_name.split(' ');

        var menu_id = '';
        var menu_id_regx = /^([a-zA-Z]+)_([0-9]+)$/;


        for(var i = 0, c = class_name_list.length; i < c; i++) {
            if(menu_id_regx.test(class_name_list[i])) {
                menu_id = class_name_list[i];
            }
        }

        if(!menu_id) return;

        // module명과 대상 번호가 없으면 return
        var tmp_arr = menu_id.split('_');
        var module_name = tmp_arr[0];
        var target_srl = tmp_arr[1];
        if(!module_name || !target_srl || target_srl < 1) return;

        // action이름을 규칙에 맞게 작성
        var action_name = "get" + module_name.substr(0,1).toUpperCase() + module_name.substr(1,module_name.length-1) + "Menu";

        // 서버에 메뉴를 요청
        var params = new Array();
        params["target_srl"] = target_srl;
        params["cur_mid"] = current_mid;
        params["cur_act"] = current_url.getQuery('act');
        params["menu_id"] = menu_id;
        params["page_x"] = evt.pageX;
        params["page_y"] = evt.pageY;

        var response_tags = new Array("error","message","menus");

        if(typeof(XE.loaded_popup_menus[menu_id]) != 'undefined') {
            XE.displayPopupMenu(params, response_tags, params);
            return;
        }
        show_waiting_message = false;
        exec_xml(module_name, action_name, params, XE.displayPopupMenu, response_tags, params);
        show_waiting_message = true;
    });

    /* select - option의 disabled=disabled 속성을 IE에서도 체크하기 위한 함수 */
    if($.browser.msie) {
        $('select').each(function(i, sels) {
            var disabled_exists = false;
            var first_enable = new Array();

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
});



/**
 * @brief location.href에서 특정 key의 값을 return
 **/
String.prototype.getQuery = function(key) {
    var idx = this.indexOf('?');
    if(idx == -1) return null;
    var query_string = this.substr(idx+1, this.length);
    var args = {};
    query_string.replace(/([^=]+)=([^&]*)(&|$)/g, function() { args[arguments[1]] = arguments[2]; });

    var q = args[key];
    if(typeof(q)=="undefined") q = "";

    return q;
}

/**
 * @brief location.href에서 특정 key의 값을 return
 **/
String.prototype.setQuery = function(key, val) {
    var idx = this.indexOf('?');
    var uri = this;
    uri = uri.replace(/#$/,'');

    if(idx != -1) {
        uri = this.substr(0, idx);
        var query_string = this.substr(idx+1, this.length);
        var args = new Array();
        query_string.replace(/([^=]+)=([^&]*)(&|$)/g, function() { args[arguments[1]] = arguments[2]; });

        args[key] = val;

        var q_list = new Array();
        for(var i in args) {
        if( !args.hasOwnProperty(i) ) continue;
            var arg = args[i];
            if(!arg.toString().trim()) continue;

            q_list[q_list.length] = i+'='+arg;
        }
        uri = uri+"?"+q_list.join("&");
    } else {
        if(val.toString().trim()) uri = uri+"?"+key+"="+val;
    }

    uri = uri.replace(/^https:\/\//i,'http://');
    if(typeof(ssl_actions)!='undefined' && typeof(ssl_actions.length)!='undefined' && uri.getQuery('act')) {
        var act = uri.getQuery('act');
        for(i=0;i<ssl_actions.length;i++) {
            if(ssl_actions[i]==act) {
                uri = uri.replace(/^http:\/\//i,'https://');
                break;
            }
        }
    }
    return encodeURI(uri);
}

/**
 * @brief string prototype으로 trim 함수 추가
 **/
String.prototype.trim = function() {
    return this.replace(/(^\s*)|(\s*$)/g, "");
}

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
var winopen_list = new Array();
function winopen(url, target, attribute) {
    try {
        if(target != "_blank" && winopen_list[target]) {
            winopen_list[target].close();
            winopen_list[target] = null;
        }
    } catch(e) {
    }

    if(typeof(target) == 'undefined') target = '_blank';
    if(typeof(attribute) == 'undefined') attribute = '';
    var win = window.open(url, target, attribute);
    win.focus();
    if(target != "_blank") winopen_list[target] = win;
}

/**
 * @brief 팝업으로만 띄우기
 * common/tpl/popup_layout.html이 요청되는 XE내의 팝업일 경우에 사용
 **/
function popopen(url, target) {
    if(typeof(target) == "undefined") target = "_blank";
    winopen(url, target, "left=10,top=10,width=10,height=10,scrollbars=no,resizable=yes,toolbars=no");
}

/**
 * @brief 메일 보내기용
 **/
function sendMailTo(to) {
    location.href="mailto:"+to;
}

/**
 * @brief url이동 (open_window 값이 N 가 아니면 새창으로 띄움)
 **/
function move_url(url, open_wnidow) {
    if(!url) return false;
    if(typeof(open_wnidow) == 'undefined') open_wnidow = 'N';
    if(open_wnidow=='N') {
        open_wnidow = false;
    } else {
        open_wnidow = true;
    }

    if(/^\./.test(url)) url = request_uri+url;

    if(open_wnidow) {
        winopen(url);
    } else {
        location.href=url;
    }

    return false;
}

/**
 * @brief 멀티미디어 출력용 (IE에서 플래쉬/동영상 주변에 점선 생김 방지용)
 **/
function displayMultimedia(src, width, height, options) {
    if(src.indexOf('files') == 0) src = request_uri + src;

    var defaults = {
        wmode : 'transparent',
        allowScriptAccess : 'sameDomain',
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

    if(/\.swf/i.test(src)) {
        clsid = 'clsid:D27CDB6E-AE6D-11cf-96B8-444553540000';
        codebase = "http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,28,0";
        html = '<object classid="'+clsid+'" codebase="'+codebase+'" width="'+width+'" height="'+height+'" flashvars="'+params.flashvars+'">';
        html += '<param name="movie" value="'+src+'" />';
        for(var name in params) {
            if(params[name] != 'undefined' && params[name] != '') {
                html += '<param name="'+name+'" value="'+params[name]+'" />';
            }
        }
        html += ''
            + '<embed src="'+src+'" autostart="'+autostart+'"  width="'+width+'" height="'+height+'" flashvars="'+params.flashvars+'" wmode="'+params.wmode+'"></embed>'
            + '</object>';
    } else if(/\.flv/i.test(src)) {
        html = '<embed src="'+request_uri+'common/tpl/images/flvplayer.swf" allowfullscreen="true" autostart="'+autostart+'" width="'+width+'" height="'+height+'" flashvars="&file='+src+'&width='+width+'&height='+height+'&autostart='+autostart+'" />';
    } else {
        html = '<embed src="'+src+'" autostart="'+autostart+'" width="'+width+'" height="'+height+'"></embed>';
    }
    document.writeln(html);
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
 * popup_layout 에서 window.onload 시 자동 요청됨.
 * @FIXME 크롬에서 resizeTo()후에 창 크기를 잘못 가져옴
 *        resizeTo() 후에 alert()창 띄운 후에 체크하면 정상.
 **/
function setFixedPopupSize() {
    var bodyObj = jQuery('#popBody');

    if(bodyObj.length) {
        if(bodyObj.height() > 500) {
            bodyObj.css({ overflowY:'scroll', overflowX:'hidden', height:500 });
        }
    }

    var wrapWidth = jQuery(document).width();
    var wrapHeight = jQuery(document).height();

    window.resizeTo(wrapWidth, wrapHeight);

    var w1 = jQuery(window).width();
    var h1 = jQuery(window).height();

    // 크롬의 문제로 W, H 값을 따로 설정
    // window.resizeBy(wrapWidth - w1, wrapHeight-h1);
    window.resizeBy(wrapWidth - w1, 0);
    window.resizeBy(0, wrapHeight-h1);

    window.scrollTo(0, 0);
}

/**
 * @brief 추천/비추천,스크랩,신고기능등 특정 srl에 대한 특정 module/action을 호출하는 함수
 **/
function doCallModuleAction(module, action, target_srl) {
    var params = new Array();
    params['target_srl'] = target_srl;
    params['cur_mid'] = current_mid;
    exec_xml(module, action, params, completeCallModuleAction);
}

function completeCallModuleAction(ret_obj, response_tags) {
    if(ret_obj['message']!='success') alert(ret_obj['message']);
    location.reload();
}

function completeMessage(ret_obj) {
    alert(ret_obj['message']);
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
    location.reload();
}
function setLangType(lang_type) {
    var expire = new Date();
    expire.setTime(expire.getTime()+ (7000 * 24 * 3600000));
    xSetCookie('lang_type', lang_type, expire);
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
            '<input type="hidden" name="module" value="document" />'+
            '<input type="hidden" name="act" value="dispDocumentPreview" />'+
            '<input type="hidden" name="content" />'+
            '</form>'
        ).appendTo(document.body);

        dummy_obj = jQuery("#previewDocument")[0];
    }

    if(dummy_obj) {
        dummy_obj.content.value = content;
        dummy_obj.submit();
    }
}

/* 게시글 저장 */
function doDocumentSave(obj) {
    var editor_sequence = obj.form.getAttribute('editor_sequence');
    var prev_content = editorRelKeys[editor_sequence]['content'].value;
    if(typeof(editor_sequence)!='undefined' && editor_sequence && typeof(editorRelKeys)!='undefined' && typeof(editorGetContent)=='function') {
        var content = editorGetContent(editor_sequence);
        editorRelKeys[editor_sequence]['content'].value = content;
    }

    var oFilter = new XmlJsFilter(obj.form, "member", "procMemberSaveDocument", completeDocumentSave);
    oFilter.addResponseItem("error");
    oFilter.addResponseItem("message");
    oFilter.addResponseItem("document_srl");
    oFilter.proc();

    editorRelKeys[editor_sequence]['content'].value = prev_content;
    return false;
}

function completeDocumentSave(ret_obj) {
    jQuery('input[name=document_srl]').eq(0).val(ret_obj['document_srl']);
    alert(ret_obj['message']);
}

/* 저장된 게시글 불러오기 */
var objForSavedDoc = null;
function doDocumentLoad(obj) {
    // 저장된 게시글 목록 불러오기
    objForSavedDoc = obj.form;
    popopen(request_uri.setQuery('module','member').setQuery('act','dispSavedDocumentList'));
}

/* 저장된 게시글의 선택 */
function doDocumentSelect(document_srl) {
    if(!opener || !opener.objForSavedDoc) {
        window.close();
        return;
    }

    // 게시글을 가져와서 등록하기
    opener.location.href = opener.current_url.setQuery('document_srl', document_srl).setQuery('act', 'dispBoardWrite');
    window.close();
}


/* 스킨 정보 */
function viewSkinInfo(module, skin) {
    popopen("./?module=module&act=dispModuleSkinInfo&selected_module="+module+"&skin="+skin, 'SkinInfo');
}


/* 관리자가 문서를 관리하기 위해서 선택시 세션에 넣음 */
var addedDocument = new Array();
function doAddDocumentCart(obj) {
    var srl = obj.value;
    addedDocument[addedDocument.length] = srl;
    setTimeout(function() { callAddDocumentCart(addedDocument.length); }, 100);
}

function callAddDocumentCart(document_length) {
    if(addedDocument.length<1 || document_length != addedDocument.length) return;
    var params = new Array();
    params["srls"] = addedDocument.join(",");
    exec_xml("document","procDocumentAdminAddCart", params, null);
    addedDocument = new Array();
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
        var c = c1 = c2 = 0;

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

}






/* ----------------------------------------------
 * DEPRECATED
 * 하위호환용으로 남겨 놓음
 * ------------------------------------------- */

if(typeof(resizeImageContents) == 'undefined') {
    function resizeImageContents() {}
}

if(typeof(activateOptionDisabled) == 'undefined') {
    function activateOptionDisabled() {}
}

objectExtend = jQuery.extend;

/**
 * @brief 특정 Element의 display 옵션 토글
 **/
function toggleDisplay(objId) {
    jQuery('#'+objId).toggle();
}

/* 체크박스 선택 */
function checkboxSelectAll(formObj, name, checked) {
    var itemName = name;
    var option = {};
    if(typeof(formObj) != "undefined") option.wrap = formObj;
    if(typeof(checked) != "undefined") option.checked = checked;

    XE.checkboxToggleAll(itemName, option);
}

/* 체크박스를 실행 */
function clickCheckBoxAll(formObj, name) {
    var itemName = name;
    var option = { doClick:true };
    if(typeof(formObj) != "undefined") option.wrap = formObj;

    XE.checkboxToggleAll(itemName, option);
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