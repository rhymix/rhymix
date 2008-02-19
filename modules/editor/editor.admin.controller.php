<?php
    /**
     * @class  editorAdminController
     * @author zero (zero@nzeo.com)
     * @brief  editor 모듈의 admin controller class
     **/

    class editorAdminController extends editor {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 컴포넌트의 활성화
         **/
        function procEditorAdminEnableComponent() {
            $args->component_name = Context::get('component_name');
            $args->enabled = 'Y';
            $output = executeQuery('editor.updateComponent', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_updated');
        }

        /**
         * @brief 컴포넌트의 비활성화
         **/
        function procEditorAdminDisableComponent() {
            $args->component_name = Context::get('component_name');
            $args->enabled = 'N';
            $output = executeQuery('editor.updateComponent', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_updated');
        }

        /**
         * @brief 컴포넌트의 위치 변경
         **/
        function procEditorAdminMoveListOrder() {
            $args->component_name = Context::get('component_name');
            $mode = Context::get('mode');

            // DB에서 전체 목록 가져옴
            $output = executeQuery('editor.getComponentList', $args);
            $db_list = $output->data;
            foreach($db_list as $key => $val) {
                if($val->component_name == $args->component_name) break;
            }

            if($mode=="up") {
                if($key == 2) return new Object(-1,'msg_component_is_first_order');

                $prev_args->component_name = $db_list[$key-1]->component_name;
                $prev_args->list_order = $db_list[$key]->list_order;
                executeQuery('editor.updateComponent', $prev_args);

                $cur_args->component_name = $db_list[$key]->component_name;
                $cur_args->list_order = $db_list[$key-1]->list_order;
                executeQuery('editor.updateComponent', $cur_args);
            } else {
                if($key == count($db_list)-1) return new Object(-1,'msg_component_is_last_order');

                $next_args->component_name = $db_list[$key+1]->component_name;
                $next_args->list_order = $db_list[$key]->list_order;
                executeQuery('editor.updateComponent', $next_args);

                $cur_args->component_name = $db_list[$key]->component_name;
                $cur_args->list_order = $db_list[$key+1]->list_order;
                executeQuery('editor.updateComponent', $cur_args);
            }

            $this->setMessage('success_updated');
        }

        /**
         * @brief 컴포넌트 설정
         **/
        function procEditorAdminSetupComponent() {
            $component_name = Context::get('component_name');
            $extra_vars = Context::getRequestVars();
            unset($extra_vars->component_name);
            unset($extra_vars->module);
            unset($extra_vars->act);
            unset($extra_vars->body);

            if($extra_vars->target_group) $extra_vars->target_group = explode('|@|', $extra_vars->target_group);

            $args->component_name = $component_name;
            $args->extra_vars = serialize($extra_vars);

            $output = executeQuery('editor.updateComponent', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_updated');
        }

        /**
         * @brief 에디터의 모듈별 추가 확장 폼을 저장
         **/
        function procEditorAdminInsertModuleConfig() {
            // 기존 설정을 가져옴 
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('editor');

            // 대상을 구함
            $module_srl = Context::get('target_module_srl');

            // 여러개의 모듈 일괄 설정일 경우
            if(preg_match('/^([0-9,]+)$/',$module_srl)) $module_srl = explode(',',$module_srl);
            else $module_srl = array($module_srl);

            $editor_config = null;

            $editor_config->editor_skin = Context::get('editor_skin');
            $editor_config->comment_editor_skin = Context::get('comment_editor_skin');

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

            $editor_config->enable_height_resizable = Context::get('enable_height_resizable');

            $editor_config->enable_comment_height_resizable = Context::get('enable_comment_height_resizable');

            $editor_config->enable_autosave = Context::get('enable_autosave');

            if($editor_config->enable_height_resizable != 'Y') $editor_config->enable_height_resizable = 'N';
            if($editor_config->enable_comment_height_resizable != 'Y') $editor_config->enable_comment_height_resizable = 'N';
            if($editor_config->enable_autosave != 'Y') $editor_config->enable_autosave = 'N';

            for($i=0;$i<count($module_srl);$i++) {
                $srl = trim($module_srl[$i]);
                if(!$srl) continue;
                $config->module_config[$srl] = $editor_config;
            }

            // module Controller 객체 생성하여 입력
            $oModuleController = &getController('module');
            $output = $oModuleController->insertModuleConfig('editor',$config);

            $this->setError(-1);
            $this->setMessage('success_updated');
        }

        /**
         * @brief 컴포넌트를 DB에 추가
         **/
        function insertComponent($component_name, $enabled = false) {
            if($enabled) $enabled = 'Y';
            else $enabled = 'N';

            $args->component_name = $component_name;
            $args->enabled = $enabled;

            // 컴포넌트가 있는지 확인
            $output = executeQuery('editor.isComponentInserted', $args);
            if($output->data->count) return new Object(-1, 'msg_component_is_not_founded');

            // 입력
            $args->list_order = getNextSequence();
            $output = executeQuery('editor.insertComponent', $args);
            return $output;
        }
    }
?>
