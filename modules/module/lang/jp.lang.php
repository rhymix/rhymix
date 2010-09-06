<?php
    /**
     * @file   modules/module/lang/jp.lang.php
     * @author NHN (developers@xpressengine.com) 翻訳：RisaPapa、liahona、ミニミ
     * @brief  日本語言語パッケージ
     **/

    $lang->virtual_site = 'バーチャル（Virtual）サイト';
    $lang->module_list = 'モジュールリスト';
    $lang->module_index = 'モジュールインデックス';
    $lang->module_category = 'モジュールカテゴリ';
    $lang->module_info = '詳細';
    $lang->add_shortcut = '管理者メニューに追加する';
    $lang->module_action = '動作';
    $lang->module_maker = 'モジュール作者';
    $lang->module_license = 'ライセンス';
    $lang->module_history = '変更履歴 ';
    $lang->category_title = 'カテゴリ名';
    $lang->header_text = 'ヘッダー内容';
    $lang->footer_text = 'フッター内容';
    $lang->use_category = 'カテゴリ使用';
    $lang->category_title = 'カテゴリ名';
    $lang->checked_count = '選択された書き込み数';
    $lang->skin_default_info = 'スキン基本情報';
    $lang->skin_author = 'スキン作者';
    $lang->skin_license = 'ライセンス';
    $lang->skin_history = '変更内容';
    $lang->module_copy = 'モジュールコピー';
    $lang->module_selector = 'モジュールセレクター';
    $lang->do_selected = '選択したものを...';
    $lang->bundle_setup = '一括基本設定';
    $lang->bundle_addition_setup = '一括追加設定';
    $lang->bundle_grant_setup = '一括権限設定';
    $lang->lang_code = '言語コード';
    $lang->filebox = 'ファイルボックス';

    $lang->access_type = 'アクセスタイプ';
    $lang->access_domain = 'Doaminアクセス';
    $lang->access_vid = 'Site IDアクセス';
    $lang->about_domain = '複数のホームページを作成するためには、「オリジナルドメイン」や「サブ ドメイン」のような専用のドメインが必要です。<br />また、 XEのインストールパスも一緒に記入して下さい。<br />ex) www.xpressengine.com/xe';
    $lang->about_vid = '別の違うドメインではなく、「http://XEアドレス/ID」へのアクセスが可能です。この際、モジュール名(mid)と重複しないように登録して下さい。<br/>必ず、頭文字は半角英文字にし、「（すべて半角の）英数字・_ 」 だけの組み合わせで入力して下さい。';
    $lang->msg_already_registed_vid = '既に登録されたサイトIDです。掲示板などのmidと重複は不可です。異なるIDを入力して下さい。';
    $lang->msg_already_registed_domain = '既に登録されているドメインです。異なるドメインを利用して下さい。';

    $lang->header_script = 'ヘッダースクリプト';
    $lang->about_header_script = 'HTMLの&lt;header&gt;と&lt;/header&gt;の間に入れるコードを直接入力出来ます。<br />&lt;script、&lt;styleまたは&lt;metaタグなどが利用出来ます。';

    $lang->grant_access = 'アクセス権限';
    $lang->grant_manager = '管理権限';

    $lang->grant_to_all = 'すべてのユーザー';
    $lang->grant_to_login_user = 'ログインユーザー';
    $lang->grant_to_site_user = '登録ユーザー';
    $lang->grant_to_group = '特定グループのユーザー';

    $lang->cmd_add_shortcut = 'ショットカット追加';
    $lang->cmd_install = 'インストール';
    $lang->cmd_update = 'アップデート';
    $lang->cmd_manage_category = 'カテゴリ管理';
    $lang->cmd_manage_grant = '権限管理';
    $lang->cmd_manage_skin = 'スキン管理';
    $lang->cmd_manage_document = '書き込み管理';
    $lang->cmd_find_module = 'モジュール検索';
    $lang->cmd_find_langcode = '言語コード検索';

    $lang->msg_new_module = 'モジュール作成';
    $lang->msg_update_module = 'モジュール修正';
    $lang->msg_module_name_exists = '既に存在するモジュール名です。他の名前を入力して下さい。';
    $lang->msg_category_is_null = '登録されているカテゴリがありません。';
    $lang->msg_grant_is_null = '登録された権限がありません。';
    $lang->msg_no_checked_document = '選択された書き込みがありません。';
    $lang->msg_move_failed = '移動することが出来ませんでした。';
    $lang->msg_cannot_delete_for_child = '下位カテゴリのカテゴリは削除することが出来ません。';
    $lang->msg_limit_mid ='モジュール名は「 半角英小文字+[半角英小文字+半角数字+_] 」のみ出来ます。';
    $lang->msg_extra_name_exists = '既に存在する拡張変数名です。他の拡張変数名を入力して下さい。';

    $lang->about_browser_title = 'ブラウザのタイトルバーに表示される内容です。RSS/Trackbackでも使用します。';
    $lang->about_mid = 'モジュール名は「http://アドレス/?mid=モジュール名」のように直接呼び出せるパラメーター値です。<br />※英数の頭文字と[英数と_のみ]の組み合わせ （すべて半角、最大40文字） ';
    $lang->about_default = 'チェックすると、サイトに「mid値」なしでアクセスした場合、デフォルトで表示します。';
    $lang->about_module_category = "カテゴリで管理出来るようにします。モジュールのカテゴリの管理は、「<a href=\"./?module=admin&amp;act=dispModuleAdminCategory\">モジュール管理 &gt; モジュールカテゴリ</a>」にて行います。";
    $lang->about_description= '管理用として用いられる説明です。';
    $lang->about_header_text = 'モジュールのヘッダーに表示される内容です。（HTMLタグの使用可能）';
    $lang->about_footer_text = 'モジュールのフッターに表示される内容です。（HTMLタグの使用可能）';
    $lang->about_skin = 'モジュールのスキンを選択します。';
    $lang->about_use_category = 'チェックするとカテゴリ機能が使用出来ます。';
    $lang->about_list_count = '１ページ当たりに表示される書き込みの数が指定出来ます（デフォルト20個）。';
    $lang->about_search_list_count = 'お勧めの記事数を設定するにさらされるかのカテゴリ検索機能を使用する場合です。 （デフォルトは20 ）';
    $lang->about_page_count = 'リストの下段に移動出来るページのリンク数が指定出来ます（デフォルト10個）。';
    $lang->about_admin_id = '該当するモジュールに対して最高権限を持つ管理者を指定することが出来ます。';
    $lang->about_grant = '特定権限の対象をすべて解除するとログインしていない会員ユーザまで権限が与えられます。';
    $lang->about_grant_deatil = '登録ユーザーとはcafeXEなど分譲型バーチャル(Virtual)サイトに登録した、ログインユーザーを意味します。';
    $lang->about_module = "XEは、基本ライブラリの他は、すべてモジュールで構成されています。モジュール管理用のモジュールはインストールされたすべてを表示し、管理出来るようにします。";
    $lang->about_extra_vars_default_value = '多重・単一選択などのデフォルト値が、複数必要な場合は、「, （コンマ）」で区切って追加することが出来ます。';
    $lang->about_search_virtual_site = 'バーチャル(Virtual)サイト（：cafeXEなど）のドメインを入力して検索して下さい。<br />バーチャル(Virtual)サイト以外のモジュールは内容を空にしてから検索します。（http://は省く）';
    $lang->about_extra_vars_eid_value = '拡張変数名を入力して下さい。　（英字+[英字+数字+_]のみ可能（全て半角））';
    $lang->about_langcode = '言語ごとに異なる設定をする場合、言語コード検索を利用して下さい。';
    $lang->about_file_extension= "%s ファイルのみ可能です。";
?>
