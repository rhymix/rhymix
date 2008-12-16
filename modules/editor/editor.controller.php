<?php
    /**
     * @class  editor
     * @author zero (zero@nzeo.com)
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

            $this->deleteSavedDoc();

            $args->document_srl = Context::get('document_srl');
            $args->content = Context::get('content');
            $args->title = Context::get('title');
            $output = $this->doSaveDoc($args);

            $this->setMessage('msg_auto_saved');
        }

        function doSaveDoc($args) {

            if(Context::get('is_logged')) {
                $logged_info = Context::get('logged_info');
                $args->member_srl = $logged_info->member_srl;
            } else {
                $args->ipaddress = $_SERVER['REMOTE_ADDR'];
            }

            // 저장
            return executeQuery('editor.insertSavedDoc', $args);
        }

        /**
         * @brief 자동저장된 문서 삭제
         **/
        function procEditorRemoveSavedDoc() {
            $oEditorController = &getController('editor');
            $oEditorController->deleteSavedDoc();
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
         * @brief 게시글의 입력/수정이 일어났을 경우 자동 저장문서를 제거하는 trigger
         **/
        function triggerDeleteSavedDoc(&$obj) {
            $this->deleteSavedDoc();
            return new Object();
        }

        /**
         * @brief 자동 저장된 글을 삭제
         * 현재 접속한 사용자를 기준
         **/
        function deleteSavedDoc() {
            if(Context::get('is_logged')) {
                $logged_info = Context::get('logged_info');
                $args->member_srl = $logged_info->member_srl;
            } else {
                $args->ipaddress = $_SERVER['REMOTE_ADDR'];
            }

            // 일단 이전 저장본 삭제
            return executeQuery('editor.deleteSavedDoc', $args);
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

    }
?>
