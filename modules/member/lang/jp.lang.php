<?php
    /**
     * @file   modules/member/jp.lang.php
     * @author zero (zero@nzeo.com) ??：RisaPapa、ミニミ、liahona
     * @brief  日本語言語パッケ?ジ（基本的な?容のみ）
     **/

    $lang->member = '?員';
    $lang->member_default_info = '基本情報';
    $lang->member_extend_info = '追加情報';
    $lang->default_group_1 = "準?員";
    $lang->default_group_2 = "正?員";
    $lang->admin_group = "管理グル?プ";
    $lang->keep_signed = '次回からIDの入力を省略';
    $lang->remember_user_id = 'ＩＤ保存';
    $lang->already_logged = '?にログインされています。';
    $lang->denied_user_id = '使用が禁じられているＩＤです。';
    $lang->null_user_id = 'ユ?ザＩＤをもう一度入力してください。';
    $lang->null_password = 'パスワ?ドを入力してください。';
    $lang->invalid_authorization = '認?できませんでした。';
    $lang->invalid_user_id= '存在しないユ?ザＩＤです。';
    $lang->invalid_password = '無?なパスワ?ドです。';
    $lang->allow_mailing = 'メ?リングリスト';
    $lang->denied = '使用中止';
    $lang->is_admin = '最高管理?限';
    $lang->group = '所?グル?プ';
    $lang->group_title = 'グル?プタイトル';
    $lang->group_srl = 'グル?プ番?';
    $lang->signature = '著名';
    $lang->profile_image = 'プロフィ?ル??';
    $lang->profile_image_max_width = '?幅サイズ制限';
    $lang->profile_image_max_height = '?幅製?制限';
    $lang->image_name = 'イメ?ジ名';
    $lang->image_name_max_width = '?幅制限サイズ';
    $lang->image_name_max_height = '?幅制限サイズ';
    $lang->image_mark = 'イメ?ジマ?ク';
    $lang->image_mark_max_width = '?幅制限サイズ';
    $lang->image_mark_max_height = '?幅制限サイズ';
    $lang->signature_max_height = '署名欄の高さの制限';
	$lang->enable_openid = 'OpenID使用';
    $lang->enable_join = '?員加入を許可する';
    $lang->enable_confirm = '메일 인증 사용';
    $lang->enable_ssl = 'SSL使用';
    $lang->security_sign_in = 'Sign in using enhanced security';
    $lang->limit_day = '臨時制限日';
    $lang->limit_date = '制限日';
    $lang->after_login_url = 'ログイン後表示するペ?ジのURL';
    $lang->after_logout_url = 'ログアウト後表示するペ?ジのURL';
    $lang->redirect_url = '加入後表示するペ?ジ';
    $lang->agreement = '?員加入規約';
    $lang->accept_agreement = '規約に同意する';
    $lang->member_info = '?員情報';
    $lang->current_password = '現在のパスワ?ド';
    $lang->openid = 'OpenID';

    $lang->webmaster_name = 'ウェブマスタ?の名前';
    $lang->webmaster_email = 'ウェブマスタ?のメ?ル';

    $lang->about_keep_signed = '브라우저를 닫더라도 로그인이 계속 유지될 수 있습니다.\n\n로그인 유지 기능을 사용할 경우 다음 접속부터는 로그인을 하실 필요가 없습니다.\n\n단, 게임방, 학교 등 공공장소에서 이용시 개인정보가 유출될 수 있으니 꼭 로그아웃을 해주세요';
	$lang->about_webmaster_name = '認?メ?ルまたはサイト管理時に使用されるウェブマスタ?の名前を入力してください（デフォルト : webmaster）';
    $lang->about_webmaster_email = 'ウェブマスタ?のメ?ルアドレスを入力してください。';

    $lang->search_target_list = array(
        'user_id' => 'ユ?ザＩＤ',
        'user_name' => '名前',
        'nick_name' => 'ニックネ?ム',
        'email_address' => 'メ?ルアドレス',
        'regdate' => '加入日',
        'last_login' => '最近のログイン',
        'extra_vars' => '?張??',
    );

    $lang->cmd_login = 'ログイン';
    $lang->cmd_logout = 'ログアウト';
    $lang->cmd_signup = '?員加入';
    $lang->cmd_modify_member_info = '?員情報修正';
    $lang->cmd_modify_member_password = 'パスワ?ド?更';
    $lang->cmd_view_member_info = '?員情報表示';
    $lang->cmd_leave = '??';
    $lang->cmd_find_member_account = 'IDとパスワ?ドの?索';

    $lang->cmd_member_list = '?員リスト';
    $lang->cmd_module_config = '基本設定';
    $lang->cmd_member_group = 'グル?プ管理';
    $lang->cmd_send_mail = 'メ?ル送信';
    $lang->cmd_manage_id = '禁止ＩＤ管理';
    $lang->cmd_manage_form = '加入フォ?ム管理';
    $lang->cmd_view_own_document = '書き?み表示';
    $lang->cmd_trace_document = 'Trace Written Articles';
    $lang->cmd_trace_comment = 'Trace Written Comments';
    $lang->cmd_view_scrapped_document = 'スクラップ表示';
    $lang->cmd_view_saved_document = '保存ドキュメント表示';
    $lang->cmd_send_email = 'メ?ル送信';

    $lang->msg_email_not_exists = "メ?ルアドレスがありません。";

    $lang->msg_alreay_scrapped = '?にスクラップされたコンテンツです。';

    $lang->msg_cart_is_null = '?象を選?してください。';
    $lang->msg_checked_file_is_deleted = '%d個の添付ファイルが削除されました。';

    $lang->msg_find_account_title = '?員IDどパスワ?ドの情報';
    $lang->msg_find_account_info = '登?された?員情報は下記の通りです。';
    $lang->msg_find_account_comment = '下のリンクをクリックすると上のパスワ?ドに?更されます。<br />ログインしてからパスワ?ドを?更してください。';
    $lang->msg_confirm_account_title = '가입 인증 메일 입니다';
    $lang->msg_confirm_account_info = '가입하신 계정 정보는 아래와 같습니다';
    $lang->msg_confirm_account_comment = '아래 링크를 클릭하시면 가입 인증이 이루어집니다.';
    $lang->msg_auth_mail_sent = '%s メ?ルでは認?情報を含んだ?容が送信されました。 メ?ルを確認してください。';
    $lang->msg_confirm_mail_sent = '%s 메일로 가입 인증 메일이 발송되었습니다. 메일을 확인하세요.';
    $lang->msg_invalid_auth_key = '正しくないアカウントの認?要求です。<br />IDとパスワ?ドの?索を行うか、サイト管理者にアカウント情報をお問い合わせください。';
    $lang->msg_success_authed = '認?が正常に行われ、ログインできました。\n必ず認?メ?ルに記載されたパスワ?ドを利用してお好みのパスワ?ドに?更してください。.';
    $lang->msg_success_confirmed = '가입 인증이 정상적으로 처리 되었습니다.';

    $lang->msg_new_member = '?員追加';
    $lang->msg_update_member = '?員情報修正';
    $lang->msg_leave_member = '?員??';
    $lang->msg_group_is_null = '登?されたグル?プがありません。';
    $lang->msg_not_delete_default = '基本項目は削除できません。';
    $lang->msg_not_exists_member = '存在しないユ?ザＩＤです。';
    $lang->msg_cannot_delete_admin = '管理者ＩＤは削除できません。管理者?限を解除した上で削除してみてください。';
    $lang->msg_exists_user_id = '?に存在するユ?ザＩＤです。他のＩＤを入力してください。';
    $lang->msg_exists_email_address = '?に存在するメ?ルアドレスです。他のメ?ルアドレスを入力してください。';
    $lang->msg_exists_nick_name = '?に存在するニックネ?ムです。他のニックネ?ムを入力してください。';
    $lang->msg_signup_disabled = '?員に加入することができません。';
    $lang->msg_already_logged = '?に?員に加入されています。';
    $lang->msg_not_logged = 'ログインしていません。';
    $lang->msg_insert_group_name = 'グル?プ名を入力してください。';
    $lang->msg_check_group = 'グル?プを選?してください。';

    $lang->msg_not_uploaded_profile_image = 'プロフィ?ルイメ?ジを登?することができません。';
    $lang->msg_not_uploaded_image_name = 'イメ?ジ名を登?することができません。';
    $lang->msg_not_uploaded_image_mark = 'イメ?ジマ?クを登?することができません。';

    $lang->msg_accept_agreement = '規約に同意しなければなりません。'; 

    $lang->msg_user_denied = '入力されたユ?ザＩＤは使用が中止されました。';
    $lang->msg_user_not_confirmed = '아직 메일 인증이 이루어지지 않았습니다. 메일을 확인해 주세요';
    $lang->msg_user_limited = '入力されたユ?ザＩＤは%s以降から使用できます。';

    $lang->about_user_id = 'ユ?ザＩＤは３～２０の英?文字で構成され、最先頭の文字は英字でなければなりません。';
    $lang->about_password = 'パスワ?ドは６～２０文字です。';
    $lang->about_user_name = '名前は２～２０文字です。';
    $lang->about_nick_name = 'ニックネ?ムは２～２０文字です。';
    $lang->about_email_address = 'メ?ルアドレスはメ?ル認?後、パスワ?ド?更または?索などに使用されます。';
    $lang->about_homepage = 'ホ?ムペ?ジがある場合は入力してください。';
    $lang->about_blog_url = '運用しているブログがあれば入力してください。';
    $lang->about_birthday = '生年月日を入力してください。';
    $lang->about_allow_mailing = 'メ?リングリストにチェックされていない場合は、全?メ?ルの送信時にメ?ルを受け取りません。';
    $lang->about_denied = 'チェックするとユ?ザＩＤを使用できないようにします。';
    $lang->about_is_admin = 'チェックすると最高管理者?限が取得できます。';
    $lang->about_member_description = '?員に?する管理者のメモ帳です。';
    $lang->about_group = '一つのユ?ザＩＤは多?のグル?プに?することができます。';

    $lang->about_column_type = '追加する加入フォ?ムのタイプを指定してください。';
    $lang->about_column_name = 'テンプレ?トで使用できる英文字の名前を入力してください（??名）。';
    $lang->about_column_title = '加入または情報修正?照合時に表示されるタイトルです。';
    $lang->about_default_value = 'デフォルトで入力される値を指定することができます。';
    $lang->about_active = '活性化（有?化）にチェックを入れないと正常に表示されません。';
    $lang->about_form_description = '?明欄に入力すると加入時に表示されます。';
    $lang->about_required = 'チェックを入れると?員加入時に必須入力項目として設定されます。';

    $lang->about_enable_openid = 'OpenIDをサポ?トする際にチェックを入れます。';
    $lang->about_enable_join = 'チェックを入れないとユ?ザが?員に加入できません。';
    $lang->about_enable_confirm = '입력된 메일 주소로 인증 메일을 보내 회원 가입을 확인 합니다';
    $lang->about_enable_ssl = '서버에서 SSL지원이 될 경우 회원가입/정보수정/로그인등의 개인정보가 서버로 보내질때 SSL(https)를 이용하도록 할 수 있습니다';
    $lang->about_limit_day = '?員加入後一定の期間中、認?制限を行うことができます。';
    $lang->about_limit_date = '指定された期間まで該?ユ?ザはログインできなくします。';
    $lang->about_after_login_url = 'ログイン後表示されるペ?ジのURLを指定できます。指定のない場合、現在のペ?ジが維持されます。';
    $lang->about_after_logout_url = 'ログアウト後表示されるペ?ジのURLを指定できます。指定のない場合、現在のペ?ジが維持されます。';
    $lang->about_redirect_url = '?員加入後、表示されるペ?ジのURLを指定できます。指定のない場合は?員加入する前のペ?ジに?ります。';
    $lang->about_agreement = '?員加入規約がない場合は表示されません。';

    $lang->about_image_name = 'ユ?ザの名前を文字の代わりにイメ?ジで表示させることができます。';
    $lang->about_image_mark = '使用者の名前の前にマ?クを付けることができます。';
    $lang->about_profile_image = 'ユ?ザのプロフィ?ルイメ?ジが使用できるようにします。';
    $lang->about_signature_max_height = '署名欄の高さのサイズを制限します。 (0 もしくは空の場合は制限なし。)';
    $lang->about_accept_agreement = '加入規約をすべて?んだ上で同意します。'; 

    $lang->about_member_default = '?員加入時に基本グル?プとして設定されます。';

    $lang->about_openid = 'OpenIDで加入する際、ＩＤとメ?ルなどの基本情報は、このサイトに保存されますが、パスワ?ドと認?のための?理用の情報は該?するOpenID提供サ?ビス側で行われます。';
    $lang->about_openid_leave = 'OpenIDの??は現在のサイトから?員情報を削除することを意味します。<br />??後ログインすると新しく加入することになり、書き?んだコンテンツに?する?限を維持することができません。';
    $lang->about_find_member_account = 'ID/パスワ?ドは加入時に登?されたメ?ルにてお知らせします。<br />加入時に登?したメ?ルアドレスを入力して「IDとパスワ?ドの?索」ボタンをクリックしてください。<br />';

    $lang->about_member = "?員の作成?修正?削除することができ、グル?プの管理、加入フォ?ムの管理などが行える?員管理モジュ?ルです。デフォルトで作成されたグル?プにグル?プを追加作成して?員管理ができるようにし、加入フォ?ム管理では基本情報の他、フォ?ムの入力情報を追加することができます。";
?>
