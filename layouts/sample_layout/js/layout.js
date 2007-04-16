/**
 * @brief sample_layout 메뉴 출력용 javascript
 * @author zero (zero@nzeo.com)
 **/

// 메뉴를 담을 javascript 변수
var xe_layout_menu = new Array();

/**
 * @brief sample_layout에서 메뉴를 출력하는 함수
 * menu_name : 레이아웃 설정상의 메뉴 이름
 * depth : 단계 
 * text : 메뉴 명
 * href : 연결할 주소
 * open_window : [Y|N] 새창으로 띄울 것인지에 대한 값
 * normal_btn : 이미지 버튼
 * hover_btn : 이미지 버튼일 경우 mouseover 일 경우
 * active_btn : 선택되어 있을 경우의 이미지
 * modifier : 구분자 ( <br /> 또는 null 이 사용 예상)
 * selected_class : 이미지 버튼이 아닐 경우 선택된 문자열에 대한 css class (지정 안되어 있으면 <span style="font-weight:bold">..</span>로 처리
 * selected : 선택된 메뉴라면 true, 아니면 false
 **/
function xe_add_menu(menu_name, depth, text, href, open_window, normal_btn, hover_btn, active_btn, modifier, selected_class, selected) {
    // 텍스트나 이미지 버튼이 없으면 패스~
    if(!text && !normal_btn) return;

    var html = "";

    // 텍스트일 경우
    if(!normal_btn) {
        // 선택되었을 때의 class or style 지정 
        if(selected) {
            if(!selected_class) selected_class = "style=\"font-weight:bold\"";
            else selected_class = "class=\""+selected_class+"\"";
        } else {
            selected_class = "";
        }

        if(open_window == "Y") html = "<span "+selected_class+"><a href=\"#\" onclick=\"winopen('"+href+"'); return false;\">"+text+"</a></span>";
        else html = "<span "+selected_class+"><a href=\""+href+"\">"+text+"</a></span>";

    // 이미지 버튼 일 경우
    } else if(normal_btn) {
        if(!hover_btn) hover_btn = normal_btn;
        if(!active_btn) active_btn = normal_btn;
        if(selected) normal_btn = active_btn;

        if(open_window == "Y") html = "<a href=\"#\" onclick=\"winopen('"+href+"'); return false;\"><img src=\""+normal_btn+"\" border=\"0\" alt=\""+text+"\" onmouseover=\"this.src='"+hover_btn+"'\" onmouseout=\"this.src='"+normal_btn+"'\" /></a>";
        else html = "<a href=\""+href+"\"><img src=\""+normal_btn+"\" border=\"0\" alt=\""+text+"\" onmouseover=\"this.src='"+hover_btn+"'\" onmouseout=\"this.src='"+normal_btn+"'\" /></a>";


    }

    // modifier 출력
    if(modifier) html += modifier;

    if(!xe_layout_menu[menu_name]) xe_layout_menu[menu_name] = new Array();
    if(!xe_layout_menu[menu_name][depth]) xe_layout_menu[menu_name][depth] = new Array();
    xe_layout_menu[menu_name][depth][xe_layout_menu[menu_name][depth].length] = html;
}

/**
 * @brief xe_layout_menu에 있는 메뉴를 출력
 * menu_name : 레이아웃 설정상의 메뉴 이름
 * depth : 입력된 단계
 * print_child : true로 하면 메뉴 출력중 하위 메뉴가 있을 시 출력
 **/
function xe_print_menu(menu_name, depth, print_child) {
    if(!xe_layout_menu[menu_name] || !xe_layout_menu[menu_name][depth]) return;
    for(var i=0;i<xe_layout_menu[menu_name][depth].length;i++) {
        document.write(xe_layout_menu[menu_name][depth][i]);
        if(typeof(print_child)!='undefined' && print_child==true) xe_print_menu(menu_name, depth+1, print_child);
    }
}
