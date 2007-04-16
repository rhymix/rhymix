/**
 * @brief sample_layout 메뉴 출력용 javascript
 * @author zero (zero@nzeo.com)
 **/

/**
 * 메뉴를 담을 javascript 변수
 *
 * xe_layout_menu 구조
 *
 * xe_layout_menu[메뉴명][depth][menu_srl] = Object
 *
 * Object {
 *   text : 메뉴 명
 *   href : 연결할 주소
 *   open_window : [Y|N] 새창으로 띄울 것인지에 대한 값
 *   normal_btn : 이미지 버튼
 *   hover_btn : 이미지 버튼일 경우 mouseover 일 경우
 *   active_btn : 선택되어 있을 경우의 이미지
 *   className : normal 상태의 className
 *   selectedClassName : 선택된 상태의 className
 *   selected : 선택된 메뉴라면 true, 아니면 false
 * }
 **/
var xe_layout_menu = new Array();

/**
 * @brief sample_layout에서 메뉴를 출력하는 함수
 * menu_name : 레이아웃 설정상의 메뉴 이름
 * depth : 입력된 단계
 * print_child : true로 하면 메뉴 출력중 하위 메뉴가 있을 시 출력
 **/
function xe_print_menu(menu_name, depth, print_child) {
    if(typeof(xe_layout_menu[menu_name])=='undefined' || typeof(xe_layout_menu[menu_name][depth])=='undefined') return;
    if(typeof(print_child)=='undefined') print_child = false;

    var menu_list = xe_layout_menu[menu_name][depth];
    var html = "";

    for(var menu_key in menu_list) {
        var menu_obj = menu_list[menu_key];
        if(typeof(menu_obj)=='undefined'||!menu_obj) continue;

        var className = menu_obj.className;
        if(menu_obj.selected) className = menu_obj.selectedClassName;

        if(!menu_obj.href) menu_obj.href = "#";

        // 텍스트일 경우
        if(!menu_obj.normal_btn) {
            
            if(menu_obj.open_window == "Y") html = "<span class=\""+className+"\"><a href=\"#\" onclick=\"winopen('"+menu_obj.href+"'); return false;\">"+menu_obj.text+"</a></span>";
            else html = "<span class=\""+className+"\"><a href=\""+menu_obj.href+"\">"+menu_obj.text+"</a></span>";

        // 이미지 버튼 일 경우
        } else if(menu_obj.normal_btn) {
            if(!menu_obj.hover_btn) menu_obj.hover_btn = menu_obj.normal_btn;
            if(!menu_obj.active_btn) menu_obj.active_btn = menu_obj.normal_btn;

            if(menu_obj.selected) menu_obj.normal_btn = menu_obj.active_btn;

            if(menu_obj.open_window == "Y") html = "<span class=\""+className+"\"><a href=\"#\" onclick=\"winopen('"+menu_obj.href+"'); return false;\"><img src=\""+menu_obj.normal_btn+"\" border=\"0\" alt=\""+menu_obj.text+"\" onmouseover=\"this.src='"+menu_obj.hover_btn+"'\" onmouseout=\"this.src='"+menu_obj.normal_btn+"'\" /></a></span>";
            else html = "<span class=\""+className+"\"><a href=\""+menu_obj.href+"\"><img src=\""+menu_obj.normal_btn+"\" border=\"0\" alt=\""+menu_obj.text+"\" onmouseover=\"this.src='"+menu_obj.hover_btn+"'\" onmouseout=\"this.src='"+menu_obj.normal_btn+"'\" /></a></span>";
        }

        if(html) document.write(html);

        if(print_child && menu_obj.selected && typeof(xe_layout_menu[menu_name][depth+1])!='undefined') xe_print_menu('main_menu', depth+1, true);
    }
}
