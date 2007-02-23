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
        function getModuleInfoByModuleSrl($module_srl) {
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
            if(!$source_module_info || !is_object($source_module_info) ) return;

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
            if($admin_id) $module_info->admin_id = explode(',',$admin_id);

            return $module_info;
        }

        /**
         * @brief 특정 모듈의 스킨의 정보를 구해옴
         **/
        function loadSkinInfo($module, $skin) {

            // 등록하려는 모듈의 path를 구함
            $module_path = ModuleHandler::getModulePath($module);

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
         * @brief module의 conf/module.xml 을 통해 grant(권한) 및 action 데이터를 return
         *
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
            $cache_file = sprintf("./files/cache/module_info/%s.php", $module);

            // 캐시 파일이 없거나 캐시 파일이 xml 파일보다 오래되었으면 내용 다시 갱신
            if(!file_exists($cache_file) || filectime($cache_file)<filectime($xml_file)) {

                $buff = ""; ///< 캐시 파일에 쓸 buff 변수 설정

                $xml_obj = XmlParser::loadXmlFile($xml_file); ///< xml 파일을 읽어서 xml object로 변환

                if(!count($xml_obj->module)) return; ///< xml 내용중에 module 태그가 없다면 오류;;

                $grants = $xml_obj->module->grants->grant; ///< 권한 정보 (없는 경우도 있음)
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
        function getModuleInfoXml($module) {
            // 요청된 모듈의 경로를 구한다. 없으면 return
            $module_path = ModuleHandler::getModulePath($module);
            if(!$module_path) return;

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

            // action 정보를 얻어서 admin_index를 추가
            $action_info = $this->getModuleActionXml($module);
            $module_info->admin_index_act = $action_info->admin_index_act;

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
