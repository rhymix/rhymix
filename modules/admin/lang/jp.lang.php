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
    $lang->about_download_link = "インストールされたバージョンより新しいバージョンが配布されています。\n｢ダウンロード｣リンクをクリックするとダウンロードできます。";

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

    $lang->welcome_to_xe = 'XEの管理者ページです。';
    $lang->about_admin_page = "管理者ページはまだ未完成です。\nクローズベータバージョンの期間に、多くの方々からご意見をいただきながら、必ず必要なコンテンツを埋めていきたいと思います。";
    $lang->about_lang_env = "上で設定された言語を、初めてサイトに訪問したユーザに同じく適用させるためには、希望する言語に変更してから「保存」ボタンをクリックしてください。";

    $lang->xe_license = 'XEのライセンスはGPLです。';
    $lang->about_shortcut = 'よく使用するモジュールに登録されたショートカットは削除できます。';

    $lang->yesterday = "Yesterday";
    $lang->today = "Today";

    $lang->cmd_lang_select = "言語選択";
    $lang->about_cmd_lang_select = "選択された言語のみでサービスを行います。";
    $lang->about_recompile_cache = "要らないか誤ったキャッシューファイルを整理します。";
    $lang->use_ssl = "SSL 사용";
    $lang->ssl_options = array(
        'none' => "사용안함",
        'optional' => "선택적으로",
        'always' => "항상사용"
    );
    $lang->about_use_ssl = "선택적으로에서는 회원가입/정보수정등의 지정된 action에서 SSL을 사용하고 항상 사용은 모든 서비스가 SSL을 이용하게 됩니다.";
    $lang->server_ports = "서버포트지정";
    $lang->about_server_ports = "HTTP는 80, HTTPS는 443이외의 다른 포트를 사용하는 경우에 포트를 지정해주어야합니다.";
?>
