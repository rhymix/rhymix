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
         * @brief component의 xml정보를 읽음
         **/
        function getComponentXmlInfo($component) {
            // 요청된 컴포넌트의 xml파일 위치를 구함
            $component_path = sprintf('%scomponents/%s/', $this->module_path, $component);

            $xml_file = sprintf('%sinfo.xml', $component_path);
            $cache_file = sprintf('./files/cache/editor/%s.php', $component);

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
            $xml_info->author->name = $xml_doc->component->author->name->body;
            $xml_info->author->email_address = $xml_doc->component->author->attrs->email_address;
            $xml_info->author->link = $xml_doc->component->author->attrs->link;
            $xml_info->author->date = $xml_doc->component->author->attrs->date;
            $xml_info->description = str_replace('\n', "\n", $xml_doc->component->author->description->body);

            $buff = '<?php if(!__ZB5__) exit(); ';
            $buff .= sprintf('$xml_info->component_name = "%s";', $component);
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

            return $xml_doc->component;
        }
    }
?>
