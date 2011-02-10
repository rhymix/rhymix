<?php
    /**
     * @file   modules/editor/lang/jp.lang.php
     * @author NHN (developers@xpressengine.com) 翻訳：RisaPapa、ミニミ
     * @brief  ウィジウィグエディター（editor）モジュールの基本言語パッケージ
     **/

	$lang->editor = 'ウイジウイグエディター';
	$lang->component_name = 'コンポーネント';
	$lang->component_version = 'バージョン';
	$lang->component_author = '作者';
	$lang->component_link = 'リンク';
	$lang->component_date = '作成日';
	$lang->component_license = 'ライセンス';
	$lang->component_history = '変更履歴';
	$lang->component_description = '説明';
	$lang->component_extra_vars = '設定変数';
	$lang->component_grant = '権限設定';
	$lang->content_style = 'コンテンツスタイル';
	$lang->content_font = 'コンテンツフォント';
	$lang->content_font_size = 'コンテンツフォントサイズ';

	$lang->about_component = 'コンポーネント情報';
	$lang->about_component_grant = '基本コンポーネント以外の拡張コンポーネント機能が利用可能な権限の設定が出来ます。<br />(選択なしの場合、誰でも利用可能)';
	$lang->about_component_mid = 'エディターコンポーネントが使われる対象を指定します。<br />(選択なしの場合、全ての対象で利用可能)';

	$lang->msg_component_is_not_founded = '%s エディターのコンポーネントが見つかりません。';
	$lang->msg_component_is_inserted = '選択されたコンポーネントは既に入力されています。';
	$lang->msg_component_is_first_order = '選択されたコンポーネントは最初に位置しています。';
	$lang->msg_component_is_last_order = '選択されたコンポーネントは最後に位置しています。';
	$lang->msg_load_saved_doc = "自動保存された書き込みがあります。復旧しますか？\n書き終わってから登録すると前の自動保存データは削除されます。";
	$lang->msg_auto_saved = '自動保存されました。';

	$lang->cmd_disable = '未使用';
	$lang->cmd_enable = '使用';

	$lang->editor_skin = 'エディタースキン';
	$lang->upload_file_grant = 'ファイル添付権限';
	$lang->enable_default_component_grant = '基本コンポーネント使用権限';
	$lang->enable_component_grant = 'コンポーネント使用権限';
	$lang->enable_html_grant = 'HTML編集権限';
	$lang->enable_autosave = '自動保存使用';
	$lang->height_resizable = '高さの調整';
	$lang->editor_height = 'エディターの高さ';

	$lang->about_editor_skin = 'エディターのスキンの選択が出来ます。';
	$lang->about_content_style = 'コンテンツの編集、および内容表示の際のスタイルを指定します。';
	$lang->about_content_font = 'コンテンツの編集、および内容表示の際のフォントを指定します。<br/>指定してない場合、ユーザーの設定を従います。<br/> 半角コンマ（,）区切りで複数フォントの登録が出来ます。';
	$lang->about_content_font_size = 'コンテンツの編集、および内容表示の際のフォントサイズを指定します。<br/>12px、1emなどサイズ単位まで入力して下さい。';
	$lang->about_upload_file_grant = 'ファイル添付可能な権限の設定が出来ます。(選択なしの場合、誰でも添付が可能)';
	$lang->about_default_component_grant = 'エディターでの基本コンポーネントを使用可能な権限の設定が出来ます。(選択なしの場合、誰でも利用可能)';
	$lang->about_editor_height = 'エディターの基本高さを設定します。';
	$lang->about_editor_height_resizable = 'エディターの高さを変更出来るようにします。';
	$lang->about_enable_html_grant = 'HTML編集権限を設定します。';
	$lang->about_enable_autosave = '書き込みのとき、自動保存機能をオンにします。';

	$lang->edit->fontname = 'フォント';
	$lang->edit->fontsize = 'フォントサイズ';
	$lang->edit->use_paragraph = '段落機能';
	$lang->edit->fontlist = array(
	'MS PGothic' => 'ＭＳ Ｐゴシック',
	'MS PMincho' => 'ＭＳ Ｐ明朝',
	'MS UI Gothic' => 'MS UI Gothic',
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

	$lang->edit->header = '見出し';
	$lang->edit->header_list = array(
	'h1' => '見出し１',
	'h2' => '見出し２',
	'h3' => '見出し３',
	'h4' => '見出し４',
	'h5' => '見出し５',
	'h6' => '見出し６',
	);

	$lang->edit->submit = '送信';

	$lang->edit->fontcolor = 'テキストの色';
	$lang->edit->fontcolor_apply = 'テキスト色適用';
	$lang->edit->fontcolor_more = '他のテキスト色';
	$lang->edit->fontbgcolor = 'テキストの背景色';
	$lang->edit->fontbgcolor_apply = 'テキスト背景色適用';
	$lang->edit->fontbgcolor_more = '他のテキスト背景色';
	$lang->edit->bold = '太字';
	$lang->edit->italic = '斜体';
	$lang->edit->underline = '下線';
	$lang->edit->strike = '取り消し線';
	$lang->edit->sup = '上付き文字';
	$lang->edit->sub = '下付き文字';
	$lang->edit->redo = '繰り返し';
	$lang->edit->undo = '元に戻す';
	$lang->edit->align_left = '左揃え';
	$lang->edit->align_center = '中央揃え';
	$lang->edit->align_right = '右揃え';
	$lang->edit->align_justify = '均等割付';
	$lang->edit->add_indent = 'インデント増';
	$lang->edit->remove_indent = 'インデント減';
	$lang->edit->list_number = '番号付リスト';
	$lang->edit->list_bullet = '箇条書き';
	$lang->edit->remove_format = '書式をクリア';

	$lang->edit->help_remove_format = '選択領域の中のタグを消します。';
	$lang->edit->help_strike_through = 'テキストに取り消し線を表示します。';
	$lang->edit->help_align_full = '左右の余白に合わせて文字列を配置します。';

	$lang->edit->help_fontcolor = 'テキストの色を指定します。';
	$lang->edit->help_fontbgcolor = 'テキストの背景色を指定します。';
	$lang->edit->help_bold = 'テキストを太字に指定します。';
	$lang->edit->help_italic = 'テキストを斜体にします。';
	$lang->edit->help_underline = 'テキストに下線（アンダーライン）を引きます。';
	$lang->edit->help_strike = '取り消し線を引きます。';
	$lang->edit->help_sup = '上付き文字';
	$lang->edit->help_sub = '下付き文字';
	$lang->edit->help_redo = '繰り返し';
	$lang->edit->help_undo = '元に戻す';
	$lang->edit->help_align_left = 'テキストを左揃えで表示します。';
	$lang->edit->help_align_center = 'テキストを中央揃えで表示します。';
	$lang->edit->help_align_right = 'テキストを右揃えで表示します。';
	$lang->edit->help_align_justify = 'テキストを両端揃えで表示します。';
	$lang->edit->help_add_indent = 'インデントを増やします。';
	$lang->edit->help_remove_indent = 'インデントを減らします。';
	$lang->edit->help_list_number = '番号付リスト';
	$lang->edit->help_list_bullet = '箇条書き';
	$lang->edit->help_use_paragraph = '段落機能を使用する場合は、「Ctrl+Enter」を押します（書き終わった後、「Alt+S」を押すと保存されます）。';

	$lang->edit->url = 'リンク';
	$lang->edit->blockquote = '引用文';
	$lang->edit->table = '表';
	$lang->edit->image = 'イメージ';
	$lang->edit->multimedia = '動画';
	$lang->edit->emoticon = '絵文字';

	$lang->edit->file = 'ファイル';
	$lang->edit->upload = '添付';
	$lang->edit->upload_file = 'ファイル添付';
	$lang->edit->upload_list = '添付リスト';
	$lang->edit->link_file = 'テキスト挿入';
	$lang->edit->delete_selected = '選択リスト削除';

	$lang->edit->icon_align_article = '一段落';
	$lang->edit->icon_align_left = '左揃え';
	$lang->edit->icon_align_middle = '中央揃え';
	$lang->edit->icon_align_right = '右揃え';

	$lang->about_dblclick_in_editor = '背景、文字、イメージ、引用文の上にカーソルを合わせ、ダブルクリックすると詳細設定出来るコンポーネントを表示します。';

	$lang->edit->rich_editor = 'ウイジウイグ編集';
	$lang->edit->html_editor = 'HTMLタグ編集';
	$lang->edit->extension ='拡張コンポーネント';
	$lang->edit->help = 'ヘルプ';
	$lang->edit->help_command = 'ショートカット‐キーの説明';

	$lang->edit->lineheight = '行間';
	$lang->edit->fontbgsampletext = 'あいうえお';

	$lang->edit->hyperlink = 'ハイパーリンク';
	$lang->edit->target_blank = '別のウィンドウズで';

	$lang->edit->quotestyle1 = '左側実線';
	$lang->edit->quotestyle2 = '引用記号';
	$lang->edit->quotestyle3 = '実線';
	$lang->edit->quotestyle4 = '実線 + 背景';
	$lang->edit->quotestyle5 = '太い実線';
	$lang->edit->quotestyle6 = '点線';
	$lang->edit->quotestyle7 = '点線 + 背景';
	$lang->edit->quotestyle8 = '適用取り消し';


	$lang->edit->jumptoedit = '編集ツール省略';
	$lang->edit->set_sel = 'マス数の指定';
	$lang->edit->row = '行';
	$lang->edit->col = '列';
	$lang->edit->add_one_row = '1行追加';
	$lang->edit->del_one_row = '1行削除';
	$lang->edit->add_one_col = '1列追加';
	$lang->edit->del_one_col = '1列削除';

	$lang->edit->table_config = 'テーブル属性の設定';
	$lang->edit->border_width = '外枠太さ';
	$lang->edit->border_color = '外枠色';
	$lang->edit->add = '挿入';
	$lang->edit->del = '削除';
	$lang->edit->search_color = 'その他の色';
	$lang->edit->table_backgroundcolor = '表の背景色';
	$lang->edit->special_character = '特殊文字';
	$lang->edit->insert_special_character = '特殊文字挿入';
	$lang->edit->close_special_character = '特殊文字レイヤーを閉じる';
	$lang->edit->symbol = '一般記号';
	$lang->edit->number_unit = '数字と単位';
	$lang->edit->circle_bracket = '円、括弧';
	$lang->edit->korean = '韓国語';
	$lang->edit->greece = 'ギリシャ語';
	$lang->edit->Latin  = 'ラテン語';
	$lang->edit->japan  = '日本語';
	$lang->edit->selected_symbol  = '選択した記号';

	$lang->edit->search_replace  = '検索/置換';
	$lang->edit->close_search_replace  = '検索/置換レイヤーを閉じる';
	$lang->edit->replace_all  = 'すべて置換';
	$lang->edit->search_words  = '検索テキスト';
	$lang->edit->replace_words  = '置換テキスト';
	$lang->edit->next_search_words  = '次を検索';
	$lang->edit->edit_height_control  = '入力サイズ調整';
	
	$lang->edit->merge_cells = 'セルの結合';
	$lang->edit->split_row = '行の挿入';
	$lang->edit->split_col = '列の挿入';
	
	$lang->edit->toggle_list   = 'リストを折りたたむ/展開する';
	$lang->edit->minimize_list = '最小化';
	
	$lang->edit->move = '移動';
	$lang->edit->refresh = '再読み込み';
	$lang->edit->materials = '資料箱';
	$lang->edit->temporary_savings = '下書きリスト';

	$lang->edit->paging_prev = '前へ';
	$lang->edit->paging_next = '次へ';
	$lang->edit->paging_prev_help = '前のページへ移動します。';
	$lang->edit->paging_next_help = '次のページへ移動します。';

	$lang->edit->toc = 'リスト';
	$lang->edit->close_help = 'ヘルプを閉じる';

	$lang->edit->confirm_submit_without_saving = 'まだ保存してない内容があります。\\nそのまま転送して宜しいでしょうか？';

	$lang->edit->image_align = 'イメージの配置';
	$lang->edit->attached_files = '添付ファイル';

	$lang->edit->fontcolor_input = 'テキスト色直接入力';
	$lang->edit->fontbgcolor_input = 'テキスト背景色直接入力';
	$lang->edit->pangram = '무궁화 꽃이 피었습니다';

	$lang->edit->table_caption_position = 'キャプションの配置';
	$lang->edit->table_caption = '表のキャプション';
	$lang->edit->table_header = '머리글 셀(th)';
	$lang->edit->table_header_none = 'なし';
	$lang->edit->table_header_left = '左';
	$lang->edit->table_header_top = '上';
	$lang->edit->table_header_both = '両方';
	$lang->edit->table_size = '表の大きさ';
	$lang->edit->table_width = '表幅';

	$lang->edit->upper_left = '上端左';
	$lang->edit->upper_center = '上端中央';
	$lang->edit->upper_right = '上端右';
	$lang->edit->bottom_left = '下端左';
	$lang->edit->bottom_center = '下端中央';
	$lang->edit->bottom_right = '下端右';

	$lang->edit->no_image = '添付されたイメージがありません。';
	$lang->edit->no_multimedia = '添付された動画がありません。';
	$lang->edit->no_attachment = '添付されたファイルがありません。';
	$lang->edit->insert_selected = '選択挿入';
	$lang->edit->delete_selected = '選択削除';

	$lang->edit->fieldset = 'テキストボックス';
	$lang->edit->paragraph = '段落';
	
	$lang->edit->autosave_format = '글을 쓰기 시작한지 <strong>%s</strong>이 지났습니다. 마지막 저장 시간은 <strong>%s</strong> 입니다.';
	$lang->edit->autosave_hour = '%d시간';
	$lang->edit->autosave_hours = '%d시간';
	$lang->edit->autosave_min = '%d분';
	$lang->edit->autosave_mins = '%d분';
	$lang->edit->autosave_hour_ago = '%d시간 전';
	$lang->edit->autosave_hours_ago = '%d시간 전';
	$lang->edit->autosave_min_ago = '%d분 전';
	$lang->edit->autosave_mins_ago = '%d분 전';
	
	$lang->edit->upload_not_enough_quota   = '허용된 용량이 부족하여 파일을 첨부할 수 없습니다.';
?>
