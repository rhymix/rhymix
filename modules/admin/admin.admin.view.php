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
            // template path 지정
            $this->setTemplatePath($this->module_path.'tpl');

            // 접속 사용자에 대한 체크
            $oMemberModel = &getModel('member');
            $logged_info = $oMemberModel->getLoggedInfo();

            // 관리자가 아니면 금지
            if($logged_info->is_admin!='Y') return $this->stop("msg_is_not_administrator");

            // 관리자용 레이아웃으로 변경
            $this->setLayoutPath($this->getTemplatePath());
            $this->setLayoutFile('layout.html');

            // 설치된 모듈 목록 가져와서 적절히 분리
            $oModuleModel = &getModel('module');
            $installed_module_list = $oModuleModel->getModulesXmlInfo();

            $installed_modules = $package_modules = array();
            $package_idx = 0;
            foreach($installed_module_list as $key => $val) {
                if($val->module == 'admin' || !$val->admin_index_act) continue;
                // action 정보 구함
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

                // 패키지 모듈
                if($val->category == 'package') {
                    if($package_idx == 0) $obj->position = "first";
                    else $obj->position = "mid";
                    $package_modules[] = $obj;
                    $package_idx ++;
                    if($obj->selected) Context::set('package_selected',true);
                // 일반 모듈
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

            $db_info = Context::getDBInfo();

            Context::set('time_zone_list', $GLOBALS['time_zone']);
            Context::set('time_zone', $GLOBALS['_time_zone']);
            Context::set('use_rewrite', $db_info->use_rewrite=='Y'?'Y':'N');
            Context::set('use_optimizer', $db_info->use_optimizer!='N'?'Y':'N');
            Context::set('qmail_compatibility', $db_info->qmail_compatibility=='Y'?'Y':'N');
            Context::set('use_ssl', $db_info->use_ssl?$db_info->use_ssl:"none");
            if($db_info->http_port) Context::set('http_port', $db_info->http_port);
            if($db_info->https_port) Context::set('https_port', $db_info->https_port);

            Context::setBrowserTitle("XE Admin Page");
        }

        /**
         * @brief 관리자 메인 페이지 출력
         **/
        function dispAdminIndex() {
            /**
             * 최근 뉴스를 가져와서 세팅
             **/
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

            // DB 정보를 세팅
            $db_info = Context::getDBInfo();
            Context::set('selected_lang', $db_info->lang_type);

            // 현재 버젼과 설치 경로 세팅
            Context::set('current_version', __ZBXE_VERSION__);
            Context::set('installed_path', realpath('./'));

            // 모듈 목록을 가져옴
            $oModuleModel = &getModel('module');
            $module_list = $oModuleModel->getModuleList();
            Context::set('module_list', $module_list);

            // 애드온 목록을 가져옴
            $oAddonModel = &getAdminModel('addon');
            $addon_list = $oAddonModel->getAddonList();
            Context::set('addon_list', $addon_list);

            /**
             * 각종 통계를 추출
             **/
            $args->date = date("Ymd000000", time()-60*60*24);
            $today = date("Ymd");

            // 회원현황
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

            // 문서현황
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

            // 댓글현황
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

            // 엮인글현황
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

            // 첨부파일현황
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

            // 게시물 신고현황
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

            // 댓글 신고현황
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

            Context::set('status', $status);

            Context::set('layout','none');
            $this->setTemplateFile('index');
            //$this->setTemplateFile('a');
        }

        /**
         * @brief 관리자 설정
         **/
        function dispAdminConfig() {
            $db_info = Context::getDBInfo();

            Context::set('selected_lang', $db_info->lang_type);

            Context::set('default_url', $db_info->default_url);

            Context::set('langs', Context::loadLangSupported());

            Context::set('lang_selected', Context::loadLangSelected());

            Context::set('ftp_info', Context::getFTPInfo());

            Context::set('layout','none');
            $this->setTemplateFile('config');
        }
    }
?>