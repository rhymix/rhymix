<?php
    /**
     * @class  addonModel
     * @author zero (zero@nzeo.com)
     * @brief  addon 모듈의 Model class
     **/

    class addonModel extends addon {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief addon의 conf/addon.xml 을 통해 grant(권한) 및 action 데이터를 return
         *
         * addon.xml 파일의 경우 파싱하는데 시간이 걸리기에 캐싱을 한다...
         * 캐싱을 할때 바로 include 할 수 있도록 역시 코드까지 추가하여 캐싱을 한다.
         * 이게 퍼포먼스 상으로는 좋은데 어떤 부정적인 결과를 유도할지는 잘 모르겠...
         **/
        function getModuleActionXml($addon) {
            // 요청된 모듈의 경로를 구한다. 없으면 return
            $class_path = ModuleHandler::getModulePath($addon);
            if(!$class_path) return;

            // 해당 경로에 addon.xml 파일이 있는지 체크한다. 없으면 return
            $xml_file = sprintf("%sconf/addon.xml", $class_path);
            if(!file_exists($xml_file)) return;

            // 캐시된 파일이 있는지 확인
            $cache_file = sprintf("./files/cache/addon_info/%s.php", $addon);

            // 캐시 파일이 없거나 캐시 파일이 xml 파일보다 오래되었으면 내용 다시 갱신
            if(!file_exists($cache_file) || filectime($cache_file)<filectime($xml_file)) {

                $buff = ""; ///< 캐시 파일에 쓸 buff 변수 설정

                $xml_obj = XmlParser::loadXmlFile($xml_file); ///< xml 파일을 읽어서 xml object로 변환

                if(!count($xml_obj->addon)) return; ///< xml 내용중에 addon 태그가 없다면 오류;;

                $grants = $xml_obj->addon->grants->grant; ///< 권한 정보 (없는 경우도 있음)
                $actions = $xml_obj->addon->actions->action; ///< action list (필수)

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
                $buff = sprintf('<?php if(!__ZB5__) exit();$info->default_index_act = \'%s\';$info->admin_index_act = \'%s\';%s?>', $default_index_act, $admin_index_act, $buff);

                FileHandler::writeFile($cache_file, $buff);

                return $info;
            }

            include $cache_file; 

            return $info;
        }

        /**
         * @brief 모듈의 conf/info.xml 을 읽어서 정보를 구함
         **/
        function getModuleInfoXml($addon) {
            // 요청된 모듈의 경로를 구한다. 없으면 return
            $addon_path = ModuleHandler::getModulePath($addon);
            if(!$addon_path) return;

            // 현재 선택된 모듈의 스킨의 정보 xml 파일을 읽음
            $xml_file = sprintf("%s/conf/info.xml", $addon_path);
            if(!file_exists($xml_file)) return;

            $oXmlParser = new XmlParser();
            $tmp_xml_obj = $oXmlParser->loadXmlFile($xml_file);
            $xml_obj = $tmp_xml_obj->addon;

            if(!$xml_obj) return;

            $info->title = $xml_obj->title->body;

            // 작성자 정보
            $addon_info->title = $xml_obj->title->body;
            $addon_info->version = $xml_obj->attrs->version;
            $addon_info->author->name = $xml_obj->author->name->body;
            $addon_info->author->email_address = $xml_obj->author->attrs->email_address;
            $addon_info->author->homepage = $xml_obj->author->attrs->link;
            $addon_info->author->date = $xml_obj->author->attrs->date;
            $addon_info->author->description = $xml_obj->author->description->body;

            // history 
            if(!is_array($xml_obj->history->author)) $history[] = $xml_obj->history->author;
            else $history = $xml_obj->history->author;

            foreach($history as $item) {
                unset($obj);
                $obj->name = $item->name->body;
                $obj->email_address = $item->attrs->email_address;
                $obj->homepage = $item->attrs->link;
                $obj->date = $item->attrs->date;
                $obj->description = $item->description->body;
                $addon_info->history[] = $obj;
            }

            // action 정보를 얻어서 admin_index를 추가
            $action_info = $this->getModuleActionXml($addon);
            $addon_info->admin_index_act = $action_info->admin_index_act;

            return $addon_info;
        }

    }
?>
