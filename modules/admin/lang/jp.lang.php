<?php
    /**
     * @file   jp.lang.php
     * @author zero (zero@nzeo.com) 翻訳：RisaPapa
     * @brief  日本語言語パッケージ（基本的な内容のみ）
     **/

    $lang->item_module = "モジュルリスト";
    $lang->item_addon  = "機能拡張リスト";
    $lang->item_widget = "ウィジェットリスト";
    $lang->item_layout = "レイアウトリスト";

    $lang->module_name = "モジュル名";
    $lang->addon_name = "機能拡張名";
    $lang->version = "バージョン";
    $lang->author = "作者";
    $lang->table_count = "テーブル数";
    $lang->installed_path = "インストールパス";

    $lang->cmd_shortcut_management = "メニューの編集";

    $lang->msg_is_not_administrator = '管理者のみ接続できます';
    $lang->msg_manage_module_cannot_delete = 'モジュル、機能拡張、ウィジェットのショットカットは削除できません。';
    $lang->msg_default_act_is_null = '基本管理者の Action が指定されていないため、ショットカットを登録することができません。';

    $lang->welcome_to_zeroboard_xe = 'ゼロボードXEの管理者ページです。';
    $lang->about_admin_page = "管理者ページはまだ未完成です。\nクローズベタバージョンの期間に、多くの方々からご意見をいただきながら、必ず必要なコンテンツを埋めていきたいと思います。";

    $lang->zeroboard_xe_user_links = 'ユーザリンク';
    $lang->zeroboard_xe_developer_links = 'デベロッパーリンク';

    $lang->xe_user_links = array(
        '公式ホームページ' => 'http://www.zeroboard.com',
        //'クローズベタサイト' => 'http://spring.zeroboard.com',
        //'モジュルダ情報' => 'http://www.zeroboard.com',
        //'機能拡張情報' => 'http://www.zeroboard.com',
        //'ウィジェット情報' => 'http://www.zeroboard.com',
        //'モジュルスキン情報' => 'http://www.zeroboard.com',
        //'ウィジェットスキン情報' => 'http://www.zeroboard.com',
        //'レイアウトスキン情報' => 'http://www.zeroboard.com',
    );

    $lang->xe_developer_links = array(
        'デベロッパーフォーラム' => 'http://spring.zeroboard.com',
        //'マニュアル' => 'http://www.zeroboard.com/wiki/manual',
        'イッシュートラッキング' => 'http://trac.zeroboard.com',
        'SVN Repository' => 'http://svn.zeroboard.com',
        'doxygen document' => 'http://doc.zeroboard.com',
        'pdfドキュメント' => 'http://doc.zeroboard.com/zeroboard_xe.pdf',
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
    $lang->about_shortcut = 'よく使用するモジュルに登録されたショットカットは削除できます。';
?>
