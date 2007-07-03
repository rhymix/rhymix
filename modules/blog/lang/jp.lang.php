<?php
    /**
     * @file   ko.lang.php
     * @author zero (zero@nzeo.com)　翻訳：keinicht
     * @brief  ブログ(blog) モジュルの基本言語パッケージ
     **/

    // ボタンに使用する言語
    $lang->cmd_blog_list = 'ブログリスト';
    $lang->cmd_module_config = 'ブログ共通設定';
    $lang->cmd_view_info = 'ブログ情報';
    $lang->cmd_manage_menu = 'メニュー管理';
    $lang->cmd_make_child = '下位カテゴリー追加';
    $lang->cmd_enable_move_category = "カテゴリー位置変更（選択後上のメニューをドラッグして下さい）";
    $lang->cmd_remake_cache = 'キャッシュファイル再生性';
    $lang->cmd_layout_setup = 'レイアウト設定';
    $lang->cmd_layout_edit = 'レイアウト編集';

    // 項目
    $lang->parent_category_name = '上位カテゴリー名';
    $lang->category_name = '分類名';
    $lang->expand = '開く';
    $lang->category_group_srls = 'グループ制限';
    $lang->search_result = '検索結果';

    // その他
    $lang->about_category_name = 'カテゴリー名を入力して下さい';
    $lang->about_expand = '選択すると常に開いた状態にします';
    $lang->about_category_group_srls = '選択したグループのみ現在のカテゴリーが見えるようになります（xmlファイルを直接閲覧すると漏出されます）';
    $lang->about_layout_setup = 'ブログのレイアウトのコードを直接修正できます。ウィジェットコードを入力、又は管理して下さい';
    $lang->about_blog_category = 'ブログの分類を作成できます。<br />ブログの分類が誤作動する場合キャッシュファイルの再生性を手動で行うと解決される事があります。';
    $lang->about_blog = "ブログを作成し管理できるブログモジュールです。\nブログモジュールはブログスキンに含まれているレイアウトを利用するので生成後必ず分類、又はスキン管理を用いてブログを編集して下さい。\nブログ内に他の掲示板を連結したい時はメニュモジュールでメニュを作った後スキン管理で連結して下さい。";
?>
