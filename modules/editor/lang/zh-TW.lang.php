<?php
    /**
     * @file   modules/editor/lang/zh-TW.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  網頁編輯器(editor) 模組語言包
     **/

    $lang->editor = "網頁編輯器";
    $lang->component_name = "組件";
    $lang->component_version = "版本";
    $lang->component_author = "作者";
    $lang->component_link = "連結";
    $lang->component_date = "編寫日期";
    $lang->component_license = '更新記錄';
    $lang->component_history = "紀錄";
    $lang->component_description = "說明";
    $lang->component_extra_vars = "變數設置";
    $lang->component_grant = "權限設置"; 

    $lang->about_component = "組件簡介";
    $lang->about_component_grant = '除預設組件外，可設置延伸組件的使用權限<br />(全部解除時，任何用戶都可使用)。';
    $lang->about_component_mid = "可以指定使用編輯器組件的對象。<br />(全部解除時，任何用戶都可使用)。";

    $lang->msg_component_is_not_founded = '找不到%s 組件說明！';
    $lang->msg_component_is_inserted = '您選擇的組件已插入！';
    $lang->msg_component_is_first_order = '您選擇的組件已達最頂端位置！';
    $lang->msg_component_is_last_order = '您選擇的組件已達最底端位置！';
    $lang->msg_load_saved_doc = "有自動儲存的內容， 確定要恢復嗎？\n儲存內容後，自動儲存的內容將會被刪除。";
    $lang->msg_auto_saved = "已自動儲存！";

    $lang->cmd_disable = "暫停";
    $lang->cmd_enable = "啟動";

    $lang->editor_skin = '編輯器面版';
    $lang->upload_file_grant = '檔案上傳權限'; 
    $lang->enable_default_component_grant = '預設組件使用權限';
    $lang->enable_component_grant = '組件使用權限';
    $lang->enable_html_grant = 'HTML編輯權限';
    $lang->enable_autosave = '內容自動儲存';
    $lang->height_resizable = '高度調整';
    $lang->editor_height = '編輯器高度';

    $lang->about_editor_skin = '選擇編輯器面版。';
    $lang->about_upload_file_grant = '可以設置上傳檔案的權限(全部解除為無限制)。';
    $lang->about_default_component_grant = '可以設置編輯器預設組件的使用權限(全部解除為無限制)。';
    $lang->about_editor_height = '可以指定編輯器的預設高度。';
    $lang->about_editor_height_resizable = '允許用戶拖曳編輯器高度。';
    $lang->about_enable_html_grant = 'HTML代碼編輯權限設置。';
    $lang->about_enable_autosave = '發表主題時，啟動內容自動儲存功能。';

    $lang->edit->fontname = '字體';
    $lang->edit->fontsize = '大小';
    $lang->edit->use_paragraph = '段落功能';
    $lang->edit->fontlist = array(


    "標楷體",
    "細明體",
    "times",
    "Courier",
    "Tahoma",
    "Arial",
    );

    $lang->edit->header = "樣式";
    $lang->edit->header_list = array(
    "h1" => "標題 1",
    "h2" => "標題 2",
    "h3" => "標題 3",
    "h4" => "標題 4",
    "h5" => "標題 5",
    "h6" => "標題 6",
    );

    $lang->edit->submit = '確認';

	$lang->edit->help_remove_format = "刪除所選區域的標籤";
    $lang->edit->help_strike_through = "文字刪除線";
    $lang->edit->help_align_full = "左右對齊";

    $lang->edit->help_fontcolor = "文字顏色";
    $lang->edit->help_fontbgcolor = "背景顏色";
    $lang->edit->help_bold = "粗體";
    $lang->edit->help_italic = "斜體";
    $lang->edit->help_underline = "底線";
    $lang->edit->help_strike = "虛線";
    $lang->edit->help_redo = "重新操作";
    $lang->edit->help_undo = "返回操作";
    $lang->edit->help_align_left = "靠左對齊";
    $lang->edit->help_align_center = "置中對齊";
    $lang->edit->help_align_right = "靠右對齊";
    $lang->edit->help_add_indent = "縮排";
    $lang->edit->help_remove_indent = "移除縮排";
    $lang->edit->help_list_number = "編號";
    $lang->edit->help_list_bullet = "清單符號";
    $lang->edit->help_use_paragrapth = "換段落請按 ctrl+backspace． (發表主題快捷鍵：alt＋S)";

    $lang->edit->upload = '上傳';
    $lang->edit->upload_file = '上傳附件'; 
    $lang->edit->link_file = '插入內容';
    $lang->edit->delete_selected = '刪除所選';

    $lang->edit->icon_align_article = '段落';
    $lang->edit->icon_align_left = '文字左側';
    $lang->edit->icon_align_middle = '置中對齊';
    $lang->edit->icon_align_right = '文字右側';

    $lang->about_dblclick_in_editor = '雙擊背景, 文字, 圖片, 引用，即可對其相關組件進行詳細設置。';
?>
