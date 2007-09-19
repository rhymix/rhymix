<?php
    /**
     * @file   modules/member/jp.lang.php
     * @author zero (zero@nzeo.com) 翻訳：RisaPapa
     * @brief  日本語言語パッケージ（基本的な内容のみ）
     **/

    $lang->member = '会員';
    $lang->member_default_info = '基本情報';
    $lang->member_extend_info = '追加情報';
    $lang->default_group_1 = "準会員";
    $lang->default_group_2 = "正会員";
    $lang->admin_group = "管理グループ";
    $lang->remember_user_id = 'ＩＤ保存';
    $lang->already_logged = '既にログインされています。';
    $lang->denied_user_id = '使用が禁じられているＩＤです。';
    $lang->null_user_id = 'ユーザＩＤをもう一度入力してください。';
    $lang->null_password = 'パスワードを入力してください。';
    $lang->invalid_authorization = '認証できませんでした。';
    $lang->invalid_user_id= '存在しないユーザＩＤです。';
    $lang->invalid_password = '無効なパスワードです。';
    $lang->allow_mailing = 'メーリングリスト';
    $lang->allow_message = 'メッセージの受信';
    $lang->allow_message_type = array(
             'Y' => '全て受信',
             'N' => '全て受信しない',
             'F' => '友達からのみ受信する',
        );
    $lang->denied = '使用中止';
    $lang->is_admin = '最高管理権限';
    $lang->group = '所属グループ';
    $lang->group_title = 'グループタイトル';
    $lang->group_srl = 'グループ番号';
    $lang->signature = '著名';
    $lang->image_name = 'イメージ名';
    $lang->image_name_max_width = '横幅制限サイズ';
    $lang->image_name_max_height = '立幅制限サイズ';
    $lang->image_mark = 'イメージマーク';
    $lang->image_mark_max_width = '横幅制限サイズ';
    $lang->image_mark_max_height = '立幅制限サイズ';
    $lang->enable_openid = 'OpenIDを使用する';
    $lang->enable_join = '会員加入を許可する';
    $lang->limit_day = '臨時制限日';
    $lang->limit_date = '制限日';
    $lang->redirect_url = '加入後移動ページ';
    $lang->agreement = '会員加入規約';
    $lang->accept_agreement = '規約に同意する';
    $lang->sender = '送信者';
    $lang->receiver = '受信者';
    $lang->friend_group = '友達グループ';
    $lang->default_friend_group = 'グループ未指定';
    $lang->member_info = '会員情報';
    $lang->current_password = '現在のパスワード';
    $lang->openid = 'OpenID';

    $lang->search_target_list = array(
        'user_id' => 'ユーザＩＤ',
        'user_name' => '名前',
        'nick_name' => 'ニックネーム',
        'email_address' => 'メールアドレス',
        'regdate' => '加入日',
        'last_login' => '最近のログイン',
    );

    $lang->message_box = array(
        'R' => 'メッセージ受信ボックス',
        'S' => 'メッセージ送信ボックス',
        'T' => '保存ボックス',
    );

    $lang->readed_date = "開封時間"; 

    $lang->cmd_login = 'ログイン';
    $lang->cmd_logout = 'ログアウト';
    $lang->cmd_signup = '会員加入';
    $lang->cmd_modify_member_info = '会員情報修正';
    $lang->cmd_modify_member_password = 'パスワード変更';
    $lang->cmd_view_member_info = '会員情報表示';
    $lang->cmd_leave = '脱会';

    $lang->cmd_member_list = '会員リスト';
    $lang->cmd_module_config = '基本設定';
    $lang->cmd_member_group = 'グループ管理';
    $lang->cmd_send_mail = 'メール送信';
    $lang->cmd_manage_id = '禁止ＩＤ管理';
    $lang->cmd_manage_form = '加入フォーム管理';
    $lang->cmd_view_own_document = 'プレビュー';
    $lang->cmd_view_scrapped_document = 'スクラップ表示';
    $lang->cmd_send_email = 'メール送信';
    $lang->cmd_send_message = 'メッセージ送信';
    $lang->cmd_reply_message = 'メッセージへの返信';
    $lang->cmd_view_friend = '友達表示';
    $lang->cmd_add_friend = '友達登録';
    $lang->cmd_view_message_box = 'メッセージ表示';
    $lang->cmd_store = "保存";
    $lang->cmd_add_friend_group = '友達グループ追加';
    $lang->cmd_rename_friend_group = '友達グループ名変更';

    $lang->msg_alreay_scrapped = '既にスクラップされたコンテンツです。';

    $lang->msg_cart_is_null = '対象を選択してください。';
    $lang->msg_checked_file_is_deleted = '%d個の添付ファイルが削除されました。';

    $lang->msg_no_message = 'メッセージがありません。';
    $lang->message_received = 'メッセージが届きました。';

    $lang->msg_new_member = '会員追加';
    $lang->msg_update_member = '会員情報修正';
    $lang->msg_leave_member = '会員脱会';
    $lang->msg_group_is_null = '登録されたグループがありません。';
    $lang->msg_not_delete_default = '基本項目は削除できません。';
    $lang->msg_not_exists_member = '存在しないユーザＩＤです。';
    $lang->msg_cannot_delete_admin = '管理者ＩＤは削除できません。管理者権限を解除した上で削除してみてください。';
    $lang->msg_exists_user_id = '既に存在するユーザＩＤです。他のＩＤを入力してください。';
    $lang->msg_exists_email_address = '既に存在するメールアドレスです。他のメールアドレスを入力してください。';
    $lang->msg_exists_nick_name = '既に存在するニックネームです。他のニックネームを入力してください。';
    $lang->msg_signup_disabled = '会員に加入することができません。';
    $lang->msg_already_logged = '既に会員に加入されています。';
    $lang->msg_not_logged = 'ログインしていません。';
    $lang->msg_title_is_null = 'メッセージのタイトルを入力してください。';
    $lang->msg_content_is_null = '内容を入力してください。';
    $lang->msg_allow_message_to_friend = '友達からのみメッセージを受信できるように設定したユーザであるため、送信できませんでした。';
    $lang->msg_disallow_message = 'メッセージの受信を拒否している受信者であるため、送信できませんでした。';
    $lang->msg_insert_group_name = 'グループ名を入力してください。';

    $lang->msg_not_uploaded_image_name = 'イメージ名を登録することができません。';
    $lang->msg_not_uploaded_image_mark = 'イメージマークを登録することができません。';

    $lang->msg_accept_agreement = '規約に同意しなければなりません。'; 

    $lang->msg_user_denied = '入力されたユーザＩＤは使用が中止されました。';
    $lang->msg_user_limited = '入力されたユーザＩＤは%s以降から使用できます。';

    $lang->about_user_id = 'ユーザＩＤは３～２０の英数文字で構成され、最先頭の文字は英字でなければなりません。';
    $lang->about_password = 'パスワードは６～２０文字です。';
    $lang->about_user_name = '名前は２～２０文字です。';
    $lang->about_nick_name = 'ニックネームは２～２０文字です。';
    $lang->about_email_address = 'メールアドレスはメール認証後、パスワード変更または検索などに使用されます。';
    $lang->about_homepage = 'ホームページがある場合は入力してください。';
    $lang->about_blog_url = '運用しているブログがあれば入力してください。';
    $lang->about_birthday = '生年月日を入力してください。';
    $lang->about_allow_mailing = 'メーリングリストにチェックされていない場合は、全体メールの送信時にメールを受け取りません。';
    $lang->about_allow_message = 'メッセージを受信するかを設定します。';
    $lang->about_denied = 'チェックするとユーザＩＤを使用できないようにします。';
    $lang->about_is_admin = 'チェックすると最高管理者権限が取得できます。';
    $lang->about_description = '会員に対する管理者のメモ帳です。';
    $lang->about_group = '一つのユーザＩＤは多数のグループに属することができます。';

    $lang->about_column_type = '追加する加入フォームのタイプを指定してください。';
    $lang->about_column_name = 'テンプレートで使用できる英文字の名前を入力してください（変数名）。';
    $lang->about_column_title = '加入または情報修正・照合時に表示されるタイトルです。';
    $lang->about_default_value = 'デフォルトで入力される値を指定することができます。';
    $lang->about_active = '活性化（有効化）にチェックを入れないと正常に表示されません。';
    $lang->about_form_description = '説明欄に入力すると加入時に表示されます。';
    $lang->about_required = 'チェックを入れると会員加入時に必須入力項目として設定されます。';

    $lang->about_enable_openid = 'OpenIDをサポートする際にチェックを入れます。';
    $lang->about_enable_join = 'チェックを入れないとユーザが会員に加入できません。';
    $lang->about_limit_day = '会員加入後一定の期間中、認証制限を行うことができます。';
    $lang->about_limit_date = '指定された期間まで該当ユーザはログインできなくします。';
    $lang->about_redirect_url = '会員加入後、移動するＵＲＬを入力してください。空の場合は会員加入する前のページに戻ります。';
    $lang->about_agreement = '会員加入規約がない場合は表示されません。';

    $lang->about_image_name = 'ユーザの名前を文字の代わりにイメージで表示させることができます。';
    $lang->about_image_mark = '使用者の名前の前にマークを付けることができます。';
    $lang->about_accept_agreement = '加入規約をすべて読んだ上で同意します。'; 

    $lang->about_member_default = '会員加入時に基本グループとして設定されます。';

    $lang->about_openid = 'OpenIDで加入する際、ＩＤとメールなどの基本情報は、このサイトに保存されますが、パスワードと認証のための処理用の情報は該当するOpenID提供サービス側で行われます。';
    $lang->about_openid_leave = '오픈아이디의 탈퇴는 현 사이트에서의 회원 정보를 삭제하는 것입니다.<br />탈퇴 후 로그인하시면 새로 가입하시는 것으로 되어 작성한 글에 대한 권한을 가질 수 없게 됩니다';

    $lang->about_member = "会員の作成・修正・削除することができ、グループの管理、加入フォームの管理などが行える会員管理モジュールです。デフォルトで作成されたグループにグループを追加作成して会員管理ができるようにし、加入フォーム管理では基本情報の他、フォームの入力情報を追加することができます。";
?>
