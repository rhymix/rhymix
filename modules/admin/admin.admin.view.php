<?php
    /**
     * @class  adminAdminView
     * @author NHN (developers@xpressengine.com)
     * @brief  admin view class of admin module
     **/

    class adminAdminView extends admin {

		var $layout_list;

        /**
         * @brief Initilization
         * @return none
         **/
        function init() {

            // forbit access if the user is not an administrator
            $oMemberModel = &getModel('member');
            $logged_info = $oMemberModel->getLoggedInfo();
            if($logged_info->is_admin!='Y') return $this->stop("msg_is_not_administrator");

            // change into administration layout
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setLayoutPath($this->getTemplatePath());
            $this->setLayoutFile('layout.html');

			$this->makeGnbUrl();

            // Retrieve the list of installed modules

            $db_info = Context::getDBInfo();

            Context::set('time_zone_list', $GLOBALS['time_zone']);
            Context::set('time_zone', $GLOBALS['_time_zone']);
            Context::set('use_rewrite', $db_info->use_rewrite=='Y'?'Y':'N');
            Context::set('use_sso', $db_info->use_sso=='Y'?'Y':'N');
            Context::set('use_html5', $db_info->use_html5=='Y'?'Y':'N');
            Context::set('use_spaceremover', $db_info->use_spaceremover?$db_info->use_spaceremover:'Y');//not use
            Context::set('qmail_compatibility', $db_info->qmail_compatibility=='Y'?'Y':'N');
            Context::set('use_db_session', $db_info->use_db_session=='N'?'N':'Y');
            Context::set('use_mobile_view', $db_info->use_mobile_view =='Y'?'Y':'N');
            Context::set('use_ssl', $db_info->use_ssl?$db_info->use_ssl:"none");
			Context::set('use_cdn', $db_info->use_cdn?$db_info->use_cdn:"none");
            if($db_info->http_port) Context::set('http_port', $db_info->http_port);
            if($db_info->https_port) Context::set('https_port', $db_info->https_port);

			$this->showSendEnv();
        }

		function makeGnbUrl($module = 'admin')
		{
			global $lang;
			$oAdminAdminModel = &getAdminModel('admin');
			$lang->menu_gnb_sub = $oAdminAdminModel->getAdminMenuLang();

			$oMenuAdminModel = &getAdminModel('menu');
			$menu_info = $oMenuAdminModel->getMenuByTitle('__XE_ADMIN__');

			if(is_readable($menu_info->php_file))
				include $menu_info->php_file;
			else {
				header('location:'.getNotEncodedUrl('', 'module', 'admin'));
				return;
			}

            $oModuleModel = &getModel('module');
			$moduleActionInfo = $oModuleModel->getModuleActionXml($module);
			if(is_object($moduleActionInfo->menu))
			{
				$subMenuTitle = '';
				foreach($moduleActionInfo->menu AS $key=>$value)
				{
					if($value->acts && in_array(Context::get('act'), $value->acts))
					{
						$subMenuTitle = $value->title;
						break;
					}
				}
			}

			$parentSrl = 0;
			if(is_array($menu->list))
			{
				foreach($menu->list AS $key=>$value)
				{
					$parentMenu = $value;
					if(is_array($parentMenu['list']) && count($parentMenu['list']) > 0)
					{
						foreach($parentMenu['list'] AS $key2=>$value2)
						{
							$childMenu = $value2;
							if($subMenuTitle == $childMenu['text'])
							{
								$parentSrl = $childMenu['parent_srl'];
								break;
							}
						}
					}
				}
			}

			// Admin logo, title setup
			$configObject = $oModuleModel->getModuleConfig('admin');
			$gnbTitleInfo->adminTitle = $configObject->adminTitle?$configObject->adminTitle:'XE Admin';
			$gnbTitleInfo->adminLogo = $configObject->adminLogo?$configObject->adminLogo:'modules/admin/tpl/img/xe.h1.png';

			$browserTitle = 'Dashboard';
			if($subMenuTitle) $browserTitle = $subMenuTitle;
			$browserTitle .= ' - '.$gnbTitleInfo->adminTitle;

			Context::set('gnbUrlList', $menu->list);
			Context::set('parentSrl', $parentSrl);
			Context::set('gnb_title_info', $gnbTitleInfo);
            Context::setBrowserTitle($browserTitle);
		}

		function loadSideBar()
		{
            $oModuleModel = &getModel('module');
            $installed_module_list = $oModuleModel->getModulesXmlInfo();

            $installed_modules = $package_modules = array();
            $package_idx = 0;
            foreach($installed_module_list as $key => $val) {
				if($val->category == 'migration') $val->category = 'system';
				if($val->category == 'interlock') $val->category = 'accessory';
				if($val->category == 'statistics') $val->category = 'accessory';

                if($val->module == 'admin' || !$val->admin_index_act) continue;
                // get action information
                $action_spec = $oModuleModel->getModuleActionXml($val->module);
                $actions = array();
                if($action_spec->default_index_act) $actions[] = $action_spec->default_index_act;
                if($action_spec->admin_index_act) $actions[] = $action_spec->admin_index_act;
                if($action_spec->action) foreach($action_spec->action as $k => $v) $actions[] = $k;

                $obj = null;
                $obj->category = $val->category;
                $obj->title = $val->title;
                $obj->description = $val->description;
                $obj->index_act = $val->admin_index_act;
                if(in_array(Context::get('act'), $actions)) $obj->selected = true;

                // Packages
                if($val->category == 'package') {
                    if($package_idx == 0) $obj->position = "first";
                    else $obj->position = "mid";
                    $package_modules[] = $obj;
                    $package_idx ++;
                    if($obj->selected) Context::set('package_selected',true);
                // Modules
                } else {
                    $installed_modules[] = $obj;
                }
                if($obj->selected) {
                    Context::set('selected_module_category', $val->category);
                    Context::set('selected_module_info', $val);
                }
            }
            if(count($package_modules)) $package_modules[count($package_modules)-1]->position = 'end';
            Context::set('package_modules', $package_modules);
            Context::set('installed_modules', $installed_modules);
            Context::setBrowserTitle("XE Admin Page");

			// add javascript tooltip plugin - gony
			Context::loadJavascriptPlugin('qtip');
			Context::loadJavascriptPlugin('watchinput');
		}

        /**
         * @brief Display Super Admin Dashboard
         * @return none
         **/
        function dispAdminIndex() {
            // Get statistics
            $args->date = date("Ymd000000", time()-60*60*24);
            $today = date("Ymd");

            // Member Status
			$oMemberAdminModel = &getAdminModel('member');
			$status->member->todayCount = $oMemberAdminModel->getMemberCountByDate($today);
			$status->member->totalCount = $oMemberAdminModel->getMemberCountByDate();

            // Document Status
			$oDocumentAdminModel = &getAdminModel('document');
			$status->document->todayCount = $oDocumentAdminModel->getDocumentCountByDate($today);
			$status->document->totalCount = $oDocumentAdminModel->getDocumentCountByDate();

            // Comment Status
			$oCommentModel = &getModel('comment');
			$status->comment->todayCount = $oCommentModel->getCommentCountByDate($today);
			$status->comment->totalCount = $oCommentModel->getCommentCountByDate();

            // Trackback Status
			$oTrackbackAdminModel = &getAdminModel('trackback');
			$status->trackback->todayCount = $oTrackbackAdminModel->getTrackbackCountByDate($today);
			$status->trackback->totalCount = $oTrackbackAdminModel->getTrackbackCountByDate();

            // Attached files Status
			$oFileAdminModel = &getAdminModel('file');
			$status->file->todayCount = $oFileAdminModel->getFilesCountByDate($today);
			$status->file->totalCount = $oFileAdminModel->getFilesCountByDate();

            Context::set('status', $status);

            // Latest Document
			$oDocumentModel = &getModel('document');
			$columnList = array('document_srl', 'module_srl', 'category_srl', 'title', 'nick_name', 'member_srl');
			$args->list_count = 5;;
			$output = $oDocumentModel->getDocumentList($args, false, false, $columnList);
            Context::set('latestDocumentList', $output->data);
			unset($args, $output, $columnList);

			// Latest Comment
			$oCommentModel = &getModel('comment');
			$columnList = array('comment_srl', 'module_srl', 'document_srl', 'content', 'nick_name', 'member_srl');
			$args->list_count = 5;
			$output = $oCommentModel->getNewestCommentList($args, $columnList);
			if(is_array($output))
			{
				foreach($output AS $key=>$value)
					$value->content = strip_tags($value->content);
			}
            Context::set('latestCommentList', $output);
			unset($args, $output, $columnList);

			//Latest Trackback
			$oTrackbackModel = &getModel('trackback');
			$columnList = array();
			$args->list_count = 5;
			$output =$oTrackbackModel->getNewestTrackbackList($args);
            Context::set('latestTrackbackList', $output->data);
			unset($args, $output, $columnList);

            //Retrieve recent news and set them into context
            $newest_news_url = sprintf("http://news.xpressengine.com/%s/news.php?version=%s&package=%s", _XE_LOCATION_, __ZBXE_VERSION__, _XE_PACKAGE_);
            $cache_file = sprintf("%sfiles/cache/newest_news.%s.cache.php", _XE_PATH_, _XE_LOCATION_);
            if(!file_exists($cache_file) || filemtime($cache_file)+ 60*60 < time()) {
                // Considering if data cannot be retrieved due to network problem, modify filemtime to prevent trying to reload again when refreshing administration page
                // Ensure to access the administration page even though news cannot be displayed
                FileHandler::writeFile($cache_file,'');
                FileHandler::getRemoteFile($newest_news_url, $cache_file, null, 1, 'GET', 'text/html', array('REQUESTURL'=>getFullUrl('')));
            }

            if(file_exists($cache_file)) {
                $oXml = new XmlParser();
                $buff = $oXml->parse(FileHandler::readFile($cache_file));

                $item = $buff->zbxe_news->item;
                if($item) {
                    if(!is_array($item)) $item = array($item);

                    foreach($item as $key => $val) {
                        $obj = null;
                        $obj->title = $val->body;
                        $obj->date = $val->attrs->date;
                        $obj->url = $val->attrs->url;
                        $news[] = $obj;
                    }
                    Context::set('news', $news);
                }

                Context::set('released_version', $buff->zbxe_news->attrs->released_version);
                Context::set('download_link', $buff->zbxe_news->attrs->download_link);
            }

            // Get list of modules
            $oModuleModel = &getModel('module');
            $module_list = $oModuleModel->getModuleList();
			if(is_array($module_list))
			{
				$isUpdated = false;
				foreach($module_list AS $key=>$value)
				{
					if($value->need_install || $value->need_update)
						$isUpdated = true;
				}
			}
            Context::set('module_list', $module_list);
            Context::set('isUpdated', $isUpdated);

			// favorite
			$oAdminModel = &getAdminModel('admin');
			$output = $oAdminModel->getFavoriteList(0);
			$favorite_list = $output->get('favoriteList');
			if ($favorite_list)
			{
				foreach($favorite_list as $no => $favorite)
				{
					$module_info = $oModuleModel->getModuleInfoXml($favorite->module);
					if (!$module_info) continue;
					$favorite_list[$no] = $module_info;
					$favorite_list[$no]->module = $favorite->module;
				}
			}
			Context::set('favorite_list', $favorite_list);

			// gathering enviroment check
			$path = FileHandler::getRealPath('./files/env/'.__ZBXE_VERSION__);
			$isEnviromentGatheringAgreement = false;
			if(file_exists($path)) $isEnviromentGatheringAgreement = true;
			Context::set('isEnviromentGatheringAgreement', $isEnviromentGatheringAgreement);
            Context::set('layout','none');

			// Get list of favorite
			$oAdminAdminModel = &getAdminModel('admin');
			$output = $oAdminAdminModel->getFavoriteList(0, true);
            Context::set('favorite_list', $output->data);

            $this->setTemplateFile('index');
        }

        /**
         * @brief Display Configuration(settings) page
         * @return none
         **/
        function dispAdminConfig() {
            $db_info = Context::getDBInfo();

			Context::set('sftp_support', function_exists(ssh2_sftp));

            Context::set('selected_lang', $db_info->lang_type);

			Context::set('default_url', $db_info->default_url);

            Context::set('langs', Context::loadLangSupported());

            Context::set('lang_selected', Context::loadLangSelected());

            Context::set('admin_ip', $db_info->admin_ip);


			$favicon_url = $this->iconUrlCheck('favicon.ico','faviconSample.png');
			$mobicon_url = $this->iconUrlCheck('mobicon.png','mobiconSample.png');
            Context::set('favicon_url', $favicon_url);
			Context::set('mobicon_url', $mobicon_url);

            $ftp_info = Context::getFTPInfo();
            Context::set('ftp_info', $ftp_info);

			$oDocumentModel = &getModel('document');
            $config = $oDocumentModel->getDocumentConfig();
            Context::set('thumbnail_type',$config->thumbnail_type);

			$oModuleModel = &getModel('module');
			$columnList = array('modules.mid', 'modules.browser_title', 'sites.index_module_srl');
			$start_module = $oModuleModel->getSiteInfo(0, $columnList);
            Context::set('start_module', $start_module);

            Context::set('pwd',$pwd);
            Context::set('layout','none');
            $this->setTemplateFile('config');
        }

		/**
         * @brief Display Admin Menu Configuration(settings) page
         * @return none
         **/
		function dispAdminSetup()
		{
			$oModuleModel = &getModel('module');
			$configObject = $oModuleModel->getModuleConfig('admin');

			$oMenuAdminModel = &getAdminModel('menu');
			$output = $oMenuAdminModel->getMenuByTitle('__XE_ADMIN__');

			Context::set('menu_srl', $output->menu_srl);
			Context::set('config_object', $configObject);
            $this->setTemplateFile('admin_setup');
		}


		function showSendEnv() {
			if(Context::getResponseMethod() != 'HTML') return;

			$server = 'http://collect.xpressengine.com/env/img.php?';
			$path = './files/env/';
			$install_env = $path . 'install';

			if(file_exists(FileHandler::getRealPath($install_env))) {
				$oAdminAdminModel = &getAdminModel('admin');
				$params = $oAdminAdminModel->getEnv('INSTALL');
				$img = sprintf('<img src="%s" alt="" style="height:0px;width:0px" />', $server.$params);
				Context::addHtmlFooter($img);

				FileHandler::removeDir($path);
				FileHandler::writeFile($path.__ZBXE_VERSION__,'1');

			}
			else if(isset($_SESSION['enviroment_gather']) && !file_exists(FileHandler::getRealPath($path.__ZBXE_VERSION__)))
			{
				if($_SESSION['enviroment_gather']=='Y')
				{
					$oAdminAdminModel = &getAdminModel('admin');
					$params = $oAdminAdminModel->getEnv();
					$img = sprintf('<img src="%s" alt="" style="height:0px;width:0px" />', $server.$params);
					Context::addHtmlFooter($img);
				}

				FileHandler::removeDir($path);
				FileHandler::writeFile($path.__ZBXE_VERSION__,'1');
				unset($_SESSION['enviroment_gather']);
			}
		}

		function dispAdminTheme(){
			// choice theme file
			$theme_file = _XE_PATH_.'files/theme/theme_info.php';
			if(is_readable($theme_file)){
				@include($theme_file);
				Context::set('current_layout', $theme_info->layout);
				Context::set('theme_info', $theme_info);
			}
			else{
				$oModuleModel = &getModel('module');
				$default_mid = $oModuleModel->getDefaultMid();
				Context::set('current_layout', $default_mid->layout_srl);
			}

			// layout list
			$oLayoutModel = &getModel('layout');
			// theme 정보 읽기

			$oAdminModel = &getAdminModel('admin');
			$theme_list = $oAdminModel->getThemeList();
			$layouts = $oLayoutModel->getLayoutList(0);
			$layout_list = array();
			if (is_array($layouts)){
				foreach($layouts as $val){
					unset($layout_info);
					$layout_info = $oLayoutModel->getLayout($val->layout_srl);
					$layout_parse = explode('.', $layout_info->layout);
					if (count($layout_parse) == 2){
						$thumb_path = sprintf('./themes/%s/layout/%s/thumbnail.png', $layout_parse[0], $layout_parse[1]);
					}
					else{
						$thumb_path = './layouts/'.$layout_info->layout.'/thumbnail.png';
					}
					$layout_info->thumbnail = (is_readable($thumb_path))?$thumb_path:null;
					$layout_list[] = $layout_info;
				}
			}
			Context::set('theme_list', $theme_list);
			Context::set('layout_list', $layout_list);

			// 설치된module 정보 가져오기
			$module_list = $oAdminModel->getModulesSkinList();
			Context::set('module_list', $module_list);

			$this->setTemplateFile('theme');
		}

		function iconUrlCheck($iconname,$default_icon_name){
			$file_exsit = FileHandler::readFile(_XE_PATH_.'files/attach/xeicon/'.$iconname);
			if(!$file_exsit){
				$icon_url = './modules/admin/tpl/img/'.$default_icon_name	;
			} else {
				$icon_url = $db_info->default_url.'files/attach/xeicon/'.$iconname;
			}
			return $icon_url;
		}
    }
