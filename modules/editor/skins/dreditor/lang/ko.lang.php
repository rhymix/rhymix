<?php
	/**
	 * @file   modules/editor/skins/dreditor/lang/ko.lang.php
	 * @author sol <sol@ngleader.com>
	 * @brief  단락에디터 언어팩
	 **/


	$lang->edit->msg_dr_delete_confirm = '선택한 단락을 삭제하시겠습니까?';
	$lang->edit->insert_dr_title = '단락의 제목을 입력해 주세요';
	$lang->edit->richtext_area = '리치 텍스트 쓰기 영역';
	$lang->edit->insert_site_name = '사이트 이름을 입력할 수 있습니다';
	$lang->edit->insert_explain = '설명을 추가로 입력할 수 있습니다';


	$lang->edit->text='텍스트';
	$lang->edit->link = '링크';
	$lang->edit->blockquote = '인용';
	$lang->edit->insert_blockquote = '인용 문구를 입력해 주세요';
	$lang->edit->insert_cite = '출처를 입력할 수 있습니다 <a>, <strong>, <em> 태그를 쓸 수 있습니다';

	$lang->edit->image = '이미지';
	$lang->edit->find_image = '이미지 찾아보기';
	$lang->edit->uploading = '업로드 중입니다';
	$lang->edit->uploading_info = '%s MB 까지 업로드가 가능 합니다';
	$lang->edit->uploaded_image = '사용자 업로드 이미지';

	$lang->edit->image_width = '이미지 너비';
	$lang->edit->resize = '줄이기';
	$lang->edit->resize_info = '지정한 크기의 사본을 만들며 원본은 유지 됩니다';
	$lang->edit->resize_error = '원본 이미지보다 작은 크기의 값을 입력하세요';
	$lang->edit->insert_image_explain = '이미지의 설명을 입력할 수 있습니다. <a>, <strong>, <em> 태그를 쓸 수 있습니다.';

	$lang->edit->mov ='동영상';
	$lang->edit->insert_mov = '<object> 또는 <embed>로 시작하는 멀티미디어 삽입 코드를 넣어주세요';
	$lang->edit->insert_mov_explain = '동영상의 설명을 입력할 수 있습니다. <a>, <strong>, <em> 태그를 쓸 수 있습니다.';

	$lang->edit->file ='파일';
	$lang->edit->file_select = '업로드할 파일을 선택해 주세요';
	$lang->edit->file_uploadinfo = '개당 %sMB, 모두 합쳐 %sMB 까지 업로드가 가능 합니다';
	$lang->edit->file_total ='총 <strong class="filecount">{total_filecount}</strong>개';
	$lang->edit->insert_file_explain ='파일의 설명을 입력할 수 있습니다';

	$lang->edit->hr = '분리선';
	$lang->edit->hr_simple= '단순 가로선';

	$lang->edit->title_title='소제목';
	$lang->edit->title='제목';
	$lang->edit->title1='큰제목';
	$lang->edit->title2='중간제목';
	$lang->edit->title3='작은제목';

	$lang->edit->list = '목록';
	$lang->edit->list_explain = 'CTRL+방향키를 누르면 항목의 위치나 단계를 변경할 수 있습니다.';
	$lang->edit->toc = '목차';
	$lang->edit->toc_explain ='문서 내의 소제목들을 모아 목차로 만들어주며, 자동으로 갱신됩니다.';
	
	$lang->edit->more = '더보기';
	$lang->edit->move_button = '버튼이동';

	$lang->edit->material = '글감';
	$lang->edit->insert = '이 글감을 본문에 넣기';
	$lang->edit->close_materials = '글감 보관함 닫기';
    $lang->edit->no_materials = '보관된 글감이 없습니다.';
    $lang->edit->msg_no_selected_object = '선택된 개체가 없습니다.';
    $lang->edit->msg_insert_value = '값을 입력해 주세요.';
	
	$lang->edit->help_first_title = '도움말';
	$lang->edit->help_shortcut = '단축키';
	$lang->edit->help_bug_report = '글쓰기 오류신고';
	$lang->edit->help_first_dt_1 = '새 단락 쓰기(1~9, 0, `)';
	$lang->edit->help_first_dd_1 = '도구모음 버튼을 클릭함으로써 새 단락 쓰기를 시작할 수 있습니다. 도구모음 버튼에는 배치된 순서대로 왼쪽으로부터 1~9까지 단축키가 할당되어 있습니다. 숫자키 \'0\' 또는 ESC키 바로 아래 위치한 [`] 그레이브(grave) 키를 누르시면 숨겨진 도구모음 버튼을 보거나 토글(toggle)할 수 있습니다.';
	$lang->edit->help_first_dt_2 = '단락 선택(Click, Ctrl+Click, Shift+Click)';
	$lang->edit->help_first_dd_2 = '클릭 또는 클릭 후 방향키(&uarr;&darr;)를 조작함으로써 원하는 단락을 선택할 수 있습니다. 단일선택(Click)과 다중선택(Ctrl+Click, Shift+Click)이 가능합니다';
	$lang->edit->help_first_dt_3 = '단락 편집 및 취소(Enter, Double Click, ESC)';
	$lang->edit->help_first_dd_3 = '단락 선택 후 Enter 또는 더블클릭으로 편집할 수 있습니다. ESC키를 이용하여 편집을 취소할 수 있습니다.';
	$lang->edit->help_first_dt_4 = '단락 이동(Ctrl+&uarr;&darr;, Click-Drag-Drop)';
	$lang->edit->help_first_dd_4 = '단락 선택 후 Ctrl+방향키(&uarr;&darr;)를 조작하거나 \'클릭-드래그-드롭\'으로 이동할 수 있습니다.';
	$lang->edit->help_first_dt_5 = '단락 저장 및 삭제(Ctrl+Enter, Del)';
	$lang->edit->help_first_dd_5 = '\'확인\'버튼을 누르거나 Ctrl+Enter 명령으로 저장할 수 있습니다. Del키를 이용하여 선택된 단락을 삭제할 수 있습니다.';

	$lang->edit->drag_this = '단락을 드래그하여 이동하세요.';
	$lang->edit->cmd_new_window = '새창';
	$lang->edit->cmd_del = '삭제';
?>