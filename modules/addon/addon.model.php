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
            $activated_addons = $this->getActivatedAddons();

            // DB 객체 생성
            $oDB = &DB::getInstance();

            // 다운받은 애드온과 설치된 애드온의 목록을 구함
            $downloaded_list = FileHandler::readDir('./files/addons');
            $installed_list = FileHandler::readDir('./addons');
            $searched_list = array_merge($downloaded_list, $installed_list);
            $searched_count = count($searched_list);
            if(!$searched_count) return;

            for($i=0;$i<$searched_count;$i++) {
                // 애드온의 이름
                $addon_name = $searched_list[$i];

                // 애드온의 경로 (files/addons가 우선)
                $path = $this->getAddonPath($addon_name);

                // 해당 애드온의 정보를 구함
                $info = $this->getAddonInfoXml($addon_name);
                unset($obj);

                $info->addon = $addon_name;
                $info->path = $path;

                if(in_array($addon_name, $activated_addons)) $info->activated = true;
                else $info->activated = false;

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

            $info->title = $xml_obj->title->body;

            // 작성자 정보
            $addon_info->title = $xml_obj->title->body;
            $addon_info->version = $xml_obj->attrs->version;
            $addon_info->author->name = $xml_obj->author->name->body;
            $addon_info->author->email_address = $xml_obj->author->attrs->email_address;
            $addon_info->author->homepage = $xml_obj->author->attrs->link;
            $addon_info->author->date = $xml_obj->author->attrs->date;
            $addon_info->author->description = trim($xml_obj->author->description->body);

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

            return $addon_info;
        }

        /**
         * @brief 활성화된 애드온 목록을 구해옴
         **/
        function getActivatedAddons() {
            $oDB = &DB::getInstance();
            $args->list_order = 'addon';
            $output = $oDB->executeQuery('addon.getAddons', $args);
            if(!$output->data) return array();
            if(!is_array($output->data)) $output->data = array($output->data);

            $activated_count = count($output->data);
            for($i=0;$i<$activated_count;$i++) {
                $addon = $output->data[$i];
                $addon_list[] = $addon->addon;
            }
            return $addon_list;
        }

        /**
         * @brief 애드온이 활성화 되어 있는지 체크
         **/
        function isActivatedAddon($addon) {
            $oDB = &DB::getInstance();
            $args->addon = $addon;
            $output = $oDB->executeQuery('addon.getAddonIsActivated', $args);
            if($output->data->count>0) return true;
            return false;
        }


    }
?>
