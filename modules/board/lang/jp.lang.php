<?php
    /**
     * @file   jp.lang.php
     * @author zero (zero@nzeo.com) 翻訳：RisaPapa、ミニミ // 細かい修正：liahona
     * @brief  掲示板(board)モジュールの基本言語パッケージ
     **/

    $lang->board = '掲示板';

    $lang->except_notice = 'お知らせの非表示機能';

    $lang->cmd_manage_menu = 'メニュー管理';
    $lang->cmd_make_child = '下位カテゴリ追加';
    $lang->cmd_enable_move_category = "カテゴリ位置変更（選択後上のメニューをドラッグして下さい）";

    // 項目
    $lang->parent_category_title = '上位カテゴリ名';
    $lang->category_title = 'カテゴリ名';
    $lang->category_color = 'カテゴリフォント色';
    
    $lang->expand = '拡張表示';
    $lang->category_group_srls = 'グループ制限';
    $lang->search_result = '検索結果';
    $lang->consultation = '相談機能';
    $lang->secret = '秘密文機能';
    $lang->admin_mail = '管理者メールアドレス';



    // ボタンに使用する用語
    $lang->cmd_board_list = '掲示板リスト';
    $lang->cmd_module_config = '掲示板共通設定';
    $lang->cmd_view_info = '掲示板情報';

    // その他
    $lang->about_category_title = 'カテゴリ名を入力して下さい。';
    $lang->about_expand = 'チェックすると常に展開された状態になります。';
    $lang->about_category_group_srls = '選択したグループのみ、現在のカテゴリの指定が出来ます。';
    $lang->about_layout_setup = 'ブログのレイアウトのコードを直接修正します。ウィジェットコードを好きなところに入力、又は管理して下さい。';
    $lang->about_board_category = 'ブログのカテゴリを作成します。<br />ブログのカテゴリが誤作動する場合、「キャッシュファイルの再生性」を手動で行うことで解決できます。';
    $lang->about_except_notice = "リストの上段に常に表示されるお知らせの書き込みを一般リストからお知らせの書き込みが表示されないようにします。";
    $lang->about_board = '掲示板の生成、および管理する掲示板モジュールです。';
    $lang->about_consultation = "相談機能とは、管理権限のない会員に本人の書き込みだけを表示する機能です。\n但し、相談機能を使用する際は、非会員の書き込みは自動的に禁止されます。";
    $lang->about_secret = '掲示板およびコメントの秘密文を登録できるようにします。';
    $lang->about_admin_mail = '書き込みやコメントが掲載される時、登録メールアドレス宛にメールが送信されます。<br /> 複数のメールアドレスへ送信する場合は「,」(半額コンマ)区切りで登録して下さい。';

    $lang->about_category_color = 'カテゴリのフォント色を設定します。';
?>