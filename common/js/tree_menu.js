/**
 * @file tree_menu.js
 * @author zero (zero@nzeo.com)
 * @brief xml파일을 읽어서 트리 메뉴를 그려줌
 *
 * 일단 이것 저것 꽁수가 좀 들어간 것이긴 한데 속도나 기타 면에서 쓸만함...\n
 * 다만 제로보드에 좀 특화되어 있어서....\n
 * GPL License 를 따릅니당~~~\n
 * 언제나 그렇듯 필요하신 분은 가져가서 쓰세요.\n
 * 더 좋게 개량하시면 공유해주세요~\n
 **/

// 트리메뉴에서 사용될 아이콘의 위치
var tree_menu_icon_path = "./common/tpl/images/";

// 아이콘을 미리 생성해 놓음
var tree_folder_icon = new Image();
tree_folder_icon.src = tree_menu_icon_path+"page.gif";
var tree_open_folder_icon = new Image();
tree_open_folder_icon.src = tree_menu_icon_path+"page.gif";

var tree_minus_icon = new Image();
tree_minus_icon.src = tree_menu_icon_path+"minus.gif";
var tree_minus_bottom_icon = new Image();
tree_minus_bottom_icon.src = tree_menu_icon_path+"minusbottom.gif";
var tree_plus_icon = new Image();
tree_plus_icon.src = tree_menu_icon_path+"plus.gif";
var tree_plus_bottom_icon = new Image();
tree_plus_bottom_icon.src = tree_menu_icon_path+"plusbottom.gif";

// 폴더를 모두 열고/닫기 위한 변수 설정
var tree_menu_folder_list = new Array();

// 노드의 정보를 가지고 있을 변수
var node_info_list = new Array();

// menu_id별로 요청된 클릭시 실행 될 callback_func
var node_callback_func = new Array();

// menu_id별로 요청된 드래그시 실행될 callback_func
var node_move_callback_func = new Array();

// 트리메뉴의 정보를 담고 있는 xml파일을 읽고 drawTreeMenu()를 호출하는 함수
function loadTreeMenu(url, menu_id, zone_id, title, callback_func, manual_select_node_srl, callback_move_func) {
    // 일단 그릴 곳을 찾아서 사전 작업을 함 (그릴 곳이 없다면 아예 시도를 안함)
    var zone = xGetElementById(zone_id);
    if(typeof(zone)=="undefined") return;

    // 관리가 아닌 사용일경우는 menu_id를 변경
    if(typeof(callback_func)=='undefined') menu_id = 'display_'+menu_id;

    // 노드 정보들을 담을 변수 세팅
    node_info_list[menu_id] = new Array();

    if(typeof(title)=='undefined') title = '';

    // xml_handler를 이용해서 직접 메뉴 xml파일(layout module에서 생성)을 읽음
    var oXml = new xml_handler();
    oXml.reset();
    oXml.xml_path = url;

    // 사용자 정의 함수가 없다면 moveTreeMenu()라는 기본적인 동작을 하는 함수를 대입
    if(typeof(callback_func)=='undefined') {
        callback_func = moveTreeMenu;
    }

    if(typeof(callback_move_func)=='undefined') {
        callback_move_func = null;
    }

    // 한 페이지에 다수의 menu_id가 있을 수 있으므로 menu_id별로 함수를 저장
    node_callback_func[menu_id] = callback_func;
    node_move_callback_func[menu_id] = callback_move_func;

    // 직접 선택시키려는 메뉴 인자값이 없으면 초기화
    if(typeof(manual_select_node_srl)=='undefined') manual_select_node_srl = '';

    // menu_id, zone_id는 계속 달고 다녀야함 
    var param = {menu_id:menu_id, zone_id:zone_id, title:title, manual_select_node_srl:manual_select_node_srl}

    // 요청후 drawTreeMenu()함수를 호출 (xml_handler.js에서 request method를 직접 이용)
    oXml.request(drawTreeMenu, oXml, null, null, null, param);
}

// 트리메뉴 XML정보를 이용해서 정해진 zone에 출력
var manual_select_node_srl = '';
function drawTreeMenu(oXml, callback_func, resopnse_tags, null_func, param) {
    var xmlDoc = oXml.getResponseXml();
    if(!xmlDoc) return null;

    // 그리기 위한 object를 찾아 놓음
    var menu_id = param.menu_id;
    var zone_id = param.zone_id;
    var title = param.title;
    if(param.manual_select_node_srl) manual_select_node_srl = param.manual_select_node_srl;
    var zone = xGetElementById(zone_id);
    var html = "";

    if(title) html = '<div style="padding-left:18px;margin-bottom:5px;background:url('+tree_menu_icon_path+'folder.gif) no-repeat left;">'+title+'</div>';

    tree_menu_folder_list[menu_id] = new Array();

    // node 태그에 해당하는 값들을 가져와서 html을 작성
    var node_list = xmlDoc.getElementsByTagName("node");
    if(node_list.length>0) {
        var root = xmlDoc.getElementsByTagName("root")[0];
        var output = drawNode(root, menu_id);
        html += output.html;
    }

    // 출력하려는 zone이 없다면 load후에 출력하도록 함
    if(!zone) {
        xAddEventListener(window, 'load', function() { drawTeeMenu(zone_id, menu_id, html); });

    // 출력하려는 zone을 찾아졌다면 바로 출력
    } else {
        xInnerHtml(zone, html);
        if(manual_select_node_srl) manualSelectNode(menu_id, manual_select_node_srl);
    }

    return null;
}

// 페이지 랜더링 중에 메뉴의 html이 완성되었을때 window.onload event 후에 그리기 재시도를 하게 될 함수
function drawTeeMenu(zone_id, menu_id, html) {
    xInnerHtml(zone_id, html);
    if(manual_select_node_srl) manualSelectNode(menu_id, manual_select_node_srl);
}

// root부터 시작해서 recursive하게 노드를 표혐
function drawNode(parent_node, menu_id) {
    var output = {html:"", expand:"N"}

    for (var i=0; i< parent_node.childNodes.length; i++) {
        var html = "";

        // nodeName이 node가 아니면 패스~
        var node = parent_node.childNodes.item(i);
        if(node.nodeName!="node") continue;

        // node의 기본 변수들 체크 
        var node_srl = node.getAttribute("node_srl");
        var text = node.getAttribute("text");
        var url = node.getAttribute("url");
        var expand = node.getAttribute("expand");
        
        if(!text) continue;

        // 자식 노드가 있는지 확인
        var hasChild = false;
        if(node.hasChildNodes()) hasChild = true;

        // nextSibling가 있는지 확인 
        var hasNextSibling = false;
        if(i==parent_node.childNodes.length-1) hasNextSibling = true;

        // 후에 사용하기 위해 node_info_list에 node_srl을 값으로 하여 node object 추가
        node_info_list[menu_id][node_srl] = node;

        // zone_id 값을 세팅
        var zone_id = "menu_"+menu_id+"_"+node_srl;
        tree_menu_folder_list[menu_id][tree_menu_folder_list[menu_id].length] = zone_id;

        // url을 확인하여 현재의 url과 동일하다고 판단되면 manual_select_node_srl 에 값을 추가 (관리자페이지일 경우는 무시함)
        if(node_callback_func[menu_id] == moveTreeMenu && url && typeof(zbxe_url)!="undefined" && zbxe_url == url) manual_select_node_srl = node_srl;

        // manual_select_node_srl이 node_srl과 같으면 펼침으로 처리
        if(manual_select_node_srl == node_srl) expand = "Y";

        // 아이콘 설정
        var line_icon = null;
        var folder_icon = null;

        // 자식 노드가 있을 경우 자식 노드의 html을 구해옴
        var child_output = null;
        var child_html = "";
        if(hasChild) {
            // 자식 노드의 zone id를 세팅
            var child_zone_id = zone_id+"_child";
            tree_menu_folder_list[menu_id][tree_menu_folder_list[menu_id].length] = child_zone_id;
            
            // html을 받아옴 
            child_output = drawNode(node, menu_id);
            var chtml = child_output.html;
            var cexpand = child_output.expand;
            if(cexpand == "Y") expand = "Y";

            // 무조건 펼침이 아닐 경우
            if(expand!="Y") {
                if(!hasNextSibling) child_html += '<div id="'+child_zone_id+'"style="display:none;padding-left:16px;background:url('+tree_menu_icon_path+'line.gif) repeat-y left;">'+chtml+'</div>';
                else child_html += '<div id="'+child_zone_id+'" style="display:none;padding-left:16px;">'+chtml+'</div>';
            // 무조건 펼침일 경우
            } else {
                if(!hasNextSibling) child_html += '<div id="'+child_zone_id+'"style="display:block;padding-left:16px;background:url('+tree_menu_icon_path+'line.gif) repeat-y left;">'+chtml+'</div>';
                else child_html += '<div id="'+child_zone_id+'" style="display:block;padding-left:16px;">'+chtml+'</div>';
            }
        }

        // 자식 노드가 있는지 확인하여 있으면 아이콘을 바꿈
        if(hasChild) {
            // 무조건 펼침이 아닐 경우
            if(expand != "Y") {
                if(!hasNextSibling) {
                    line_icon = "minus";
                    folder_icon = "page";
                } else {
                    line_icon = "minusbottom";
                    folder_icon = "page";
                }
            // 무조건 펼침일 경우 
            } else {
                if(!hasNextSibling) {
                    line_icon = "plus";
                    folder_icon = "page";
                } else {
                    line_icon = "plusbottom";
                    folder_icon = "page";
                }
            }

        // 자식 노드가 없을 경우
        } else {
            if(hasNextSibling) {
                line_icon = "joinbottom";
                folder_icon = "page";
            } else {
                line_icon = "join";
                folder_icon = "page";
            }
        }


        // html 작성
        html += '<div id="'+zone_id+'" style="margin:0px;font-size:9pt;">';

        if(hasChild) html+= '<span style="cursor:pointer;" onclick="toggleFolder(\''+zone_id+'\');return false;">';
        else html+= '<span>';

        html += '<img id="'+zone_id+'_line_icon" src="'+tree_menu_icon_path+line_icon+'.gif" alt="line" align="top" /><img id="'+zone_id+'_folder_icon" src="'+tree_menu_icon_path+folder_icon+'.gif" alt="folder" align="top" /></span>';

        var chk_enable = xGetElementById(menu_id+"_enable_move");
        if(chk_enable) {
            html += '<span><span id="'+zone_id+'_node" style="cursor:move;padding:1px 2px 1px 2px;margin-top:1px;cursor:pointer;" onmousedown="doNodeFunc(this, \''+menu_id+'\','+node_srl+',\''+zone_id+'\');">';
        } else {
            html += '<span><span id="'+zone_id+'_node" style="cursor:move;padding:1px 2px 1px 2px;margin-top:1px;cursor:pointer;" onclick="selectNode(\''+menu_id+'\','+node_srl+',\''+zone_id+'\')" ondblclick="toggleFolder(\''+zone_id+'\')">';
        }

        html += text+'</span></span>';

        html += child_html;

        html += '</div>';

        output.html += html;

        if(expand=="Y") output.expand = "Y";
    }
    return output;
}

// 관리자 모드일 경우 *_enable_move 의 값에 따라 메뉴 이동을 시키거나 정보를 보여주도록 변경
function doNodeFunc(obj, menu_id, node_srl, zone_id) {
    var chk_enable = xGetElementById(menu_id+"_enable_move");
    if(!chk_enable || chk_enable.checked!=true || !obj) {
        selectNode(menu_id,node_srl,zone_id);
        return;
    }

    deSelectNode();
    tree_drag_enable(obj,tree_drag_start,tree_drag,tree_drag_end);
}

// 수동으로 메뉴를 선택하도록 함
function manualSelectNode(menu_id, node_srl) {
    var zone_id = "menu_"+menu_id+"_"+node_srl;
    selectNode(menu_id,node_srl,zone_id,false);
    return;
}

// 노드의 폴더 아이콘 클릭시
function toggleFolder(zone_id) {
    // 아이콘을 클릭한 대상을 찾아봄
    var child_zone = xGetElementById(zone_id+"_child");
    if(!child_zone) return;

    // 대상의 아이콘들 찾음 
    var line_icon = xGetElementById(zone_id+'_line_icon');
    var folder_icon = xGetElementById(zone_id+'_folder_icon');

    // 대상의 자식 노드들이 숨겨져 있다면 열고 아니면 닫기
    if(child_zone.style.display == "block") {
        child_zone.style.display = "none";
        if(line_icon.src.indexOf('bottom')>0) line_icon.src = tree_minus_bottom_icon.src;
        else line_icon.src = tree_minus_icon.src;

        folder_icon.src = tree_folder_icon.src;
    } else {
        if(line_icon.src.indexOf('bottom')>0) line_icon.src = tree_plus_bottom_icon.src;
        else line_icon.src = tree_plus_icon.src;
        folder_icon.src = tree_open_folder_icon.src;
        child_zone.style.display = "block";
    }
}

// 노드의 글자 선택시
var prev_selected_node = null;
function selectNode(menu_id, node_srl, zone_id, move_url) {
    // 선택된 노드를 찾아봄
    var node_zone = xGetElementById(zone_id+'_node');
    if(!node_zone) return;

    // 이전에 선택된 노드가 있었다면 원래데로 돌림
    if(prev_selected_node) {
        var prev_zone = xGetElementById(prev_selected_node.id);
        prev_zone.style.backgroundColor = "#ffffff";
        prev_zone.style.fontWeight = "normal";
        prev_zone.style.color = "#000000";
    }

    // 선택된 노드의 글자를 변경
    prev_selected_node = node_zone;
    node_zone.style.backgroundColor = "#0e078f";
    node_zone.style.fontWeight = "bold";
    node_zone.style.color = "#FFFFFF";

    // 함수 실행
    if(typeof(move_url)=="undefined"||move_url==true) {
        var func = node_callback_func[menu_id];
        func(menu_id, node_info_list[menu_id][node_srl]);
        toggleFolder(zone_id);
    }
}

// 선택된 노드의 표시를 없앰
function deSelectNode() {
    // 이전에 선택된 노드가 있었다면 원래데로 돌림
    if(!prev_selected_node) return;
    prev_selected_node.style.backgroundColor = "#ffffff";
    prev_selected_node.style.fontWeight = "normal";
    prev_selected_node.style.color = "#000000";
}


// 모두 닫기
function closeAllTreeMenu(menu_id) {
    for(var i in tree_menu_folder_list[menu_id]) {
        var zone_id = tree_menu_folder_list[menu_id][i];
        var zone = xGetElementById(zone_id);
        if(!zone) continue;
        var child_zone = xGetElementById(zone_id+"_child");
        if(!child_zone) continue;

        child_zone.style.display = "block";
        toggleFolder(zone_id);
    }
}

// 모두 열기
function openAllTreeMenu(menu_id) {
    for(var i in tree_menu_folder_list[menu_id]) {
        var zone_id = tree_menu_folder_list[menu_id][i];
        var zone = xGetElementById(zone_id);
        if(!zone) continue;
        var child_zone = xGetElementById(zone_id+"_child");
        if(!child_zone) continue;

        child_zone.style.display = "none";
        toggleFolder(zone_id);
    }
}

// 메뉴 클릭시 기본으로 동작할 함수 (사용자 임의 함수로 대체될 수 있음)
function moveTreeMenu(menu_id, node) {
    // url과 open_window값을 구함
    var node_srl = node.getAttribute("node_srl");
    var url = node.getAttribute("url");
    var open_window = node.getAttribute("open_window");
    var hasChild = false;
    if(node.hasChildNodes()) hasChild = true;

    // url이 없고 child가 있으면 해당 폴더 토글한다 
    if(!url && hasChild) {
        var zone_id = "menu_"+menu_id+"_"+node_srl;
        toggleFolder(zone_id);
        return;
    }

    // url이 있으면 url을 분석한다 (제로보드 특화된 부분. url이 http나 ftp등으로 시작하면 그냥 해당 url 열기)
    if(url) {
        // http, ftp등의 연결이 아닌 경우 제로보드용으로 처리
        if(url.indexOf('://')==-1) {
            url = "./?"+url;
        }

        // open_window에 따라서 처리
        if(open_window != "Y") location.href=url;
        else {
            var win = window.open(url);
            win.focus();
        }
    }
}

// 메뉴 드래그 중이라는 상황을 간직할 변수
var tree_drag_manager = {obj:null, isDrag:false}
var tree_tmp_object = new Array();
var tree_disappear = 0;

/**
 * 메뉴 드래깅을 위한 함수들
 **/
// 드래깅시 보여줄 임시 object를 생성하는 함수
function tree_create_tmp_object(obj) {
    var tmp_obj = tree_tmp_object[obj.id];
    if(tmp_obj) return tmp_obj;

    tmp_obj = xCreateElement('DIV');
    tmp_obj.id = obj.id + '_tmp';
    tmp_obj.style.display = 'none';
    tmp_obj.style.position = 'absolute';
    tmp_obj.style.backgroundColor = obj.style.backgroundColor;
    tmp_obj.style.fontSize = obj.style.fontSize;
    tmp_obj.style.fontFamlily = obj.style.fontFamlily;
    tmp_obj.style.color = "#5277ff";
    tmp_obj.style.opacity = 1;
    tmp_obj.style.filter = 'alpha(opacity=100)';

    document.body.appendChild(tmp_obj);
    tree_tmp_object[obj.id] = tmp_obj;
    return tmp_obj;
}

// 기생성된 임시 object를 찾아서 return, 없으면 만들어서 return
function tree_get_tmp_object(obj) {
    var tmp_obj = tree_tmp_object[obj.id];
    if(!tmp_obj) tmp_obj = tree_create_tmp_object(obj);
    return tmp_obj;
}

// 메뉴에 마우스 클릭이 일어난 시점에 드래그를 위한 제일 첫 동작 (해당 object에 각종 함수나 상태변수 설정) 
function tree_drag_enable(child_obj, funcDragStart, funcDrag, funcDragEnd) {
    // 클릭이 일어난 메뉴의 상위 object를 찾음
    var obj = child_obj.parentNode.parentNode;

    // 상위 object에 드래그 가능하다는 상태와 각 드래그 관련 함수를 설정
    obj.draggable = true;
    obj.drag_start = funcDragStart;
    obj.drag = funcDrag;
    obj.drag_end = funcDragEnd;
    obj.target_id = null;

    // 드래그 가능하지 않다면 드래그 가능하도록 상태 지정하고 mousemove이벤트 등록
    if (!tree_drag_manager.isDrag) {
        tree_drag_manager.isDrag = true;
        xAddEventListener(document, 'mousemove', tree_drag_mouse_move, false);
    } 

    // mousedown이벤트 값을 지정
    xAddEventListener(obj, 'mousedown', tree_mouse_down, false);
} 

// 드래그를 시작할때 호출되는 함수 (이동되는 형태를 보여주기 위한 작업을 함)
function tree_drag_start(tobj, px, py) { 
    var obj = tree_get_tmp_object(tobj);

    xInnerHtml(obj, xInnerHtml(tobj));

    tobj.source_color = tobj.style.color;
    tobj.style.color = "#BBBBBB";

    xLeft(obj, xPageX(tobj));
    xTop(obj, xPageY(tobj));
    xWidth(obj, xWidth(tobj));
    xHeight(obj, xHeight(tobj));

    xDisplay(obj, 'block');
}

// 드래그 시작후 마우스를 이동할때 발생되는 이벤트에 의해 실행되는 함수
function tree_drag(tobj, dx, dy) {
    var obj = tree_get_tmp_object(tobj);
    xLeft(obj, parseInt(xPageX(obj),10) + parseInt(dx,10));
    xTop(obj, parseInt(xPageY(obj),10) + parseInt(dy,10));

    var menu_id = tobj.id.replace(/menu_/,'');
    menu_id = menu_id.replace(/_([0-9]+)$/,'');
    if(!menu_id) return;

    for(var node_srl in node_info_list[menu_id]) {
        var zone_id = "menu_"+menu_id+"_"+node_srl;
        var target_obj = xGetElementById(zone_id);

        var hh = parseInt(xHeight(target_obj),10);
        var h = parseInt(parseInt(xHeight(target_obj),10)/2,10);

        var l =  xPageX(target_obj);
        var t =  xPageY(target_obj);
        var ll =  parseInt(l,10) + parseInt(xWidth(target_obj),10);
        var tt =  parseInt(t,10) + hh;

        if( tobj != target_obj && tobj.xDPX >= l && tobj.xDPX <= ll) {
            if(tobj.xDPY >= t && tobj.xDPY < tt-h) {
                try {
                    target_obj.parentNode.insertBefore(tobj, target_obj);
                    tobj.target_id = target_obj.id;
                } catch(e) {
                }
            }
        }
    } 
} 
  
// 드래그 종료 (이동되는 object가 이동할 곳에 서서히 이동되는 것처럼 보이는 효과)
function tree_drag_end(tobj, px, py) {
    var obj = tree_get_tmp_object(tobj);
    tree_disappear = tree_disapear_object(obj, tobj);
    tree_drag_disable(tobj.id);
}

// 스르르 사라지게 함;;
function tree_disapear_object(obj, tobj) {
    var it = 150;
    var ib = 15;

    var x = parseInt(xPageX(obj),10);
    var y = parseInt(xPageY(obj),10);
    var ldt = (x - parseInt(xPageX(tobj),10)) / ib;
    var tdt = (y - parseInt(xPageY(tobj),10)) / ib;

    return setInterval(function() {
        if(ib < 1) {
            clearInterval(tree_disappear);
            xInnerHtml(tobj,xInnerHtml(obj));
            xInnerHtml(obj,'');
            xDisplay(obj, 'none');
            return;
        }
        ib -= 5;
        x-=ldt;
        y-=tdt;
        xLeft(obj, x);
        xTop(obj, y);
    }, it/ib);
}

// 마우스다운 이벤트 발생시 호출됨
function tree_mouse_down(e) {
    var evt = new xEvent(e);
    var obj = evt.target;

    while(obj && !obj.draggable) {
        obj = xParent(obj, true);
    }

    if(obj) {
        xPreventDefault(e);
        obj.xDPX = evt.pageX;
        obj.xDPY = evt.pageY;
        tree_drag_manager.obj = obj;
        xAddEventListener(document, 'mouseup', tree_mouse_up, false);
        if (obj.drag_start) obj.drag_start(obj, evt.pageX, evt.pageY);
    }
}

// 마우스 버튼을 놓았을때 동작될 함수 (각종 이벤트 해제 및 변수 설정 초기화)
function tree_mouse_up(e) { 
    if (tree_drag_manager.obj) {
        xPreventDefault(e);
        xRemoveEventListener(document, 'mouseup', tree_mouse_up, false);

        if (tree_drag_manager.obj.drag_end) {
            var evt = new xEvent(e);
            tree_drag_manager.obj.drag_end(tree_drag_manager.obj, evt.pageX, evt.pageY);
        } 

        tree_drag_manager.obj = null;
        tree_drag_manager.isDrag = false;
    } 
}  

// 드래그할때의 object이동등을 담당 
function tree_drag_mouse_move(e) {
    var evt = new xEvent(e);

    if (tree_drag_manager.obj) {
        xPreventDefault(e);

        var obj = tree_drag_manager.obj;
        var dx = evt.pageX - obj.xDPX;
        var dy = evt.pageY - obj.xDPY;

        obj.xDPX = evt.pageX;
        obj.xDPY = evt.pageY;

        if (obj.drag) {
            obj.drag(obj, dx, dy);
        } else {
            xMoveTo(obj, xLeft(obj) + dx, xTop(obj) + dy);
        }
    }
}

// 해당 object 에 더 이상 drag가 되지 않도록 설정
function tree_drag_disable(id) {
    if (!tree_drag_manager) return;
    var obj = xGetElementById(id);
    obj.draggable = false;
    obj.drag_start = null;
    obj.drag = null;
    obj.drag_end = null;
    obj.style.color = obj.source_color;

    xRemoveEventListener(obj, 'mousedown', tree_mouse_down, false);

    if(obj.id && obj.target_id && obj.id!=obj.target_id) {
        var menu_id = obj.id.replace(/menu_/,'');
        menu_id = menu_id.replace(/_([0-9]+)$/,'');
        if(menu_id) {
            var callback_move_func = node_move_callback_func[menu_id];
            if(callback_move_func) callback_move_func(menu_id, obj.id, obj.target_id);
        }
    } 
    obj.target_id = null;
}
