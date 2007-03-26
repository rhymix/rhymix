/**
 * 몇가지 유용한 & 기본적으로 자주 사용되는 자바스크립트 함수들 모음
 **/

// string prototype으로 trim 함수 추가
String.prototype.trim = function() {
    return this.replace(/(^\s*)|(\s*$)/g, "");
}

// 주어진 인자가 하나라도 defined되어 있지 않으면 false return
function isDef() {
    for(var i=0; i<arguments.length; ++i) {
        if(typeof(arguments[i])=="undefined") return false;
    }
    return true;
}

// 윈도우 오픈
var winopen_list = new Array();
function winopen(url, target, attribute) {
    if(target != "_blank" && winopen_list[target]) {
        winopen_list[target].close();
        winopen_list[target] = null;
    }

    if(typeof(target)=='undefined') target = '_blank';
    if(typeof(attribute)=='undefined') attribute = '';
    var win = window.open(url, target, attribute);
    win.focus();
    if(target != "_blank") winopen_list[target] = win;
}

// 특정 div(or span...)의 display옵션 토글
function toggleDisplay(obj, opt) {
    obj = xGetElementById(obj);
    if(typeof(opt)=="undefined") opt = "inline";
    if(obj.style.display == "none") obj.style.display = opt;
    else obj.style.display = "none";
}

// 멀티미디어 출력용 (IE에서 플래쉬/동영상 주변에 점선 생김 방지용)
function displayMultimedia(src, width, height, auto_start) {
    var ext = src.split(".");
    var type = ext[ext.length-1];

    if(auto_start) auto_start = "true";
    else auto_start = "false";

    var clsid = "";
    var codebase = "";
    var html = "";
    switch(type) {
        case "flv" :
                html = "<embed src=\"./common/tpl/images/flvplayer.swf?autoStart="+auto_start+"&file="+src+"\" width=\""+width+"\" height=\""+height+"\" type=\"application/x-shockwave-flash\"></embed>";
            break;
        case "swf" :
                clsid = "clsid:D27CDB6E-AE6D-11cf-96B8-444553540000"; 
                codebase = "http://download.macromedia.com/pub/shockwave/cabs/flash/swflash.c-ab#version=6,0,29,0"; 
                html = ""+
                    "<object classid=\""+clsid+"\" codebase=\""+codebase+"\" width=\""+width+"\" hegiht=\""+height+"\" >"+
                    "<param name=\"movie\" value=\""+src+"\" />"+
                    "<param name=\"quality\" value=\"high\" />"+
                    "<embed src=\""+src+"\" autostart=\""+auto_start+"\" "+style+"></embed>"+
                    "<\/object>";
            break;
        default : 
                html = ""+
                    "<embed src=\""+src+"\" autostart=\""+auto_start+"\" width=\""+width+"\" hegiht=\""+height+"\"></embed>";
            break;
    }

    document.writeln(html);
}

// 화면내에서 이미지 리사이즈 및 클릭할 수 있도록 
function resizeImageContents() {
    var objs = xGetElementsByTagName("img");
    for(var i in objs) {
        var obj = objs[i];
        var parent = xParent(obj);
        if(!obj||!parent) continue;

        var parent_width = xWidth(parent);
        var obj_width = xWidth(obj);
        if(parent_width>=obj_width) continue;

        obj.style.cursor = "pointer";
        obj.source_width = obj_width;
        obj.source_height = xHeight(obj);
        xWidth(obj, xWidth(parent)-1);

        xAddEventListener(obj,"click", resizeImagePopup);
    }
}
xAddEventListener(window, "load", resizeImageContents);

// 컨텐츠에서 컨텐츠 영역보다 큰 이미지 리사이징후 팝업 클릭시 사용되는 함수
function resizeImagePopup(evt) {
    var e = new xEvent(evt);
    if(!e.target.src) return;
    var obj = e.target;
    var scrollbars = "no";
    var resizable = "no";

    var width = obj.source_width;
    if(width>screen.availWidth) {
        width = screen.availWidth-50;
        scrollbars = "yes";
        resizable = "yes";
    }
    var height = obj.source_height;
    if(height>screen.availHeight) {
        height = screen.availHeight-50;
        scrollbars = "yes";
        resizable = "yes";
    }
    var popup = window.open(e.target.src,"_imagePopup","width="+width+",height="+height+",top=1,left=1,resizable="+resizable+",toolbars=no,scrollbars="+resizable);
    if(popup) popup.focus();
}

// 에디터에서 사용하는 내용 여닫는 코드 (고정, zbxe용)
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


// 에디터에서 사용하는 내용 여닫는 코드 (고정, zb5 beta 호환용)
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

// 팝업의 경우 내용에 맞춰 현 윈도우의 크기를 조절해줌 
// 팝업의 내용에 맞게 크기를 늘리는 것은... 쉽게 되지는 않음.. ㅡ.ㅜ
// 혹시.. 제대로 된 소스 있으신 분은 헬프미.. ㅠ0ㅠ
function setFixedPopupSize() {
    var w = xWidth("popup_content");
    var h = xHeight("popup_content");

    // 윈도우에서는 브라우저 상관없이 가로 픽셀이 조금 더 늘어나야 한다.
    if(xUA.indexOf('windows')>0) {
        if(xOp7Up) w += 10;
        else if(xIE4Up) w += 10;
        else w += 6;
    }
    window.resizeTo(w,h);

    var w1 = xWidth(window.document.body);
    var h1 = xHeight(window.document.body);
    window.resizeBy(0,h-h1);
}

// url이동 (open_window 값이 N 가 아니면 새창으로 띄움)
function move_url(url, open_wnidow) {
    if(!url) return false;
    if(typeof(open_wnidow)=='undefined') open_wnidow = 'N';
    if(open_wnidow=='Y') {
        var win = window.open(url);
        win.focus();
    } else {
        location.href=url;
    }
    return false;
}

// 본문내에서 컨텐츠 영역보다 큰 이미지의 경우 원본 크기를 보여줌
function showOriginalImage(evt) {
    var e = new xEvent(evt);
    var obj = e.target;
    var src = obj.src;

    var orig_image = xGetElementById("fororiginalimage");

    orig_image.src = src;

    var area = xGetElementById("fororiginalimagearea");

    xLeft(area, xScrollLeft());
    xTop(area, xScrollTop());
    xWidth(area, xWidth(document));
    xHeight(area, xHeight(document));
    area.style.visibility = "visible";

    var area_width = xWidth(area);
    var area_height = xHeight(area);
    var image_width = orig_image.width;
    var image_height = orig_image.height;

    var x = parseInt((area_width-image_width)/2,10);
    var y = parseInt((area_height-image_height)/2,10);
    if(x<0) x = 0;
    if(y<0) y = 0;

    orig_image.style.position = "absolute";
    orig_image.style.left = "0px";
    orig_image.style.top = "0px";
    orig_image.style.margin = "0px 0px 0px 0px";
    orig_image.style.cursor = "pointer";
    xLeft(orig_image, x);
    xTop(orig_image, y);

    xAddEventListener(orig_image, "mousedown", origImageDragEnable);
    xAddEventListener(window, "scroll", closeOriginalImage);
    xAddEventListener(window, "resize", closeOriginalImage);
}

// 원본 이미지 보여준 후 닫는 함수
function closeOriginalImage(evt) {
    var area = xGetElementById("fororiginalimagearea");
    if(area.style.visibility != "visible") return;
    area.style.visibility = "hidden";

    xRemoveEventListener(area, "mousedown", closeOriginalImage);
    xRemoveEventListener(window, "scroll", closeOriginalImage);
    xRemoveEventListener(window, "resize", closeOriginalImage);
}

// 원본 이미지 드래그
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

    xLeft(obj, xLeft(obj)+x);
    xTop(obj, xTop(obj)+y);

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
