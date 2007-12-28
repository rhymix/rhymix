<?php
    /**
     * @class livechat_info
     * @author digirave (digirave@kmle.com)
     * @version 1.31
     * @brief 라이브 대화방(LiveChat)
     *
     * 
     **/

    class gagachat extends WidgetHandler {

        /**
         * @brief 위젯의 실행 부분
         * ./widgets/위젯/conf/info.xml에 선언한 extra_vars를 args로 받는다
         * 결과를 만든후 print가 아니라 return 해주어야 한다
         **/
        function proc($args) {
            // 템플릿의 스킨 경로를 지정 (skin, colorset에 따른 값을 설정)
            $tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
            Context::set('colorset', $args->colorset);
						/*
						$name = getenv("HTTP_HOST") . getenv("SCRIPT_NAME");
						$name = preg_replace("/^http:\/\//", "" ,$name);
						$name = preg_replace("/(.+)\/.+?$/", "$1" ,$name);
						*/
						$name = $args->chatroom;
						$roomkey  = $args->roomkey;
						$gposition  = $args->gposition;
						$gfonttype  = $args->gfonttype;
						$gfontcolor  = $args->gfontcolor;
						$gbgcolor  = $args->gbgcolor;

						if(strlen($args->gheight) > 2)  {
							$gheight  = $args->gheight;
						}
						else  {
							$gheight  = 450;
						}
						$fontlarge  = $args->fontlarge;
						$gagaadmin = $args->gagaadmin;
						$gagaadmin = preg_replace('/\s*,\s*/', ',', $gagaadmin);
						$gagaadmins = split(',', $gagaadmin);
						
						$fixbug = 0;
				
						$vars = Context::getRequestVars();
						if($vars->act == "dispPageAdminContentModify")  {
							$fixbug = 15;
						}

						Context::set('fixbug', $fixbug);
						Context::set('gheight', $gheight);
            Context::set('gposition', $gposition);
            Context::set('fontlarge', $fontlarge);
            Context::set('gfonttype', $gfonttype);
            Context::set('gfontcolor', $gfontcolor);
            Context::set('gbgcolor', $gbgcolor);
						Context::set('name', $name);
						
            $tpl_file = 'livechat';
						
						if(substr($name, 0, 1) == "#" || substr($name, 0, 1) == "@") {
							if(Context::get('is_logged'))  {
		            $oModuleModel = &getModel('module');
		            $this->member_config = $oModuleModel->getModuleConfig('member');
								$logged_info = Context::get('logged_info');
		            //Context::set('member_config', $this->member_config);
		            Context::set('user', $logged_info->nick_name);
		            Context::set('userkey', userKey($logged_info->nick_name, $roomkey));
								foreach($gagaadmins as $value)  {
									if($logged_info->user_id == $value)  {
										 //일반 유저인 경우
				            Context::set('userkey', md5(userKey($logged_info->nick_name, $roomkey)));
									}  //관리자인 경우
								}
		            $tpl_file = 'livechat2';
							}
						}
						// 템플릿 파일을 지정
						/*
            if(Context::get('is_logged')) $tpl_file = 'login_info';
            else $tpl_file = 'login_form';

            // 회원 관리 정보를 받음
            $oModuleModel = &getModel('module');
            $this->member_config = $oModuleModel->getModuleConfig('member');
            Context::set('member_config', $this->member_config);
						*/

            // 템플릿 컴파일
            $oTemplate = &TemplateHandler::getInstance();
            return $oTemplate->compile($tpl_path, $tpl_file);
        }


    }

	if($GLOBALS['userKeyDefined'] != 1)  {
	 $GLOBALS['userKeyDefined'] = 1;
	  function userKey($user, $roomKey)  {
	    return md5(md5($user . $roomKey) . $roomKey);
  }
}
?>
