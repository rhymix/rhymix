<?php
    /**
     * @class  addonAdminModel
     * @author zero (zero@nzeo.com)
     * @brief  addon 모듈의 admin model class
     **/

    class addonAdminModel extends addon {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 애드온의 경로를 구함
         **/
        function getAddonPath($addon_name) {
            $class_path = sprintf('./addons/%s/', $addon_name);
            if(is_dir($class_path)) return $class_path;
            return "";
        }

        /**
         * @brief 애드온의 종류와 정보를 구함
         **/
        function getAddonList() {
            // activated된 애드온 목록을 구함
            $inserted_addons = $this->getInsertedAddons();

            // 다운받은 애드온과 설치된 애드온의 목록을 구함
            $searched_list = FileHandler::readDir('./addons');
            $searched_count = count($searched_list);
            if(!$searched_count) return;
            sort($searched_list);

            for($i=0;$i<$searched_count;$i++) {
                // 애드온의 이름
                $addon_name = $searched_list[$i];

                // 애드온의 경로 (files/addons가 우선)
                $path = $this->getAddonPath($addon_name);

                // 해당 애드온의 정보를 구함
                unset($info);
                $info = $this->getAddonInfoXml($addon_name);

                $info->addon = $addon_name;
                $info->path = $path;
                $info->activated = false;

                // DB에 입력되어 있는지 확인
                if(!in_array($addon_name, array_keys($inserted_addons))) {
                    // DB에 입력되어 있지 않으면 입력 (model에서 이런짓 하는거 싫지만 귀찮아서.. ㅡ.ㅜ)
                    $oAddonAdminController = &getAdminController('addon');
                    $oAddonAdminController->doInsert($addon_name);

                // 활성화 되어 있는지 확인
                } else {
                    if($inserted_addons[$addon_name]->is_used=='Y') $info->activated = true;
                }

                $list[] = $info;
            }
            return $list;
        }

        /**
         * @brief 모듈의 conf/info.xml 을 읽어서 정보를 구함
         **/
        function getAddonInfoXml($addon) {
            // 요청된 모듈의 경로를 구한다. 없으면 return
            $addon_path = $this->getAddonPath($addon);
            if(!$addon_path) return;

            // 현재 선택된 모듈의 스킨의 정보 xml 파일을 읽음
            $xml_file = sprintf("%sconf/info.xml", $addon_path);
            if(!file_exists($xml_file)) return;

            $oXmlParser = new XmlParser();
            $tmp_xml_obj = $oXmlParser->loadXmlFile($xml_file);
            $xml_obj = $tmp_xml_obj->addon;

            if(!$xml_obj) return;


            // 애드온 정보
            if($xml_obj->version && $xml_obj->attrs->version == '0.2') {
                // addon format 0.2
                $addon_info->title = $xml_obj->title->body;
                $addon_info->description = trim($xml_obj->description->body);
                $addon_info->version = $xml_obj->version->body;
                $addon_info->homepage = $xml_obj->link->body;
                sscanf($xml_obj->date->body, '%d-%d-%d', $date_obj->y, $date_obj->m, $date_obj->d);
                $addon_info->date = sprintf('%04d%02d%02d', $date_obj->y, $date_obj->m, $date_obj->d);
                $addon_info->license = $xml_obj->license->body;
                $addon_info->license_link = $xml_obj->license->attrs->link;

                if(!is_array($xml_obj->author)) $author_list[] = $xml_obj->author;
                else $author_list = $xml_obj->author;
                
                foreach($author_list as $author) {
                    unset($author_obj);
                    $author_obj->name = $author->name->body;
                    $author_obj->email_address = $author->attrs->email_address;
                    $author_obj->homepage = $author->attrs->link;
                    $addon_info->author[] = $author_obj;
                }

            } else {
                // addon format 0.1
                $addon_info->addon_name = $addon;
                $addon_info->title = $xml_obj->title->body;
                $addon_info->description = trim($xml_obj->author->description->body);
                $addon_info->version = $xml_obj->attrs->version;
                sscanf($xml_obj->author->attrs->date, '%d. %d. %d', $date_obj->y, $date_obj->m, $date_obj->d);
                $addon_info->date = sprintf('%04d%02d%02d', $date_obj->y, $date_obj->m, $date_obj->d);
                $author_obj->name = $xml_obj->author->name->body;
                $author_obj->email_address = $xml_obj->author->attrs->email_address;
                $author_obj->homepage = $xml_obj->author->attrs->link;
                $addon_info->author[] = $author_obj;

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
                            $obj->logs[] = $log->body;
                        }
                    }

                    $addon_info->history[] = $obj;
                }
            }

            // DB에 설정된 내역을 가져온다
            $db_args->addon = $addon;
            $output = executeQuery('addon.getAddonInfo',$db_args);
            $extra_vals = unserialize($output->data->extra_vars);

            if($extra_vals->mid_list) {
                $addon_info->mid_list = $extra_vals->mid_list;
            } else {
                $addon_info->mid_list = array();
            }

            if($xml_obj->extra_vars) {
                // 확장변수를 정리
                $extra_var_groups = $xml_obj->extra_vars->group;
                if(!$extra_var_groups) $extra_var_groups = $xml_obj->extra_vars;
                if(!is_array($extra_var_groups)) $extra_var_groups = array($extra_var_groups);
                foreach($extra_var_groups as $group) {
                    $extra_vars = $group->var;
                    if(!is_array($group->var)) $extra_vars = array($group->var);

                    foreach($extra_vars as $key => $val) {
                        unset($obj);
                        if(!$val->type->body) { $val->type->body = 'text'; }

                        $obj->group = $group->title->body;
                        $obj->name = $val->attrs->name;
                        $obj->title = $val->title->body;
                        $obj->type = $val->type->body;
                        $obj->description = $val->description->body;
                        $obj->value = $extra_vals->{$obj->name};
                        if(strpos($obj->value, '|@|') != false) { $obj->value = explode('|@|', $obj->value); }
                        if($obj->type == 'mid_list' && !is_array($obj->value)) { $obj->value = array($obj->value); }

                        // 'select'type에서 option목록을 구한다.
                        if(is_array($val->options)) {
                            $option_count = count($val->options);

                            for($i = 0; $i < $option_count; $i++) {
                                $obj->options[$i]->title = $val->options[$i]->title->body;
                                $obj->options[$i]->value = $val->options[$i]->value->body;
                            }
                        }

                        $addon_info->extra_vars[] = $obj;
                    }
                }
            }

            return $addon_info;
        }

        /**
         * @brief 활성화된 애드온 목록을 구해옴
         **/
        function getInsertedAddons() {
            $args->list_order = 'addon';
            $output = executeQuery('addon.getAddons', $args);
            if(!$output->data) return array();
            if(!is_array($output->data)) $output->data = array($output->data);

            $activated_count = count($output->data);
            for($i=0;$i<$activated_count;$i++) {
                $addon = $output->data[$i];
                $addon_list[$addon->addon] = $addon;
            }
            return $addon_list;
        }

        /**
         * @brief 애드온이 활성화 되어 있는지 체크
         **/
        function isActivatedAddon($addon) {
            $args->addon = $addon;
            $output = executeQuery('addon.getAddonIsActivated', $args);
            if($output->data->count>0) return true;
            return false;
        }

    }
?>
