<?php
	/**
	 * @file   modules/editor/lang/ko.lang.php
	 * @author zero <zero@nzeo.com>
	 * @brief  위지윅에디터(editor) 모듈의 기본 언어팩
	 **/

	$lang->editor = '위지윅 에디터';
	$lang->component_name = '컴포넌트';
	$lang->component_version = '버전';
	$lang->component_author = '제작자 ';
	$lang->component_link = '링크';
	$lang->component_date = '제작일';
	$lang->component_license = '라이선스';
	$lang->component_history = '변경 이력';
	$lang->component_description = '설명';
	$lang->component_extra_vars = '설정 변수';
	$lang->component_grant = '권한설정';
	$lang->content_style = '문서 서식';
	$lang->content_font = '문서 폰트';
	$lang->content_font_size = '문서 폰트 크기';

	$lang->about_component = '컴포넌트 소개';
	$lang->about_component_grant = '기본 컴포넌트외의 확장 컴포넌트 기능을 사용할 수 있는 권한을 지정할 수 있습니다.<br /> (모두 해제 시 아무나 사용 가능합니다)';
	$lang->about_component_mid = '에디터 컴포넌트가 사용될 대상을 지정할 수 있습니다.<br />(모두 해제 시 모든 대상에서 사용 가능합니다)';

	$lang->msg_component_is_not_founded = '%s 에디터 컴포넌트를 찾을 수 없습니다.';
	$lang->msg_component_is_inserted = '선택하신 컴포넌트는 이미 입력되어 있습니다.';
	$lang->msg_component_is_first_order = '선택하신 컴포넌트는 첫 번째에 위치하고 있습니다.';
	$lang->msg_component_is_last_order = '선택하신 컴포넌트는 마지막에 위치하고 있습니다.';
	$lang->msg_load_saved_doc = "자동 저장된 글이 있습니다. 복구하시겠습니까?\n글을 다 쓰신 후 저장하시면 자동 저장 본은 사라집니다.";
	$lang->msg_auto_saved = '자동 저장되었습니다.';

	$lang->cmd_disable = '비활성';
	$lang->cmd_enable = '활성';

	$lang->editor_skin = '에디터 스킨';
	$lang->upload_file_grant = '파일 첨부 권한';
	$lang->enable_default_component_grant = '기본 컴포넌트 사용 권한';
	$lang->enable_component_grant = '컴포넌트 사용 권한';
	$lang->enable_html_grant = 'HTML편집 권한';
	$lang->enable_autosave = '자동저장 사용';
	$lang->height_resizable = '높이 조절 가능';
	$lang->editor_height = '에디터 높이';

	$lang->about_editor_skin = '에디터 스킨을 선택하실 수 있습니다';
	$lang->about_content_style = '문서 편집 및 내용 출력 시 원하는 서식을 지정할 수 있습니다';
	$lang->about_content_font = '문서 편집 및 내용 출력 시 원하는 폰트를 지정할 수 있습니다.<br/>지정하지 않으면 사용자 설정에 따르게 됩니다<br/> ,(콤마)로 여러 폰트를 지정할 수 있습니다.';
	$lang->about_content_font_size = '문서 편집 및 내용 출력 시 원하는 폰트의 크기를 지정할 수 있습니다.<br/>12px, 1em등 단위까지 포함해서 입력해주세요.';
	$lang->about_upload_file_grant = '파일을 첨부할 수 있는 권한을 지정하실 수 있습니다. (모두 해제 시 아무나 첨부 가능합니다)';
	$lang->about_default_component_grant = '에디터에서 사용되는 기본 컴포넌트를 사용할 수 있는 권한을 지정할 수 있습니다. (모두 해제 시 아무나 사용 가능합니다)';
	$lang->about_editor_height = '에디터의 기본 높이를 지정하실 수 있습니다.';
	$lang->about_editor_height_resizable = '에디터의 높이를 직접 변경할 수 있도록 허용합니다.';
	$lang->about_enable_html_grant = 'HTML편집 권한을 부여할 수 있습니다.';
	$lang->about_enable_autosave = '글 작성 시 자동 저장 기능을 활성화 시킬 수 있습니다.';

	$lang->edit->fontname = '글꼴';
	$lang->edit->fontsize = '크기';
	$lang->edit->use_paragraph = '문단기능';
	$lang->edit->fontlist = array(
	'Dotum' => '돋움',
	'Gulim' => '굴림',
	'Batang' => '바탕',
	'Arial' => 'Arial',
	'Arial Black' => 'Arial Black',
	'Tahoma' => 'Tahoma',
	'Verdana' => 'Verdana',
	'Sans-serif' => 'Sans-serif',
	'Serif' => 'Serif',
	'Monospace' => 'Monospace',
	'Cursive' => 'Cursive',
	'Fantasy' => 'Fantasy',
	);

	$lang->edit->header = '형식';
	$lang->edit->header_list = array(
	'h1' => '제목 1',
	'h2' => '제목 2',
	'h3' => '제목 3',
	'h4' => '제목 4',
	'h5' => '제목 5',
	'h6' => '제목 6',
	);

	$lang->edit->submit = '확인';

	$lang->edit->fontcolor = '글자 색';
	$lang->edit->fontbgcolor = '글자 배경색';
	$lang->edit->bold = '진하게';
	$lang->edit->italic = '기울임';
	$lang->edit->underline = '밑줄';
	$lang->edit->strike = '취소선';
	$lang->edit->sup = '위 첨자';
	$lang->edit->sub = '아래 첨자';
	$lang->edit->redo = '다시 실행';
	$lang->edit->undo = '되돌리기';
	$lang->edit->align_left = '왼쪽 정렬';
	$lang->edit->align_center = '가운데 정렬';
	$lang->edit->align_right = '오른쪽 정렬';
	$lang->edit->align_justify = '양쪽 정렬';
	$lang->edit->add_indent = '들여쓰기';
	$lang->edit->remove_indent = '내어쓰기';
	$lang->edit->list_number = '번호 매기기';
	$lang->edit->list_bullet = '목록';
	$lang->edit->remove_format = '글맵시 지우기';

	$lang->edit->help_remove_format = '선택된 영역 내의 태그를 지웁니다.';
	$lang->edit->help_strike_through = '글자에 취소선을 표시합니다.';
	$lang->edit->help_align_full = '좌우 폭에 맞게 정렬을 합니다.';

	$lang->edit->help_fontcolor = '글자의 색상을 지정합니다.';
	$lang->edit->help_fontbgcolor = '글자의 배경색상을 지정합니다.';
	$lang->edit->help_bold = '글자를 진하게 합니다.';
	$lang->edit->help_italic = '글자를 기울이게 합니다.';
	$lang->edit->help_underline = '밑줄을 긋습니다.';
	$lang->edit->help_strike = '취소선을 긋습니다.';
	$lang->edit->help_sup = '위 첨자를 입력합니다.';
	$lang->edit->help_sub = '아래 첨자를 입력합니다.';
	$lang->edit->help_redo = '다음 동작으로 돌아갑니다.';
	$lang->edit->help_undo = '이전 동작으로 돌아갑니다.';
	$lang->edit->help_align_left = '왼쪽 정렬을 합니다.';
	$lang->edit->help_align_center = '가운데 정렬을 합니다.';
	$lang->edit->help_align_right = '오른쪽 정렬을 합니다.';
	$lang->edit->help_align_justify = '양쪽 정렬을 합니다.';
	$lang->edit->help_add_indent = '단락의 들여쓰기 수준을 높입니다.';
	$lang->edit->help_remove_indent = '단락의 들여쓰기 수준을 낮춥니다.';
	$lang->edit->help_list_number = '숫자로된 목록을 적용합니다.';
	$lang->edit->help_list_bullet = '기호로된 목록을 적용합니다.';
	$lang->edit->help_use_paragraph = '문단 나누기를 하시려면 Ctrl+Enter를 누르시면 됩니다. (글 작성완료 후 Alt+S를 누르면 저장이 됩니다.)';

	$lang->edit->url = '링크';
	$lang->edit->blockquote = '인용문';
	$lang->edit->table = '표';
	$lang->edit->image = '그림';
	$lang->edit->multimedia = '동영상';
	$lang->edit->emoticon = '이모티콘';

	$lang->edit->upload = '첨부';
	$lang->edit->upload_file = '파일 첨부';
	$lang->edit->link_file = '본문 삽입';
	$lang->edit->delete_selected = '선택 삭제';

	$lang->edit->icon_align_article = '한 문단을 차지';
	$lang->edit->icon_align_left = '글의 왼쪽으로';
	$lang->edit->icon_align_middle = '가운데 정렬';
	$lang->edit->icon_align_right = '글의 우측으로';

	$lang->about_dblclick_in_editor = '배경, 글자, 이미지, 인용문등에서 더블클릭을 하시면 상세한 컴포넌트 설정이 가능합니다.';

	$lang->edit->rich_editor = '스타일 편집기';
	$lang->edit->html_editor = 'HTML 편집기';
	$lang->edit->extension ='확장 컴포넌트';
	$lang->edit->help = '도움말';
	$lang->edit->help_command = '단축키 안내';

	$lang->edit->lineheight = '줄 간격';
	$lang->edit->fontbgsampletext = '가나다';

	$lang->edit->hyperlink = '하이퍼링크';
	$lang->edit->target_blank = '새 창으로';

	$lang->edit->quotestyle1 = '왼쪽 실선';
	$lang->edit->quotestyle2 = '인용 부호';
	$lang->edit->quotestyle3 = '실선';
	$lang->edit->quotestyle4 = '실선 + 배경';
	$lang->edit->quotestyle5 = '굵은 실선';
	$lang->edit->quotestyle6 = '점선';
	$lang->edit->quotestyle7 = '점선 + 배경';
	$lang->edit->quotestyle8 = '적용 취소';


	$lang->edit->jumptoedit = '편집 도구모음 건너뛰기';
	$lang->edit->set_sel = '칸 수 지정';
	$lang->edit->row = '행';
	$lang->edit->col = '열';
	$lang->edit->add_one_row = '1행 추가';
	$lang->edit->del_one_row = '1행 삭제';
	$lang->edit->add_one_col = '1열 추가';
	$lang->edit->del_one_col = '1열 삭제';

	$lang->edit->table_config = '표 속성 지정';
	$lang->edit->border_width = '테두리 굵기';
	$lang->edit->border_color = '테두리 색';
	$lang->edit->add = '더하기';
	$lang->edit->del = '빼기';
	$lang->edit->search_color = '색상 찾기';
	$lang->edit->table_backgroundcolor = '표 배경색';
	$lang->edit->special_character = '특수문자';
	$lang->edit->insert_special_character = '특수문자 삽입';
	$lang->edit->close_special_character = '특수문자 레이어 닫기';
	$lang->edit->symbol = '일반기호';
	$lang->edit->number_unit = '숫자와 단위';
	$lang->edit->circle_bracket = '원, 괄호';
	$lang->edit->korean = '한글';
	$lang->edit->greece = '그리스';
	$lang->edit->Latin  = '라틴어';
	$lang->edit->japan  = '일본어';
	$lang->edit->selected_symbol  = '선택한 기호';

	$lang->edit->search_replace  = '찾기/바꾸기';
	$lang->edit->close_search_replace  = '찾기/바꾸기 레이어 닫기';
	$lang->edit->replace_all  = '모두 바꾸기';
	$lang->edit->search_words  = '찾을 단어';
	$lang->edit->replace_words  = '바꿀 단어';
	$lang->edit->next_search_words  = '다음 찾기';
	$lang->edit->edit_height_control  = '입력창 크기 조절';
	
	$lang->edit->merge_cells = '셀 병합';
	$lang->edit->split_row = '행 분할';
	$lang->edit->split_col = '열 분할';
	
	$lang->edit->toggle_list   = '목록 접기/펼치기';
	$lang->edit->minimize_list = '최소화';
	
	$lang->edit->move = '이동';
	$lang->edit->refresh = '새로 고침';
	$lang->edit->materials = '글감 보관함';
	$lang->edit->temporary_savings = '임시 저장 목록';

	$lang->edit->paging_prev = '이전';
	$lang->edit->paging_next = '다음';
	$lang->edit->paging_prev_help = '이전 페이지로 이동합니다.';
	$lang->edit->paging_next_help = '다음 페이지로 이동합니다.';

	$lang->edit->toc = '목차';
	$lang->edit->close_help = '도움말 닫기';

	$lang->edit->confirm_submit_without_saving = '저장하지 않은 단락이 있습니다.\\n그냥 전송하시겠습니까?';

	$lang->edit->image_align = '이미지 정렬';
	$lang->edit->attached_files = '첨부 파일';
?>