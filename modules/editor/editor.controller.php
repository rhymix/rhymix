<?php
    /**
     * @class  editor
     * @author NHN (developers@xpressengine.com)
     * @brief  editor 모듈의 controller class
     **/

    class editorController extends editor {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 자동 저장
         **/
        function procEditorSaveDoc() {

            $this->deleteSavedDoc(false);

            $args->document_srl = Context::get('document_srl');
            $args->content = Context::get('content');
            $args->title = Context::get('title');
            $output = $this->doSaveDoc($args);

            $this->setMessage('msg_auto_saved');
        }

        /**
         * @brief 자동저장된 문서 삭제
         **/
        function procEditorRemoveSavedDoc() {
            $oEditorController = &getController('editor');
            $oEditorController->deleteSavedDoc(true);
        }

        /**
         * @brief 컴포넌트에서 ajax요청시 해당 컴포넌트의 method를 실행 
         **/
        function procEditorCall() {
            $component = Context::get('component');
            $method = Context::get('method');
            if(!$component) return new Object(-1, sprintf(Context::getLang('msg_component_is_not_founded'), $component));

            $oEditorModel = &getModel('editor');
            $oComponent = &$oEditorModel->getComponentObject($component);
            if(!$oComponent->toBool()) return $oComponent;

            if(!method_exists($oComponent, $method)) return new Object(-1, sprintf(Context::getLang('msg_component_is_not_founded'), $component));

            //$output = call_user_method($method, $oComponent);
            //$output = call_user_func(array($oComponent, $method));
            if(method_exists($oComponent, $method)) $output = $oComponent->{$method}();
            else return new Object(-1,sprintf('%s method is not exists', $method));

            if((is_a($output, 'Object') || is_subclass_of($output, 'Object')) && !$output->toBool()) return $output;

            $this->setError($oComponent->getError());
            $this->setMessage($oComponent->getMessage());

            $vars = $oComponent->getVariables();
            if(count($vars)) {
                foreach($vars as $key=>$val) $this->add($key, $val);
            }
        }

        /**
         * @brief 에디터의 모듈별 추가 확장 폼을 저장
         **/
        function procEditorInsertModuleConfig() {
            $module_srl = Context::get('target_module_srl');

            // 여러개의 모듈 일괄 설정일 경우
            if(preg_match('/^([0-9,]+)$/',$module_srl)) $module_srl = explode(',',$module_srl);
            else $module_srl = array($module_srl);

            $editor_config = null;

            $editor_config->editor_skin = Context::get('editor_skin');
            $editor_config->comment_editor_skin = Context::get('comment_editor_skin');
            $editor_config->content_style = Context::get('content_style');
            $editor_config->comment_content_style = Context::get('comment_content_style');
            $editor_config->content_font = Context::get('content_font');
            if($editor_config->content_font) {
                $font_list = array();
                $fonts = explode(',',$editor_config->content_font);
                for($i=0,$c=count($fonts);$i<$c;$i++) {
                    $font = trim(str_replace(array('"','\''),'',$fonts[$i]));
                    if(!$font) continue;
                    $font_list[] = $font;
                }
                if(count($font_list)) $editor_config->content_font = '"'.implode('","',$font_list).'"';
            }
            $editor_config->content_font_size = Context::get('content_font_size');
            $editor_config->sel_editor_colorset = Context::get('sel_editor_colorset');
            $editor_config->sel_comment_editor_colorset = Context::get('sel_comment_editor_colorset');

            $enable_html_grant = trim(Context::get('enable_html_grant'));
            if($enable_html_grant) $editor_config->enable_html_grant = explode('|@|', $enable_html_grant);
            else $editor_config->enable_html_grant = array();

            $enable_comment_html_grant = trim(Context::get('enable_comment_html_grant'));
            if($enable_comment_html_grant) $editor_config->enable_comment_html_grant = explode('|@|', $enable_comment_html_grant);
            else $editor_config->enable_comment_html_grant = array();

            $upload_file_grant = trim(Context::get('upload_file_grant'));
            if($upload_file_grant) $editor_config->upload_file_grant = explode('|@|', $upload_file_grant);
            else $editor_config->upload_file_grant = array();

            $comment_upload_file_grant = trim(Context::get('comment_upload_file_grant'));
            if($comment_upload_file_grant) $editor_config->comment_upload_file_grant = explode('|@|', $comment_upload_file_grant);
            else $editor_config->comment_upload_file_grant = array();

            $enable_default_component_grant = trim(Context::get('enable_default_component_grant'));
            if($enable_default_component_grant) $editor_config->enable_default_component_grant = explode('|@|', $enable_default_component_grant);
            else $editor_config->enable_default_component_grant = array();

            $enable_comment_default_component_grant = trim(Context::get('enable_comment_default_component_grant'));
            if($enable_comment_default_component_grant) $editor_config->enable_comment_default_component_grant = explode('|@|', $enable_comment_default_component_grant);
            else $editor_config->enable_comment_default_component_grant = array();

            $enable_component_grant = trim(Context::get('enable_component_grant'));
            if($enable_component_grant) $editor_config->enable_component_grant = explode('|@|', $enable_component_grant);
            else $editor_config->enable_component_grant = array();

            $enable_comment_component_grant = trim(Context::get('enable_comment_component_grant'));
            if($enable_comment_component_grant) $editor_config->enable_comment_component_grant = explode('|@|', $enable_comment_component_grant);
            else $editor_config->enable_comment_component_grant = array();

            $editor_config->editor_height = (int)Context::get('editor_height');

            $editor_config->comment_editor_height = (int)Context::get('comment_editor_height');

            $editor_config->enable_autosave = Context::get('enable_autosave');

            if($editor_config->enable_autosave != 'Y') $editor_config->enable_autosave = 'N';

            $oModuleController = &getController('module');
            for($i=0;$i<count($module_srl);$i++) {
                $srl = trim($module_srl[$i]);
                if(!$srl) continue;
                $oModuleController->insertModulePartConfig('editor',$srl,$editor_config);
            }

            $this->setError(-1);
            $this->setMessage('success_updated');
        }

        /**
         * @brief 에디터컴포넌트의 코드를 결과물로 변환 + 문서서식 style 지정
         **/
        function triggerEditorComponentCompile(&$content) {
            if(Context::getResponseMethod()!='HTML') return new Object();
			if(Mobile::isFromMobilePhone()) return new Object(); 

            $module_info = Context::get('module_info');
            $module_srl = $module_info->module_srl;
            if($module_srl) {
                $oEditorModel = &getModel('editor');
                $editor_config = $oEditorModel->getEditorConfig($module_srl);
                $content_style = $editor_config->content_style;
                if($content_style) {
                    $path = _XE_PATH_.'modules/editor/styles/'.$content_style.'/';
                    if(is_dir($path) && file_exists($path.'style.ini')) {
                        $ini = file($path.'style.ini');
                        for($i=0,$c=count($ini);$i<$c;$i++) {
                            $file = trim($ini[$i]);
                            if(!$file) continue;
                            if(preg_match('/\.css$/i',$file)) Context::addCSSFile('./modules/editor/styles/'.$content_style.'/'.$file, false);
                            elseif(preg_match('/\.js/i',$file)) Context::addJsFile('./modules/editor/styles/'.$content_style.'/'.$file, false);
                        }
                    }
                }
                $content_font = $editor_config->content_font;
                $content_font_size = $editor_config->content_font_size;
                if($content_font || $content_font_size) {
                    $buff = '<style type="text/css"> .xe_content { ';
                    if($content_font) $buff .= 'font-family:'.$content_font.';';
                    if($content_font_size) $buff .= 'font-size:'.$content_font_size.';';
                    $buff .= ' }; </style>';
                    Context::addHtmlHeader($buff);
                }
            }

            $content = $this->transComponent($content);
			return new Object();
        }

        /**
         * @brief 에디터 컴포넌트코드를 결과물로 변환
         **/
        function transComponent($content) {
            $content = preg_replace_callback('!<div([^\>]*)editor_component=([^\>]*)>(.*?)\<\/div\>!is', array($this,'transEditorComponent'), $content);
            $content = preg_replace_callback('!<img([^\>]*)editor_component=([^\>]*?)\>!is', array($this,'transEditorComponent'), $content);
            return $content;
        }

        /**
         * @brief 내용의 에디터 컴포넌트 코드를 변환
         **/
        function transEditorComponent($matches) {
            $script = sprintf(' %s editor_component=%s', $matches[1], $matches[2]);
            $script = preg_replace_callback('/([^=^"^ ]*)=([^ ^>]*)/i', fixQuotation, $script);
            preg_match_all('/([a-z0-9\-\_]+)\=\"([^\"]+)\"/is', $script, $m);
            for($i=0,$c=count($m[0]);$i<$c;$i++) {
                $xml_obj->attrs->{$m[1][$i]} = $m[2][$i];
            }
            $xml_obj->body = $matches[3];

            if(!$xml_obj->attrs->editor_component) return $matches[0];

            // component::transHTML() 을 이용하여 변환된 코드를 받음
            $oEditorModel = &getModel('editor');
            $oComponent = &$oEditorModel->getComponentObject($xml_obj->attrs->editor_component, 0);
            if(!is_object($oComponent)||!method_exists($oComponent, 'transHTML')) return $matches[0];

            return $oComponent->transHTML($xml_obj);
        }


        /**
         * @brief 자동 저장
         **/
        function doSaveDoc($args) {

            if(!$args->document_srl) $args->document_srl = $_SESSION['upload_info'][$editor_sequence]->upload_target_srl;
            if(Context::get('is_logged')) {
                $logged_info = Context::get('logged_info');
                $args->member_srl = $logged_info->member_srl;
            } else {
                $args->ipaddress = $_SERVER['REMOTE_ADDR'];
            }
            // module_srl이 없으면 현재 모듈
            if(!$args->module_srl) {
                $args->module_srl = Context::get('module_srl');
            }
            if(!$args->module_srl) {
                $current_module_info = Context::get('current_module_info');
                $args->module_srl = $current_module_info->module_srl;
            }

            // 저장
            return executeQuery('editor.insertSavedDoc', $args);
        }

        /**
          * @brief 자동 저장글 Srl 로드 - XE 이전 버전 사용자를 위함.
         **/
        function procEditorLoadSavedDocument() {
            $editor_sequence = Context::get('editor_sequence');
            $primary_key = Context::get('primary_key');
            $oEditorModel = &getModel('editor');
            $oFileController = &getController('file');

            $saved_doc = $oEditorModel->getSavedDoc(null);

            $oFileController->setUploadInfo($editor_sequence, $saved_doc->document_srl);
            $vars = $this->getVariables();
            $this->add("editor_sequence", $editor_sequence);
            $this->add("key", $primary_key);
            $this->add("title", $saved_doc->title);
            $this->add("content", $saved_doc->content);
            $this->add("document_srl", $saved_doc->document_srl);
        }


        /**
         * @brief 게시글의 입력/수정이 일어났을 경우 자동 저장문서를 제거하는 trigger
         **/
        function triggerDeleteSavedDoc(&$obj) {
            $this->deleteSavedDoc(false);
            return new Object();
        }

        /**
         * @brief 자동 저장된 글을 삭제
         * 현재 접속한 사용자를 기준
         **/
        function deleteSavedDoc($mode = false) {
            if(Context::get('is_logged')) {
                $logged_info = Context::get('logged_info');
                $args->member_srl = $logged_info->member_srl;
            } else {
                $args->ipaddress = $_SERVER['REMOTE_ADDR'];
            }
            $args->module_srl = Context::get('module_srl');
            // module_srl이 없으면 현재 모듈
            if(!$args->module_srl) {
                $current_module_info = Context::get('current_module_info');
                $args->module_srl = $current_module_info->module_srl;
            }

            // 자동저장된 값이 혹시 이미 등록된 글인지 확인
            $output = executeQuery('editor.getSavedDocument', $args);
            $saved_doc = $output->data;
            if(!$saved_doc) return;

            $oDocumentModel = &getModel('document');
            $oSaved = $oDocumentModel->getDocument($saved_doc->document_srl);
            if(!$oSaved->isExists()) {
                if($mode) {
                    $output = executeQuery('editor.getSavedDocument', $args);
                    $output = ModuleHandler::triggerCall('editor.deleteSavedDoc', 'after', $saved_doc);
                }
            }

            // 일단 이전 저장본 삭제
            return executeQuery('editor.deleteSavedDoc', $args);
        }

        /**
         * @brief 가상 사이트에서 사용된 에디터 컴포넌트 정보를 제거
         **/
        function removeEditorConfig($site_srl) {
            $args->site_srl = $site_srl;
            executeQuery('editor.deleteSiteComponent', $args);
        }

        /**
         * @brief 에디터 컴포넌트 목록 캐싱 (editorModel::getComponentList)
         * 에디터 컴포넌트 목록의 경우 DB query + Xml Parsing 때문에 캐싱 파일을 이용하도록 함
         **/
        function makeCache($filter_enabled = true, $site_srl) {
            $oEditorModel = &getModel('editor');

            if($filter_enabled) $args->enabled = "Y";

            if($site_srl) {
                $args->site_srl = $site_srl;
                $output = executeQuery('editor.getSiteComponentList', $args);
            } else $output = executeQuery('editor.getComponentList', $args);
            $db_list = $output->data;

            // 파일목록을 구함
            $downloaded_list = FileHandler::readDir(_XE_PATH_.'modules/editor/components');

            // 로그인 여부 및 소속 그룹 구함
            $is_logged = Context::get('is_logged');
            if($is_logged) {
                $logged_info = Context::get('logged_info');
                if($logged_info->group_list && is_array($logged_info->group_list)) {
                    $group_list = array_keys($logged_info->group_list);
                } else $group_list = array();
            }

            // DB 목록을 loop돌면서 xml정보까지 구함
            if(!is_array($db_list)) $db_list = array($db_list);
            foreach($db_list as $component) {
                if(in_array($component->component_name, array('colorpicker_text','colorpicker_bg'))) continue;

                $component_name = $component->component_name;
                if(!$component_name) continue;

                if(!in_array($component_name, $downloaded_list)) continue;

                unset($xml_info);
                $xml_info = $oEditorModel->getComponentXmlInfo($component_name);
                $xml_info->enabled = $component->enabled;

                if($component->extra_vars) {
                    $extra_vars = unserialize($component->extra_vars);
					if($extra_vars->target_group) {
						$xml_info->target_group = $extra_vars->target_group;	
					}

					if($extra_vars->mid_list && count($extra_vars->mid_list))
					{
						$xml_info->mid_list = $extra_vars->mid_list;
					}
					/*
                    // 사용권한이 있으면 권한 체크
                    if($extra_vars->target_group) {
                        // 사용권한이 체크되어 있는데 로그인이 되어 있지 않으면 무조건 사용 중지
                        if(!$is_logged) continue;

                        // 대상 그룹을 구해서 현재 로그인 사용자의 그룹과 비교
                        $target_group = $extra_vars->target_group;
                        unset($extra_vars->target_group);

                        $is_granted = false;
                        foreach($group_list as $group_srl) {
                            if(in_array($group_srl, $target_group)) {
                                $is_granted = true;
                                break;
                            }
                        }
                        if(!$is_granted) continue;
                    }

                    // 대상 모듈이 있으면 체크
                    if($extra_vars->mid_list && count($extra_vars->mid_list) && Context::get('mid')) {
                        if(!in_array(Context::get('mid'), $extra_vars->mid_list)) continue;
                    }*/

                    // 에디터 컴포넌트의 설정 정보를 체크
                    if($xml_info->extra_vars) {
                        foreach($xml_info->extra_vars as $key => $val) {
                            $xml_info->extra_vars->{$key}->value = $extra_vars->{$key};
                        }
                    }
                }

                $component_list->{$component_name} = $xml_info;

                // 버튼, 아이콘 이미지 구함
                $icon_file = _XE_PATH_.'modules/editor/components/'.$component_name.'/icon.gif';
                $component_icon_file = _XE_PATH_.'modules/editor/components/'.$component_name.'/component_icon.gif';
                if(file_exists($icon_file)) $component_list->{$component_name}->icon = true;
                if(file_exists($component_icon_file)) $component_list->{$component_name}->component_icon = true;
            }

            // enabled만 체크하도록 하였으면 그냥 return
            if($filter_enabled) {
                $cache_file = $oEditorModel->getCacheFile($filter_enabled, $site_srl);
                $buff = sprintf('<?php if(!defined("__ZBXE__")) exit(); $component_list = unserialize("%s"); ?>', str_replace('"','\\"',serialize($component_list)));
                FileHandler::writeFile($cache_file, $buff);
                return $component_list;
            }

            // 다운로드된 목록의 xml_info를 마저 구함
            foreach($downloaded_list as $component_name) {
                if(in_array($component_name, array('colorpicker_text','colorpicker_bg'))) continue;

                // 설정된 것이라면 패스
                if($component_list->{$component_name}) continue;

                // DB에 입력
                $oEditorController = &getAdminController('editor');
                $oEditorController->insertComponent($component_name, false, $site_srl);

                // component_list에 추가
                unset($xml_info);
                $xml_info = $oEditorModel->getComponentXmlInfo($component_name);
                $xml_info->enabled = 'N';

                $component_list->{$component_name} = $xml_info;
            }

            $cache_file = $oEditorModel->getCacheFile($filter_enabled, $site_srl);
            $buff = sprintf('<?php if(!defined("__ZBXE__")) exit(); $component_list = unserialize("%s"); ?>', str_replace('"','\\"',serialize($component_list)));
            FileHandler::writeFile($cache_file, $buff);

            return $component_list;
        }

        /**
         * @brief 캐시 파일 삭제
         **/
        function removeCache($site_srl = 0) {
            $oEditorModel = &getModel('editor');
            FileHandler::removeFile($oEditorModel->getCacheFile(true, $site_srl));
            FileHandler::removeFile($oEditorModel->getCacheFile(false, $site_srl));
        }
    }
?>
