<?php
    /**
     * @file   zh-TW.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  正體中文語言包 (包含基本內容)
     **/

    $lang->member = '會員';
    $lang->member_default_info = '基本資料';
    $lang->member_extend_info = '延伸資料';
    $lang->default_group_1 = "準會員";
    $lang->default_group_2 = "正會員";
    $lang->admin_group = "管理組";
    $lang->keep_signed = '自動登入';
    $lang->remember_user_id = '儲存ID';
    $lang->already_logged = '您已經登入！';
    $lang->denied_user_id = '被禁止的用戶名。';
    $lang->null_user_id = '請輸入用戶名。';
    $lang->null_password = '請輸入密碼。';
    $lang->invalid_authorization = '還沒有認證！';
    $lang->invalid_user_id= '該用戶名不存在，請檢查您的輸入是否有誤！';
    $lang->invalid_password = '您的密碼不正確！';
    $lang->allow_mailing = '接收郵件';
    $lang->denied = '禁止使用';
    $lang->is_admin = '最高管理權限';
    $lang->group = '用戶組';
    $lang->group_title = '用戶組標題';
    $lang->group_srl = '用戶組編號';
    $lang->signature = '簽名';
    $lang->profile_image = '簽名圖片';
    $lang->profile_image_max_width = '寬度限制';
    $lang->profile_image_max_height = '高度限制';
    $lang->image_name = '暱稱圖片';
    $lang->image_name_max_width = '寬度限制';
    $lang->image_name_max_height = '高度限制';
    $lang->image_mark = '用戶圖標';
    $lang->image_mark_max_width = '寬度限制';
    $lang->image_mark_max_height = '高度限制';
    $lang->signature_max_height = '簽名高度限制';
    $lang->enable_openid = '支援OpenID';
    $lang->enable_join = '允許會員註冊';
    $lang->enable_confirm = '使用郵件認證';
    $lang->enable_ssl = '使用SSL功能';
    $lang->security_sign_in = '使用安全登入';
    $lang->limit_day = '認證限制';
    $lang->limit_date = '限制日期';
    $lang->after_login_url = '登入後頁面轉向';
    $lang->after_logout_url = '登出後頁面轉向';
    $lang->redirect_url = '註冊會員後頁面轉向';
    $lang->agreement = '會員註冊條款';
    $lang->accept_agreement = '同意條款';
    $lang->member_info = '會員資料';
    $lang->current_password = '當前密碼';
    $lang->openid = 'OpenID';

    $lang->webmaster_name = '管理員名';
    $lang->webmaster_email = '管理員電子郵件';

    $lang->about_keep_signed = '關閉瀏覽器後也將維持登入狀態。\n\n使用自動登入功能，可解決每次訪問都要輸入用戶名及密碼的麻煩。\n\n為防止個人資料洩露，在網咖，學校等公共場所，請務必要確認解除登入狀態。';
    $lang->about_webmaster_name = '請輸入認證所需的電子郵件地址或管理其他網站時要使用的網站管理員名稱。(預設 : webmaster)';
    $lang->about_webmaster_email = '請輸入網站管理員的電子郵件地址。';

    $lang->search_target_list = array(
        'user_id' => '用戶名',
        'user_name' => '姓名',
        'nick_name' => '暱稱',
        'email_address' => '郵件地址',
        'regdate' => '註冊日期',
        'last_login' => '最近登入',
        'extra_vars' => '延伸變數',
    );

    $lang->cmd_login = '登入';
    $lang->cmd_logout = '登出';
    $lang->cmd_signup = '新會員註冊';
    $lang->cmd_modify_member_info = '修改會員資料';
    $lang->cmd_modify_member_password = '修改密碼';
    $lang->cmd_view_member_info = '檢視會員資料';
    $lang->cmd_leave = '退出';
    $lang->cmd_find_member_account = '查詢用戶名/密碼';

    $lang->cmd_member_list = '會員目錄';
    $lang->cmd_module_config = '基本設置';
    $lang->cmd_member_group = '用戶組管理';
    $lang->cmd_send_mail = '發送郵件';
    $lang->cmd_manage_id = '禁止用戶名管理';
    $lang->cmd_manage_form = '註冊表單管理';
    $lang->cmd_view_own_document = '檢視發表主題';
    $lang->cmd_trace_document = '主題追蹤';
    $lang->cmd_trace_comment = '評論追蹤';
    $lang->cmd_view_scrapped_document = '檢視收藏';
    $lang->cmd_view_saved_document = '檢視臨時儲存箱';
    $lang->cmd_send_email = '發送郵件';

    $lang->msg_email_not_exists = "找不到您輸入的Email地址。";

    $lang->msg_alreay_scrapped = '已收藏的主題！';

    $lang->msg_cart_is_null = '請選擇對象。';
    $lang->msg_checked_file_is_deleted = '已刪除%d個附件。';

    $lang->msg_find_account_title = '註冊資料。';
    $lang->msg_find_account_info = '您要尋找的註冊資料如下。';
    $lang->msg_find_account_comment = '點擊下面的連結您的註冊密碼，將更新為上述系統自動建立的密碼。<br />請重新登入，將密碼改為您想要的密碼。';
    $lang->msg_confirm_account_title = '會員註冊';
    $lang->msg_confirm_account_info = '您的註冊資料如下:';
    $lang->msg_confirm_account_comment = '請點擊下面連結完成會員認證。';
    $lang->msg_auth_mail_sent = '已向%s發送了認證郵件。請確認！！';
    $lang->msg_confirm_mail_sent = '已向%s發送了認證郵件。請確認！！';
    $lang->msg_invalid_auth_key = '錯誤的註冊資料請求。<br />請重新尋找用戶名及密碼， 或聯繫管理員。';
    $lang->msg_success_authed = '新的註冊資料已得到認證。請用郵件中的新密碼修改您要想使用的密碼。';
    $lang->msg_success_confirmed = '註冊資料已成功確認！';

    $lang->msg_new_member = '會員註冊';
    $lang->msg_update_member = '修改會員資料';
    $lang->msg_leave_member = '退出會員';
    $lang->msg_group_is_null = '無用戶組。';
    $lang->msg_not_delete_default = '不能刪除基本項目';
    $lang->msg_not_exists_member = '不存在的用戶';
    $lang->msg_cannot_delete_admin = '無法解除管理員ID，請解除管理後再刪除';
    $lang->msg_exists_user_id = '重複的用戶名 ，請重新輸入用戶名。';
    $lang->msg_exists_email_address = '重複的電子郵件地址，請重新輸入電子郵件地址。';
    $lang->msg_exists_nick_name = '重複的暱稱，請重新輸入暱稱。';
    $lang->msg_signup_disabled = '無法註冊會員';
    $lang->msg_already_logged = '您是註冊會員。';
    $lang->msg_not_logged = '您還沒登入。';
    $lang->msg_insert_group_name = '請輸入群組名稱';
    $lang->msg_check_group = '請選擇群組';

    $lang->msg_not_uploaded_profile_image = '無法登錄簽名圖像！';
    $lang->msg_not_uploaded_image_name = '無法登錄暱稱圖像！';
    $lang->msg_not_uploaded_image_mark = '無法登錄用戶圖標！';

    $lang->msg_accept_agreement = '您必須同意條款。'; 

    $lang->msg_user_denied = '您輸入的用戶名已禁止使用！';
    $lang->msg_user_not_confirmed = '您的註冊資料還沒有被確認，請確認您的電子郵箱。';
    $lang->msg_user_limited = '您輸入的用戶名%s以後才可以開始使用。';

    $lang->about_user_id = '用戶名長度必須由 3 ~20字以內的英文+數字組成，且首個字母必須是英文。';
    $lang->about_password = '密碼長度必須在 6~20字以內。';
    $lang->about_user_name = '姓名必須是 2~20字以內。';
    $lang->about_nick_name = '暱稱必須是 2~20字以內。';
    $lang->about_email_address = '電子郵件地址除郵件認證外，在修改密碼或找回密碼時使用。';
    $lang->about_homepage = '請輸入您的主頁地址。';
    $lang->about_blog_url = '請輸入部落格地址。';
    $lang->about_birthday = '請輸入您的出生年月日。';
    $lang->about_allow_mailing = '如不選擇此項，以後無法接收站內發送的重要資料。';
    $lang->about_denied = '選擇時不能使用此用戶名。';
    $lang->about_is_admin = '選擇時將具有最高管理權限。';
    $lang->about_member_description = '管理員對會員的筆記。';
    $lang->about_group = '一個用戶名可屬多個用戶組。';

    $lang->about_column_type = '請選擇要新增的註冊表單格式。';
    $lang->about_column_name = '請輸入在樣板中可以使用的英文名稱。（變數名）';
    $lang->about_column_title = '註冊或修改/檢視資料時要顯示的標題。';
    $lang->about_default_value = '可以設置預設值。';
    $lang->about_active = '必須選擇此項後才可以正常啟用。';
    $lang->about_form_description = '說明欄裡輸入的內容，在註冊時會顯示。';
    $lang->about_required = '註冊時成為必填項目。';

    $lang->about_enable_openid = '要想網站支援 OpenID時，請勾選此項。';
    $lang->about_enable_join = '選擇此項後用戶才可以註冊。';
    $lang->about_enable_confirm = '為確認會員註冊資料，會向會員輸入的郵件地址發送註冊認證郵件。';
    $lang->about_enable_ssl = '如主機提供 SSL認證服務，新會員註冊/修改會員資料/登入等資料的傳送將使用 SSL(https)認證。';
    $lang->about_limit_day = '註冊會員後的認證有效期限。';
    $lang->about_limit_date = '直到指定日期該用戶無法登入。';
    $lang->about_after_login_url = '可以指定登入後的頁面轉向url(留空為目前頁面)。';
    $lang->about_after_logout_url = '可以指定登出登錄後的頁面轉向url(留空為目前頁面)。';
    $lang->about_redirect_url = '請輸入註冊會員後的頁面轉向 url。(留空為返回前頁)';
    $lang->about_agreement = '沒有會員條款時不顯示。';

    $lang->about_image_name = '用戶暱稱可以用小圖片來替代顯示。';
    $lang->about_image_mark = '顯示在用戶暱稱前的小圖標。';
    $lang->about_profile_image = '可以使用簽名圖片。';
    $lang->about_signature_max_height = '可以限制簽名欄高度(0或留空為不限制)。';
    $lang->about_accept_agreement = '已閱讀全部條款並同意。'; 

    $lang->about_member_default = '將成為註冊會員時的預設用戶組。';

    $lang->about_openid = '用OpenID註冊時，該網站只儲存用戶名和 郵件等基本資料，密碼和認證處理是在提供OpenID服務的網站中得到解決。';
    $lang->about_openid_leave = '刪除OpenID就等於永久刪除站內用戶的資料。<br />被刪除後，再重新登錄就等於新會員註冊，因此對以前自己寫的主題將失去相應權限。';
    $lang->about_find_member_account = '用戶名/密碼將發送到您註冊時，所輸入的電子郵件當中。<br />輸入註冊時的電子郵件地址後，請點擊「尋找用戶名/密碼」按鈕。<br />';

    $lang->about_member = "可以新增/修改/刪除會員及管理用戶組或註冊表單的會員管理模組。\n此模組不僅可以建立預設用戶組以外的其他用戶組來管理會員，並且通過註冊表單的管理獲得除會員基本資料以外的延伸資料。";
?>
