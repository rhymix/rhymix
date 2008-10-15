<?php
    /**
     * @class  adminAdminView
     * @author zero (zero@nzeo.com)
     * @brief  admin 모듈의 admin view class
     **/

    class adminAdminView extends admin {

        /**
         * @brief 초기화
         **/
        function init() {
            if(!$this->grant->is_admin) return;

            // template path 지정
            $this->setTemplatePath($this->module_path.'tpl');

            // 접속 사용자에 대한 체크
            $oMemberModel = &getModel('member');
            $logged_info = $oMemberModel->getLoggedInfo();

            // 관리자용 레이아웃으로 변경
            $this->setLayoutPath($this->getTemplatePath());
            $this->setLayoutFile('layout.html');

            // 설치된 모듈 목록 가져오기
            $oModuleModel = &getModel('module');
            $installed_module_list = $oModuleModel->getModulesXmlInfo();
            foreach($installed_module_list as $key => $val) {
                $action_spec = $oModuleModel->getModuleActionXml($val->module);
                $actions = array();
                if($action_spec->default_index_act) $actions[] = $action_spec->default_index_act;
                if($action_spec->admin_index_act) $actions[] = $action_spec->admin_index_act;
                if($action_spec->action) foreach($action_spec->action as $k => $v) $actions[] = $k;
                $installed_module_list[$key]->actions = $actions;
            }
            Context::set('installed_module_list', $installed_module_list);

            $db_info = Context::getDBInfo();

            Context::set('time_zone_list', $GLOBALS['time_zone']);
            Context::set('time_zone', $GLOBALS['_time_zone']);
            Context::set('use_rewrite', $db_info->use_rewrite=='Y'?'Y':'N');
            Context::set('use_optimizer', $db_info->use_optimizer!='N'?'Y':'N');
            Context::set('qmail_compatibility', $db_info->qmail_compatibility=='Y'?'Y':'N');

            Context::setBrowserTitle("XE Admin Page");
        }

        /**
         * @brief 관리자 메인 페이지 출력
         **/
        function dispAdminIndex() {
            $newest_news_url = sprintf("http://news.zeroboard.com/%s/news.php", Context::getLangType());
            $cache_file = sprintf("%sfiles/cache/newest_news.%s.cache.php", _XE_PATH_,Context::getLangType());
            if(!file_exists($cache_file) || filemtime($cache_file)+ 60*60 < time()) {
                FileHandler::getRemoteFile($newest_news_url, $cache_file);
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

            $db_info = Context::getDBInfo();
            Context::set('selected_lang', $db_info->lang_type);

            Context::set('current_version', __ZBXE_VERSION__);
            Context::set('installed_path', realpath('./'));

            $oModuleModel = &getModel('module');
            $module_list = $oModuleModel->getModuleList();
            Context::set('module_list', $module_list);

            $oAddonModel = &getAdminModel('addon');
            $addon_list = $oAddonModel->getAddonList();
            Context::set('addon_list', $addon_list);

            $args->date = date("Ymd000000", time()-60*60*24);

            $output = executeQueryArray("admin.getMemberStatus", $args);
            $status->member->yesterday = number_format($output->data[1]->count);
            $status->member->today = number_format($output->data[2]->count);
            $output = executeQuery("admin.getMemberCount", $args);
            $status->member->total = number_format($output->data->count);

            $output = executeQueryArray("admin.getDocumentStatus", $args);
            $status->document->yesterday = number_format($output->data[1]->count);
            $status->document->today = number_format($output->data[2]->count);
            $output = executeQuery("admin.getDocumentCount", $args);
            $status->document->total = number_format($output->data->count);

            $output = executeQueryArray("admin.getCommentStatus", $args);
            $status->comment->yesterday = number_format($output->data[1]->count);
            $status->comment->today = number_format($output->data[2]->count);
            $output = executeQuery("admin.getCommentCount", $args);
            $status->comment->total = number_format($output->data->count);

            $output = executeQueryArray("admin.getTrackbackStatus", $args);
            $status->trackback->yesterday = number_format($output->data[1]->count);
            $status->trackback->today = number_format($output->data[2]->count);
            $output = executeQuery("admin.getTrackbackCount", $args);
            $status->trackback->total = number_format($output->data->count);

            $output = executeQueryArray("admin.getFileStatus", $args);
            $status->file->yesterday = number_format($output->data[1]->count);
            $status->file->today = number_format($output->data[2]->count);
            $output = executeQuery("admin.getFileCount", $args);
            $status->file->total = number_format($output->data->count);
            Context::set('status', $status);

            $this->setTemplateFile('index');
        }

        /**
         * @brief 관리자 설정
         **/
        function dispAdminConfig() {
            $db_info = Context::getDBInfo();
            Context::set('selected_lang', $db_info->lang_type);

            Context::set('lang_supported', Context::loadLangSupported());

            Context::set('lang_selected', Context::loadLangSelected());

            Context::set('ftp_info', Context::getFTPInfo());

            $this->setTemplateFile('config');
        }
    }
?>
