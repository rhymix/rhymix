/**
 * @file common.js
 * @author zero (zero@nzeo.com)
 * @brief 몇가지 유용한 & 기본적으로 자주 사용되는 자바스크립트 함수들 모음
 **/

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
        return uri+"?"+q_list.join("&");
    } else {
        if(val.toString().trim()) return uri+"?"+key+"="+val;
        else return uri;
    }
}

/**
 * @breif replace outerHTML
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
 **/
function getOuterHTML(obj) {
    if(obj.outerHTML) return obj.outerHTML;
    var dummy = xCreateElement("div");
    dummy.insertBefore(obj, dummy.lastChild);
    return xInnerHtml(dummy);
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
 * common/tpl/popup_layout.html이 요청되는 제로보드 XE내의 팝업일 경우에 사용
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
 * @brief 특정 div(or span...)의 display옵션 토글
 **/
function toggleDisplay(obj, opt) {
    obj = xGetElementById(obj);
    if(!obj) return;
    if(typeof(opt)=="undefined") opt = "inline";
    if(!obj.style.display || obj.style.display == "block") obj.style.display = 'none';
    else obj.style.display = opt;
}

/**
 * @brief 멀티미디어 출력용 (IE에서 플래쉬/동영상 주변에 점선 생김 방지용)
 **/
function displayMultimedia(src, width, height, auto_start, flashvars) {
    if(src.indexOf('files')==0) src = request_uri+src;
    if(auto_start) auto_start = "true";
    else auto_start = "false";

    var clsid = "";
    var codebase = "";
    var html = "";

    if(typeof(flashvars)=="undefined") flashvars = "";

    if(/\.swf/i.test(src)) {
        clsid = "clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"; 
        codebase = "http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=9,0,28,0";
        html = ""+
            "<object classid=\""+clsid+"\" codebase=\""+codebase+"\" width=\""+width+"\" height=\""+height+"\" flashvars=\""+flashvars+"\">"+
            "<param name=\"wmode\" value=\"transparent\" />"+
            "<param name=\"allowScriptAccess\" value=\"sameDomain\" />"+
            "<param name=\"movie\" value=\""+src+"\" />"+
            "<param name=\"quality\" value=\"high\" />"+
            "<param name=\"flashvars\" value=\""+flashvars+"\" />"+
            "<embed src=\""+src+"\" autostart=\""+auto_start+"\"  width=\""+width+"\" height=\""+height+"\" wmode=\"transparent\"></embed>"+
            "<\/object>";
    } else if(/\.flv/i.test(src)) {
        html = "<embed src=\""+request_uri+"common/tpl/images/flvplayer.swf\" allowfullscreen=\"true\" autostart=\""+auto_start+"\" width=\""+width+"\" height=\""+height+"\" flashvars=\"&file="+src+"&width="+width+"&height="+height+"&autostart="+auto_start+"\" />";
    } else {
        html = "<embed src=\""+src+"\" autostart=\""+auto_start+"\" width=\""+width+"\" height=\""+height+"\"></embed>";
    }
    document.writeln(html);
}

/**
 * @brief 화면내에서 상위 영역보다 이미지가 크면 리사이즈를 하고 클릭시 원본을 보여줄수 있도록 변경
 **/
function resizeImageContents() {
    // 일단 모든 이미지에 대한 체크를 시작
    var objs = xGetElementsByTagName("IMG");
    for(var i in objs) {
        var obj = objs[i];
        if(!obj.parentNode) continue;

        if(/\/modules\//i.test(obj.src)) continue;
        if(/\/layouts\//i.test(obj.src)) continue;
        if(/\/widgets\//i.test(obj.src)) continue;
        if(/\/classes\//i.test(obj.src)) continue;
        if(/\/common\/tpl\//i.test(obj.src)) continue;
        if(/\/member_extra_info\//i.test(obj.src)) continue;

        // 상위 node의 className이 document_ 또는 comment_ 로 시작하지 않으면 패스
        var parent = obj.parentNode;
        while(parent) {
            if(parent.className && parent.className.search(/xe_content|document_|comment_/i) != -1) break;
            parent = parent.parentNode;
        }
        if (!parent || parent.className.search(/xe_content|document_|comment_/i) < 0) continue;

        if(parent.parentNode) xWidth(parent, xWidth(parent.parentNode));
        parent.style.width = '100%';
        parent.style.overflow = 'hidden';


        var parent_width = xWidth(parent);
        if(parent.parentNode && xWidth(parent.parentNode)<parent_width) parent_width = xWidth(parent.parentNode);
        var obj_width = xWidth(obj);
        var obj_height = xHeight(obj);

        // 만약 선택된 이미지의 가로 크기가 부모의 가로크기보다 크면 리사이즈 (이때 부모의 가로크기 - 2 정도로 지정해줌)
        if(obj_width > parent_width - 2) {
            obj.style.cursor = "pointer";
            var new_w = parent_width - 2;
            var new_h = Math.round(obj_height * new_w/obj_width);
            xWidth(obj, new_w);
            xHeight(obj, new_h);
            xAddEventListener(obj,"click", showOriginalImage);
        // 선택된 이미지가 부모보다 작을 경우 일단 원본 이미지를 불러와서 비교
        } else {
            var orig_img = new Image();
            orig_img.src = obj.src;
            if(orig_img.width > parent_width - 2 || orig_img.width != obj_width) {
                obj.style.cursor = "pointer";
                xAddEventListener(obj,"click", showOriginalImage);
            }
        }
    }
}
xAddEventListener(window, "load", resizeImageContents);

/**
 * @brief 에디터에서 사용되는 내용 여닫는 코드 (고정, zbxe용)
 **/
function zbxe_folder_open(id) {
    var open_text_obj = xGetElementById("folder_open_"+id);
    var close_text_obj = xGetElementById("folder_close_"+id);
    var folder_obj = xGetElementById("folder_"+id);
    open_text_obj.style.display = "none";
    close_text_obj.style.display = "block";
    folder_obj.style.display = "block";
}

function zbxe_folder_close(id) {
    var open_text_obj = xGetElementById("folder_open_"+id);
    var close_text_obj = xGetElementById("folder_close_"+id);
    var folder_obj = xGetElementById("folder_"+id);
    open_text_obj.style.display = "block";
    close_text_obj.style.display = "none";
    folder_obj.style.display = "none";
}


/**
 * @brief 에디터에서 사용하되 내용 여닫는 코드 (zb5beta beta 호환용으로 남겨 놓음)
 **/
function svc_folder_open(id) {
    var open_text_obj = xGetElementById("_folder_open_"+id);
    var close_text_obj = xGetElementById("_folder_close_"+id);
    var folder_obj = xGetElementById("_folder_"+id);
    open_text_obj.style.display = "none";
    close_text_obj.style.display = "block";
    folder_obj.style.display = "block";
}

function svc_folder_close(id) {
    var open_text_obj = xGetElementById("_folder_open_"+id);
    var close_text_obj = xGetElementById("_folder_close_"+id);
    var folder_obj = xGetElementById("_folder_"+id);
    open_text_obj.style.display = "block";
    close_text_obj.style.display = "none";
    folder_obj.style.display = "none";
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
 * @brief 본문내에서 컨텐츠 영역보다 큰 이미지의 경우 원본 크기를 보여줌
 **/
function showOriginalImage(evt) {
    var e = new xEvent(evt);
    var obj = e.target;
    var src = obj.src;

    var orig_image = xGetElementById("fororiginalimage");
    var tmp_image = new Image();
    tmp_image.src = src;
    var image_width = tmp_image.width;
    var image_height = tmp_image.height;

    orig_image.style.margin = "0px 0px 0px 0px";
    orig_image.style.cursor = "move";
    orig_image.src = src;

    var areabg = xGetElementById("fororiginalimageareabg");
    xWidth(areabg, image_width+36);
    xHeight(areabg, image_height+46);

    var area = xGetElementById("fororiginalimagearea");
    xLeft(area, xScrollLeft());
    xTop(area, xScrollTop());
    xWidth(area, xWidth(document));
    xHeight(area, xHeight(document));
    area.style.visibility = "visible";
    var area_width = xWidth(area);
    var area_height = xHeight(area);

    var x = parseInt((area_width-image_width)/2,10);
    var y = parseInt((area_height-image_height)/2,10);
    if(x<0) x = 0;
    if(y<0) y = 0;
    xLeft(areabg, x);
    xTop(areabg, y);

    var sel_list = xGetElementsByTagName("select");
    for (var i = 0; i < sel_list.length; ++i) sel_list[i].style.visibility = "hidden";

    xAddEventListener(orig_image, "mousedown", origImageDragEnable);
    xAddEventListener(orig_image, "dblclick", closeOriginalImage);
    xAddEventListener(window, "scroll", closeOriginalImage);
    xAddEventListener(window, "resize", closeOriginalImage);
    xAddEventListener(document, 'keydown',closeOriginalImage);

    areabg.style.visibility = 'visible';
}

/**
 * @brief 원본 이미지 보여준 후 닫는 함수
 **/
function closeOriginalImage(evt) {
    var area = xGetElementById("fororiginalimagearea");
    if(area.style.visibility != "visible") return;
    area.style.visibility = "hidden";
    xGetElementById("fororiginalimageareabg").style.visibility = "hidden";

    var sel_list = xGetElementsByTagName("select");
    for (var i = 0; i < sel_list.length; ++i) sel_list[i].style.visibility = "visible";

    xRemoveEventListener(area, "mousedown", closeOriginalImage);
    xRemoveEventListener(window, "scroll", closeOriginalImage);
    xRemoveEventListener(window, "resize", closeOriginalImage);
    xRemoveEventListener(document, 'keydown',closeOriginalImage);
}

/**
 * @brief 원본 이미지 드래그
 **/
var origDragManager = {obj:null, isDrag:false}
function origImageDragEnable(evt) {
    var e = new xEvent(evt);
    var obj = e.target;
    if(obj.id != "fororiginalimage") return;

    obj.draggable = true;
    obj.startX = e.pageX;
    obj.startY = e.pageY;

    if(!origDragManager.isDrag) {
        origDragManager.isDrag = true;
        xAddEventListener(document, "mousemove", origImageDragMouseMove, false);
    }

    xAddEventListener(document, "mousedown", origImageDragMouseDown, false);
}

function origImageDrag(obj, px, py) {
    var x = px - obj.startX;
    var y = py - obj.startY;

    var areabg = xGetElementById("fororiginalimageareabg");
    xLeft(areabg, xLeft(areabg)+x);
    xTop(areabg, xTop(areabg)+y);

    obj.startX = px;
    obj.startY = py;
}

function origImageDragMouseDown(evt) {
    var e = new xEvent(evt);
    var obj = e.target;
    if(obj.id != "fororiginalimage" || !obj.draggable) return;

    if(obj) {
        xPreventDefault(evt);
        obj.startX = e.pageX;
        obj.startY = e.pageY;
        origDragManager.obj = obj;
        xAddEventListener(document, 'mouseup', origImageDragMouseUp, false);
        origImageDrag(obj, e.pageX, e.pageY);
    }
}

function origImageDragMouseUp(evt) {
    if(origDragManager.obj) {
        xPreventDefault(evt);
        xRemoveEventListener(document, 'mouseup', origImageDragMouseUp, false);
        xRemoveEventListener(document, 'mousemove', origImageDragMouseMove, false);
        xRemoveEventListener(document, 'mousemdown', origImageDragMouseDown, false);
        origDragManager.obj.draggable  = false;
        origDragManager.obj = null;
        origDragManager.isDrag = false;
    }
}

function origImageDragMouseMove(evt) {
    var e = new xEvent(evt);
    var obj = e.target;
    if(!obj) return;
    if(obj.id != "fororiginalimage") {
        xPreventDefault(evt);
        xRemoveEventListener(document, 'mouseup', origImageDragMouseUp, false);
        xRemoveEventListener(document, 'mousemove', origImageDragMouseMove, false);
        xRemoveEventListener(document, 'mousemdown', origImageDragMouseDown, false);
        origDragManager.obj.draggable  = false;
        origDragManager.obj = null;
        origDragManager.isDrag = false;
        return;
    }

    xPreventDefault(evt);
    origDragManager.obj = obj;
    xAddEventListener(document, 'mouseup', origImageDragMouseUp, false);
    origImageDrag(obj, e.pageX, e.pageY);
}

/**
 * @brief 이름, 게시글등을 클릭하였을 경우 팝업 메뉴를 보여주는 함수
 **/
xAddEventListener(document, 'click', chkPopupMenu);
var loaded_popup_menu_list = new Array();

/* 클릭 이벤트 발생시 이벤트가 일어난 대상을 검사하여 적절한 규칙에 맞으면 처리 */
function chkPopupMenu(evt) {
    // 이전에 호출되었을지 모르는 팝업메뉴 숨김
    var area = xGetElementById("popup_menu_area");
    if(!area) return;
    if(area.style.visibility!="hidden") area.style.visibility="hidden";

    // 이벤트 대상이 없으면 무시
    var e = new xEvent(evt);
    if(!e) return;

    // 대상의 객체 구함
    var obj = e.target;
    if(!obj) return;

    // obj의 nodeName이 div나 span이 아니면 나올대까지 상위를 찾음
    if(obj && obj.nodeName != 'DIV' && obj.nodeName != 'SPAN') {
        obj = obj.parentNode;
    }
    if(!obj || (obj.nodeName != 'DIV' && obj.nodeName != 'SPAN')) return;

    // 객체의 className값을 구함
    var class_name = obj.className;
    if(!class_name) return;

    // className을 분리
    var class_name_list = class_name.split(' ');
    var menu_id = '';
    var menu_id_regx = /^([a-zA-Z]+)_([0-9]+)$/ig;
    for(var i in class_name_list) {
        if(menu_id_regx.test(class_name_list[i])) {
            menu_id = class_name_list[i];
            break;
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
    params["page_x"] = e.pageX;
    params["page_y"] = e.pageY;

    var response_tags = new Array("error","message","menu_list");

    if(loaded_popup_menu_list[menu_id]) {
        params["menu_list"] = loaded_popup_menu_list[menu_id];
        displayPopupMenu(params, response_tags, params);
        return;
    }

    show_waiting_message = false;
    exec_xml(module_name, action_name, params, displayPopupMenu, response_tags, params);
    show_waiting_message = true;
}

function displayPopupMenu(ret_obj, response_tags, params) {
    var area = xGetElementById("popup_menu_area");

    var menu_list = ret_obj['menu_list'];

    var target_srl = params["target_srl"];
    var menu_id = params["menu_id"];

    var html = "";

    if(loaded_popup_menu_list[menu_id]) {
        html = loaded_popup_menu_list[menu_id];
    } else {
        var infos = menu_list.split("\n");
        if(infos.length) {
            for(var i=0;i<infos.length;i++) {
                var info_str = infos[i];
                var pos = info_str.indexOf(",");
                var icon = info_str.substr(0,pos).trim();

                info_str = info_str.substr(pos+1, info_str.length).trim();
                var pos = info_str.indexOf(",");
                var str = info_str.substr(0,pos).trim();
                var func = info_str.substr(pos+1, info_str.length).trim();

                var className = "item";

                if(!str || !func) continue;

                if(icon) html += "<div class=\""+className+"\" onmouseover=\"this.className='"+className+"_on'\" onmouseout=\"this.className='"+className+"'\" style=\"background:url("+icon+") no-repeat left center; padding-left:18px;\" onclick=\""+func+"\">"+str+"</div>";
                else html += "<div class=\""+className+"\" onmouseover=\"this.className='"+className+"_on'\" onmouseout=\"this.className='"+className+"'\" onclick=\""+func+"\">"+str+"</div>";
            }
        } 
        loaded_popup_menu_list[menu_id] =  html;
    }

    if(html) {
        // 레이어 출력
        xInnerHtml('popup_menu_area', "<div class=\"box\">"+html+"</div>");
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
    oFilter.proc();

    editorRelKeys[editor_sequence]['content'].value = prev_content;
    return false;
}

function completeDocumentSave(ret_obj) {
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
    opener.location.href = opener.current_url.setQuery('document_srl', document_srl);
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
function doAddDocumentCart(obj) {
    var srl = obj.value;
    var params = new Array();
    params["srl"] = srl;
    exec_xml("document","procDocumentAdminAddCart", params, null);
}

/* ff의 rgb(a,b,c)를 #... 로 변경 */
function transRGB2Hex(value) {
    if(!value) return value;
    if(value.indexOf('#')>-1) return value.replace(/^#/,'');

    if(value.toLowerCase().indexOf('rgb')<0) return value;
    value = value.replace(/^rgb\(/i,'').replace(/\)$/,'');
    value_list = value.split(',');

    var hex = '';
    for(var i=0;i<value_list.length;i++) {
        var color = parseInt(value_list[i],10).toString(16);
        hex += color;
    }
    return '#'+hex;
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
            for(var j=0; j < sels[i].options.length; j++) {
                if(sels[i].options[j].disabled) {
                    sels[i].options[j].style.color = '#CCCCCC';
                    disabled_exists = true;
                }
            }

            if(!disabled_exists) continue;
            
            sels[i].onchange = function() {  
                if(this.options[this.selectedIndex].disabled) {
                    if(this.options.length<=1) this.selectedIndex = -1;
                    else if(this.selectedIndex < this.options.length - 1) this.selectedIndex++;
                    else this.selectedIndex--;
                }
            }

            if(sels[i].selectedIndex >= 0 && sels[i].options[ sels[i].selectedIndex ].disabled) sels[i].onchange();

        }
    }
}
