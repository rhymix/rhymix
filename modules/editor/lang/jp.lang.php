<?php
    /**
     * @file   modules/editor/lang/jp.lang.php
     * @author zero <zero@nzeo.com> 翻訳：RisaPapa、ミニミ
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
    'MS PGothic'=>'ＭＳ Ｐゴシック',
    'MS PMincho'=>'ＭＳ Ｐ明朝',
    'MS UI Gothic'=>'MS UI Gothic',
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
    $lang->edit->fontbgcolor = 'ハイライト カラー';
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

    $lang->edit->upload = '添付';
    $lang->edit->upload_file = 'ファイル添付';
    $lang->edit->link_file = 'テキスト挿入';
    $lang->edit->delete_selected = '選択リスト削除';

    $lang->edit->icon_align_article = '一段落';
    $lang->edit->icon_align_left = '左揃え';
    $lang->edit->icon_align_middle = '中央揃え';
    $lang->edit->icon_align_right = '右揃え';

    $lang->about_dblclick_in_editor = '背景、文字、イメージ、引用文の上にカーソルを合わせ、ダブルクリックすると詳細設定できるコンポーネントを表示します。';


    $lang->edit->rich_editor = 'ウイジウイグ編集';
    $lang->edit->html_editor = 'HTMLタグ編集';
    $lang->edit->extension ='拡張コンポーネント';
    $lang->edit->help = 'ヘルプ';
    $lang->edit->help_command = 'ショートカット‐キーの説明';

?>
