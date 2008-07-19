<?php
    /**
     * @file   modules/editor/lang/ko.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  위지윅에디터(editor) 모듈의 기본 언어팩
     **/

    $lang->editor = "위지윅 에디터";
    $lang->component_name = "컴포넌트";
    $lang->component_version = "버전";
    $lang->component_author = "제작자 ";
    $lang->component_link = "링크";
    $lang->component_date = "제작일";
    $lang->component_license = '라이선스';
    $lang->component_history = '변경 이력';
    $lang->component_description = "설명";
    $lang->component_extra_vars = "설정 변수";
    $lang->component_grant = "권한설정"; 

    $lang->about_component = "컴포넌트 소개";
    $lang->about_component_grant = '기본 컴포넌트외의 확장 컴포넌트 기능을 사용할 수 있는 권한을 지정할 수 있습니다.<br /> (모두 해제시 아무나 사용 가능합니다)';
    $lang->about_component_mid = "에디터 컴포넌트가 사용될 대상을 지정할 수 있습니다.<br />(모두 해제시 모든 대상에서 사용 가능합니다)";

    $lang->msg_component_is_not_founded = '%s 에디터 컴포넌트를 찾을 수 없습니다';
    $lang->msg_component_is_inserted = '선택하신 컴포넌트는 이미 입력되어 있습니다';
    $lang->msg_component_is_first_order = '선택하신 컴포넌트는 첫번째에 위치하고 있습니다';
    $lang->msg_component_is_last_order = '선택하신 컴포넌트는 마지막에 위치하고 있습니다';
    $lang->msg_load_saved_doc = "자동저장된 글이 있습니다. 복구하시겠습니까?\n글을 다 쓰신 후 저장하시면 자동저장본은 사라집니다";
    $lang->msg_auto_saved = "자동 저장되었습니다";

    $lang->cmd_disable = "비활성";
    $lang->cmd_enable = "활성";

    $lang->editor_skin = '에디터 스킨';
    $lang->upload_file_grant = '파일 첨부 권한'; 
    $lang->enable_default_component_grant = '기본 컴포넌트 사용 권한';
    $lang->enable_component_grant = '컴포넌트 사용 권한';
    $lang->enable_html_grant = 'HTML편집 권한';
    $lang->enable_autosave = '자동저장 사용';
    $lang->height_resizable = '높이 조절 가능';
    $lang->editor_height = '에디터 높이';

    $lang->about_editor_skin = '에디터 스킨을 선택하실 수 있습니다';
    $lang->about_upload_file_grant = '파일을 첨부할 수 있는 권한을 지정하실 수 있습니다 (모두 해제시 아무나 첨부 가능합니다)';
    $lang->about_default_component_grant = '에디터에서 사용되는 기본 컴포넌트를 사용할 수 있는 권한을 지정할 수 있습니다. (모두 해제시 아무나 사용 가능합니다)';
    $lang->about_editor_height = '에디터의 기본 높이를 지정하실 수 있습니다';
    $lang->about_editor_height_resizable = '에디터의 높이를 직접 변경할 수 있도록 허용합니다';
    $lang->about_enable_html_grant = 'HTML편집 권한을 부여할 수 있습니다.';
    $lang->about_enable_autosave = '글작성시 자동 저장 기능을 활성화 시킬 수 있습니다';

    $lang->edit->fontname = '글꼴';
    $lang->edit->fontsize = '크기';
    $lang->edit->use_paragraph = '문단기능';
    $lang->edit->fontlist = array(
    "굴림",
    "돋움",
    "바탕",
    "궁서",
    "times",
    "Courier",
    "Tahoma",
    "Arial",
    );

    $lang->edit->header = "형식";
    $lang->edit->header_list = array(
    "h1" => "제목 1",
    "h2" => "제목 2",
    "h3" => "제목 3",
    "h4" => "제목 4",
    "h5" => "제목 5",
    "h6" => "제목 6",
    );

    $lang->edit->submit = '확인';

    $lang->edit->help_remove_format = "선택된 영역 내의 태그를 지웁니다";
    $lang->edit->help_strike_through = "글자에 취소선을 표시합니다";
    $lang->edit->help_align_full = "좌우 폭에 맞게 정렬을 합니다";

    $lang->edit->help_fontcolor = "글자의 색상을 지정합니다";
    $lang->edit->help_fontbgcolor = "글자의 배경색상을 지정합니다";
    $lang->edit->help_bold = "글자를 진하게 합니다";
    $lang->edit->help_italic = "글자를 기울이게 합니다";
    $lang->edit->help_underline = "밑줄을 긋습니다";
    $lang->edit->help_strike = "취소선을 긋습니다";
    $lang->edit->help_redo = "다음 동작으로 돌아갑니다";
    $lang->edit->help_undo = "이전 동작으로 돌아갑니다";
    $lang->edit->help_align_left = "왼쪽 정렬을 합니다";
    $lang->edit->help_align_center = "가운데 정렬을 합니다";
    $lang->edit->help_align_right = "오른쪽 정렬을 합니다";
    $lang->edit->help_add_indent = "들여쓰기를 합니다";
    $lang->edit->help_remove_indent = "들여쓰기를 제거합니다";
    $lang->edit->help_list_number = "숫자로된 목록을 적용합니다";
    $lang->edit->help_list_bullet = "기호로된 목록을 적용합니다";
    $lang->edit->help_use_paragrapth = "문단 나누기를 하시려면 ctrl-엔터를 누르시면 됩니다. (글 작성완료후 alt-S를 누르면 저장이 됩니다)";

    $lang->edit->upload = '첨부';
    $lang->edit->upload_file = '파일 첨부'; 
    $lang->edit->link_file = '본문 삽입';
    $lang->edit->delete_selected = '선택 삭제';

    $lang->edit->icon_align_article = '한 문단을 차지';
    $lang->edit->icon_align_left = '글의 왼쪽으로';
    $lang->edit->icon_align_middle = '가운데 정렬';
    $lang->edit->icon_align_right = '글의 우측으로';

    $lang->about_dblclick_in_editor = '배경, 글자, 이미지, 인용문등에서 더블클릭을 하시면 상세한 컴포넌트 설정이 가능합니다';
?>
