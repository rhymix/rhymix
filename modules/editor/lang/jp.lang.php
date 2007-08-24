<?php
    /**
     * @file   modules/editor/lang/jp.lang.php
     * @author zero <zero@nzeo.com> 翻訳：RisaPapa
     * @brief  ウィジウィグエディター（editor）モジュールの基本言語パッケージ
     **/

    $lang->editor = "ウィジウィグエディター";
    $lang->component_name = "コンポネント";
    $lang->component_version = "バージョン";
    $lang->component_author = "作者";
    $lang->component_link = "リンク";
    $lang->component_date = "作成日";
    $lang->component_description = "説明";
    $lang->component_extra_vars = "設定変数";
    $lang->component_grant = "権限設定"; 

    $lang->about_component = "コンポネント情報";
    $lang->about_component_grant = "選択されたグループでのみ使用できます（すべて解除時はすべて使用可能）。";

    $lang->msg_component_is_not_founded = '%s エディターのコンポネントが見つかりません。';
    $lang->msg_component_is_inserted = '選択されたコンポネントは既に入力されています。';
    $lang->msg_component_is_first_order = '選択されたコンポネントは最初に位置しています。';
    $lang->msg_component_is_last_order = '選択されたコンポネントは最後に位置しています。';
    $lang->msg_load_saved_doc = "自動保存された書き込みがあります。復旧しますか？\n書き終わってから保存すると自動保存データは削除されます。";
    $lang->msg_auto_saved = "自動保存されました。";

    $lang->cmd_disable = "未使用";
    $lang->cmd_enable = "使用";

    $lang->edit->fontname = 'フォント';
    $lang->edit->fontsize = 'サイズ';
    $lang->edit->use_paragraph = '段落機能';
    $lang->edit->fontlist = array(
    "ＭＳ Ｐゴシック",
    "ＭＳ Ｐ明朝",
    "Osaka－等幅",
    "ヒラギノ角ゴ Pro W3",
    "times",
    "Courier",
    "Tahoma",
    "Arial",
    );

    $lang->edit->header = "見出し";
    $lang->edit->header_list = array(
    "h1" => "見出し１",
    "h2" => "見出し２",
    "h3" => "見出し３",
    "h4" => "見出し４",
    "h5" => "見出し５",
    "h6" => "見出し６",
    );

    $lang->edit->submit = '送信';

    $lang->edit->help_fontcolor = "テキストの色を指定します。";
    $lang->edit->help_fontbgcolor = "テキストの背景色を指定します。";
    $lang->edit->help_bold = "テキストを太字に指定します。";
    $lang->edit->help_italic = "テキストを斜体にします。";
    $lang->edit->help_underline = "テキストに下線（アンダーライン）を引きます。";
    $lang->edit->help_strike = "打ち消し線を引きます。";
    $lang->edit->help_redo = "直前に取り消した処理をもう一度繰り返して実行します。";
    $lang->edit->help_undo = "直前に行った操作や処理を取り消し元に戻します。";
    $lang->edit->help_align_left = "テキストを左揃えで表示します。";
    $lang->edit->help_align_center = "テキストを中央揃えで表示します。";
    $lang->edit->help_align_right = "テキストを右揃えで表示します。";
    $lang->edit->help_add_indent = "テキストの行頭の位置を右に寄せます。";
    $lang->edit->help_remove_indent = "インデント（字下げ）を除去します。";
    $lang->edit->help_list_number = "リスト項目に数字で順序を付けます。";
    $lang->edit->help_list_bullet = "記号でリスト項目を記述します。";
    $lang->edit->help_use_paragrapth = "段落機能を使用する場合は、「Ctrl+Enter」を押します（書き終わった後、「Alt+S」を押すと保存されます）。";

    $lang->edit->upload = '添付';
    $lang->edit->upload_file = 'ファイル添付'; 
    $lang->edit->link_file = 'テキスト挿入';
    $lang->edit->delete_selected = '選択リスト削除';

    $lang->edit->icon_align_article = '一段落';
    $lang->edit->icon_align_left = '左揃え';
    $lang->edit->icon_align_middle = '中央揃え';
    $lang->edit->icon_align_right = '右揃え';

    $lang->about_dblclick_in_editor = '背景、文字、イメージ、引用文の上にカーソルを合わせ,ダブルクリックすると詳細設定できるコンポーネントを表示します。';
?>
