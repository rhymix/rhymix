<?php
    /**
     * @file   jp.lang.php
     * @author zero (zero@nzeo.com) 翻訳：RisaPapa、ミニミ // 細かい修正：liahona
     * @brief  日本語言語パッケージ（基本的な内容のみ）
     **/

    $lang->admin_info = '管理者情報';
    $lang->admin_index = '管理者トップページ';

    $lang->module_category_title = array(
        'service' => 'サービス型モジュール',
        'manager' => '管理型モジュール',
        'utility' => '機能性モジュール',
        'accessory' => '付加モジュール',
        'base' => '基本モジュール',
    );

    $lang->newest_news = "最新ニュース";

    $lang->env_setup = "環境設定";

    $lang->env_information = "環境情報";
    $lang->current_version = "インストールバージョン";
    $lang->current_path = "インストールパス";
    $lang->released_version = "最新バージョン";
    $lang->about_download_link = "インストールされたバージョンより新しいバージョンが配布されています。｢ダウンロード｣リンクをクリックするとダウンロードできます。";

    $lang->item_module = "モジュールリスト";
    $lang->item_addon  = "アドオンリスト";
    $lang->item_widget = "ウィジェットリスト";
    $lang->item_layout = "レイアウトリスト";

    $lang->module_name = "モジュール名";
    $lang->addon_name = "アドオン名";
    $lang->version = "バージョン";
    $lang->author = "作者";
    $lang->table_count = "テーブル数";
    $lang->installed_path = "インストールパス";

    $lang->cmd_shortcut_management = "メニュー編集";

    $lang->msg_is_not_administrator = '管理者のみアクセスできます';
    $lang->msg_manage_module_cannot_delete = 'モジュール、アドオン、ウィジェットのショットカットは削除できません。';
    $lang->msg_default_act_is_null = 'デフォルトの管理者のアクションが指定されていないため、ショットカットを登録することができません。';

    $lang->welcome_to_zeroboard_xe = 'ゼロボードXEの管理者ページです。';
    $lang->about_admin_page = "管理者ページはまだ未完成です。\nクローズベータバージョンの期間に、多くの方々からご意見をいただきながら、必ず必要なコンテンツを埋めていきたいと思います。";
    $lang->about_lang_env = "上で設定された言語を、初めてサイトに訪問したユーザに同じく適用させるためには、希望する言語に変更してから「保存」ボタンをクリックしてください。";

    $lang->zeroboard_xe_user_links = 'ユーザのためのリンク';
    $lang->zeroboard_xe_developer_links = 'デベロッパーのためのリンク';

    $lang->xe_user_links = array(
        '公式ホームページ' => 'http://www.zeroboard.com',
        //'クローズベータサイト' => 'http://spring.zeroboard.com',
        //'モジュルダ情報' => 'http://www.zeroboard.com',
        //'アドオン情報' => 'http://www.zeroboard.com',
        //'ウィジェット情報' => 'http://www.zeroboard.com',
        //'モジュール・スキン情報' => 'http://www.zeroboard.com',
        //'ウィジェットスキン情報' => 'http://www.zeroboard.com',
        //'レイアウトスキン情報' => 'http://www.zeroboard.com',
    );

    $lang->xe_developer_links = array(
        'マニュアル' => 'http://www.zeroboard.com/manual',
        //'デベロッパーフォーラム' => 'http://spring.zeroboard.com',
        'イッシュートラッキング' => 'http://www.zeroboard.com/xe_issuetracker',
        'SVNリポジトリー' => 'http://svn.zeroboard.com',
        'Doxygenドキュメント' => 'http://doc.zeroboard.com',
        'PDFドキュメント' => 'http://doc.zeroboard.com/zeroboard_xe.pdf',
        'XEツール' => './tools/',
    );

    $lang->zeroboard_xe_usefulness_module = '有用なモジュール';
    $lang->xe_usefulness_modules = array(
        'dispEditorAdminIndex' => 'エディター管理',
        'dispDocumentAdminList' => 'ドキュメント管理',
        'dispCommentAdminList' => 'コメント管理',
        'dispFileAdminList' => '添付ファイル管理',
        'dispPollAdminList' => 'アンケート管理',
        'dispSpamfilterAdminConfig' => 'スパムフィルター管理',
        'dispCounterAdminIndex' => 'カウンターログ',

    );

    $lang->xe_license = 'ゼロボードXEのライセンスはGPLです。';
    $lang->about_shortcut = 'よく使用するモジュールに登録されたショートカットは削除できます。';
?>
