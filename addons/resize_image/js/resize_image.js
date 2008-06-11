/**
 * @brief 화면내에서 상위 영역보다 이미지가 크면 리사이즈를 하고 클릭시 원본을 보여줄수 있도록 변경
 **/
var imageGalleryIndex = new Array();
function resizeImageContents() {
    // 이미지 태그 정규 표현식
    var img_regx = new RegExp("<img","im");
    var site_regx = new RegExp("^"+request_uri,"im");
    
    // xe_content 내의 이미지 요소들에 대한 체크
    var xe_objs = xGetElementsByClassName("xe_content");
    for(var j=0;j<xe_objs.length;j++) {

        imageGalleryIndex[j] = new Array();

        var html = xInnerHtml(xe_objs[j]);
        if(!img_regx.test(html)) continue;

        // 모든 이미지에 대한 체크를 시작
        var objs = xGetElementsByTagName("IMG", xe_objs[j]);

        for(var i=0;i<objs.length;i++) {
            var obj = objs[i];

            // zbXE내부 프로그램 또는 스킨의 이미지라면 이미지 리사이즈를 하지 않음
            if(!/\/(modules|addons|classes|common|layouts|libs|widgets)\//i.test(obj.src)) {
                var parent = obj.parentNode;
                while(parent) {
                    if(/(document|comment)_([0-9]+)_([0-9]+)/i.test(parent.className) ) break;
                    parent = parent.parentNode;
                }

                var dummy = xCreateElement("div");
                dummy.style.visibility = "hidden";
                dummy.style.border = "1px solid red";
                parent.parentNode.insertBefore(dummy, parent);

                var parent_width = xWidth(dummy);
                parent.parentNode.removeChild(dummy);
                dummy = null;

                var obj_width = xWidth(obj);
                var obj_height = xHeight(obj);

                // 만약 선택된 이미지의 가로 크기가 부모의 가로크기보다 크면 리사이즈 (이때 부모의 가로크기 - 2 정도로 지정해줌)
                if(obj_width > parent_width - 2) {
                    obj.style.cursor = "pointer";
                    var new_w = parent_width - 2;
                    var new_h = Math.round(obj_height * new_w/obj_width);
                    xWidth(obj, new_w);
                    xHeight(obj, new_h);
                } 

                obj.style.cursor = "pointer";

                // 만약 대상 이미지에 링크가 설정되어 있거나 onclick 이벤트가 부여되어 있으면 원본 보기를 하지 않음
                if(obj.parentNode.nodeName.toLowerCase()!='a' && !obj.getAttribute('onclick')) xAddEventListener(obj,"click", showOriginalImage);

                imageGalleryIndex[j][i] = obj.src;
                obj.setAttribute("rel", j+','+i);
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
    var rel = obj.getAttribute('rel');
    displayOriginalImage(src, rel);
}

function displayOriginalImage(src, rel) {
    // 투명 배경을 지정
    var bgObj = xGetElementById("forOriginalImageBGArea");
    if(!bgObj) {
        bgObj = xCreateElement("div");
        bgObj.id = "forOriginalImageBGArea";
        bgObj.style.visibility = "hidden";
        bgObj.style.backgroundColor = "#000000";
        bgObj.style.zIndex = 500;
        bgObj.style.position = "absolute";
        document.body.appendChild(bgObj);
    }
    xWidth(bgObj, xClientWidth());
    xHeight(bgObj, xClientHeight());
    xLeft(bgObj, xScrollLeft());
    xTop(bgObj, xScrollTop());
    bgObj.style.opacity = .5;
    bgObj.style.filter = "alpha(opacity=50);";
    bgObj.style.visibility = "visible";

    // 원본 이미지 노출을 위한 준비
    var foreObj = xGetElementById("forOriginalImageArea");
    if(!foreObj) {
        foreObj = xCreateElement("div");
        foreObj.id = "forOriginalImageArea";
        foreObj.style.visibility = "hidden";
        foreObj.style.overflow = "hidden";
        foreObj.style.position = "absolute";
        foreObj.style.zIndex = 510;
        document.body.appendChild(foreObj);
    }
    xWidth(foreObj, xClientWidth());
    xHeight(foreObj, xClientHeight());
    xLeft(foreObj, xScrollLeft());
    xTop(foreObj, xScrollTop());
    foreObj.style.visibility = "visible";

    var foreWidth = xWidth(foreObj);
    var foreHeight = xHeight(foreObj);

    // 버튼
    var iconClose = xGetElementById("forOriginalImageIconClose");
    if(!iconClose) {
        iconClose = xCreateElement("img");
        iconClose.id = "forOriginalImageIconClose";
        iconClose.style.position = "absolute";
        iconClose.src = request_uri+"addons/resize_image/iconClose.png";
        iconClose.style.width = iconClose.style.height = "60px";
        iconClose.className = 'iePngFix';
        iconClose.style.zIndex = 530;
        iconClose.style.cursor = "pointer";
        foreObj.appendChild(iconClose);
    }
    iconClose.style.visibility = 'visible';
    xLeft(iconClose, (foreWidth-60)/2);
    xTop(iconClose, 10);

    var iconLeft = xGetElementById("forOriginalImageIconLeft");
    if(!iconLeft) {
        iconLeft = xCreateElement("img");
        iconLeft.id = "forOriginalImageIconLeft";
        iconLeft.style.position = "absolute";
        iconLeft.src = request_uri+"addons/resize_image/iconLeft.png";
        iconLeft.style.width = iconLeft.style.height = "60px";
        iconLeft.style.zIndex = 530;
        iconLeft.className = 'iePngFix';
        iconLeft.style.cursor = "pointer";
        foreObj.appendChild(iconLeft);
    }
    iconLeft.onclick = null;
    xLeft(iconLeft, 10);
    xTop(iconLeft, (foreHeight-60)/2);
    iconLeft.style.visibility = 'hidden';

    var iconRight = xGetElementById("forOriginalImageIconRight");
    if(!iconRight) {
        iconRight = xCreateElement("img");
        iconRight.id = "forOriginalImageIconRight";
        iconRight.style.position = "absolute";
        iconRight.src = request_uri+"addons/resize_image/iconRight.png";
        iconRight.style.width = iconRight.style.height = "60px";
        iconRight.className = 'iePngFix';
        iconRight.style.zIndex = 530;
        iconRight.style.cursor = "pointer";
        foreObj.appendChild(iconRight);
    }
    iconRight.onclick = null;
    xLeft(iconRight, foreWidth - 10 - 60);
    xTop(iconRight, (foreHeight-60)/2);
    iconRight.style.visibility = 'hidden';


    if(rel) {
        var tmp = rel.split(',');
        var j = parseInt(tmp[0],10);
        var i = parseInt(tmp[1],10);
        var length = imageGalleryIndex[j].length;

        if(length>1) {

            var prev = i-1;
            var next = i+1;
            if(prev>=0) {
                iconLeft.style.visibility = 'visible';
                iconLeft.onclick = function() { displayOriginalImage(imageGalleryIndex[j][prev], j+','+prev); }
            } else {
                iconLeft.style.visibility = 'hidden';
            }

            if(next<length) {
                iconRight.style.visibility = 'visible';
                iconRight.onclick = function() { displayOriginalImage(imageGalleryIndex[j][next], j+','+next); }
            } else {
                iconRight.style.visibility = 'hidden';
            }

        }

    }

    // 원본 이미지를 추가
    var origObj = xGetElementById("forOriginalImage");
    if(origObj) foreObj.removeChild(origObj);

    origObj = null;
    origObj = xCreateElement("img");
    origObj.id = "forOriginalImage";
    origObj.style.border = "7px solid #ffffff";
    origObj.style.visibility = "hidden";
    origObj.style.cursor = "move";
    origObj.style.zIndex = 520;
    foreObj.appendChild(origObj);

    origObj.style.position = "relative";
    origObj.src = src;

    var objWidth = xWidth(origObj);
    var objHeight = xHeight(origObj);

    var posX = 0;
    var posY = 0;

    if(objWidth < foreWidth) posX = parseInt( (foreWidth - objWidth) / 2, 10);
    if(objHeight < foreHeight) posY = parseInt( (foreHeight - objHeight) / 2, 10);

    xLeft(origObj, posX);
    xTop(origObj, posY);

    origObj.style.visibility = "visible";

    var sel_list = xGetElementsByTagName("select");
    for (var i = 0; i < sel_list.length; ++i) sel_list[i].style.visibility = "hidden";

    xAddEventListener(origObj, "mousedown", origImageDragEnable);
    xAddEventListener(origObj, "dblclick", closeOriginalImage);
    xAddEventListener(iconClose, "mousedown", closeOriginalImage);
    xAddEventListener(window, "scroll", closeOriginalImage);
    xAddEventListener(window, "resize", closeOriginalImage);
    xAddEventListener(document, 'keydown',closeOriginalImage);
}

/**
 * @brief 원본 이미지 보여준 후 닫는 함수
 **/
function closeOriginalImage(evt) {
    var bgObj = xGetElementById("forOriginalImageBGArea");
    var foreObj = xGetElementById("forOriginalImageArea");
    var origObj = xGetElementById("forOriginalImage");
    var iconClose = xGetElementById("forOriginalImageIconClose");
    var iconLeft = xGetElementById("forOriginalImageIconLeft");
    var iconRight = xGetElementById("forOriginalImageIconRight");

    var sel_list = xGetElementsByTagName("select");
    for (var i = 0; i < sel_list.length; ++i) sel_list[i].style.visibility = "visible";

    xRemoveEventListener(origObj, "mousedown", origImageDragEnable);
    xRemoveEventListener(origObj, "dblclick", closeOriginalImage);
    xRemoveEventListener(iconClose, "mousedown", closeOriginalImage);
    xRemoveEventListener(window, "scroll", closeOriginalImage);
    xRemoveEventListener(window, "resize", closeOriginalImage);
    xRemoveEventListener(document, 'keydown',closeOriginalImage);

    bgObj.style.visibility = "hidden";
    foreObj.style.visibility = "hidden";
    origObj.style.visibility = "hidden";
    iconClose.style.visibility = 'hidden';
    iconLeft.style.visibility = 'hidden';
    iconRight.style.visibility = 'hidden';
}

/**
 * @brief 원본 이미지 드래그
 **/
var origDragManager = {obj:null, isDrag:false}
function origImageDragEnable(evt) {
    var e = new xEvent(evt);
    var obj = e.target;
    if(obj.id != "forOriginalImage") return;

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

    var origObj = xGetElementById("forOriginalImage");
    xLeft(origObj, xLeft(origObj)+x);
    xTop(origObj, xTop(origObj)+y);

    obj.startX = px;
    obj.startY = py;
}

function origImageDragMouseDown(evt) {
    var e = new xEvent(evt);
    var obj = e.target;
    if(obj.id != "forOriginalImage" || !obj.draggable) return;

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
    if(obj.id != "forOriginalImage") {
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

