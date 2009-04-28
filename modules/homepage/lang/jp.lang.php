<?php
    /**
     * @file   jp.lang.php
          * @author zero (zero@nzeo.com)　翻訳：ミニミ
     * @brief  CafeXE(homepage)モジュールの日本語言語パッケージ（基本的な内容のみ）
     **/

    $lang->cafe = "CafeXE"; 
    $lang->cafe_id = "カフェへのアクセスID"; 
    $lang->cafe_title = "ホームページ名";
    $lang->cafe_description = 'カフェの説明';
    $lang->cafe_banner = 'カフェのバナーイメージ';
    $lang->module_type = "タイプ";
    $lang->board = "掲示板";
    $lang->page = "ページ";
    $lang->module_id = "モジュール ID";
    $lang->item_group_grant = "メニューを公開するグループ";
    $lang->cafe_info = "カフェの情報";
    $lang->cafe_admin = "カフェ管理者";
    $lang->do_selected_member = "選択した会員を : ";
    $lang->cafe_latest_documents = 'カフェの新規書き込み';
    $lang->cafe_latest_comments = 'カフェの新規コメント';
    $lang->mycafe_list = '登録したカフェ';
    $lang->cafe_creation_type = 'カフェアクセス方法';
    $lang->about_cafe_creation_type = '作成するカフェへのユーザーからのアクセス方法を定めてください。Site IDとは http://ドメイン/<b>ID</b>へアクセスが可能になり、ドメインアクセスとは登録したサブドメイン（http://domain.mydomain.net)にカフェが作成されます。';
    $lang->cafe_main_layout = 'カフェトップページのレイアウト';

    $lang->default_layout = 'デフォルトレイアウト';
    $lang->about_default_layout = 'カフェを作成する時のデフォルトレイアウトを指定します。';
    $lang->enable_change_layout = 'レイアウト変更';
    $lang->about_change_layout = '選択すると、個々のカフェにてレイアウト変更が可能になります。';
    $lang->allow_service = '許可サービス';
    $lang->about_allow_service = '個々のカフェで利用する基本サービスを設定します。';

    $lang->cmd_make_cafe = '개별 카페에서 사용할 기본 서비스를 설정할 수 있습니다
カフェ作成';
    $lang->cmd_import = 'インポート';
    $lang->cmd_export = 'エクスポート';
    $lang->cafe_creation_privilege = 'カフェの作成権限';

    $lang->cafe_main_mid = 'カフェメインID';
    $lang->about_cafe_main_mid = 'カフェのトップページを「http://ドメイン/<b>ID</b>」のように設定するためのID値を入力して下さい。';

    $lang->default_menus = array(
        'home' => 'ホーム',
        'notice' => 'お知らせ',
        'levelup' => 'レベルアップ',
        'freeboard' => '自由掲示板',
        'view_total' => '全文を表示',
        'view_comment' => '一行の物語',
        'cafe_album' => 'フォトギャラリー',
        'menu' => 'メニュー',
        'default_group1' => 'スタンバイ会員',
        'default_group2' => '準会員',
        'default_group3' => '正会員',
    );

    $lang->cmd_admin_menus = array(
        "dispHomepageManage" => "カフェ設定",
        "dispHomepageMemberGroupManage" => "会員のグループ管理",
        "dispHomepageMemberManage" => "会員リスト",
        "dispHomepageTopMenu" => "基本メニュー 管理",
        "dispHomepageComponent" => "機能設定",
        "dispHomepageCounter" => "アクセス集計",
        "dispHomepageMidSetup" => "モジュール詳細設定",
    );
    $lang->cmd_cafe_registration = "カフェ作成";
    $lang->cmd_cafe_setup = "カフェ設定";
    $lang->cmd_cafe_delete = "カフェ削除";
    $lang->cmd_go_home = "ホームへ移動";
    $lang->cmd_go_cafe_admin = 'カフェ全体管理';
    $lang->cmd_change_layout = "変更";
    $lang->cmd_select_index = "初期ページ選択";
    $lang->cmd_add_new_menu = "新しいメニュー追加";
    $lang->default_language = "基本言語";
    $lang->about_default_language = "初めてアクセスするユーザーに見せるページの言語を指定します。";

    $lang->about_cafe_act = array(
        "dispHomepageManage" => "カフェのレイアウトを変更します。",
        "dispHomepageMemberGroupManage" => "カフェ内のグループを管理します。",
        "dispHomepageMemberManage" => "カフェに登録されている会員を管理します。",
        "dispHomepageTopMenu" => "カフェのヘッダー（header、上段）や左側などのメニューを管理します。",
        "dispHomepageComponent" => "エディターのコンポーネント/アドオンをオンにしたら、設定を変更します。",
        "dispHomepageCounter" => "カフェへのアクセス状況を確認できます。",
        "dispHomepageMidSetup" => "カフェの掲示板、ページなどのモジュールを管理します。",
    );
    $lang->about_cafe = "カフェサービス管理者は複数のカフェ作成、および各カフェを簡単に管理が出来ます。";
    $lang->about_cafe_title = "カフェ名は管理をするためだけに使われ、実サービスには表示されません。";
    $lang->about_menu_names = "カフェに使うメニュー名を言語別に指定出来ます。<br/>一個だけ記入した場合、他言語に一括適用されます。";
    $lang->about_menu_option = "メニューを選択するとき新しいウィンドウズに開けるかを選択します。<br />拡張メニューはレイアウトによって動作します。";
    $lang->about_group_grant = "選択グループのみ、メニューが見えます。<br/>全てを解除すると非会員にも見えます。";
    $lang->about_module_type = "掲示板、ページはモジュールを生成し、URLはリンクの情報のみ要ります。<br/>一度作成した後、変更は出来ません。";
    $lang->about_browser_title = "メニューにアクセスした時、ブラウザーのタイトルです。";
    $lang->about_module_id = "掲示板、ページなどにリンクさせるアドレスです。<br/>例) http://ドメイン/[モジュールID], http://ドメイン/?mid=[モジュールID]";
    $lang->about_menu_item_url = "タイプをURLにした場合、リンク先を入れて下さい。<br/>http://は省いて入力して下さい。";
    $lang->about_menu_image_button = "テキストのメニュー名の代わりに、イメージのメニューを使えます。";
    $lang->about_cafe_delete = "カフェを削除すると、リンクされている全てのモジュール(掲示板、ページなど)とそれに付随する書き込みが削除されます。<br />ご注意下さい。";
    $lang->about_cafe_admin = "カフェ管理者の設定が出来ます。<br/>カフェ管理者は「http://ドメイン/?act=dispHomepageManage」にて管理者ページにアクセスが出来ます。<br />存在しない会員は管理者として登録出来ません。";

    $lang->confirm_change_layout = "レイアウトの変更時、一部のレイアウト情報が失われる可能性があります。 変更しますか?";
    $lang->confirm_delete_menu_item = "メニューの削除時、リンクされている掲示板やページモジュールも一緒に削除されます。削除しますか?";
    $lang->msg_module_count_exceed = '許可されたモジュール数を超過したため、作成が出来ません。';
    $lang->msg_not_enabled_id = '利用出来ないIDです。';
    $lang->msg_same_site = '同一バーチャル（Virtual）サイトのモジュールは移動出来ません。';
    $lang->about_move_module = 'バーチャル（Virtual）サイトと基本サイト間でのモジュール移動が出来ます。<br/>ただし、他のバーチャル（Virtual）サイトへのモジュールを移動したり、同名のmidがある場合、予測が出来ない不具合が有り得るので、必ずバーチャル（Virtual）サイトと基本サイト間で異なる名前のモジュールを移動してください。
';
?>
