<?php
    /**
     * @file   jp.lang.php
     * @author zero (zero@nzeo.com)　翻訳：ミニミ
     * @brief  ホームページ(homepage)モジュールの日本語言語パッケージ（基本的な内容のみ）
     **/

    $lang->homepage = "ホームページ"; 
    $lang->homepage_title = "ホームページ名";
    $lang->domain = "ドメイン";
    $lang->module_type = "ターゲット"; // 20081127 ミニミ：検討必要
    $lang->board = "掲示板";
    $lang->page = "ページ";
    $lang->url = "URL";
    $lang->module_id = "モジュール ID";
    $lang->item_group_grant = "メニューを見せるグループ";
    $lang->homepage_admin = "ホームページ管理者";
    $lang->do_selected_member = "選択した会員を : ";

    $lang->homepage_default_menus = array(
        'first' => array(
            'home' => 'ホーム',
            'notice' => 'お知らせ',
            'download' => 'ダウンロード',
            'gallery' => 'ギャラリー',
            'community' => 'コミュニティー',
            'freeboard' => '自由掲示板',
            'humor' => 'ユーモア掲示板',
            'qa' => 'Q and A',
        ),
        'second' => array(
            'profile' => 'ホームページ紹介',
            'rule' => '運営規則',
        ),
        'menu' => array(
            'first' => '基本メニュー',
            'second' => 'サブメニュー',
        ),
        'widget' => array(
            'download_rank' => 'ダウンロードランキング',
        ),
    );

    $lang->cmd_homepage_menus = array(
        "dispHomepageManage" => "ホームページ設定",
        "dispHomepageMemberGroupManage" => "会員のグループ管理",
        "dispHomepageMemberManage" => "会員リスト",
        "dispHomepageTopMenu" => "基本メニュー 管理",
        "dispHomepageBottomMenu" => "フッターメニュー 管理",
        "dispHomepageMidSetup" => "モジュール詳細設定",
    );
    $lang->cmd_homepage_registration = "ホームページ作成";
    $lang->cmd_homepage_setup = "ホームページ設定";
    $lang->cmd_homepage_delete = "ホームページ削除";
    $lang->cmd_go_home = "ホームへ移動";
    $lang->cmd_go_homepage_admin = 'ホームページ全体管理';
    $lang->cmd_change_layout = "変更";
    $lang->cmd_change_layout = "変更";
    $lang->cmd_select_index = "初期ページ選択";
    $lang->cmd_add_new_menu = "新しいメニュー追加";

    $lang->about_homepage_act = array(
        "dispHomepageManage" => "ホームページのレイアウトを変更します。",
        "dispHomepageMemberGroupManage" => "ホームページ内のグループを管理します。",
        "dispHomepageMemberManage" => "ホームページに登録されている会員を管理します。",
        "dispHomepageTopMenu" => "ホームページのヘッダー（header、上段）や左側などのメニューを管理します。",
        "dispHomepageBottomMenu" => "ホームページのフッター(footer、下段)のメニューを管理します。",
        "dispHomepageMidSetup" => "ホームページの掲示板、ページなどのモジュールを管理します。",
    );
    $lang->about_homepage = "ホームページサービス管理者は複数のホームページ作成、および各ホームページを簡単に管理が出来ます。";
    $lang->about_homepage_title = "ホームページ名は管理のためのみ使われ、実サービスには表示されません。";
    $lang->about_domain = "複数のホームページを作成するためには、専用のドメインが必要です。オリジナルドメインやサブ ドメインがあれば結構です。<br />また、 XEインストール経路も一緒に記入してください。<br />ex) www.zeroboard.com/zbxe";
    $lang->about_menu_names = "ホームページに使うメニュー名を言語別に指定出来ます。<br/>一個だけ記入した場合、他言語に一括適用されます。";
    $lang->about_menu_option = "メニューを選択するとき新しいウィンドウズに開けるかを選択します。<br />拡張メニューはレイアウトによって動作します。";
    $lang->about_group_grant = "選択グループのみ、メニューが見えます。<br/>全てを解除すると非会員にも見えます。";
    $lang->about_module_type = "掲示板、ページはモジュールを生成し、URLはリンクの情報のみ要ります。<br/>一度作成した後、変更は出来ません。"; // 20081127 ミニミ：検討必要
    $lang->about_browser_title = "メニューにアクセスした時、ブラウザーのタイトルです。";
    $lang->about_module_id = "掲示板、ページなどにリンクさせるアドレスです。<br/>例) http://ドメイン/[モジュールID], http://ドメイン/?mid=[モジュールID]";
    $lang->about_menu_item_url = "ターゲットをURLにした場合、リンク先を入れて下さい。<br/>http://は省いて入力して下さい。"; // 20081127 ミニミ：検討必要
    $lang->about_menu_image_button = "テキストのメニュー名の代わりに、イメージのメニューを使えます。";
    $lang->about_homepage_delete = "ホームページを削除すると、リンクされている全てのモジュール(掲示板、ページなど)とそれに付随する書き込みが削除されます。<br />ご注意ください。";
    $lang->about_homepage_admin = "ホームページ管理者の設定が出来ます。<br/>ホームページ管理者は 「 http://ドメイン/?module=homepage 」 として管理者ページにアクセスが出来ます。<br />存在しない会員は管理者に登録できません。";

    $lang->confirm_change_layout = "レイアウトの変更時、一部のレイアウト情報が失われる可能性があります。 変更しますか?";
    $lang->confirm_delete_menu_item = "メニュー削除時、リンクされている掲示板やページモジュールも一緒に削除されます。削除しますか?";
    $lang->msg_already_registed_domain = "既に登録されたドメインです。違うドメインを利用して下さい。";
?>
