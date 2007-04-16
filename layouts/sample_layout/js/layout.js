/**
 * @brief sample_layout에서 메뉴를 출력하는 함수
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
function xe_print_menu(text, href, open_window, normal_btn, hover_btn, active_btn, modifier, selected_class, selected) {
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

    document.write(html);
}
