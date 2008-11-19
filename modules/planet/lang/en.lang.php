<?php
    /**
     * @file   ko.lang.php
     * @author sol (sol@ngleader.com)
     * @brief  플래닛(planet) 모듈의 기본 언어팩
     **/

    $lang->planet = "Planet";
    $lang->planet_title = "Title";
    $lang->planet_url = "Planet URL";
    $lang->planet_myplanet = "My planet";
    $lang->planet_whos_planet = "%s's planet";
    $lang->planet_whos_favorite = "%s's favorite planets";
    $lang->planet_whos_favorite_list = "%s's favorite planets";

    $lang->planet_welcome = "Welcome!";

    $lang->planet_reply_content = "플래닛 댓글 내용";

    $lang->about_planet = 
        "XE microblog module. Each member can create the own planet.
         Planet may not be accessible with the domain name used in the homepage package.
         If you want to use planet as the index page, use different domain from domain name used in the homepage package.";

    $lang->planet_mid = "Access mid for the planet";
    $lang->about_planet_mid = "The planet can be accessed via http://addr/[mid]";

    $lang->planet_default_skin = "Planet default skin";
    $lang->about_planet_default_skin = "It will be set for main planet page and created planets.";

    $lang->planet_comment = "플래닛 한줄 소개";
    $lang->about_planet_comment = "플래닛 한줄 소개는 플래닛 접속시 브라우저 제목과 플래닛의 한줄 소개란에 표시되는 내용입니다";

    $lang->use_signup = "Display the link to sign up";
    $lang->about_use_signup = "If set, the link to sign up will be displayed at the top of the planet";

    $lang->cmd_create_planet = "Create my planet";
    $lang->create_message = "Introducing message";
    $lang->about_create_message = "Input the introducing message when user creates a planet";

    $lang->cmd_planet_setup = "Configuration";
    $lang->cmd_planet_list = "Planet List";

    $lang->msg_not_logged = "You are not signed in";
    $lang->msg_planet_exists = "You already have a planet, thus you cannot create more!";

    $lang->planet_userinfo = "User Info";
    $lang->planet_change_userinfo = "Change user info";

    $lang->planet_change_photo = "Change Photo";
    $lang->about_planet_change_photo = "Image size is set to 96*96 pixels(same with MSN)";
    $lang->cmd_planet_image_upload = "Upload";

    $lang->cmd_planet_good = "원츄";
    $lang->cmd_planet_addfavorite = "Add to Favorite";

    $lang->planet_hot_tag = "Popular tags";
    $lang->planet_home = "Home";
    $lang->cmd_planet_more_tag = "Display more tags";

    $lang->planet_memo = "Memo";
    $lang->cmd_planet_show_memo_write_form = "Write";
    $lang->cmd_planet_delete_memo = "Delete memo";
    $lang->cmd_planet_memo_write_ok = "Submit";

    $lang->planet_interest_tag = "Interesting tags";
    $lang->planet_interest_content = "Interesting articles";
    $lang->cmd_planet_show_interest_tag = "Display interesting tags";
    $lang->cmd_planet_close_interest_tag = "Close interesting tags";
    $lang->msg_planet_already_added_interest_tag = "이미 등록된 관심태그입니다";

    $lang->cmd_planet_edit_subject = "Edit title";
    $lang->cmd_planet_edit_intro = "Edit intro";
    $lang->cmd_planet_edit_tag = "Edit tags";

    $lang->cmd_planet_openclose_memo = "Memo open/close";
    $lang->cmd_planet_del_tag = "Delete tag";

    $lang->cmd_planet_openclose_recommend_search = "추천 검색어 열기/닫기";
    $lang->about_planet_input_search_text = "검색어입력";

    $lang->about_planet_make_planet = "Create your own planet. Please input the following information";
    $lang->about_planet_make_planet_info = "내 플래닛의 상단에 보여질 나의 정보입니다. 각 항목을 원하는 대로 설정하고 변경해보세요.";
    $lang->planet_input_personalinfo = "개인정보입력";
    $lang->planet_photo = "Photo";
    $lang->planet_myintro = "Introduction";

    $lang->about_planet_url = "You cannot change the url later.";
    $lang->planet_mytag = "Personal Tag";
    $lang->about_planet_mytag = "Tags that can express you (seperated by ,)";

    $lang->about_planet_tag = "Multiple tags can be seperated by comma(,)";

    $lang->cmd_planet_makeOk_move_myplanet = "Confirm : Move to my planet";
    $lang->cmd_planet_ok_move_myplanet = "Confirm : Move to my planet";


    $lang->about_planet_login = "Input ID and password, click the login button";

    $lang->cmd_planet_login = "Login";


    $lang->planet_nowhot_tag = "플래닛 실시간 인기태그";
    $lang->cmd_planet_close_nowhot_tag = "실시간 인기태그 닫기";

    $lang->about_planet_whats_textSearch_in_planet = "<strong>%s</strong>님의 플래닛에서 검색한 <strong>'%s'</strong> 에 대한 결과 입니다.";
    $lang->about_planet_whats_textSearch = "<strong>'%s'</strong> 에 대한 전체 검색결과 입니다.";

    $lang->planet_acticle = "Article";
    $lang->planet_persontag = "Person Tag";

    $lang->planet_recent_acticle = "Recent Articles";


    $lang->cmd_planet_add_tag = "Add"; 
    $lang->cmd_planet_add_article = "Write a message";
    $lang->cmd_planet_post_article = "Submit";
    $lang->planet_postscript = "P.S.";
    $lang->planet_article_preview = "Preview";


    $lang->planet_notice_title = "Welcome %s<br />This is a planet where you can share your thoughts, opinions, information, and knowledge with others. We will introduce how to use it. :)";
    $lang->planet_notice_list = array(
        "Click the 'Open' button of 'Write a message' window. The window for writing a message will be open",
        "New message is displayed by all the visitors, and they may write comments to it.",
        "If you use 'Add to favorite', and 'Add an interesting tag' feature, you can find them easily.",
        "You can immediately change your information, such as 'photo, nickname, tag' here.",
        "Corious about others' plates? Use 'hot tags' or search.",
        "If you have more question, search with 'Question' tag.",
    );
    $lang->planet_notice_disable = "Do not display this message again.";

    $lang->msg_planet_about_postscript = "Input the postscript.";
    $lang->msg_planet_about_tag = "Input tags. (seperated by ,)";
    $lang->msg_planet_already_added_favorite = "It is already registered as favorite";
    $lang->msg_planet_no_memo = "There is no memo";

    $lang->msg_planet_rss_enabled = "RSS is enabled";
    $lang->msg_planet_rss_disabled = "RSS is disabled";

    $lang->msg_me2day_sync = "Send to Me2day";
    $lang->msg_me2day_sync_q = "Would you like to send a message to Me2day?";
    $lang->me2day_id = "Me2day address";
    $lang->me2day_ukey = "User Key";
    $lang->msg_me2day_activate = "Send to Me2day always";
    $lang->msg_fail_auth_me2day = "Me2day authentication failed";
    $lang->msg_success_auth_me2day = "Me2day authentication was successful";

    $lang->planet_total_articles = "All";
    $lang->planet_wantyou = "원츄";
    $lang->planet_best = "월척";
    $lang->planet_catch = "낚은 글";
    $lang->planet_fish = "낚인 글";
    $lang->planet_bigfish = "월척";
    $lang->cmd_send_me2day = "Me2day";

    $lang->msg_already_have_phone_number = 'The phone number is already registered';
    $lang->planet_mobile_receive = '모바일 연동';
    $lang->planet_mobile_number = 'Phone number';
    $lang->msg_success_set_phone_number = 'The phone number is registered';

    $lang->planet_tagtab = "Tags for Main Tab";
    $lang->about_planet_tagtab = "You can set multiple tags seperated by comma(,), These tags displayed as tabs in the main page.";
    $lang->planet_smstag = "SMS Tag";
    $lang->about_planet_smstag = "You can set multiple tags seperated by comma(,), These tags automatically added if the posting is registered via SMS";

    $lang->planet_use_mobile = "Enable SMS";
    $lang->about_use_mobile = "Enable to write posting via mobile SMS";
    $lang->planet_use_me2day = "Use Me2day";
    $lang->about_use_me2day = "When writing a message, users can send it to me2day(http://me2day.net).";

?>
