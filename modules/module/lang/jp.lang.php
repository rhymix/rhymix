<?php
    /**
     * @file   jp.lang.php
     * @author zero (zero@nzeo.com) 翻訳：RisaPapa、liahona
     * @brief  日本語言語パッケージ
     **/

    $lang->virtual_site = "Virtual Site";
    $lang->module_list = "モジュールリスト";
    $lang->module_index = "モジュールインデックス";
    $lang->module_category = "モジュールカテゴリ";
    $lang->module_info = "詳細";
    $lang->add_shortcut = "管理者メニューに追加する";
    $lang->module_action = "動作";
    $lang->module_maker = "モジュール作者";
    $lang->module_license = 'License';
    $lang->module_history = "変更内容 ";
    $lang->category_title = "カテゴリ名";
    $lang->header_text = 'ヘッダー内容';
    $lang->footer_text = 'フッター内容';
    $lang->use_category = 'カテゴリ使用';
    $lang->category_title = 'カテゴリ名';
    $lang->checked_count = '選択された書き込み数';
    $lang->skin_default_info = 'スキン基本情報';
    $lang->skin_author = 'スキン作者';
    $lang->skin_license = 'License';
    $lang->skin_history = '変更内容';
    $lang->module_copy = "モジュールコピー";
    $lang->module_selector = "Module Selector";
    $lang->do_selected = "선택된 것들을...";
    $lang->bundle_setup = "일괄 기본 설정";
    $lang->bundle_addition_setup = "일괄 추가 설정";
    $lang->bundle_grant_setup = "일괄 권한 설정";
    $lang->lang_code = "언어 코드";
    $lang->filebox = "파일박스";

    $lang->header_script = "ヘッダースクリプト";
    $lang->about_header_script = "HTMLの&lt;header&gt;と&lt;/header&gt;の間に入れるコードを直接入力できます。<br />&lt;script、&lt;styleまたは&lt;metaタグなどが利用できます";

    $lang->grant_access = "Access";
    $lang->grant_manager = "Management";

    $lang->grant_to_all = "All users";
    $lang->grant_to_login_user = "Logged users";
    $lang->grant_to_site_user = "Joined users";
    $lang->grant_to_group = "Specification group users";

    $lang->cmd_add_shortcut = "ショットカット追加";
    $lang->cmd_install = "インストール";
    $lang->cmd_update = "アップデート";
    $lang->cmd_manage_category = 'カテゴリ管理';
    $lang->cmd_manage_grant = '権限管理';
    $lang->cmd_manage_skin = 'スキン管理';
    $lang->cmd_manage_document = '書き込み管理';
    $lang->cmd_find_module = '모듈 찾기';
    $lang->cmd_find_langcode = 'Find lang code';

    $lang->msg_new_module = "モジュール作成";
    $lang->msg_update_module = "モジュール修正";
    $lang->msg_module_name_exists = "既に存在するモジュール名です。他の名前を入力してください。";
    $lang->msg_category_is_null = '登録されているカテゴリがありません。';
    $lang->msg_grant_is_null = '登録された権限がありません。';
    $lang->msg_no_checked_document = '選択された書き込みがありません。';
    $lang->msg_move_failed = '移動することができませんでした。';
    $lang->msg_cannot_delete_for_child = '下位カテゴリのカテゴリは削除することができません。';
    $lang->msg_limit_mid ="モジュール名は「 半角英小文字+[半角英小文字+半角数字+_] 」のみ出来ます。";

    $lang->about_browser_title = "ブラウザのタイトルバーに表示される内容です。RSS/Trackbackでも使用します。";
    $lang->about_mid = "モジュール名は、http://アドレス/?mid=モジュール名、のように直接呼び出せる値です（英数＋[英数,_のみ可]）。";
    $lang->about_default = "チェックすると、サイトに「 mid値」なしで接続した場合、デフォルトで表示します。";
    $lang->about_module_category = "カテゴリで管理できるようにします。モジュールのカテゴリの管理は、<a href=\"./?module=admin&amp;act=dispModuleAdminCategory\">モジュール管理 > モジュールカテゴリ </a>で行うことができます。";
    $lang->about_description= '管理用に用いられる説明です。';
    $lang->about_default = 'チェックすると、サイトに「mid値」なしで接続した場合、デフォルトで表示します。';
    $lang->about_header_text = 'モジュールのヘッダーに表示される内容です（HTMLタグが使用できる）。';
    $lang->about_footer_text = 'モジュールのフッターに表示される内容です。（HTMLタグが使用できる）。';
    $lang->about_skin = 'モジュールのスキンを選択することができます。';
    $lang->about_use_category = 'チェックするとカテゴリ機能が使用できます。';
    $lang->about_list_count = '１ページ当たりに表示される書き込みの数が指定できます（デフォルト20個）。';
    $lang->about_search_list_count = 'お勧めの記事数を設定するにさらされるかのカテゴリ検索機能を使用する場合です。 （デフォルトは20 ）';
    $lang->about_page_count = 'リストの下段に移動できるページのリンク数が指定できます（デフォルト１０個）。';
    $lang->about_admin_id = '該当するモジュールに対して最高権限を持つ管理者を指定することができます。';
    $lang->about_grant = '特定権限の対象をすべて解除するとログインしていない会員ユーザまで権限が与えられます。';
    $lang->about_grant_deatil = '가입한 사용자는 cafeXE등 분양형 가상 사이트에 가입을 한 로그인 사용자를 의미합니다';
    $lang->about_module = "XEは、基本ライブラリの他は、すべてモジュールで構成されています。モジュール管理用のモジュールはインストールされたすべてを表示し、管理できるようにします。";

    $lang->about_extra_vars_default_value = '多重・単一選択などのデフォルト値が、複数必要な場合は、「, （コンマ）」で区切って追加することができます。';
    $lang->about_search_virtual_site = "가상 사이트(카페XE등)의 도메인을 입력하신 후 검색하세요.<br/>가상 사이트이외의 모듈은 내용을 비우고 검색하시면 됩니다.  (http:// 는 제외)";
    $lang->about_langcode = "언어별로 다르게 설정하고 싶으시면 언어코드 찾기를 이용해주세요";
    $lang->about_file_extension= "%s 파일만 가능합니다.";
?>
