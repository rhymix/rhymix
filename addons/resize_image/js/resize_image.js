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
 * @brief 본문내에서 컨텐츠 영역보다 큰 이미지의 경우 원본 크기를 보여줌
 **/
function showOriginalImage(evt) {
    var e = new xEvent(evt);
    var obj = e.target;
    var src = obj.src;

    if(!xGetElementById("forOriginalImageArea")) {
        var dummy = xCreateElement("div");
        dummy.id = "forOriginalImageArea";
        dummy.style.visibility = "hidden";
        xInnerHtml(dummy, "<div id=\"forOriginalImageAreaBackground\"><img src=\""+request_uri+"common/tpl/images/blank.gif\" alt=\"original image\" border=\"0\" id=\"fororiginalimage\" /></div>");
        document.body.appendChild(dummy);
    }

    var orig_image = xGetElementById("fororiginalimage");
    var tmp_image = new Image();
    tmp_image.src = src;
    var image_width = tmp_image.width;
    var image_height = tmp_image.height;

    orig_image.style.margin = "0px 0px 0px 0px";
    orig_image.style.cursor = "move";
    orig_image.src = src;

    var areabg = xGetElementById("forOriginalImageAreaBackground");
    xWidth(areabg, image_width+16);
    xHeight(areabg, image_height+16);

    var area = xGetElementById("forOriginalImageArea");
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
    var area = xGetElementById("forOriginalImageArea");
    if(area.style.visibility != "visible") return;
    area.style.visibility = "hidden";
    xGetElementById("forOriginalImageAreaBackground").style.visibility = "hidden";

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

    var areabg = xGetElementById("forOriginalImageAreaBackground");
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

