<?php
    /**
     * @file   modules/editor/lang/zh-TW.lang.php
     * @author zero <zero@nzeo.com> 翻譯：royallin
     * @brief  網頁編輯器(editor)模組正體中文語言
     **/

    $lang->editor = '網頁編輯器';
    $lang->component_name = '組件';
    $lang->component_version = '版本';
    $lang->component_author = '作者';
    $lang->component_link = '連結';
    $lang->component_date = '編寫日期';
    $lang->component_license = '版權';
    $lang->component_history = '紀錄';
    $lang->component_description = '說明';
    $lang->component_extra_vars = '變數設置';
    $lang->component_grant = '權限設置'; 
    $lang->content_style = 'Content Style';
    $lang->content_font = 'Content Font';

    $lang->about_component = '組件簡介';
    $lang->about_component_grant = '除預設組件外，可設置延伸組件的使用權限<br />(全部解除時，任何用戶都可使用)。';
    $lang->about_component_mid = '可以指定使用編輯器組件的對象。<br />(全部解除時，任何用戶都可使用)。';

    $lang->msg_component_is_not_founded = '找不到%s 組件說明！';
    $lang->msg_component_is_inserted = '您選擇的組件已插入！';
    $lang->msg_component_is_first_order = '您選擇的組件已達最頂端位置！';
    $lang->msg_component_is_last_order = '您選擇的組件已達最底端位置！';
    $lang->msg_load_saved_doc = "有自動儲存的內容，確定要恢復嗎?\n儲存內容後，自動儲存的內容將會被刪除。";
    $lang->msg_auto_saved = '已自動儲存！';

    $lang->cmd_disable = '暫停';
    $lang->cmd_enable = '啟動';

    $lang->editor_skin = '編輯器面板';
    $lang->upload_file_grant = '檔案上傳權限'; 
    $lang->enable_default_component_grant = '預設組件使用權限';
    $lang->enable_component_grant = '組件使用權限';
    $lang->enable_html_grant = 'HTML編輯權限';
    $lang->enable_autosave = '內容自動儲存';
    $lang->height_resizable = '高度調整';
    $lang->editor_height = '編輯器高度';

    $lang->about_editor_skin = '選擇編輯器面板。';
    $lang->about_content_style = '문서 편집 및 내용 출력시 원하는 서식을 지정할 수 있습니다';
    $lang->about_content_font = '문서 편집 및 내용 출력시 원하는 폰트를 지정할 수 있습니다.<br/>지정하지 않으면 사용자 설정에 따르게 됩니다<br/> ,(콤마)로 여러 폰트를 지정할 수 있습니다.';
    $lang->about_upload_file_grant = '設置上傳檔案的權限(全部解除為無限制)。';
    $lang->about_default_component_grant = '設置編輯器預設組件的使用權限(全部解除為無限制)。';
    $lang->about_editor_height = '指定編輯器的預設高度。';
    $lang->about_editor_height_resizable = '允許用戶拖曳編輯器高度。';
    $lang->about_enable_html_grant = 'HTML原始碼編輯權限設置。';
    $lang->about_enable_autosave = '發表主題時，開啟內容自動儲存功能。';

    $lang->edit->fontname = '字體';
    $lang->edit->fontsize = '大小';
    $lang->edit->use_paragraph = '段落功能';
    $lang->edit->fontlist = array(
    '新細明體'=>'新細明體',
    '標楷體'=>'標楷體',
    '細明體'=>'細明體',
    'Arial'=>'Arial',
    'Arial Black'=>'Arial Black',
    'Tahoma'=>'Tahoma',
    'Verdana'=>'Verdana',
    'Sans-serif'=>'Sans-serif',
    'Serif'=>'Serif',
    'Monospace'=>'Monospace',
    'Cursive'=>'Cursive',
    'Fantasy'=>'Fantasy',
    );

    $lang->edit->header = '樣式';
    $lang->edit->header_list = array(
    'h1' => '標題 1',
    'h2' => '標題 2',
    'h3' => '標題 3',
    'h4' => '標題 4',
    'h5' => '標題 5',
    'h6' => '標題 6',
    );

    $lang->edit->submit = '確認';

    $lang->edit->fontcolor = '文字顏色';
    $lang->edit->fontbgcolor = '背景顏色';
    $lang->edit->bold = '粗體';
    $lang->edit->italic = '斜體';
    $lang->edit->underline = '底線';
    $lang->edit->strike = '虛線';
    $lang->edit->sup = '上標';
    $lang->edit->sub = '下標';
    $lang->edit->redo = '重新操作';
    $lang->edit->undo = '返回操作';
    $lang->edit->align_left = '靠左對齊';
    $lang->edit->align_center = '置中對齊';
    $lang->edit->align_right = '靠右對齊';
    $lang->edit->align_justify = '左右對齊';
    $lang->edit->add_indent = '縮排';
    $lang->edit->remove_indent = '凸排';
    $lang->edit->list_number = '編號';
    $lang->edit->list_bullet = '清單符號';
    $lang->edit->remove_format = '移除格式';

    $lang->edit->help_remove_format = '移除格式';
    $lang->edit->help_strike_through = '文字刪除線';
    $lang->edit->help_align_full = '左右對齊';

    $lang->edit->help_fontcolor = '文字顏色';
    $lang->edit->help_fontbgcolor = '背景顏色';
    $lang->edit->help_bold = '粗體';
    $lang->edit->help_italic = '斜體';
    $lang->edit->help_underline = '底線';
    $lang->edit->help_strike = '虛線';
    $lang->edit->help_sup = '上標';
    $lang->edit->help_sub = '下標';
    $lang->edit->help_redo = '重新操作';
    $lang->edit->help_undo = '返回操作';
    $lang->edit->help_align_left = '靠左對齊';
    $lang->edit->help_align_center = '置中對齊';
    $lang->edit->help_align_right = '靠右對齊';
    $lang->edit->help_add_indent = '縮排';
    $lang->edit->help_remove_indent = '凸排';
    $lang->edit->help_list_number = '編號';
    $lang->edit->help_list_bullet = '清單符號';
    $lang->edit->help_use_paragraph = '換行請按 Ctrl+Backspace (快速發表主題：Alt+S)';

    $lang->edit->url = '連結';
    $lang->edit->blockquote = '引用';
    $lang->edit->table = '表格';
    $lang->edit->image = '圖片';
    $lang->edit->multimedia = '影片';
    $lang->edit->emoticon = '表情符號';

    $lang->edit->upload = '上傳';
    $lang->edit->upload_file = '上傳附檔';
    $lang->edit->link_file = '插入圖片';
    $lang->edit->delete_selected = '刪除所選';

    $lang->edit->icon_align_article = '段落';
    $lang->edit->icon_align_left = '靠左';
    $lang->edit->icon_align_middle = '置中';
    $lang->edit->icon_align_right = '靠右';

    $lang->about_dblclick_in_editor = '對背景，文字，圖片，引用等組件按兩下，即可對其相關組件進行詳細設置。';


    $lang->edit->rich_editor = '所見即得';
    $lang->edit->html_editor = 'HTML';
    $lang->edit->extension ='延伸組件';
    $lang->edit->help = '使用說明';
    $lang->edit->help_command = '快速鍵指引';
    
    $lang->edit->lineheight = '줄간격';
	$lang->edit->fontbgsampletext = '가나다';
	
	$lang->edit->hyperlink = '하이퍼링크';
	$lang->edit->target_blank = '새창으로';
	
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
    $lang->edit->add_one_row = '1행추가';
    $lang->edit->del_one_row = '1행삭제';
    $lang->edit->add_one_col = '1열추가';
    $lang->edit->del_one_col = '1열삭제';

    $lang->edit->table_config = '표 속성 지정';
    $lang->edit->border_width = '테두리 굵기';
    $lang->edit->border_color = '테두리 색';
    $lang->edit->add = '더하기';
    $lang->edit->del = '빼기';
    $lang->edit->search_color = '색상찾기';
    $lang->edit->table_backgroundcolor = '표 배경색';
    $lang->edit->special_character = '특수문자';
    $lang->edit->insert_special_character = '특수문자 삽입';
    $lang->edit->close_special_character = '특수문자 레이어 닫기';
    $lang->edit->symbol = '일반기호';
    $lang->edit->number_unit = '숫자와 단위';
    $lang->edit->circle_bracket = '원,괄호';
    $lang->edit->korean = '한글';
    $lang->edit->greece = '그리스';
    $lang->edit->Latin  = '라틴어';
    $lang->edit->japan  = '일본어';
    $lang->edit->selected_symbol  = '선택한 기호';

    $lang->edit->search_replace  = '찾기/바꾸기';
    $lang->edit->close_search_replace  = '찾기/바꾸기 레이어 닫기';
    $lang->edit->replace_all  = '모두바꾸기';
    $lang->edit->search_words  = '찾을단어';
    $lang->edit->replace_words  = '바꿀단어';
    $lang->edit->next_search_words  = '다음찾기';
    $lang->edit->edit_height_control  = '입력창 크기 조절';

	$lang->edit->merge_cells = '셀 병합';
    $lang->edit->split_row = '행 분할';
    $lang->edit->split_col = '열 분할';
?>
