/**
 * popup으로 열렸을 경우 부모창의 위지윅에디터에 select된 멀티미디어 컴포넌트 코드를 체크하여
 * 있으면 가져와서 원하는 곳에 삽입
 **/
function getNaverMap() {
    // 부모 위지윅 에디터에서 선택된 영역이 있는지 확인
    if(typeof(opener)=="undefined") return;

    var node = opener.editorPrevNode;
    if(!node || node.nodeName != "IMG") return;

    var x = node.getAttribute("x");
    var y = node.getAttribute("y");
    var width = xWidth(node);
    var height = xHeight(node);
    var address = node.getAttribute("address");
    var zoom = node.getAttribute("zoom");

    if(x&&y) {
        if(zoom) {
            moveMap(x,y,zoom);
        }
        else {
            moveMap(x,y,3);
        }
    }
    if(address) {
        xGetElementById("address").value = address;
        search_address(address);
    }

    xGetElementById("map_width").value = width-4;
    xGetElementById("map_height").value = height-4;
}

function insertNaverMap(obj) {
    if(typeof(opener)=="undefined") return;

    var x = display_map.mapObj.getCenter().x;
    var y = display_map.mapObj.getCenter().y;
    var marker = xGetElementById("marker").value;
    var address = xGetElementById("address").value;
    var zoom = display_map.mapObj.getZoom();

    var width = xGetElementById("map_width").value;
    var height = xGetElementById("map_height").value;

    var text = "<img src=\"./common/tpl/images/blank.gif\" editor_component=\"naver_map\"  address=\""+address+"\" x=\""+x+"\" y=\""+y+"\" zoom=\""+zoom+"\" width=\""+width+"\" height=\""+height+"\" style=\"width:"+width+"px;height:"+height+"px;border:2px dotted #3CBC2f;background:url(./modules/editor/components/naver_map/tpl/navermap_component.gif) no-repeat center;\" marker=\""+marker+"\" />";

    opener.editorFocus(opener.editorPrevSrl);

    var iframe_obj = opener.editorGetIFrame(opener.editorPrevSrl)

    opener.editorReplaceHTML(iframe_obj, text);
    opener.editorFocus(opener.editorPrevSrl);

    window.close();
}

/* 네이버의 map openapi로 주소에 따른 좌표를 요청 */
function search_address(selected_address) {
  if(typeof(selected_address)=="undefined") selected_address = null;
  var address = xGetElementById("address").value;
  if(!address) return;
  var params = new Array();
  params['component'] = "naver_map";
  params['address'] = address;
  params['method'] = "search_address";

  var response_tags = new Array('error','message','address_list');
  exec_xml('editor', 'procEditorCall', params, complete_search_address, response_tags, selected_address);
}

function moveMap(x,y,scale) {
    if(typeof(scale)=="undefined") scale = 3;
    display_map.moveMap(x,y,scale);
}

var naver_address_list = new Array();
function complete_search_address(ret_obj, response_tags, selected_address) {
  var address_list = ret_obj['address_list'];
  if(!address_list) return;

  naver_address_list = new Array();

  var html = "";
  var address_list = address_list.split("\n");
  for(var i=0;i<address_list.length;i++) {
    var item = address_list[i].split(",");

    naver_address_list[naver_address_list.length] = item;
    html += "<li class=\"address_lists\"><a href='#' onclick=\"moveMap('"+item[0]+"','"+item[1]+"');return false;\">"+item[2]+"</a></li>";
  }
  if(address_list.length == 1) {
    moveMap(item[0],item[1]);
  }

  var list_zone = xGetElementById("address_list");
  xInnerHtml(list_zone, html);
}

/* 마커 표시 */
var marker_count = 1;
function addMarker(pos) {
    if(marker_count>10) return;
    xGetElementById("marker").value += '|@|'+pos;
    marker_count++;
    return true;
}
xAddEventListener(window, "load", getNaverMap);
