<?php
    /**
     * @class  editorModel
     * @author zero (zero@nzeo.com)
     * @brief  editor 모듈의 model 클래스
     **/

    class editorModel extends editor {

        /**
         * @brief component의 객체 생성
         **/
        function getComponentObject($component, $upload_target_srl = 0) {
            // 해당 컴포넌트의 객체를 생성해서 실행
            $class_path = sprintf('%scomponents/%s/', $this->module_path, $component);
            $class_file = sprintf('%s%s.class.php', $class_path, $component);
            if(!file_exists($class_file)) return new Object(-1, sprintf(Context::getLang('msg_component_is_not_founded'), $component));

            require_once($class_file);
            $eval_str = sprintf('$oComponent = new %s("%s","%s");', $component, $upload_target_srl, $class_path);
            @eval($eval_str);
            if(!$oComponent) return new Object(-1, sprintf(Context::getLang('msg_component_is_not_founded'), $component));

            return $oComponent;
        }

        /**
         * @brief component 목록을 return (DB정보 보함)
         **/
        function getComponentList($filter_enabled = true) {
            if($filter_enabled) $args->enabled = "Y";

            // DB에서 가져옴
            $oDB = &DB::getInstance();
            $output = $oDB->executeQuery('editor.getComponentList', $args);
            $db_list = $output->data;

            // 파일목록을 구함
            $downloaded_list = FileHandler::readDir($this->module_path.'components');

            // DB 목록을 loop돌면서 xml정보까지 구함
            if(!is_array($db_list)) $db_list = array($db_list);
            foreach($db_list as $component) {
                if(in_array($component->component_name, array('colorpicker_text','colorpicker_bg'))) continue;
                if(!$component->component_name) continue;

                $component_name = $component->component_name;

                unset($xml_info);
                $xml_info = $this->getComponentXmlInfo($component_name);
                $xml_info->enabled = $component->enabled;

                if($component->extra_vars) {
                    $extra_vars = unserialize($component->extra_vars);
                    foreach($xml_info->extra_vars as $key => $val) {
                        $xml_info->extra_vars->{$key}->value = $extra_vars->{$key};
                    }
                }

                $component_list->{$component_name} = $xml_info;
            }

            // enabled만 체크하도록 하였으면 그냥 return
            if($filter_enabled) return $component_list;

            // 다운로드된 목록의 xml_info를 마저 구함
            foreach($downloaded_list as $component_name) {
                if(in_array($component_name, array('colorpicker_text','colorpicker_bg'))) continue;

                // 설정된 것이라면 패스
                if($component_list->{$component_name}) continue;

                // DB에 입력
                $oEditorController = &getController('editor');
                $oEditorController->insertComponent($component_name, false);

                // component_list에 추가
                unset($xml_info);
                $xml_info = $this->getComponentXmlInfo($component_name);
                $xml_info->enabled = 'N';

                $component_list->{$component_name} = $xml_info;
            }

            return $component_list;
        }

        /**
         * @brief compnent의 xml+db정보를 구함
         **/
        function getComponent($component_name) {
            $args->component_name = $component_name;

            // DB에서 가져옴
            $oDB = &DB::getInstance();
            $output = $oDB->executeQuery('editor.getComponent', $args);
            $component = $output->data;

            $component_name = $component->component_name;

            unset($xml_info);
            $xml_info = $this->getComponentXmlInfo($component_name);
            $xml_info->enabled = $component->enabled;

            if($component->extra_vars) {
                $extra_vars = unserialize($component->extra_vars);
                foreach($xml_info->extra_vars as $key => $val) {
                    $xml_info->extra_vars->{$key}->value = $extra_vars->{$key};
                }
            }

            return $xml_info;
        }

        /**
         * @brief component의 xml정보를 읽음
         **/
        function getComponentXmlInfo($component) {
            $lang_type = Context::getLangType();

            // 요청된 컴포넌트의 xml파일 위치를 구함
            $component_path = sprintf('%scomponents/%s/', $this->module_path, $component);

            $xml_file = sprintf('%sinfo.xml', $component_path);
            $cache_file = sprintf('./files/cache/editor/%s.%s.php', $component, $lang_type);

            // 캐시된 xml파일이 있으면 include 후 정보 return
            if(file_exists($cache_file) && filectime($cache_file) > filectime($xml_file)) {
                @include $cache_file;
                return $xml_info;
            }

            // 캐시된 파일이 없으면 파싱후 캐싱 후 return
            $oParser = new XmlParser();
            $xml_doc = $oParser->loadXmlFile($xml_file);

            // 정보 정리
            $xml_info->component_name = $component;
            $xml_info->version = $xml_doc->component->attrs->version;
            $xml_info->title = $xml_doc->component->title->body;
            $xml_info->author->name = $xml_doc->component->author->name->body;
            $xml_info->author->email_address = $xml_doc->component->author->attrs->email_address;
            $xml_info->author->link = $xml_doc->component->author->attrs->link;
            $xml_info->author->date = $xml_doc->component->author->attrs->date;
            $xml_info->description = str_replace('\n', "\n", $xml_doc->component->author->description->body);

            $buff = '<?php if(!__ZBXE__) exit(); ';
            $buff .= sprintf('$xml_info->component_name = "%s";', $component);
            $buff .= sprintf('$xml_info->version = "%s";', $xml_info->version);
            $buff .= sprintf('$xml_info->title = "%s";', $xml_info->title);
            $buff .= sprintf('$xml_info->author->name = "%s";', $xml_info->author->name);
            $buff .= sprintf('$xml_info->author->email_address = "%s";', $xml_info->author->email_address);
            $buff .= sprintf('$xml_info->author->link = "%s";', $xml_info->author->link);
            $buff .= sprintf('$xml_info->author->date = "%s";', $xml_info->author->date);
            $buff .= sprintf('$xml_info->description = "%s";', $xml_info->description);

            // 추가 변수 정리 (에디터 컴포넌트에서는 text형만 가능)
            $extra_vars = $xml_doc->component->extra_vars->var;
            if($extra_vars) {
                if(!is_array($extra_vars)) $extra_vars = array($extra_vars);
                foreach($extra_vars as $key => $val) {
                    unset($obj);
                    $key = $val->attrs->name;
                    $title = $val->title->body;
                    $description = $val->description->body;
                    $xml_info->extra_vars->{$key}->title = $title;
                    $xml_info->extra_vars->{$key}->description = $description;

                    $buff .= sprintf('$xml_info->extra_vars->%s->%s = "%s";', $key, 'title', $title);
                    $buff .= sprintf('$xml_info->extra_vars->%s->%s = "%s";', $key, 'description', $description);
                }
            }

            $buff .= ' ?>';

            FileHandler::writeFile($cache_file, $buff, "w");

            return $xml_info;
        }
    }
?>
