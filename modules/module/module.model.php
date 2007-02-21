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
         * @brief module의 conf/module.xml 을 통해 grant(권한) 및 action 데이터를 return
         **/
        function getModuleXmlInfo($module) {
            $class_path = ModuleHandler::getModulePath($module);
            if(!$class_path) return;

            $xml_file = sprintf("%sconf/module.xml", $class_path);
            if(!file_exists($xml_file)) return;

            $xml_obj = XmlParser::loadXmlFile($xml_file);
            if(!count($xml_obj->module)) return;

            $output->standalone = $xml_obj->module->attrs->standalone=='true'?true:false; ///< 모듈 자체적으로 실행이 가능한지에 대한 값 (기본=false)
            $output->default_action = $xml_obj->module->attrs->default_action; ///< 별도의 action이 지정되지 않으면 호출될 action
            $output->management_action = $xml_obj->module->attrs->management_action; ///< 관리자용으로 사용될 기본 action

            $grants = $xml_obj->module->grants->grant; ///< 권한 정보 (없는 경우도 있음)
            $actions = $xml_obj->module->actions->action; ///< action list (필수)

            // 권한 정보의 정리
            if($grants) {
                if(is_array($grants)) $grant_list = $grants;
                else $grant_list[] = $grants;

                foreach($grant_list as $grant) {
                    $name = $grant->attrs->name;
                    $default = $grant->attrs->default;
                    $title = $grant->title->body;
                    if(!$default) $default = 'guest';

                    $output->grant->{$name}->title = $title;
                    $output->grant->{$name}->default = $default;
                }
            }

            // actions 정리
            if($actions) {
                if(is_array($actions)) $action_list = $actions;
                else $action_list[] = $actions;

                foreach($action_list as $action) {
                    $name = $action->attrs->name;
                    $type = $action->attrs->type;
                    $grant = $action->attrs->grant;
                    if(!$grant) $grant = 'guest';
                    $output->action->{$name}->type = $type;
                    $output->action->{$name}->grant = $grant;
                }
            }

            return $output;
        }


        /**
         * @brief 모듈의 종류와 정보를 구함
         **/
        function getModuleList() {
            // DB 객체 생성
            $oDB = &DB::getInstance();

            // 다운받은 모듈과 설치된 모듈의 목록을 구함
            $downloaded_list = FileHandler::readDir('./files/modules');
            $installed_list = FileHandler::readDir('./modules');
            $searched_list = array_merge($downloaded_list, $installed_list);
            if(!count($searched_list)) return;

            for($i=0;$i<count($searched_list);$i++) {
                // 모듈의 이름
                $module_name = $searched_list[$i];

                // 모듈의 경로 (files/modules가 우선)
                $path = ModuleHandler::getModulePath($module_name);

                // schemas내의 테이블 생성 xml파일수를 구함
                $tmp_files = FileHandler::readDir($path."schemas");
                $table_count = count($tmp_files);

                // 테이블이 설치되어 있는지 체크
                $created_table_count = 0;
                for($j=0;$j<count($tmp_files);$j++) {
                    list($table_name) = explode(".",$tmp_files[$j]);
                    if($oDB->isTableExists($table_name)) $created_table_count ++;
                }

                // 해당 모듈의 정보를 구함
                $info = $this->loadModuleXml($path);
                unset($obj);

                $info->module = $module_name;
                $info->created_table_count = $created_table_count;
                $info->table_count = $table_count;
                $info->path = $path;

                $list[] = $info;
            }
            return $list;
        }

        /**
         * @brief document_srl로 모듈의 정보르 구함
         **/
        function getModuleInfoByDocumentSrl($document_srl) {
            // DB 객체 생성
            $oDB = &DB::getInstance();

            // 데이터를 DB에서 가져옴
            $args->document_srl = $document_srl;
            $output = $oDB->executeQuery('module.getModuleInfoByDocument', $args);

            return $this->arrangeModuleInfo($output->data);
        }

        /**
         * @brief mid로 모듈의 정보를 구함
         **/
        function getModuleInfoByMid($mid='') {
            // DB 객체 생성
            $oDB = &DB::getInstance();

            // $mid값이 인자로 주어질 경우 $mid로 모듈의 정보를 구함
            if($mid) {
                $args->mid = $mid;
                $output = $oDB->executeQuery('module.getMidInfo', $args);
            }

            // 모듈의 정보가 없다면($mid가 잘못이거나 없었을 경우) 기본 모듈을 가져옴
            if(!$output->data) {
                $output = $oDB->executeQuery('module.getDefaultMidInfo');
            }

            return $this->arrangeModuleInfo($output->data);
        }

        /**
         * @brief module_srl에 해당하는 모듈의 정보를 구함
         **/
        function getModuleInfoByModuleSrl($module_srl='') {
            // db객체 생성
            $oDB = &DB::getInstance();

            // 데이터를 가져옴
            $args->module_srl = $module_srl;
            $output = $oDB->executeQuery('module.getMidInfo', $args);
            if(!$output->data) return;
        
            return $this->arrangeModuleInfo($output->data);
        }

        /**
         * @brief DB에서 가져온 원 모듈 정보에서 grant, extraVar등의 정리
         **/
        function arrangeModuleInfo($source_module_info) {
            if(!$source_module_info) return;

            // serialize되어 있는 변수들 추출
            $extra_vars = $source_module_info->extra_vars;
            $grants = $source_module_info->grants;
            $admin_id = $source_module_info->admin_id;

            unset($source_module_info->extra_vars);
            unset($source_module_info->grants);
            unset($source_module_info->admin_id);

            $module_info = clone($source_module_info);

            // extra_vars의 정리
            if($extra_vars) {
                $extra_vars = unserialize($extra_vars);
                foreach($extra_vars as $key => $val) if(!$module_info->{$key}) $module_info->{$key} = $val;
            }

            // 권한의 정리
            if($grants) $module_info->grants = unserialize($grants);

            // 관리자 아이디의 정리
            if($module_info->admin_id) {
                $module_info->admin_id = explode(',',$module_info->admin_id);
            }

            return $module_info;
        }

        /**
         * @brief 특정 모듈의 스킨의 정보를 구해옴
         **/
        function loadSkinInfo($module, $skin) {

            // 등록하려는 모듈의 path를 구함
            $module_path = ModuleHandler::getModulePath($args->module);

            // 모듈의 스킨의 정보 xml 파일을 읽음
            $skin_xml_file = sprintf("%sskins/%s/skin.xml", $module_path, $skin);
            if(!file_exists($skin_xml_file)) return;

            // XmlParser 객체 생성
            $oXmlParser = new XmlParser();
            $xml_obj = $oXmlParser->loadXmlFile($skin_xml_file);

            // 스킨 정보가 없으면 return
            if(!$xml_obj) return;

            // 스킨이름
            $skin_info->title = $xml_obj->title->body;

            // 작성자 정보
            $skin_info->maker->name = $xml_obj->maker->name->body;
            $skin_info->maker->email_address = $xml_obj->maker->attrs->email_address;
            $skin_info->maker->homepage = $xml_obj->maker->attrs->link;
            $skin_info->maker->date = $xml_obj->maker->attrs->date;
            $skin_info->maker->description = $xml_obj->maker->description->body;

            // colorset
            if(!is_array($xml_obj->colorset->color)) $colorset[] = $xml_obj->colorset->color;
            else $colorset = $xml_obj->colorset->color;

            foreach($colorset as $color) {
                $name = $color->attrs->name;
                $title = $color->title->body;
                $screenshot = $color->attrs->src;
                if($screenshot && file_exists($screenshot)) $screenshot = sprintf("%sskins/%s/%s",$module_path,$skin,$screenshot);
                else $screenshot = "";

                unset($obj);
                $obj->name = $name;
                $obj->title = $title;
                $obj->screenshot = $screenshot;
                $skin_info->colorset[] = $obj;
            }

            // 스킨에서 사용되는 변수들
            if(!is_array($xml_obj->extra_vars->var)) $extra_vars[] = $xml_obj->extra_vars->var;
            else $extra_vars = $xml_obj->extra_vars->var;

            foreach($extra_vars as $var) {
                    $name = $var->attrs->name;
                    $type = $var->attrs->type;
                    $title = $var->title->body;
                    $description = $var->description->body;
                    if($var->default) {
                        unset($default);
                        if(is_array($var->default)) {
                            for($i=0;$i<count($var->default);$i++) $default[] = $var->default[$i]->body;
                        } else {
                            $default = $var->default->body;
                    }
                }

                $width = $var->attrs->width;
                $height = $var->attrs->height;

                unset($obj);
                $obj->title = $title;
                $obj->description = $description;
                $obj->name = $name;
                $obj->type = $type;
                $obj->default = $default;
                $obj->width = $width;
                $obj->height = $height;

                $skin_info->extra_vars[] = $obj;
            }

            return $skin_info;
        }

        /**
         * @brief 모듈의 conf/info.xml 을 읽어서 정보를 구함
         **/
        function loadModuleXml($module_path) {
            // 현재 선택된 모듈의 스킨의 정보 xml 파일을 읽음
            $xml_file = sprintf("%s/conf/info.xml", $module_path);
            if(!file_exists($xml_file)) return;

            $oXmlParser = new XmlParser();
            $xml_obj = $oXmlParser->loadXmlFile($xml_file);

            if(!$xml_obj) return;

            $info->title = $xml_obj->title->body;

            // 작성자 정보
            $module_info->title = $xml_obj->title->body;
            $module_info->version = $xml_obj->attrs->version;
            $module_info->author->name = $xml_obj->author->name->body;
            $module_info->author->email_address = $xml_obj->author->attrs->email_address;
            $module_info->author->homepage = $xml_obj->author->attrs->link;
            $module_info->author->date = $xml_obj->author->attrs->date;
            $module_info->author->description = $xml_obj->author->description->body;

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
                $module_info->history[] = $obj;
            }

            return $module_info;
        }

        /**
         * @brief 모듈의 스킨 목록을 구함
         **/
        function getSkins($module_path) {
            $skins_path = sprintf("%s/skins/", $module_path);
            $list = FileHandler::readDir($skins_path);
            return $list;
        }

    }
?>
