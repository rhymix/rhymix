<?php
    /**
     * @class  adminAdminView
     * @author zero (zero@nzeo.com)
     * @brief  admin view class of admin module
     **/

    class adminAdminView extends admin {

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

			$this->loadSideBar();

            // Retrieve the list of installed modules 

            $db_info = Context::getDBInfo();

            Context::set('time_zone_list', $GLOBALS['time_zone']);
            Context::set('time_zone', $GLOBALS['_time_zone']);
            Context::set('use_rewrite', $db_info->use_rewrite=='Y'?'Y':'N');
            Context::set('use_optimizer', $db_info->use_optimizer!='N'?'Y':'N');
            Context::set('use_spaceremover', $db_info->use_spaceremover?$db_info->use_spaceremover:'Y');
            Context::set('qmail_compatibility', $db_info->qmail_compatibility=='Y'?'Y':'N');
            Context::set('use_db_session', $db_info->use_db_session=='N'?'N':'Y');
            Context::set('use_ssl', $db_info->use_ssl?$db_info->use_ssl:"none");
            if($db_info->http_port) Context::set('http_port', $db_info->http_port);
            if($db_info->https_port) Context::set('https_port', $db_info->https_port);

        }

        /**
         * @brief Display main administration page
         * @return none
         **/
        function dispAdminIndex() {
            //Retrieve recent news and set them into context
            $newest_news_url = sprintf("http://news.xpressengine.com/%s/news.php", Context::getLangType());
            $cache_file = sprintf("%sfiles/cache/newest_news.%s.cache.php", _XE_PATH_,Context::getLangType());
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

            // DB Information 
            $db_info = Context::getDBInfo();
            Context::set('selected_lang', $db_info->lang_type);

            // Current Version and Installed Path
            Context::set('current_version', __ZBXE_VERSION__);
            Context::set('installed_path', realpath('./'));

            // Get list of modules
            $oModuleModel = &getModel('module');
            $module_list = $oModuleModel->getModuleList();
            Context::set('module_list', $module_list);

            // Get list of addons
            $oAddonModel = &getAdminModel('addon');
            $addon_list = $oAddonModel->getAddonList();
            Context::set('addon_list', $addon_list);

            // 방문자수
			$oCounterModel = &getModel('counter');
            $time = time();
            $w = date("D");
            while(date("D",$time) != "Sun") {
                $time += 60*60*24;
            }
            $time -= 60*60*24;
            while(date("D",$time)!="Sun") {
                $thisWeek[] = date("Ymd",$time);
                $time -= 60*60*24;
            }
            $thisWeek[] = date("Ymd",$time);
            asort($thisWeek);
            $thisWeekCounter = $oCounterModel->getStatus($thisWeek);

            $time -= 60*60*24;
            while(date("D",$time)!="Sun") {
                $lastWeek[] = date("Ymd",$time);
                $time -= 60*60*24;
            }
            $lastWeek[] = date("Ymd",$time);
            asort($lastWeek);
            $lastWeekCounter = $oCounterModel->getStatus($lastWeek);

            $max = 0;
            foreach($thisWeek as $day) {
                $v = (int)$thisWeekCounter[$day]->unique_visitor;
                if($v && $v>$max) $max = $v;
                $status->week[date("D",strtotime($day))]->this = $v;
            }
            foreach($lastWeek as $day) {
                $v = (int)$lastWeekCounter[$day]->unique_visitor;
                if($v && $v>$max) $max = $v;
                $status->week[date("D",strtotime($day))]->last = $v;
            }
            $status->week_max = $max;
            $idx = 0;
            foreach($status->week as $key => $val) {
                $_item[] = sprintf("<item id=\"%d\" name=\"%s\" />", $idx, $thisWeek[$idx]);
                $_thisWeek[] = $val->this;
                $_lastWeek[] = $val->last;
                $idx++;
            }

            $buff = '<?xml version="1.0" encoding="utf-8" ?><Graph><gdata title="Textyle Counter" id="data2"><fact>'.implode('',$_item).'</fact><subFact>';
            $buff .= '<item id="0"><data name="'.Context::getLang('this_week').'">'.implode('|',$_thisWeek).'</data></item>';
            $buff .= '<item id="1"><data name="'.Context::getLang('last_week').'">'.implode('|',$_lastWeek).'</data></item>';
            $buff .= '</subFact></gdata></Graph>';
            Context::set('xml', $buff);

            // 각종 통계 정보를 구함
            $counter = $oCounterModel->getStatus(array(0,date("Ymd")),$this->site_srl);
            $status->total_visitor = $counter[0]->unique_visitor;
            $status->visitor = $counter[date("Ymd")]->unique_visitor;

            // 오늘의 댓글 수
            $args->regdate = date("Ymd");
            $output = executeQuery('admin.getTodayCommentCount', $args);
            $status->comment_count = $output->data->count;

            // 오늘의 엮인글 수
            $args->regdate = date("Ymd");
            $output = executeQuery('admin.getTodayTrackbackCount', $args);
            $status->trackback_count = $output->data->count;

            Context::set('status', $status);

            // 최근글 추출
			$oDocumentModel = &getModel('document');
            $doc_args->sort_index = 'list_order';
            $doc_args->order_type = 'asc';
            $doc_args->list_count = 3;
            $output = $oDocumentModel->getDocumentList($doc_args, false, false);
            Context::set('newest_documents', $output->data);

            // 최근 댓글 추출
			$oCommentModel = &getModel('comment');
            $com_args->sort_index = 'list_order';
            $com_args->order_type = 'asc';
            $com_args->list_count = 5;
            $output = $oCommentModel->getTotalCommentList($com_args);
            Context::set('newest_comments', $output->data);













            // Get statistics
            $args->date = date("Ymd000000", time()-60*60*24);
            $today = date("Ymd");

            // Member Status
            $output = executeQueryArray("admin.getMemberStatus", $args);
            if($output->data) {
                foreach($output->data as $var) {
                    if($var->date == $today) {
                        $status->member->today = $var->count;
                    } else {
                        $status->member->yesterday = $var->count;
                    }
                }
            }
            $output = executeQuery("admin.getMemberCount", $args);
            $status->member->total = $output->data->count;

            // Document Status
            $output = executeQueryArray("admin.getDocumentStatus", $args);
            if($output->data) {
                foreach($output->data as $var) {
                    if($var->date == $today) {
                        $status->document->today = $var->count;
                    } else {
                        $status->document->yesterday = $var->count;
                    }
                }
            }
            $output = executeQuery("admin.getDocumentCount", $args);
            $status->document->total = $output->data->count;

            // Comment Status 
            $output = executeQueryArray("admin.getCommentStatus", $args);
            if($output->data) {
                foreach($output->data as $var) {
                    if($var->date == $today) {
                        $status->comment->today = $var->count;
                    } else {
                        $status->comment->yesterday = $var->count;
                    }
                }
            }
            $output = executeQuery("admin.getCommentCount", $args);
            $status->comment->total = $output->data->count;

            // Trackback Status 
            $output = executeQueryArray("admin.getTrackbackStatus", $args);
            if($output->data) {
                foreach($output->data as $var) {
                    if($var->date == $today) {
                        $status->trackback->today = $var->count;
                    } else {
                        $status->trackback->yesterday = $var->count;
                    }
                }
            }
            $output = executeQuery("admin.getTrackbackCount", $args);
            $status->trackback->total = $output->data->count;

            // Attached files Status 
            $output = executeQueryArray("admin.getFileStatus", $args);
            if($output->data) {
                foreach($output->data as $var) {
                    if($var->date == $today) {
                        $status->file->today = $var->count;
                    } else {
                        $status->file->yesterday = $var->count;
                    }
                }
            }
            $output = executeQuery("admin.getFileCount", $args);
            $status->file->total = $output->data->count;

            // Reported documents Status
            $output = executeQueryArray("admin.getDocumentDeclaredStatus", $args);
            if($output->data) {
                foreach($output->data as $var) {
                    if($var->date == $today) {
                        $status->documentDeclared->today = $var->count;
                    } else {
                        $status->documentDeclared->yesterday = $var->count;
                    }
                }
            }
            $output = executeQuery("admin.getDocumentDeclaredCount", $args);
            $status->documentDeclared->total = $output->data->count;

            // Reported comments Status
            $output = executeQueryArray("admin.getCommentDeclaredStatus", $args);
            if($output->data) {
                foreach($output->data as $var) {
                    if($var->date == $today) {
                        $status->commentDeclared->today = $var->count;
                    } else {
                        $status->commentDeclared->yesterday = $var->count;
                    }
                }
            }
            $output = executeQuery("admin.getCommentDeclaredCount", $args);
            $status->commentDeclared->total = $output->data->count;

            $site_args->site_srl = 0;
            $output = executeQuery('module.getSiteInfo', $site_args);
            Context::set('start_module', $output->data);

            Context::set('status', $status);

            Context::set('layout','none');
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

			Context::set('use_mobile_view', $db_info->use_mobile_view=="Y"?'Y':'N');
            
            $ftp_info = Context::getFTPInfo();
            Context::set('ftp_info', $ftp_info);

            $site_args->site_srl = 0;
            $output = executeQuery('module.getSiteInfo', $site_args);
            Context::set('start_module', $output->data);

            Context::set('pwd',$pwd);
            Context::set('layout','none');
            $this->setTemplateFile('config');
        }
    }
?>
