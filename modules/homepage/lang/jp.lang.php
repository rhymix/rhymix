<?php
    /**
     * @file   jp.lang.php
          * @author zero (zero@nzeo.com)　翻訳：ミニミ
     * @brief  CafeXE(homepage)モジュールの日本語言語パッケージ（基本的な内容のみ）
     **/

    $lang->cafe = "CafeXE"; 
    $lang->cafe_id = "카페 접속 ID"; 
    $lang->cafe_title = "ホームページ名";
    $lang->cafe_description = 'Description of cafe';
    $lang->cafe_banner = 'Banner of Cafe';
    $lang->module_type = "タイプ";
    $lang->board = "掲示板";
    $lang->page = "ページ";
    $lang->module_id = "モジュール ID";
    $lang->item_group_grant = "メニューを見せるグループ";
    $lang->cafe_info = "Cafe Infomation";
    $lang->cafe_admin = "ホームページ管理者";
    $lang->do_selected_member = "選択した会員を : ";
    $lang->cafe_latest_documents = '카페 최신 글';
    $lang->cafe_latest_comments = '카페 최신 댓글';
    $lang->mycafe_list = '가입한 카페';
    $lang->cafe_creation_type = '카페 접속 방법';
    $lang->about_cafe_creation_type = '사용자들이 카페를 생성할때 카페 접속 방법을 정해야 합니다. Site ID는 http://기본주소/ID 로 접속 가능하고 Domain 접속은 입력하신 도메인의 2차 도메인(http://domain.mydomain.net) 으로 카페가 생성됩니다';
    $lang->cafe_main_layout = '카페 메인 레이아웃';

    $lang->default_layout = '기본 레이아웃';
    $lang->about_default_layout = '카페가 생성될때 설정될 기본 레이아웃을 지정할 수 있습니다';
    $lang->enable_change_layout = '레이아웃 변경';
    $lang->about_change_layout = '선택하시면 개별 카페에서 레이아웃 변경을 허용할 수 있습니다';
    $lang->allow_service = '허용 서비스';
    $lang->about_allow_service = '개별 카페에서 사용할 기본 서비스를 설정할 수 있습니다';

    $lang->cmd_make_cafe = '카페 생성';
    $lang->cmd_import = 'Import';
    $lang->cmd_export = 'Export';
    $lang->cafe_creation_privilege = 'カフェの作成権限';

    $lang->cafe_main_mid = '카페 메인 ID';
    $lang->about_cafe_main_mid = '카페 메인 페이지를 http://주소/ID 값으로 접속하기 위한 ID값을 입력해주세요.';

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
        "dispHomepageManage" => "ホームページ設定",
        "dispHomepageMemberGroupManage" => "会員のグループ管理",
        "dispHomepageMemberManage" => "会員リスト",
        "dispHomepageTopMenu" => "基本メニュー 管理",
        "dispHomepageComponent" => "기능 설정",
        "dispHomepageCounter" => "접속 통계",
        "dispHomepageMidSetup" => "モジュール詳細設定",
    );
    $lang->cmd_cafe_registration = "ホームページ作成";
    $lang->cmd_cafe_setup = "ホームページ設定";
    $lang->cmd_cafe_delete = "ホームページ削除";
    $lang->cmd_go_home = "ホームへ移動";
    $lang->cmd_go_cafe_admin = 'ホームページ全体管理';
    $lang->cmd_change_layout = "変更";
    $lang->cmd_select_index = "初期ページ選択";
    $lang->cmd_add_new_menu = "新しいメニュー追加";
    $lang->default_language = "基本言語";
    $lang->about_default_language = "初めてアクセスするユーザーに見せるページの言語を指定します。";

    $lang->about_cafe_act = array(
        "dispHomepageManage" => "ホームページのレイアウトを変更します。",
        "dispHomepageMemberGroupManage" => "ホームページ内のグループを管理します。",
        "dispHomepageMemberManage" => "ホームページに登録されている会員を管理します。",
        "dispHomepageTopMenu" => "ホームページのヘッダー（header、上段）や左側などのメニューを管理します。",
        "dispHomepageComponent" => "에디터 컴포넌트/ 애드온을 활성화 하거나 설정을 변경할 수 있습니다",
        "dispHomepageCounter" => "Cafe의 접속 현황을 볼 수 있습니다",
        "dispHomepageMidSetup" => "ホームページの掲示板、ページなどのモジュールを管理します。",
    );
    $lang->about_cafe = "ホームページサービス管理者は複数のホームページ作成、および各ホームページを簡単に管理が出来ます。";
    $lang->about_cafe_title = "ホームページ名は管理をするためだけに使われ、実サービスには表示されません。";
    $lang->about_menu_names = "ホームページに使うメニュー名を言語別に指定出来ます。<br/>一個だけ記入した場合、他言語に一括適用されます。";
    $lang->about_menu_option = "メニューを選択するとき新しいウィンドウズに開けるかを選択します。<br />拡張メニューはレイアウトによって動作します。";
    $lang->about_group_grant = "選択グループのみ、メニューが見えます。<br/>全てを解除すると非会員にも見えます。";
    $lang->about_module_type = "掲示板、ページはモジュールを生成し、URLはリンクの情報のみ要ります。<br/>一度作成した後、変更は出来ません。";
    $lang->about_browser_title = "メニューにアクセスした時、ブラウザーのタイトルです。";
    $lang->about_module_id = "掲示板、ページなどにリンクさせるアドレスです。<br/>例) http://ドメイン/[モジュールID], http://ドメイン/?mid=[モジュールID]";
    $lang->about_menu_item_url = "タイプをURLにした場合、リンク先を入れて下さい。<br/>http://は省いて入力して下さい。";
    $lang->about_menu_image_button = "テキストのメニュー名の代わりに、イメージのメニューを使えます。";
    $lang->about_cafe_delete = "ホームページを削除すると、リンクされている全てのモジュール(掲示板、ページなど)とそれに付随する書き込みが削除されます。<br />ご注意下さい。";
    $lang->about_cafe_admin = "ホームページ管理者の設定が出来ます。<br/>ホームページ管理者は「http://ドメイン/?act=dispHomepageManage」にて管理者ページにアクセスが出来ます。<br />存在しない会員は管理者として登録出来ません。";

    $lang->confirm_change_layout = "レイアウトの変更時、一部のレイアウト情報が失われる可能性があります。 変更しますか?";
    $lang->confirm_delete_menu_item = "メニューの削除時、リンクされている掲示板やページモジュールも一緒に削除されます。削除しますか?";
    $lang->msg_module_count_exceed = '허용된 모듈의 개수를 초과하였기에 생성할 수 없습니다';
    $lang->msg_not_enabled_id = '사용할 수 없는 아이디입니다';
    $lang->msg_same_site = '동일한 가상 사이트의 모듈은 이동할 수가 없습니다';
    $lang->about_move_module = '가상사이트와 기본사이트간의 모듈을 옮길 수 있습니다.<br/>다만 가상사이트끼리 모듈을 이동하거나 같은 이름의 mid가 있을 경우 예기치 않은 오류가 생길 수 있으니 꼭 가상 사이트와 기본 사이트간의 다른 이름을 가지는 모듈만 이동하세요';
?>
