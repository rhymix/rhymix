<?php
    /**
     * @class  moduleModel
     * @author zero (zero@nzeo.com)
     * @brief  module 모듈의 Model class
     **/

    class moduleModel extends module {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief site 정보를 구함
         **/
        function getSiteInfo($site_srl) {
            $args->site_srl = $site_srl;
            $output = executeQuery('module.getSiteInfo', $args);
            return $output->data;
        }

        function getSiteInfoByDomain($domain) {
            $args->domain= $domain;
            $output = executeQuery('module.getSiteInfoByDomain', $args);
            return $output->data;
        }

        /**
         * @brief document_srl로 모듈의 정보르 구함
         * 이 경우는 캐시파일을 이용할 수가 없음
         **/
        function getModuleInfoByDocumentSrl($document_srl) {
            $args->document_srl = $document_srl;
            $output = executeQuery('module.getModuleInfoByDocument', $args);
            return $this->addModuleExtraVars($output->data);
        }

        /**
         * @brief domain에 따른 기본 mid를 구함
         **/
        function getDefaultMid() {
            // domain 으로 등록된 virtual site가 있는지 확인
            $url_info = parse_url(Context::getRequestUri());
            $hostname = $url_info['host'];
            $path = preg_replace('/\/$/','',$url_info['path']);
            $sites_args->domain = sprintf('%s%s%s', $hostname, $url_info['port']&&$url_info['port']!=80?':'.$url_info['port']:'',$path);

            $output = executeQuery('module.getSiteDefaultInfo', $sites_args);

            // virtual site를 못 찾으면 가장 기본 모듈 추출
            if(!$output->toBool() || !$output->data) {
                $args->site_srl = 0;
                $output = executeQuery('module.getDefaultMidInfo', $args);
            }
            $module_info = $output->data;
            if(!$module_info->module_srl) return;
            if(is_array($module_info) && $module_info->data[0]) $module_info = $module_info[0];
            return $this->addModuleExtraVars($module_info);
        }

        /**
         * @brief mid로 모듈의 정보를 구함
         **/
        function getModuleInfoByMid($mid, $site_srl = 0) {
            $args->mid = $mid;
            $args->site_srl = $site_srl;
            $output = executeQuery('module.getMidInfo', $args);
            $module_info = $output->data;
            if(!$module_info->module_srl && $module_info->data[0]) $module_info = $module_info->data[0];
            return $this->addModuleExtraVars($module_info);
        }

        /**
         * @brief module_srl에 해당하는 모듈의 정보를 구함
         **/
        function getModuleInfoByModuleSrl($module_srl) {
            // 데이터를 가져옴
            $args->module_srl = $module_srl;
            $output = executeQuery('module.getMidInfo', $args);
            if(!$output->data) return;
            $module_info = $this->addModuleExtraVars($output->data);
            return $module_info;
        }

        /**
         * @brief layout_srl에 해당하는 모듈의 정보를 구함
         **/
        function getModulesInfoByLayout($layout_srl) {
            // 데이터를 가져옴
            $args->layout_srl = $layout_srl;
            $output = executeQueryArray('module.getModulesByLayout', $args);

            $count = count($output->data);

            $modules = array();
            for($i=0;$i<$count;$i++) {
                $modules[] = $output->data[$i];
            }
            return $this->addModuleExtraVars($modules);
        }

        /**
         * @brief 여러개의 module_srl에 해당하는 모듈의 정보를 구함
         **/
        function getModulesInfo($module_srls) {
            if(is_array($module_srls)) $module_srls = implode(',',$module_srls);
            $args->module_srls = $module_srls;
            $output = executeQueryArray('module.getModulesInfo', $args);
            if(!$output->toBool()) return;
            return $this->addModuleExtraVars($output->data);
        }

        /**
         * @brief 모듈의 기본 정보에 추가 변수 구함
         **/
        function addModuleExtraVars($module_info) {
            // 1개 이상의 모듈정보를 요청받아도 처리 가능하도록
            if(!is_array($module_info)) $target_module_info = array($module_info);
            else $target_module_info = $module_info;

            // 모듈 번호를 구함 
            $module_srls = array();
            foreach($target_module_info as $key => $val) {
                $module_srl = $val->module_srl;
                if(!$module_srl) continue;
                $module_srls[] = $val->module_srl;
            }

            // 모듈의 추가정보/ 스킨 정보를 추출
            $extra_vars = $this->getModuleExtraVars($module_srls);
            if(!count($module_srls) || !count($extra_vars)) return $module_info;

            foreach($target_module_info as $key => $val) {
                if(!$extra_vars[$val->module_srl] || !count($extra_vars[$val->module_srl])) continue;
                foreach($extra_vars[$val->module_srl] as $k => $v) {
                    if($target_module_info[$key]->{$k}) continue;
                    $target_module_info[$key]->{$k} = $v;
                }
            }
            if(is_array($module_info)) return $target_module_info;
            return $target_module_info[0];
        }

        /**
         * @brief DB에 생성된 mid 전체 목록을 구해옴
         **/
        function getMidList($args = null) {
            $output = executeQuery('module.getMidList', $args);
            if(!$output->toBool()) return $output;

            $list = $output->data;
            if(!$list) return;

            if(!is_array($list)) $list = array($list);

            foreach($list as $val) {
                $mid_list[$val->mid] = $val;
            }
            return $mid_list;
        }

        /**
         * @brief mid 목록에 대응하는 module_srl을 배열로 return
         **/
        function getModuleSrlByMid($mid) {
            if($mid && !is_array($mid)) $mid = explode(',',$mid);
            if(is_array($mid)) $mid = "'".implode("','",$mid)."'";

            $site_module_info = Context::get('site_module_info');

            $args->mid = $mid;
            if($site_module_info) $args->site_srl = $site_module_info->site_srl;
            $output = executeQuery('module.getModuleSrlByMid', $args);
            if(!$output->toBool()) return $output;

            $list = $output->data;
            if(!$list) return;
            if(!is_array($list)) $list = array($list);

            foreach($list as $key => $val) {
                $module_srl_list[] = $val->module_srl;
            }

            return $module_srl_list;
        }

        /**
         * @brief act 값에 의한 forward 값을 구함
         **/
        function getActionForward($act) {
            $args->act = $act;
            $output = executeQuery('module.getActionForward',$args);
            return $output->data;
        }

        /**
         * @brief trigger_name에 등록된 모든 목록을 추출
         **/
        function getTriggers($trigger_name, $called_position) {
            $args->trigger_name = $trigger_name;
            $args->called_position = $called_position;
            $output = executeQueryArray('module.getTriggers',$args);
            return $output->data;
        }

        /**
         * @brief 특정 trigger_name의 특정 대상을 추출
         **/
        function getTrigger($trigger_name, $module, $type, $called_method, $called_position) {
            $args->trigger_name = $trigger_name;
            $args->module = $module;
            $args->type = $type;
            $args->called_method = $called_method;
            $args->called_position = $called_position;
            $output = executeQuery('module.getTrigger',$args);
            return $output->data;
        }

        /**
         * @brief 모듈의 conf/info.xml 을 읽어서 정보를 구함
         **/
        function getModuleInfoXml($module) {
            // 요청된 모듈의 경로를 구한다. 없으면 return
            $module_path = ModuleHandler::getModulePath($module);
            if(!$module_path) return;

            // 현재 선택된 모듈의 스킨의 정보 xml 파일을 읽음
            $xml_file = sprintf("%s/conf/info.xml", $module_path);
            if(!file_exists($xml_file)) return;

            $oXmlParser = new XmlParser();
            $tmp_xml_obj = $oXmlParser->loadXmlFile($xml_file);
            $xml_obj = $tmp_xml_obj->module;

            if(!$xml_obj) return;

            // 모듈 정보
            if($xml_obj->version && $xml_obj->attrs->version == '0.2') {
                // module format 0.2
                $module_info->title = $xml_obj->title->body;
                $module_info->description = $xml_obj->description->body;
                $module_info->version = $xml_obj->version->body;
                $module_info->homepage = $xml_obj->link->body;
                $module_info->category = $xml_obj->category->body;
                if(!$module_info->category) $module_info->category = 'service';
                sscanf($xml_obj->date->body, '%d-%d-%d', $date_obj->y, $date_obj->m, $date_obj->d);
                $module_info->date = sprintf('%04d%02d%02d', $date_obj->y, $date_obj->m, $date_obj->d);
                $module_info->license = $xml_obj->license->body;
                $module_info->license_link = $xml_obj->license->attrs->link;

                if(!is_array($xml_obj->author)) $author_list[] = $xml_obj->author;
                else $author_list = $xml_obj->author;

                foreach($author_list as $author) {
                    unset($author_obj);
                    $author_obj->name = $author->name->body;
                    $author_obj->email_address = $author->attrs->email_address;
                    $author_obj->homepage = $author->attrs->link;
                    $module_info->author[] = $author_obj;
                }

                // history
                if($xml_obj->history) {
                    if(!is_array($xml_obj->history)) $history[] = $xml_obj->history;
                    else $history = $xml_obj->history;

                    foreach($history as $item) {
                        unset($obj);

                        if($item->author) {
                            (!is_array($item->author)) ? $obj->author_list[] = $item->author : $obj->author_list = $item->author;

                            foreach($obj->author_list as $author) {
                                unset($author_obj);
                                $author_obj->name = $author->name->body;
                                $author_obj->email_address = $author->attrs->email_address;
                                $author_obj->homepage = $author->attrs->link;
                                $obj->author[] = $author_obj;
                            }
                        }

                        $obj->name = $item->name->body;
                        $obj->email_address = $item->attrs->email_address;
                        $obj->homepage = $item->attrs->link;
                        $obj->version = $item->attrs->version;
                        $obj->date = $item->attrs->date;
                        $obj->description = $item->description->body;

                        if($item->log) {
                            (!is_array($item->log)) ? $obj->log[] = $item->log : $obj->log = $item->log;

                            foreach($obj->log as $log) {
                                unset($logs_obj);
                                $logs_obj->text = $log->body;
                                $logs_obj->link = $log->attrs->link;
                                $obj->logs[] = $logs_obj;
                            }
                        }

                        $module_info->history[] = $obj;
                    }
                }


            } else {
                // module format 0.1
                $module_info->title = $xml_obj->title->body;
                $module_info->description = $xml_obj->author->description->body;
                $module_info->version = $xml_obj->attrs->version;
                $module_info->category = $xml_obj->attrs->category;
                if(!$module_info->category) $module_info->category = 'service';
                sscanf($xml_obj->author->attrs->date, '%d. %d. %d', $date_obj->y, $date_obj->m, $date_obj->d);
                $module_info->date = sprintf('%04d%02d%02d', $date_obj->y, $date_obj->m, $date_obj->d);
                $author_obj->name = $xml_obj->author->name->body;
                $author_obj->email_address = $xml_obj->author->attrs->email_address;
                $author_obj->homepage = $xml_obj->author->attrs->link;
                $module_info->author[] = $author_obj;
            }

            // action 정보를 얻어서 admin_index를 추가
            $action_info = $this->getModuleActionXml($module);
            $module_info->admin_index_act = $action_info->admin_index_act;

            return $module_info;
        }

        /**
         * @brief module의 conf/module.xml 을 통해 grant(권한) 및 action 데이터를 return
         * module.xml 파일의 경우 파싱하는데 시간이 걸리기에 캐싱을 한다...
         * 캐싱을 할때 바로 include 할 수 있도록 역시 코드까지 추가하여 캐싱을 한다.
         * 이게 퍼포먼스 상으로는 좋은데 어떤 부정적인 결과를 유도할지는 잘 모르겠...
         **/
        function getModuleActionXml($module) {
            // 요청된 모듈의 경로를 구한다. 없으면 return
            $class_path = ModuleHandler::getModulePath($module);
            if(!$class_path) return;

            // 해당 경로에 module.xml 파일이 있는지 체크한다. 없으면 return
            $xml_file = sprintf("%sconf/module.xml", $class_path);
            if(!file_exists($xml_file)) return;

            // 캐시된 파일이 있는지 확인
            $cache_file = sprintf("./files/cache/module_info/%s.%s.php", $module, Context::getLangType());

            // 캐시 파일이 없거나 캐시 파일이 xml 파일보다 오래되었으면 내용 다시 갱신
            if(!file_exists($cache_file) || filemtime($cache_file)<filemtime($xml_file)) {

                $buff = ""; ///< 캐시 파일에 쓸 buff 변수 설정

                $xml_obj = XmlParser::loadXmlFile($xml_file); ///< xml 파일을 읽어서 xml object로 변환

                if(!count($xml_obj->module)) return; ///< xml 내용중에 module 태그가 없다면 오류;;

                $grants = $xml_obj->module->grants->grant; ///< 권한 정보 (없는 경우도 있음)
                $permissions = $xml_obj->module->permissions->permission; ///< 권한 대행 (없는 경우도 있음)
                $actions = $xml_obj->module->actions->action; ///< action list (필수)

                $default_index = $admin_index = '';

                // 권한 정보의 정리
                if($grants) {
                    if(is_array($grants)) $grant_list = $grants;
                    else $grant_list[] = $grants;

                    foreach($grant_list as $grant) {
                        $name = $grant->attrs->name;
                        $default = $grant->attrs->default?$grant->attrs->default:'guest';
                        $title = $grant->title->body;

                        $info->grant->{$name}->title = $title;
                        $info->grant->{$name}->default = $default;

                        $buff .= sprintf('$info->grant->%s->title=\'%s\';', $name, $title);
                        $buff .= sprintf('$info->grant->%s->default=\'%s\';', $name, $default);
                    }
                }

                // 권한 허용 정리
                if($permissions) {
                    if(is_array($permissions)) $permission_list = $permissions;
                    else $permission_list[] = $permissions;

                    foreach($permission_list as $permission) {
                        $action = $permission->attrs->action;
                        $target = $permission->attrs->target;

                        $info->permission->{$action} = $target;

                        $buff .= sprintf('$info->permission->%s = \'%s\';', $action, $target);
                    }
                }

                // actions 정리
                if($actions) {
                    if(is_array($actions)) $action_list = $actions;
                    else $action_list[] = $actions;

                    foreach($action_list as $action) {
                        $name = $action->attrs->name;

                        $type = $action->attrs->type;
                        $grant = $action->attrs->grant?$action->attrs->grant:'guest';
                        $standalone = $action->attrs->standalone=='true'?'true':'false';

                        $index = $action->attrs->index;
                        $admin_index = $action->attrs->admin_index;

                        $output->action->{$name}->type = $type;
                        $output->action->{$name}->grant = $grant;
                        $output->action->{$name}->standalone= $standalone;

                        $info->action->{$name}->type = $type;
                        $info->action->{$name}->grant = $grant;
                        $info->action->{$name}->standalone = $standalone=='true'?true:false;

                        $buff .= sprintf('$info->action->%s->type=\'%s\';', $name, $type);
                        $buff .= sprintf('$info->action->%s->grant=\'%s\';', $name, $grant);
                        $buff .= sprintf('$info->action->%s->standalone=%s;', $name, $standalone);

                        if($index=='true') {
                            $default_index_act = $name;
                            $info->default_index_act = $name;
                        }
                        if($admin_index=='true') {
                            $admin_index_act = $name;
                            $info->admin_index_act = $name;
                        }
                    }
                }
                $buff = sprintf('<?php if(!defined("__ZBXE__")) exit();$info->default_index_act = \'%s\';$info->admin_index_act = \'%s\';%s?>', $default_index_act, $admin_index_act, $buff);

                FileHandler::writeFile($cache_file, $buff);

                return $info;
            }

            @include($cache_file);

            return $info;
        }


        /**
         * @brief 주어진 곳의 스킨 목록을 구함
         * 스킨과 skin.xml 파일을 분석 정리한 결과를 return
         **/
        function getSkins($path) {
            $skin_path = sprintf("%s/skins/", $path);
            $list = FileHandler::readDir($skin_path);
            if(!count($list)) return;

            foreach($list as $skin_name) {
                unset($skin_info);
                $skin_info = $this->loadSkinInfo($path, $skin_name);
                if(!$skin_info) $skin_info->title = $skin_name;

                $skin_list[$skin_name] = $skin_info;
            }

            return $skin_list;
        }

        /**
         * @brief 특정 위치의 특정 스킨의 정보를 구해옴
         **/
        function loadSkinInfo($path, $skin) {

            // 모듈의 스킨의 정보 xml 파일을 읽음
            if(substr($path,-1)!='/') $path .= '/';
            $skin_xml_file = sprintf("%sskins/%s/skin.xml", $path, $skin);
            if(!file_exists($skin_xml_file)) return;

            // XmlParser 객체 생성
            $oXmlParser = new XmlParser();
            $_xml_obj = $oXmlParser->loadXmlFile($skin_xml_file);

            // 스킨 정보가 없으면 return
            if(!$_xml_obj->skin) return;
            $xml_obj = $_xml_obj->skin;

            // 스킨이름
            $skin_info->title = $xml_obj->title->body;


            // 작성자 정보
            if($xml_obj->version && $xml_obj->attrs->version == '0.2') {
                // skin format v0.2
                sscanf($xml_obj->date->body, '%d-%d-%d', $date_obj->y, $date_obj->m, $date_obj->d);
                $skin_info->version = $xml_obj->version->body;
                $skin_info->date = sprintf('%04d%02d%02d', $date_obj->y, $date_obj->m, $date_obj->d);
                $skin_info->homepage = $xml_obj->link->body;
                $skin_info->license = $xml_obj->license->body;
                $skin_info->license_link = $xml_obj->license->attrs->link;
                $skin_info->description = $xml_obj->description->body;

                if(!is_array($xml_obj->author)) $author_list[] = $xml_obj->author;
                else $author_list = $xml_obj->author;

                foreach($author_list as $author) {
                    unset($author_obj);
                    $author_obj->name = $author->name->body;
                    $author_obj->email_address = $author->attrs->email_address;
                    $author_obj->homepage = $author->attrs->link;
                    $skin_info->author[] = $author_obj;
                }

                // 확장변수를 정리
                if($xml_obj->extra_vars) {
                    $extra_var_groups = $xml_obj->extra_vars->group;
                    if(!$extra_var_groups) $extra_var_groups = $xml_obj->extra_vars;
                    if(!is_array($extra_var_groups)) $extra_var_groups = array($extra_var_groups);

                    foreach($extra_var_groups as $group) {
                        $extra_vars = $group->var;
                        if(!is_array($group->var)) $extra_vars = array($group->var);

                        foreach($extra_vars as $key => $val) {
                            unset($obj);
                            if(!$val->attrs->type) { $val->attrs->type = 'text'; }

                            $obj->group = $group->title->body;
                            $obj->name = $val->attrs->name;
                            $obj->title = $val->title->body;
                            $obj->type = $val->attrs->type;
                            $obj->description = $val->description->body;
                            $obj->value = $extra_vals->{$obj->name};
                            $obj->default = $val->attrs->default;
                            if(strpos($obj->value, '|@|') != false) { $obj->value = explode('|@|', $obj->value); }
                            if($obj->type == 'mid_list' && !is_array($obj->value)) { $obj->value = array($obj->value); }

                            // 'select'type에서 option목록을 구한다.
                            if(is_array($val->options)) {
                                $option_count = count($val->options);

                                for($i = 0; $i < $option_count; $i++) {
                                    $obj->options[$i]->title = $val->options[$i]->title->body;
                                    $obj->options[$i]->value = $val->options[$i]->attrs->value;
                                }
                            } else {
                                $obj->options[0]->title = $val->options->title->body;
                                $obj->options[0]->value = $val->options->attrs->value;
                            }

                            $skin_info->extra_vars[] = $obj;
                        }
                    }
                }

                // history
                if($xml_obj->history) {
                    if(!is_array($xml_obj->history)) $history[] = $xml_obj->history;
                    else $history = $xml_obj->history;

                    foreach($history as $item) {
                        unset($obj);

                        if($item->author) {
                            (!is_array($item->author)) ? $obj->author_list[] = $item->author : $obj->author_list = $item->author;

                            foreach($obj->author_list as $author) {
                                unset($author_obj);
                                $author_obj->name = $author->name->body;
                                $author_obj->email_address = $author->attrs->email_address;
                                $author_obj->homepage = $author->attrs->link;
                                $obj->author[] = $author_obj;
                            }
                        }

                        $obj->name = $item->name->body;
                        $obj->email_address = $item->attrs->email_address;
                        $obj->homepage = $item->attrs->link;
                        $obj->version = $item->attrs->version;
                        $obj->date = $item->attrs->date;
                        $obj->description = $item->description->body;

                        if($item->log) {
                            (!is_array($item->log)) ? $obj->log[] = $item->log : $obj->log = $item->log;

                            foreach($obj->log as $log) {
                                unset($log_obj);
                                $log_obj->text = $log->body;
                                $log_obj->link = $log->attrs->link;
                                $obj->logs[] = $log_obj;
                            }
                        }

                        $skin_info->history[] = $obj;
                    }
                }


            } else {

                // skin format v0.1
                sscanf($xml_obj->maker->attrs->date, '%d-%d-%d', $date_obj->y, $date_obj->m, $date_obj->d);

                $skin_info->version = $xml_obj->version->body;
                $skin_info->date = sprintf('%04d%02d%02d', $date_obj->y, $date_obj->m, $date_obj->d);
                $skin_info->homepage = $xml_obj->link->body;
                $skin_info->license = $xml_obj->license->body;
                $skin_info->license_link = $xml_obj->license->attrs->link;
                $skin_info->description = $xml_obj->maker->description->body;

                $skin_info->author[0]->name = $xml_obj->maker->name->body;
                $skin_info->author[0]->email_address = $xml_obj->maker->attrs->email_address;
                $skin_info->author[0]->homepage = $xml_obj->maker->attrs->link;

                // 스킨에서 사용되는 변수들
                $extra_var_groups = $xml_obj->extra_vars->group;
                if(!$extra_var_groups) $extra_var_groups = $xml_obj->extra_vars;
                if(!is_array($extra_var_groups)) $extra_var_groups = array($extra_var_groups);

                foreach($extra_var_groups as $group){
                    $extra_vars = $group->var;

                    if($extra_vars) {

                        if(!is_array($extra_vars)) $extra_vars = array($extra_vars);

                        foreach($extra_vars as $var) {
                            unset($obj);
                            unset($options);

                            $group = $group->title->body;
                            $name = $var->attrs->name;
                            $type = $var->attrs->type;
                            $title = $var->title->body;
                            $description = $var->description->body;

                            // 'select'type에서 option목록을 구한다.
                            if(is_array($var->default)) {
                                $option_count = count($var->default);

                                for($i = 0; $i < $option_count; $i++) {
                                    $options[$i]->title = $var->default[$i]->body;
                                    $options[$i]->value = $var->default[$i]->body;
                                }
                            } else {
                                $options[0]->title = $var->default->body;
                                $options[0]->value = $var->default->body;
                            }

                            $width = $var->attrs->width;
                            $height = $var->attrs->height;

                            unset($obj);
                            $obj->group = $group;
                            $obj->title = $title;
                            $obj->description = $description;
                            $obj->name = $name;
                            $obj->type = $type;
                            $obj->options = $options;
                            $obj->width = $width;
                            $obj->height = $height;
                            $obj->default = $options[0]->value;

                            $skin_info->extra_vars[] = $obj;
                        }
                    }
                }
            }

            // colorset
            $colorset = $xml_obj->colorset->color;
            if($colorset) {
                if(!is_array($colorset)) $colorset = array($colorset);

                foreach($colorset as $color) {
                    $name = $color->attrs->name;
                    $title = $color->title->body;
                    $screenshot = $color->attrs->src;
                    if($screenshot) {
                        $screenshot = sprintf("%sskins/%s/%s", $path, $skin, $screenshot);
                        if(!file_exists($screenshot)) $screenshot = "";
                    } else $screenshot = "";

                    unset($obj);
                    $obj->name = $name;
                    $obj->title = $title;
                    $obj->screenshot = $screenshot;
                    $skin_info->colorset[] = $obj;
                }
            }

            // 메뉴 종류 (레이아웃을 위한 설정)
            if($xml_obj->menus->menu) {
                $menus = $xml_obj->menus->menu;
                if(!is_array($menus)) $menus = array($menus);

                $menu_count = count($menus);
                $skin_info->menu_count = $menu_count;
                for($i=0;$i<$menu_count;$i++) {
                    unset($obj);

                    $obj->name = $menus[$i]->attrs->name;
                    if($menus[$i]->attrs->default == "true") $obj->default = true;
                    $obj->title = $menus[$i]->title->body;
                    $obj->maxdepth = $menus[$i]->maxdepth->body;

                    $skin_info->menu->{$obj->name} = $obj;
                }
            }

            return $skin_info;
        }


        /**
         * @brief 특정 모듈의 설정 return
         * board, member등 특정 모듈의 global config 관리용
         **/
        function getModuleConfig($module) {
            if(!$GLOBALS['__ModuleConfig__'][$module]) {
                $args->module = $module;
                $output = executeQuery('module.getModuleConfig', $args);
                $config = unserialize($output->data->config);
                $GLOBALS['__ModuleConfig__'][$module] = $config;
            }
            return $GLOBALS['__ModuleConfig__'][$module];
        }

        /**
         * @brief 특정 mid의 모듈 설정 정보 return
         * mid의 모듈 의존적인 설정을 관리
         **/
        function getModulePartConfig($module, $module_srl) {
            if(!$GLOBALS['__ModulePartConfig__'][$module][$module_srl]) {
                $args->module = $module;
                $args->module_srl = $module_srl;
                $output = executeQuery('module.getModulePartConfig', $args);
                $config = unserialize($output->data->config);
                $GLOBALS['__ModulePartConfig__'][$module][$module_srl] = $config;
            }
            return $GLOBALS['__ModulePartConfig__'][$module][$module_srl];
        }

        /**
         * @brief mid별 모듈 설정 정보 전체를 구함
         **/
        function getModulePartConfigs($module) {
            $args->module = $module;
            $output = executeQueryArray('module.getModulePartConfigs', $args);
            if(!$output->toBool() || !$output->data) return array();

            foreach($output->data as $key => $val) {
                $result[$val->module_srl] = unserialize($val->config);
            }
            return $result;
        }


        /**
         * @brief 모듈 카테고리의 목록을 구함
         **/
        function getModuleCategories() {
            // 데이터를 DB에서 가져옴
            $output = executeQuery('module.getModuleCategories');
            if(!$output->toBool()) return $output;
            $list = $output->data;
            if(!$list) return;
            if(!is_array($list)) $list = array($list);

            foreach($list as $val) {
                $category_list[$val->module_category_srl] = $val;
            }
            return $category_list;
        }

        /**
         * @brief 특정 모듈 카테고리의 내용을 구함
         **/
        function getModuleCategory($module_category_srl) {
            // 데이터를 DB에서 가져옴
            $args->module_category_srl = $module_category_srl;
            $output = executeQuery('module.getModuleCategory', $args);
            if(!$output->toBool()) return $output;
            return $output->data;
        }

        /**
         * @brief 모듈의 xml 정보만 구함
         **/
        function getModulesXmlInfo() {
            // 다운받은 모듈과 설치된 모듈의 목록을 구함
            $searched_list = FileHandler::readDir('./modules');
            $searched_count = count($searched_list);
            if(!$searched_count) return;
            sort($searched_list);

            for($i=0;$i<$searched_count;$i++) {
                // 모듈의 이름
                $module_name = $searched_list[$i];

                $path = ModuleHandler::getModulePath($module_name);

                // 해당 모듈의 정보를 구함
                $info = $this->getModuleInfoXml($module_name);
                unset($obj);

                $info->module = $module_name;
                $info->created_table_count = $created_table_count;
                $info->table_count = $table_count;
                $info->path = $path;
                $info->admin_index_act = $info->admin_index_act;
                $list[] = $info;
            }
            return $list;
        }

        /**
         * @brief 모듈의 종류와 정보를 구함
         **/
        function getModuleList() {
            // DB 객체 생성
            $oDB = &DB::getInstance();

            // 다운받은 모듈과 설치된 모듈의 목록을 구함
            $searched_list = FileHandler::readDir('./modules');
            sort($searched_list);

            $searched_count = count($searched_list);
            if(!$searched_count) return;

            for($i=0;$i<$searched_count;$i++) {
                // 모듈의 이름
                $module_name = $searched_list[$i];

                $path = ModuleHandler::getModulePath($module_name);

                // schemas내의 테이블 생성 xml파일수를 구함
                $tmp_files = FileHandler::readDir($path."schemas", '/(\.xml)$/');
                $table_count = count($tmp_files);

                // 테이블이 설치되어 있는지 체크
                $created_table_count = 0;
                for($j=0;$j<count($tmp_files);$j++) {
                    list($table_name) = explode(".",$tmp_files[$j]);
                    if($oDB->isTableExists($table_name)) $created_table_count ++;
                }

                // 해당 모듈의 정보를 구함
                $info = $this->getModuleInfoXml($module_name);
                unset($obj);

                $info->module = $module_name;
                $info->category = $info->category;
                $info->created_table_count = $created_table_count;
                $info->table_count = $table_count;
                $info->path = $path;
                $info->admin_index_act = $info->admin_index_act;

                // 설치 유무 체크 (설치는 DB의 설치만 관리)
                if($table_count > $created_table_count) $info->need_install = true;
                else $info->need_install = false;

                // 각 모듈의 module.class.php로 upgrade 유무 체크
                $oDummy = null;
                $oDummy = &getModule($module_name, 'class');
                if($oDummy) $info->need_update = $oDummy->checkUpdate();

                $list[] = $info;
            }
            return $list;
        }

        /**
         * @brief 특정 module srls를 sites의 domain과 결합
         * 아직 XE DBHandler에서 left outer join이 안되어서..
         * $output->data[]->module_srl 과 같은 구조여야 함
         **/
        function syncModuleToSite(&$data) {
            if(!$data) return;

            if(is_array($data)) {
                foreach($data as $key => $val) {
                    $module_srls[] = $val->module_srl;
                }
                if(!count($module_srls)) return;
            } else {
                $module_srls[] = $data->module_srl;
            }

            $args->module_srls = implode(',',$module_srls);
            $output = executeQueryArray('module.getModuleSites', $args);
            if(!$output->data) return array();
            foreach($output->data as $key => $val) {
                $modules[$val->module_srl] = $val;
            }

            if(is_array($data)) {
                foreach($data as $key => $val) {
                    $data[$key]->domain = $modules[$val->module_srl]->domain;
                }
            } else {
                $data->domain = $modules[$data->module_srl]->domain;
            }
        }

        /**
         * @brief site_module_info의 관리자 인지 체크
         **/
        function isSiteAdmin($member_info) {
            if(!$member_info->member_srl) return false;
            if($member_info->is_admin == 'Y') return true;

            $site_module_info = Context::get('site_module_info');
            if(!$site_module_info) return;
            $args->site_srl = $site_module_info->site_srl;
            $args->member_srl = $member_info->member_srl;
            $output = executeQuery('module.isSiteAdmin', $args);
            if($output->data->member_srl == $args->member_srl) return true;
            return false;

        }

        /**
         * @brief site의 관리자 정보를 구함
         **/
        function getSiteAdmin($site_srl) {
            $args->site_srl = $site_srl;
            $output = executeQueryArray('module.getSiteAdmin', $args);
            return $output->data;
        }

        /**
         * @brief 특정 모듈의 관리자 아이디 구함
         **/
        function getAdminId($module_srl) {
            $obj->module_srl = $module_srl;
            $output = executeQueryArray('module.getAdminID', $obj);
            if(!$output->toBool() || !$output->data) return;

            return $output->data;
        }

        /**
         * @brief 특정 모듈의 추가 변수를 구함 
         * modules 테이블의 기본 정보 이외의 것
         **/
        function getModuleExtraVars($module_srl) {
            if(is_array($module_srl)) $module_srl = implode(',',$module_srl);
            $args->module_srl = $module_srl;
            $output = executeQueryArray('module.getModuleExtraVars',$args);
            if(!$output->toBool() || !$output->data) return;

            $vars = array();
            foreach($output->data as $key => $val) {
                if(in_array($val->name, array('mid','module')) || $val->value == 'Array') continue;
                $vars[$val->module_srl]->{$val->name} = $val->value;
            }
            return $vars;
        }

        /**
         * @brief 특정 모듈의 스킨 정보를 구함
         **/
        function getModuleSkinVars($module_srl) {
            $args->module_srl = $module_srl;
            $output = executeQueryArray('module.getModuleSkinVars',$args);
            if(!$output->toBool() || !$output->data) return;

            $skin_vars = array();
            foreach($output->data as $val) $skin_vars[$val->name] = $val;
            return $skin_vars;
        }

        /**
         * @brief 특정 모듈의 스킨 정보를 모듈 정보와 결합
         **/
        function syncSkinInfoToModuleInfo(&$module_info) {
            if(!$module_info->module_srl) return;

            $args->module_srl = $module_info->module_srl;
            $output = executeQueryArray('module.getModuleSkinVars',$args);
            if(!$output->toBool() || !$output->data) return;

            foreach($output->data as $val) {
                if(isset($module_info->{$val->name})) continue;
                $module_info->{$val->name} = $val->value;
            }
        }

        /**
         * @brief 특정 모듈정보와 XML, 그리고 회원 정보로 권한을 return
         **/
        function getGrant($module_info, $member_info, $xml_info = '') {
            if(!$xml_info) {
                $module = $module_info->module;
                $xml_info = $this->getModuleActionXml($module);
            }
            // 그룹 권한 설정에 필요한 변수를 세팅
            $module_srl = $module_info->module_srl;
            $grant_info = $xml_info->grant;
            if($member_info->member_srl) {
                if(is_array($member_info->group_list)) $group_list = array_keys($member_info->group_list);
                else $group_list = array();
            } else {
                $group_list = array();
            }

            // module_srl이 없는 즉 별도의 권한 설정이 안되는 경우
            if(!$module_srl) {
                $grant->access = true;
                if($this->isSiteAdmin($member_info)) $grant->access = $grant->is_admin = $grant->manager = true;
                else $grant->is_admin = $grant->manager = $member_info->is_admin=='Y'?true:false;

            // module_srl이 있는 경우
            } else {

                // grant 종류를 구함
                $grant->access = $grant->is_admin = $grant->manager = ($member_info->is_admin=='Y'||$this->isSiteAdmin($member_info))?true:false;

                // 관리자가 아니라 로그인 회원일 경우 이 모듈의 관리자인지 확인
                if(!$grant->manager && $member_info->member_srl) {
                    $args->module_srl = $module_srl;
                    $args->member_srl = $member_info->member_srl;
                    $output = executeQuery('module.getModuleAdmin',$args);
                    if($output->data && $output->data->member_srl == $member_info->member_srl) $grant->manager = $grant->is_admin = true;
                }

                // 관리자가 아니면 직접 DB에서 정보를 구해서 권한 설정
                if(!$grant->manager) {
                    $args = null;
                    $args->module_srl = $module_srl;
                    $output = executeQueryArray('module.getModuleGrants', $args);

                    $grant_exists = $granted = array();

                    if($output->data) {
                        // 1차적으로 권한 대상 이름과 그룹을 정리
                        foreach($output->data as $val) {
                            $grant_exists[$val->name] = true;
                            if($granted[$val->name]) continue;

                            // 로그인 회원만
                            if($val->group_srl == -1) {
                                $granted[$val->name] = true;
                                if($member_info->member_srl) $grant->{$val->name} = true;

                            // 사이트 가입한 회원만
                            } elseif($val->group_srl == -2) {
                                $granted[$val->name] = true;
                                // 비로그인 회원이면 권한 미부여
                                if(!$member_info->member_srl) $grant->{$val->name} = false;
                                // 로그인 회원
                                else {
                                    $site_module_info = Context::get('site_module_info');
                                    // 현재 접속된 사이트 정보가 없으면 권한 부여
                                    if(!$site_module_info->site_srl) $grant->{$val->name} = true;
                                    // 현재 접속된 사이트의 그룹 정보가 있 으면 권한 미부여
                                    elseif(count($group_list)) $grant->{$val->name} = true;
                                }

                            // 비로그인 회원 모두
                            } elseif($val->group_srl == 0) {
                                $granted[$val->name] = true;
                                $grant->{$val->name} = true;
                            // 특정 그룹 대상일 경우
                            } else {
                                if($group_list && count($group_list) && in_array($val->group_srl, $group_list)) {
                                    $grant->{$val->name} = true;
                                    $granted[$val->name] = true;
                                }
                            }
                        }
                    }

                    // 가상 그룹인 access에 대해서 별도 처리
                    if(!$grant_exists['access']) $grant->access = true;
                    if(count($grant_info)) {
                        foreach($grant_info as  $grant_name => $grant_item) {
                            if($grant_exists[$grant_name]) continue;
                            switch($grant_item->default) {
                                case 'guest' :
                                        $grant->{$grant_name} = true;
                                    break;
                                case 'member' :
                                        if($member_info->member_srl) $grant->{$grant_name} = true;
                                        else $grant->{$grant_name} = false;
                                    break;
                                case 'site' :
                                        $site_module_info = Context::get('site_module_info');
                                        if($member_info->member_srl && (($site_module_info->site_srl && count($group_list)) || !$site_module_info->site_srl)) $grant->{$grant_name} = true;
                                        else $grant->{$grant_name} = false;
                                    break;
                                case 'manager' :
                                case 'root' :
                                        if($member_info->is_admin == 'Y') $grant->{$grant_name} = true;
                                        else $grant->{$grant_name} = false;
                                    break;
                            }
                        }
                    }
                }

                // 관리자일 경우 모든 권한에 대해 true 지정
                if($grant->manager) {
                    $grant->access = true;
                    if(count($grant_info)) {
                        foreach($grant_info as $key => $val) {
                            $grant->{$key} = true;
                        }
                    }
                }

            }
            return $grant;
        }



        function getModuleFileBox($module_filebox_srl){
            $args->module_filebox_srl = $module_filebox_srl;
            return executeQuery('getModuleFileBox', $args);
        }

        function getModuleFileBoxList(){
            $args->page = Context::get('page');
            $args->list_count = 10;
            $args->page_count = 10;
            return executeQuery('module.getModuleFileBoxList', $args);
        }

        function getModuleFileBoxPath($module_filebox_srl){
            return sprintf("./files/attach/filebox/%s",getNumberingPath($module_filebox_srl,3));
        }
    }
?>
