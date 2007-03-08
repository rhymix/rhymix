/**
 * @file tree_menu.js
 * @author zero (zero@nzeo.com)
 * @brief xml파일을 읽어서 트리 메뉴를 그려줌
 *
 * 일단 이것 저것 꽁수가 좀 들어간 것이긴 한데 속도나 기타 면에서 쓸만함...
 * 언제나 그렇듯 필요하신 분은 가져가서 쓰세요.
 * 다만 제로보드에 좀 특화되어 있어서....
 **/

// 아이콘을 미리 생성해 놓음
var tree_folder_icon = new Image();
tree_folder_icon.src = "./common/tpl/images/page.gif";
var tree_open_folder_icon = new Image();
tree_open_folder_icon.src = "./common/tpl/images/page.gif";

var tree_minus_icon = new Image();
tree_minus_icon.src = "./common/tpl/images/minus.gif";
var tree_minus_bottom_icon = new Image();
tree_minus_bottom_icon.src = "./common/tpl/images/minusbottom.gif";
var tree_plus_icon = new Image();
tree_plus_icon.src = "./common/tpl/images/plus.gif";
var tree_plus_bottom_icon = new Image();
tree_plus_bottom_icon.src = "./common/tpl/images/plusbottom.gif";

// 폴더를 모두 열고/닫기 위한 변수 설정
var tree_menu_folder_list = new Array();

// 노드의 정보를 가지고 있을 변수
var node_info_list = new Array();

// menu_id별로 요청된 callback_func
var node_callback_func = new Array();

// 메뉴 클릭시 기본으로 동작할 함수
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

// 트리메뉴의 정보를 담고 있는 xml파일을 읽고 drawTreeMenu()를 호출하는 함수
function loadTreeMenu(url, menu_id, zone_id, title, callback_func, manual_select_node_srl) {
    // 일단 그릴 곳을 찾아서 사전 작업을 함 (그릴 곳이 없다면 아예 시도를 안함)
    var zone = xGetElementById(zone_id);
    if(typeof(zone)=="undefined") return;

    // 노드 추가를 위한 빈 div하나 입력해 넣음
    xInnerHtml(zone, "");

    // xml_handler를 이용해서 직접 메뉴 xml파일(layout module에서 생성)을 읽음
    var oXml = new xml_handler();
    oXml.reset();
    oXml.xml_path = url;

    if(typeof(callback_func)=='undefined') {
        callback_func = moveTreeMenu;
    }

    node_callback_func[menu_id] = callback_func;

    if(typeof(manual_select_node_srl)=='undefined') manual_select_node_srl = '';

    // menu_id, zone_id는 계속 달고 다녀야함 
    var param = {menu_id:menu_id, zone_id:zone_id, title:title, manual_select_node_srl:manual_select_node_srl}

    // 요청후 drawTreeMenu()함수를 호출
    oXml.request(drawTreeMenu, oXml, null, null, param);
}

// 트리메뉴 XML정보를 이용해서 정해진 zone에 출력
function drawTreeMenu(oXml, callback_func, resopnse_tags, param) {
    // 그리기 위한 object를 찾아 놓음
    var menu_id = param.menu_id;
    var zone_id = param.zone_id;
    var title = param.title;
    var manual_select_node_srl = param.manual_select_node_srl;
    var zone = xGetElementById(zone_id);
    var html = "";
    html = '<div style="height:20px;"><img src="./common/tpl/images/folder.gif" alt="root" align="top" />'+title+'</div>';

    tree_menu_folder_list[menu_id] = new Array();

    // xml 정보가 들어올때까지 대기 (async)
    var xmlDoc = oXml.getResponseXml();
    if(xmlDoc) {

        // node 태그에 해당하는 값들을 가져옴
        var node_list = xmlDoc.getElementsByTagName("node");
        if(node_list.length>0) {
            var root = xmlDoc.getElementsByTagName("root")[0];
            html += drawNode(root, menu_id);
        }
    }

    xInnerHtml(zone, html);

    if(manual_select_node_srl) manualSelectNode(menu_id, manual_select_node_srl);
}

// root부터 시작해서 recursive하게 노트를 표혐
function drawNode(parent_node, menu_id) {
    var html = '';
    for (var i=0; i< parent_node.childNodes.length; i++) {
        var node = parent_node.childNodes.item(i);
        if(node.nodeName!="node") continue;

        // 자식 노드가 있는지 확인
        var hasChild = false;
        if(node.hasChildNodes()) hasChild = true;

        // nextSibling가 있는지 확인 
        var hasNextSibling = false;
        if(i==parent_node.childNodes.length-1) hasNextSibling = true;

        // 아이콘 설정
        var line_icon = null;
        var folder_icon = null;

        // 자식 노드가 있는지 확인하여 있으면 아이콘을 바꿈
        if(hasChild) {
            if(!hasNextSibling) {
                line_icon = "minus";
                folder_icon = "page";
            } else {
                line_icon = "minusbottom";
                folder_icon = "page";
            }
        } else {
            if(hasNextSibling) {
                line_icon = "joinbottom";
                folder_icon = "page";
            } else {
                line_icon = "join";
                folder_icon = "page";
            }
        }

        var node_srl = node.getAttribute("node_srl");
        var text = node.getAttribute("text");

        node_info_list[node_srl] = node;

        var zone_id = "menu_"+menu_id+"_"+node_srl;
        tree_menu_folder_list[menu_id][tree_menu_folder_list[menu_id].length] = zone_id;

        html += ''+
                '<div id="'+zone_id+'" style="margin:0px;font-size:9pt;">'+
                '';
        if(hasChild)
            html+= ''+
                '<span style="cursor:pointer;" onclick="toggleFolder(\''+zone_id+'\');return false;">'+
                '';
        else 
            html+= ''+
                '<span>'+
                '';

        html += ''+
                '<img id="'+zone_id+'_line_icon" src="./common/tpl/images/'+line_icon+'.gif" alt="line" align="top" />'+
                '<img id="'+zone_id+'_folder_icon" src="./common/tpl/images/'+folder_icon+'.gif" alt="folder" align="top" />'+
                '</span>'+
                '<span id="'+zone_id+'_node" style="padding:1px 2px 1px 2px;margin-top:1px;cursor:pointer;" onclick="selectNode(\''+menu_id+'\','+node_srl+',\''+zone_id+'\')">'+
                text+
                '</span>'+
                '';

        if(node.childNodes.length) {
            zone_id = zone_id+"_child";
            tree_menu_folder_list[menu_id][tree_menu_folder_list[menu_id].length] = zone_id;
            if(!hasNextSibling) html += '<div id="'+zone_id+'"style="display:none;padding-left:18px;background:url(./common/tpl/images/line.gif) repeat-y left;">'+drawNode(node, menu_id)+'</div>';
            else html += '<div id="'+zone_id+'" style="display:none;padding-left:18px;">'+drawNode(node, menu_id)+'</div>';
        }

        html += ''+
                '</div>'
                '';


    }
    return html;
}

// 수동으로 메뉴를 선택하도록 함
function manualSelectNode(menu_id, node_srl) {
    var zone_id = "menu_"+menu_id+"_"+node_srl;
    selectNode(menu_id,node_srl,zone_id);

    var zone = xGetElementById(zone_id);
    try {
        while(zone = zone.parentNode) {
            if(!zone) break;
            if(typeof(zone.id)=='undefined') continue;
            var id = zone.id;
            if(id.indexOf("menu_")<0 || id.indexOf("child")<0) continue;

            var child_zone = xGetElementById(id);
            child_zone.style.display = "block";
            toggleFolder(zone_id);
        }
    } catch(e) {
    }
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
function selectNode(menu_id, node_srl, zone_id) {
    // 이전에 선택된 노드가 있었다면 원래데로 돌림
    if(prev_selected_node) {
        prev_selected_node.style.backgroundColor = "#ffffff";
        prev_selected_node.style.fontWeight = "normal";
        prev_selected_node.style.color = "#000000";
    }

    // 선택된 노드를 찾아봄
    var node_zone = xGetElementById(zone_id+'_node');
    if(!node_zone) return;

    // 선택된 노드의 글자를 변경
    node_zone.style.backgroundColor = "#0e078f";
    node_zone.style.fontWeight = "bold";
    node_zone.style.color = "#FFFFFF";
    prev_selected_node = node_zone;

    // 함수 실행
    var func = node_callback_func[menu_id];
    func(menu_id, node_info_list[node_srl]);
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
