<?php
    /**
     * @file   modules/admin/lang/jp.lang.php
     * @author zero (zero@nzeo.com) 翻訳：RisaPapa、ミニミ // 細かい修正：liahona
     * @brief  日本語言語パッケージ（基本的な内容のみ）
     **/

    $lang->admin_info = '管理者情報';
    $lang->admin_index = '管理者トップページ';
    $lang->control_panel = 'コントロールパネル';
    $lang->start_module = '初期起動モジュール';
    $lang->about_start_module = 'デフォルトで起動するモジュールを指定することができます。';

    $lang->module_category_title = array(
        'service' => 'サービス管理',
        'member' => '会員管理',
        'content' => 'コンテンツ管理',
        'statistics' => '統計確認',
        'construction' => 'サイト設定',
        'utility' => '機能設定',
        'interlock' => '連動設定',
        'accessory' => '付加機能設定',
        'migration' => 'データ管理／復元',
        'system' => 'システム管理',
    );

    $lang->newest_news = '最新ニュース';

    $lang->env_setup = '環境設定';
    $lang->default_url = '基本URL';
    $lang->about_default_url = '複数のバーチャル（Virtual）サイトを運営する場合、どちらからログインしてもバーチャル（Virtual）サイトの間でログイン情報を維持出来るようにするためには、基本になるサイトでのXEをインストールしたurlを登録して下さい。 (例: http://ドメイン/インストールパス)';


    $lang->env_information = '環境情報';
    $lang->current_version = 'インストール済みバージョン';
    $lang->current_path = 'インストールパス';
    $lang->released_version = '最新バージョン';
    $lang->about_download_link = "新しいバージョンが配布されています。\n｢ダウンロード｣リンクをクリックするとダウンロード出来ます。";

    $lang->item_module = 'モジュールリスト';
    $lang->item_addon  = 'アドオンリスト';
    $lang->item_widget = 'ウィジェットリスト';
    $lang->item_layout = 'レイアウトリスト';

    $lang->module_name = 'モジュール名';
    $lang->addon_name = 'アドオン名';
    $lang->version = 'バージョン';
    $lang->author = '制作者';
    $lang->table_count = 'テーブル数';
    $lang->installed_path = 'インストールパス';

    $lang->cmd_shortcut_management = 'メニューの編集';

    $lang->msg_is_not_administrator = '管理者のみアクセス出来ます';
    $lang->msg_manage_module_cannot_delete = 'モジュール、アドオン、ウィジェットのショットカットは削除出来ません。';
    $lang->msg_default_act_is_null = 'デフォルトの管理者のアクションが指定されていないため、ショットカットを登録することが出来ません。';

    $lang->welcome_to_xe = 'XEの管理者ページです。';
    $lang->about_lang_env = '初めてサイトに訪問したユーザーに対し、上記の選択した言語でサイトを表示させるためには、必ず下記の「保存」ボタンをクリックして適用して下さい。';

    $lang->xe_license = 'XEのライセンスはGPLです。';
    $lang->about_shortcut = 'よく使用するモジュールに登録されたショートカットは削除出来ます。';

    $lang->yesterday = '昨日';
    $lang->today = '今日';

    $lang->cmd_lang_select = '言語選択';
    $lang->about_cmd_lang_select = '選択した言語だけでサービスを行います。';
    $lang->about_recompile_cache = '要らないかごみのキャッシューファイルを整理します。';
    $lang->use_ssl = 'SSL環境設定';
    $lang->ssl_options = array(
        'none' => '使わない',
        'optional' => '部分的に使う',
        'always' => '常に使う'
    );
    $lang->about_use_ssl = '「部分的に使う場合」は「会員登録/会員情報変更」など特定のactionでSSLを利用する場合、「常に使う」は全てのサービスがSSLを使う場合に選択します。';
    $lang->server_ports = 'サーバーポート指定';
    $lang->about_server_ports = '一般的に使われているHTTPの80、HTTPSの443以外の他のポートを使うために、ポートを指定して下さい。';
    $lang->use_db_session = 'DBで認証セッション管理';
    $lang->about_db_session = '認証の時に使われるPHPセッションをDBで使う機能です。<br />ウェブサーバーの負荷が低いサイトではこの機能をオフにすることでむしろサイトのレスポンスが向上されることもあります。<br />また、この機能をオンにすると、「現在ログイン中の会員」の機能が不可になります。';
    $lang->sftp = "Use SFTP";
    $lang->ftp_get_list = "Get List";
    $lang->ftp_remove_info = 'Remove FTP Info.';
	$lang->msg_ftp_invalid_path = 'Failed to read the specified FTP Path.';
	$lang->msg_self_restart_cache_engine = 'Please restart Memcached or cache daemon.';
	$lang->mobile_view = 'Use Mobile View';
	$lang->about_mobile_view = 'If accessing with a smartphone, display content with mobile layout.';
    $lang->autoinstall = 'イージーインストール';
?>
