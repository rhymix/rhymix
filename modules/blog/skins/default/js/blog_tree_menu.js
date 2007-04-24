/**
 * @file blog_tree_menu.js
 * @author zero (zero@nzeo.com)
 * @brief xml파일을 읽어서 트리 메뉴를 그려줌
 *
 * common/tpl/tree_menu.js 를 서비스용으로만 사용하기 위해서 수정한 것.
 * 관리 기능이 없고 css 적용이 가능
 **/

// 폴더를 모두 열고/닫기 위한 변수 설정
var blog_tree_menu_folder_list = new Array();

// 노드의 정보를 가지고 있을 변수
var blog_node_info_list = new Array();

// 트리메뉴의 정보를 담고 있는 xml파일을 읽고 drawTreeMenu()를 호출하는 함수
function blogLoadTreeMenu(xml_url, title, index_url) {
    // 일단 그릴 곳을 찾아서 사전 작업을 함 (그릴 곳이 없다면 아예 시도를 안함)
    var zone = xGetElementById("blog_category");
    if(typeof(zone)=="undefined") return;

    // 제목이 없으면 제목을 category로 지정
    if(typeof(title)=="undefined" || !title) title = "category";

    // index url이 없으면 현재 # 으로 대체
    if(!index_url) index_url= "#";

    // xml_handler를 이용해서 직접 메뉴 xml파일를 읽음
    if(!xml_url) return;
    var oXml = new xml_handler();
    oXml.reset();
    oXml.xml_path = xml_url;

    var param = {"title":title, "index_url":index_url}

    // 요청후 drawTreeMenu()함수를 호출 (xml_handler.js에서 request method를 직접 이용)
    oXml.request(blogDrawTreeMenu, oXml, null, null, null, param);
}

// 트리메뉴 XML정보를 이용해서 정해진 zone에 출력
var blog_menu_selected = false;
function blogDrawTreeMenu(oXml, callback_func, resopnse_tags, null_func, param) {
    var title = param.title;
    var index_url = param.index_url;

    var zone = xGetElementById("blog_category");
    var html = "";

    // 받아온 xml내용을 이용하여 트리 메뉴 그림
    var xmlDoc = oXml.getResponseXml();
    if(!xmlDoc) {
        xInnerHtml(zone, html);
        return null;
    }

    // node 태그에 해당하는 값들을 가져와서 html을 작성
    var node_list = xmlDoc.getElementsByTagName("node");
    if(node_list.length>0) {
        var root = xmlDoc.getElementsByTagName("root")[0];
        var output = blogDrawNode(root);
        html += output.html;
    }

    // 제목 지정 
    var title_class = "selected";
    if(blog_menu_selected) title_class = "unselected";
    html = '<div class="title_box"><span class="'+title_class+'" id="blog_title" onclick="location.href=\''+index_url+'\';return false;" >'+title+'</span></div>'+html;

    // 출력하려는 zone이 없다면 load후에 출력하도록 함
    if(!zone) {
        xAddEventListener(window, 'load', function() { blogDrawTeeMenu(html); });

    // 출력하려는 zone을 찾아졌다면 바로 출력
    } else {
        xInnerHtml(zone, html);
    }

    return null;
}

// 페이지 랜더링 중에 메뉴의 html이 완성되었을때 window.onload event 후에 그리기 재시도를 하게 될 함수
function blogDrawTeeMenu(html) {
    xInnerHtml("blog_category", html);
}

// root부터 시작해서 recursive하게 노드를 표혐
function blogDrawNode(parent_node) {
    var output = {html:"", expand:"N"}

    for (var i=0; i< parent_node.childNodes.length; i++) {
        var html = "";
        var selected = false;

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

        // 후에 사용하기 위해 blog_node_info_list에 node_srl을 값으로 하여 node object 추가
        blog_node_info_list[node_srl] = node;

        // zone_id 값을 세팅
        var zone_id = "blog_category_"+node_srl;
        blog_tree_menu_folder_list[blog_tree_menu_folder_list.length] = zone_id;

        if(url && typeof(zbxe_url)!="undefined" && zbxe_url == url) {
            selected = true;
            blog_menu_selected = true;
        }

        // blog_selected_node이 node_srl과 같으면 펼침으로 처리
        if(selected) expand = "Y";

        // 아이콘 설정
        var line_class = null;
        var folder_class = null;

        // 자식 노드가 있을 경우 자식 노드의 html을 구해옴
        var child_output = null;
        var child_html = "";

        if(hasChild) {
            // 자식 노드의 zone id를 세팅
            var child_zone_id = zone_id+"_child";
            blog_tree_menu_folder_list[blog_tree_menu_folder_list.length] = child_zone_id;
            
            // html을 받아옴 
            child_output = blogDrawNode(node);
            var chtml = child_output.html;
            var cexpand = child_output.expand;
            if(cexpand == "Y") expand = "Y";

            // 무조건 펼침이 아닐 경우
            if(expand!="Y") {
                if(!hasNextSibling) child_html += '<div id="'+child_zone_id+'" class="line_close">'+chtml+'</div>';
                else child_html += '<div id="'+child_zone_id+'" class="item_close">'+chtml+'</div>';

            // 무조건 펼침일 경우
            } else {
                if(!hasNextSibling) child_html += '<div id="'+child_zone_id+'" class="line_open"">'+chtml+'</div>';
                else child_html += '<div id="'+child_zone_id+'" class="item_open">'+chtml+'</div>';
            }
        }

        // 자식 노드가 있는지 확인하여 있으면 아이콘을 바꿈
        if(hasChild) {

            // 무조건 펼침이 아닐 경우
            if(expand != "Y") {
                if(!hasNextSibling) {
                    line_class = "minus";
                    folder_class = "folder_close";
                } else {
                    line_class = "minus_bottom";
                    folder_class = "folder_close";
                }
            // 무조건 펼침일 경우 
            } else {
                if(!hasNextSibling) {
                    line_class = "plus";
                    folder_class = "folder_open";
                } else {
                    line_class = "plus_bottom";
                    folder_class = "folder_open";
                }
            }

        // 자식 노드가 없을 경우
        } else {
            if(hasNextSibling) {
                line_class = "join_bottom";
                folder_class = "page";
            } else {
                line_class = "join";
                folder_class = "page";
            }
        }


        // html 작성
        var click_str = ' class="'+folder_class+'"' ;
        if(hasChild) click_str += ' onclick="blogToggleFolder(\''+zone_id+'\');return false;" ';

        var text_class = "unselected";
        if(selected) text_class = "selected";

        html += '<div id="'+zone_id+'" class="node_item">'+
                    '<div id="'+zone_id+'_line" class="'+line_class+'">'+
                        '<div id="'+zone_id+'_folder" '+click_str+'></div>'+
                        '<span id="'+zone_id+'_node" class="'+text_class+'" onclick="blogSelectNode('+node_srl+')">'+text+'</span>'+
                    '</div>';

        if(hasChild && child_html) html += child_html;

        html += '</div>';

        output.html += html;

        if(expand=="Y") output.expand = "Y";
    }
    return output;
}

// 노드의 폴더 아이콘 클릭시
function blogToggleFolder(zone_id) {
    // 아이콘을 클릭한 대상을 찾아봄
    var child_zone = xGetElementById(zone_id+"_child");
    if(!child_zone) return;

    var line_obj = xGetElementById(zone_id+'_line');
    var folder_obj = xGetElementById(zone_id+'_folder');

    // 대상의 자식 노드들이 숨겨져 있다면 열고 아니면 닫기
    if(folder_obj.className == "folder_open") {
        child_zone.style.display = "none";

        if(line_obj.className.indexOf('bottom')>0) line_obj.className = 'minus_bottom';
        else line_obj.className = 'minus';

        folder_obj.className = 'folder_close'
    } else {
        child_zone.style.display = "block";

        if(line_obj.className.indexOf('bottom')>0) line_obj.className = 'plus_bottom';
        else line_obj.className = 'plus';

        folder_obj.className = 'folder_open';
    }
}

// 노드 클릭시
function blogSelectNode(node_srl) {
    // url과 open_window값을 구함
    var node = blog_node_info_list[node_srl];
    if(!node) return;

    var url = node.getAttribute("url");
    var open_window = node.getAttribute("open_window");
    var hasChild = false;
    if(node.hasChildNodes()) hasChild = true;

    // url이 없고 child가 있으면 해당 폴더 토글한다 
    if(!url && hasChild) {
        var zone_id = "menu_blog_category_"+node_srl;
        blogToggleFolder(zone_id);
        return;
    }

    // url이 있으면 url을 분석한다 (제로보드 특화된 부분. url이 http나 ftp등으로 시작하면 그냥 해당 url 열기)
    if(url) {
        // http, ftp등의 연결이 아닌 경우 제로보드용으로 처리
        if(url.indexOf('://')==-1) url = "./?"+url;

        // open_window에 따라서 처리
        if(open_window != "Y") location.href=url;
        else {
            var win = window.open(url);
            win.focus();
        }
    }
}
