<?php
    /**
     * @class  editorModel
     * @author zero (zero@nzeo.com)
     * @brief  editor 모듈의 model 클래스
     **/

    class editorModel extends editor {

        /**
         * @brief 에디터를 return
         **/
        function getEditor($upload_target_srl, $allow_fileupload = false, $enable_autosave = false) {
            // 저장된 임시본이 있는지 검사
            if($enable_autosave) {
                $saved_doc = $this->getSavedDoc($upload_target_srl);
                Context::set('saved_doc', $saved_doc);
            }
            Context::set('enable_autosave', $enable_autosave);

            // 업로드를 위한 변수 설정
            Context::set('upload_target_srl', $upload_target_srl);
            Context::set('allow_fileupload', $allow_fileupload);

            // 에디터 컴포넌트를 구함
            if(!Context::get('component_list')) {
                $component_list = $this->getComponentList();
                Context::set('component_list', $component_list);
            }

            // 첨부파일 모듈의 정보를 구함
            $logged_info = Context::get('logged_info');
            if($logged_info->member_srl && $logged_info->is_admin == 'Y') {
                $file_config->allowed_filesize = 1024*1024*1024;
                $file_config->allowed_attach_size = 1024*1024*1024;
                $file_config->allowed_filetypes = '*.*';
            } else {
                $oModuleModel = &getModel('module');
                $file_config = $oModuleModel->getModuleConfig('file');
                $file_config->allowed_filesize = $file_config->allowed_filesize * 1024;
                $file_config->allowed_attach_size = $file_config->allowed_attach_size * 1024;
            }
            Context::set('file_config',$file_config);

            // 템플릿을 미리 컴파일해서 컴파일된 소스를 return
            $tpl_path = $this->module_path.'tpl';
            $tpl_file = 'editor.html';

            // editor_path를 지정
            Context::set('editor_path', $tpl_path);

            // 만약 allow_fileupload == true 이면 upload_target_srl에 upload가능하다고 설정 
            if($allow_fileupload) {
                $oFileController = &getController('file');
                $oFileController->setUploadEnable($upload_target_srl);
            }
            $oTemplate = &TemplateHandler::getInstance();
            return $oTemplate->compile($tpl_path, $tpl_file);
        }

        /**
         * @brief 자동저장되어 있는 정보를 가져옴
         **/
        function getSavedDoc($upload_target_srl) {
            // 로그인 회원이면 member_srl, 아니면 ipaddress로 저장되어 있는 문서를 찾음
            if(Context::get('is_logged')) {
                $logged_info = Context::get('logged_info');
                $auto_save_args->member_srl = $logged_info->member_srl;
            } else {
                $auto_save_args->ipaddress = $_SERVER['REMOTE_ADDR'];
            }

            $output = executeQuery('editor.getSavedDocument', $auto_save_args);
            $saved_doc = $output->data;
            if(!$saved_doc) return;

            // 원본 글이 저장되어 있지 않은 글일 경우 첨부된 파일이 있으면 현재 글 번호로 옮김
            $oDocumentModel = &getModel('document');
            $document = $oDocumentModel->getDocument($saved_doc->document_srl);
            if($document->document_srl != $saved_doc->document_srl) {
                $module_srl = Context::get('module_srl');
                $oFileController = &getController('file');
                $oFileController->moveFile($saved_doc->document_srl, $module_srl, $upload_target_srl);
            }

            return $saved_doc;
        }

        /**
         * @brief component의 객체 생성
         **/
        function getComponentObject($component, $upload_target_srl = 0) {
            // 해당 컴포넌트의 객체를 생성해서 실행
            $class_path = sprintf('%s/components/%s/', $this->module_path, $component);
            $class_file = sprintf('%s%s.class.php', $class_path, $component);
            if(!file_exists($class_file)) return new Object(-1, sprintf(Context::getLang('msg_component_is_not_founded'), $component));

            // 클래스 파일을 읽은 후 객체 생성
            require_once($class_file);
            $eval_str = sprintf('$oComponent = new %s("%s","%s");', $component, $upload_target_srl, $class_path);
            @eval($eval_str);
            if(!$oComponent) return new Object(-1, sprintf(Context::getLang('msg_component_is_not_founded'), $component));

            // 설정 정보를 추가
            $component_info = $this->getComponent($component);
            $oComponent->setInfo($component_info);

            return $oComponent;
        }

        /**
         * @brief component 목록을 return (DB정보 보함)
         **/
        function getComponentList($filter_enabled = true) {
            if($filter_enabled) $args->enabled = "Y";

            $output = executeQuery('editor.getComponentList', $args);
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
                $oEditorController = &getAdminController('editor');
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

            $output = executeQuery('editor.getComponent', $args);
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
            $component_path = sprintf('%s/components/%s/', $this->module_path, $component);

            $xml_file = sprintf('%sinfo.xml', $component_path);
            $cache_file = sprintf('./files/cache/editor/%s.%s.php', $component, $lang_type);

            // 캐시된 xml파일이 있으면 include 후 정보 return
            if(file_exists($cache_file) && filectime($cache_file) > filectime($xml_file)) {
                @include($cache_file);
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

            $buff = '<?php if(!defined("__ZBXE__")) exit(); ';
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
