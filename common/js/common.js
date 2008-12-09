/**
 * @file common.js
 * @author zero (zero@nzeo.com)
 * @brief 몇가지 유용한 & 기본적으로 자주 사용되는 자바스크립트 함수들 모음
 **/

/* jQuery 참조변수($) 제거 */
if(jQuery) jQuery.noConflict();

/**
 * @brief XE 공용 유틸리티 함수
 * @namespace XE
 */
;(function($) {
window.XE = {
    /**
     * @brief 특정 name을 가진 체크박스들의 checked 속성 변경
     * @param [itemName='cart',][options={checked:true, doClick:false}]
     */
    setCheckedAll : function() {
        var itemName='cart', options={checked:true, doClick:false};

        switch(arguments.length) {
            case 1:
                if(typeof(arguments[0]) == "string") {
                    itemName = arguments[0];
                } else {
                    options = $.extend(options, arguments[0] || {});
                }
                break;
            case 2:
                itemName = arguments[0];
                options = $.extend(options, arguments[1] || {});
        }

        var obj = $('[name='+itemName+']');

        if(options.checked == 'toggle') {
            obj.each(function() {
                if($(this).attr('checked')) {
                    (options.doClick == true) ? $(this).click() : $(this).attr('checked', false);
                } else {
                    (options.doClick == true) ? $(this).click() : $(this).attr('checked', true);
                }
            });
        } else {
            (options.doClick == true) ? obj.click() : obj.attr('checked', options.checked);
        }
    }
}
}) (jQuery);



/**
 * @brief location.href에서 특정 key의 값을 return
 **/
String.prototype.getQuery = function(key) {
    var idx = this.indexOf('?');
    if(idx == -1) return null;
    var query_string = this.substr(idx+1, this.length);
    var args = {}
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
 * @brief 주어진 인자가 하나라도 defined되어 있지 않으면 false return
 **/
function isDef() {
    for(var i=0; i<arguments.length; ++i) {
        if(typeof(arguments[i])=="undefined") return false;
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

    if(typeof(target)=='undefined') target = '_blank';
    if(typeof(attribute)=='undefined') attribute = '';
    var win = window.open(url, target, attribute);
    win.focus();
    if(target != "_blank") winopen_list[target] = win;
}

/**
 * @brief 팝업으로만 띄우기
 * common/tpl/popup_layout.html이 요청되는 XE내의 팝업일 경우에 사용
 **/
function popopen(url, target) {
    if(typeof(target)=="undefined") target = "_blank";
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
    if(typeof(open_wnidow)=='undefined') open_wnidow = 'N';
    if(open_wnidow=='N') open_wnidow = false;
    else open_wnidow = true;

    if(/^\./.test(url)) url = request_uri+url;

    if(open_wnidow) {
        winopen(url);
    } else {
        location.href=url;
    }
    return false;
}

/**
 * @brief 특정 Element의 display 옵션 토글
 **/
function toggleDisplay(obj, display_type) {
    var obj = xGetElementById(obj);
    if(!obj) return;
    if(!obj.style.display || obj.style.display != 'none') {
        obj.style.display = 'none';
    } else {
        if(display_type) obj.style.display = display_type;
        else obj.style.display = '';
    }
}

/* jQuery의 extend. */
/* TODO:jQuery 등 자바스크립트 프레임웍 차용시 대체 가능하면 제거 대상 */
objectExtend = function() {
    // copy reference to target object
    var target = arguments[0] || {}, i = 1, length = arguments.length, deep = false, options;

    // Handle a deep copy situation
    if ( target.constructor == Boolean ) {
        deep = target;
        target = arguments[1] || {};
        // skip the boolean and the target
        i = 2;
    }

    // Handle case when target is a string or something (possible in deep copy)
    if ( typeof target != "object" && typeof target != "function" )
        target = {};

    // extend jQuery itself if only one argument is passed
    if ( length == i ) {
        target = this;
        --i;
    }

    for ( ; i < length; i++ )
        // Only deal with non-null/undefined values
        if ( (options = arguments[ i ]) != null )
            // Extend the base object
            for ( var name in options ) {
                var src = target[ name ], copy = options[ name ];

                // Prevent never-ending loop
                if ( target === copy )
                    continue;

                // Recurse if we're merging object values
                if ( deep && copy && typeof copy == "object" && !copy.nodeType )
                    target[ name ] = objectExtend( deep,
                        // Never move original objects, clone them
                        src || ( copy.length != null ? [ ] : { } )
                    , copy );

                // Don't bring in undefined values
                else if ( copy !== undefined )
                    target[ name ] = copy;

            }

    // Return the modified object
    return target;
};

/**
 * @brief 멀티미디어 출력용 (IE에서 플래쉬/동영상 주변에 점선 생김 방지용)
 **/
function displayMultimedia(src, width, height, options) {
    if(src.indexOf('files') == 0) src = request_uri + src;

    var defaults = {
        wmode : 'transparent',
        allowScriptAccess : 'sameDomain',
        quality : 'high',
        flashvars : ''
    };

    if(options) {
        var autostart = (options.autostart) ? 'true' : 'false';
        delete(options.autostart);
    }

    var params = objectExtend(defaults, options || {});

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
 * @brief 팝업의 경우 내용에 맞춰 현 윈도우의 크기를 조절해줌
 * 팝업의 내용에 맞게 크기를 늘리는 것은... 쉽게 되지는 않음.. ㅡ.ㅜ
 * popup_layout 에서 window.onload 시 자동 요청됨.
 **/
function setFixedPopupSize() {

    if(xGetElementById('popBody')) {
        if(xHeight('popBody')>500) {
            xGetElementById('popBody').style.overflowY = 'scroll';
            xGetElementById('popBody').style.overflowX = 'hidden';
            xHeight('popBody', 500);
        }
    }

    var w = xWidth("popup_content");
    var h = xHeight("popup_content");

    var obj_list = xGetElementsByTagName('div');
    for(i=0;i<obj_list.length;i++) {
        var ww = xWidth(obj_list[i]);
        var id = obj_list[i].id;
        if(id == 'waitingforserverresponse' || id == 'fororiginalimagearea' || id == 'fororiginalimageareabg') continue;
        if(ww>w) w = ww;
    }

    // 윈도우에서는 브라우저 상관없이 가로 픽셀이 조금 더 늘어나야 한다.
    if(xUA.indexOf('windows')>0) {
        if(xOp7Up) w += 10;
        else if(xIE4Up) w += 10;
        else w += 6;
    }
    window.resizeTo(w,h);

    var h1 = xHeight(window.document.body);
    window.resizeBy(0,h-h1);

    window.scrollTo(0,0);
}

/**
 * @brief 이름, 게시글등을 클릭하였을 경우 팝업 메뉴를 보여주는 함수
 **/
xAddEventListener(window, 'load', createPopupMenu);
xAddEventListener(document, 'click', chkPopupMenu);

var loaded_popup_menus = new Array();

/* 멤버 팝업 메뉴 레이어를 생성하는 함수 (문서 출력이 완료되었을때 동작) */
function createPopupMenu(evt) {
    var area = xGetElementById("popup_menu_area");
    if(area) return;
    area = xCreateElement("div");
    area.id = "popup_menu_area";
    area.style.visibility = 'hidden';
    area.style.zIndex = 9999;
    document.body.appendChild(area);
}

/* 클릭 이벤트 발생시 이벤트가 일어난 대상을 검사하여 적절한 규칙에 맞으면 처리 */
function chkPopupMenu(evt) {

    // 이전에 호출되었을지 모르는 팝업메뉴 숨김
    var area = xGetElementById("popup_menu_area");
    if(!area) return;

    if(area.style.visibility != "hidden") area.style.visibility = "hidden";

    // 이벤트 대상이 없으면 무시
    var e = new xEvent(evt);

    if(!e) return;

    // 대상의 객체 구함
    var obj = e.target;
    if(!obj) return;


    // obj의 nodeName이 div나 span이 아니면 나올대까지 상위를 찾음
    if(obj && obj.nodeName != 'DIV' && obj.nodeName != 'SPAN' && obj.nodeName != 'A') obj = obj.parentNode;
    if(!obj || (obj.nodeName != 'DIV' && obj.nodeName != 'SPAN' && obj.nodeName != 'A')) return;

    // 객체의 className값을 구함
    var class_name = obj.className;
    if(!class_name) return;
    // className을 분리
    var class_name_list = class_name.split(' ');

    var menu_id = '';
    var menu_id_regx = /^([a-zA-Z]+)_([0-9]+)$/;


    for(var i=0,c=class_name_list.length;i<c;i++) {
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
    params["page_x"] = e.pageX >0 ? e.pageX : GetObjLeft(obj);
    params["page_y"] = e.pageY >0 ? e.pageY : GetObjTop(obj)+ xHeight(obj);

    var response_tags = new Array("error","message","menus");

    if(loaded_popup_menus[menu_id]) {
        displayPopupMenu(params, response_tags, params);
        return;
    }
    show_waiting_message = false;
    exec_xml(module_name, action_name, params, displayPopupMenu, response_tags, params);
    show_waiting_message = true;

}

function GetObjTop(obj) {
    if(obj.offsetParent == document.body) return xOffsetTop(obj);
    else return xOffsetTop(obj) + GetObjTop(obj.offsetParent);
}
function GetObjLeft(obj) {
    if(obj.offsetParent == document.body) return xOffsetLeft(obj);
    else return xOffsetLeft(obj) + GetObjLeft(obj.offsetParent);
}

function displayPopupMenu(ret_obj, response_tags, params) {

    var target_srl = params["target_srl"];
    var menu_id = params["menu_id"];
    var menus = ret_obj['menus'];
    var html = "";

    if(loaded_popup_menus[menu_id]) {
        html = loaded_popup_menus[menu_id];

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
        loaded_popup_menus[menu_id] =  html;
    }

    // 레이어 출력
    if(html) {
        var area = xGetElementById("popup_menu_area");
        xInnerHtml(area, '<ul>'+html+'</ul>');
        xLeft(area, params["page_x"]);
        xTop(area, params["page_y"]);
        if(xWidth(area)+xLeft(area)>xClientWidth()+xScrollLeft()) xLeft(area, xClientWidth()-xWidth(area)+xScrollLeft());
        if(xHeight(area)+xTop(area)>xClientHeight()+xScrollTop()) xTop(area, xClientHeight()-xHeight(area)+xScrollTop());
        area.style.visibility = "visible";
    }
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

/* 언어코드 (lang_type) 쿠키값 변경 */
function doChangeLangType(obj) {
    if(typeof(obj)=="string") {
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

    var win = window.open("","previewDocument","toolbars=no,width=700px;height=800px,scrollbars=yes,resizable=yes");

    var dummy_obj = xGetElementById("previewDocument");

    if(!dummy_obj) {
        var fo_code = '<form id="previewDocument" target="previewDocument" method="post" action="'+request_uri+'">'+
                      '<input type="hidden" name="module" value="document" />'+
                      '<input type="hidden" name="act" value="dispDocumentPreview" />'+
                      '<input type="hidden" name="content" />'+
                      '</form>';
        var dummy = xCreateElement("DIV");
        xInnerHtml(dummy, fo_code);
        window.document.body.insertBefore(dummy,window.document.body.lastChild);
        dummy_obj = xGetElementById("previewDocument");
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
    xGetElementsByAttribute('input', 'name', 'document_srl')[0].value = ret_obj['document_srl'];
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

/* 체크박스 선택 */
function checkboxSelectAll(form, name, option){
    var value;
    var fo_obj = xGetElementById(form);
    for ( var i = 0 ; i < fo_obj.length ; i++ ){
        if(typeof(option) == "undefined") {
            var select_mode = fo_obj[i].checked;
            if ( select_mode == 0 ){
                value = true;
                select_mode = 1;
            }else{
                value = false;
                select_mode = 0;
            }
        }
        else if(option == true) value = true
        else if(option == false) value = false

        if(fo_obj[i].name == name) fo_obj[i].checked = value;
    }
}

/* 체크박스를 실행 */
function clickCheckBoxAll(form, name) {
    var fo_obj = xGetElementById(form);
    for ( var i = 0 ; i < fo_obj.length ; i++ ){
        if(fo_obj[i].name == name) fo_obj[i].click();
    }
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

/* select - option의 disabled=disabled 속성을 IE에서도 체크하기 위한 함수 */
if(xIE4Up) {
    xAddEventListener(window, 'load', activateOptionDisabled);

    function activateOptionDisabled(evt) {
        var sels = xGetElementsByTagName('select');
        for(var i=0; i < sels.length; i++){
            var disabled_exists = false;
            var first_enable = new Array();
            for(var j=0; j < sels[i].options.length; j++) {
                if(sels[i].options[j].disabled) {
                    sels[i].options[j].style.color = '#CCCCCC';
                    disabled_exists = true;
                }else{
                    first_enable[i] = first_enable[i]>-1? first_enable[i] : j;
                }
            }

            if(!disabled_exists) continue;

            sels[i].oldonchange = sels[i].onchange;
            sels[i].onchange = function() {
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
            }

            if(sels[i].selectedIndex >= 0 && sels[i].options[ sels[i].selectedIndex ].disabled) sels[i].onchange();

        }
    }
}


/* 보안 로그인 모드로 전환 */
function toggleSecuritySignIn() {
    var href = location.href;
    if(/https:\/\//i.test(href)) location.href = href.replace(/^https/i,'http');
    else location.href = href.replace(/^http/i,'https');
}

/* 하위호환성 문제 */
if(typeof(resizeImageContents) == 'undefined')
{
    function resizeImageContents() {}
}

function reloadDocument() {
    location.reload();
}



/* --------------------------
 * XE 2.0에서 제거될 함수
 * This feature has been DEPRECATED and REMOVED as of XE 2.0.
 * -------------------------- */
/**
 * @brief 에디터에서 사용되는 내용 여닫는 코드 (고정, zbxe용)
 * This feature has been DEPRECATED and REMOVED as of XE 2.0.
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
 * @brief 에디터에서 사용하되 내용 여닫는 코드 (zb5beta beta 호환용으로 남겨 놓음)
 * This feature has been DEPRECATED and REMOVED as of XE 2.0.
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
 * @breif replace outerHTML
 * This feature has been DEPRECATED and REMOVED as of XE 2.0.
 **/
function replaceOuterHTML(obj, html) {
    if(obj.outerHTML) {
        obj.outerHTML = html;
    } else {
        var dummy = xCreateElement("div");
        xInnerHtml(dummy, html);
        var parent = obj.parentNode;
        while(dummy.firstChild) {
            parent.insertBefore(dummy.firstChild, obj);
        }
        parent.removeChild(obj);
    }
}

/**
 * @breif get outerHTML
 * This feature has been DEPRECATED and REMOVED as of XE 2.0.
 **/
function getOuterHTML(obj) {
    if(obj.outerHTML) return obj.outerHTML;
    var dummy = xCreateElement("div");
    dummy.insertBefore(obj, dummy.lastChild);
    return xInnerHtml(dummy);
}

/**
 * @brief xSleep(micro time)
 * This feature has been DEPRECATED and REMOVED as of XE 2.0.
 **/
function xSleep(sec) {
    sec = sec / 1000;
    var now = new Date();
    var sleep = new Date();
    while( sleep.getTime() - now.getTime() < sec) {
        sleep = new Date();
    }
}