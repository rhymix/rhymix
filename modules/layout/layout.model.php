<?php
    /**
     * @class  layoutModel
     * @author zero (zero@nzeo.com)
     * @brief  layout 모듈의 Model class
     **/

    class layoutModel extends layout {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief DB 에 생성된 레이아웃의 목록을 구함
         **/
        function getLayoutList() {
            $oDB = &DB::getInstance();
            $output = $oDB->executeQuery('layout.getLayoutList');
            if(!$output->data) return;

            if(is_array($output->data)) return $output->data;
            return array($output->data);
        }

        /**
         * @brief 레이아웃의 경로를 구함
         **/
        function getLayoutPath($layout_name) {
            $class_path = sprintf('./files/layouts/%s/', $layout_name);
            if(is_dir($class_path)) return $class_path;

            $class_path = sprintf('./layouts/%s/', $layout_name);
            if(is_dir($class_path)) return $class_path; 

            return "";
        }

        /**
         * @brief 레이아웃의 종류와 정보를 구함
         **/
        function getDownloadedLayoutList() {
            // 다운받은 레이아웃과 설치된 레이아웃의 목록을 구함
            $downloaded_list = FileHandler::readDir('./files/layouts');
            $installed_list = FileHandler::readDir('./layouts');
            $searched_list = array_merge($downloaded_list, $installed_list);
            $searched_count = count($searched_list);
            if(!$searched_count) return;

            for($i=0;$i<$searched_count;$i++) {
                // 레이아웃의 이름
                $layout_name = $searched_list[$i];

                // 레이아웃의 경로 (files/layouts가 우선)
                $path = $this->getLayoutPath($layout_name);

                // 해당 레이아웃의 정보를 구함
                $info = $this->getLayoutInfoXml($layout_name);
                unset($obj);

                $info->layout = $layout_name;
                $info->path = $path;

                $list[] = $info;
            }
            return $list;
        }

        /**
         * @brief 모듈의 conf/info.xml 을 읽어서 정보를 구함
         **/
        function getLayoutInfoXml($layout) {
            // 요청된 모듈의 경로를 구한다. 없으면 return
            $layout_path = $this->getLayoutPath($layout);
            if(!$layout_path) return;

            // 현재 선택된 모듈의 스킨의 정보 xml 파일을 읽음
            $xml_file = sprintf("%sconf/info.xml", $layout_path);
            if(!file_exists($xml_file)) return;

            $oXmlParser = new XmlParser();
            $tmp_xml_obj = $oXmlParser->loadXmlFile($xml_file);
            $xml_obj = $tmp_xml_obj->layout;

            if(!$xml_obj) return;

            $info->title = $xml_obj->title->body;

            // 작성자 정보
            $layout_info->title = $xml_obj->title->body;
            $layout_info->version = $xml_obj->attrs->version;
            $layout_info->author->name = $xml_obj->author->name->body;
            $layout_info->author->email_address = $xml_obj->author->attrs->email_address;
            $layout_info->author->homepage = $xml_obj->author->attrs->link;
            $layout_info->author->date = $xml_obj->author->attrs->date;
            $layout_info->author->description = $xml_obj->author->description->body;

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
                $layout_info->history[] = $obj;
            }

            // navigations
            if(!is_array($xml_obj->navigations->navigation)) $navigations[] = $xml_obj->navigations->navigation;
            else $navigations = $xml_obj->navigations->navigation;

            unset($item);
            foreach($navigations as $item) {
                unset($obj);
                $obj->id = $item->attrs->id;
                $obj->name = $item->name->body;
                $obj->maxdepth = $item->maxdepth->body;
                $layout_info->navigations[] = $obj;
            }

            return $layout_info;
        }

        /**
         * @brief 메뉴 구성을 하기 위해 메뉴 srl을 return
         **/
        function getLayoutMenuSrl() {
            $menu_id = Context::get('menu_id');

            $oDB = &DB::getInstance();
            $menu_srl = $oDB->getNextSequence();

            $this->add('menu_id', $menu_id);
            $this->add('menu_srl', $menu_srl);
        }

        /**
         * @brief 특정 menu_srl의 정보를 이용하여 템플릿을 구한후 return
         **/
        function getMenuInfo() {
            $menu_id = Context::get('menu_id');
            $menu_srl = Context::get('menu_srl');
            $layuot = Context::get('layout');

            require_once("./classes/template/TemplateHandler.class.php");
            $oTemplate = new TemplateHandler();
            $tpl = $oTemplate->compile($this->module_path.'tpl.admin', 'layout_menu_info');

            $this->add('menu_id', $menu_id);
            $this->add('tpl', $tpl);
        }
    }
?>
