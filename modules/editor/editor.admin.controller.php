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
            $site_module_info = Context::get('site_module_info');

            $args->component_name = Context::get('component_name');
            $args->enabled = 'Y';
            $args->site_srl = (int)$site_module_info->site_srl;
            if(!$args->site_srl) $output = executeQuery('editor.updateComponent', $args);
            else $output = executeQuery('editor.updateSiteComponent', $args);
            if(!$output->toBool()) return $output;

            $oEditorController = &getController('editor');
            $oEditorController->removeCache($args->site_srl);

            $this->setMessage('success_updated');
        }

        /**
         * @brief 컴포넌트의 비활성화
         **/
        function procEditorAdminDisableComponent() {
            $site_module_info = Context::get('site_module_info');

            $args->component_name = Context::get('component_name');
            $args->enabled = 'N';
            $args->site_srl = (int)$site_module_info->site_srl;
            if(!$args->site_srl) $output = executeQuery('editor.updateComponent', $args);
            else $output = executeQuery('editor.updateSiteComponent', $args);
            if(!$output->toBool()) return $output;

            $oEditorController = &getController('editor');
            $oEditorController->removeCache($args->site_srl);

            $this->setMessage('success_updated');
        }

        /**
         * @brief 컴포넌트의 위치 변경
         **/
        function procEditorAdminMoveListOrder() {
            $site_module_info = Context::get('site_module_info');
            $args->site_srl = (int)$site_module_info->site_srl;
            $args->component_name = Context::get('component_name');
            $mode = Context::get('mode');

            // DB에서 전체 목록 가져옴
            if(!$args->site_srl) $output = executeQuery('editor.getComponentList', $args);
            else $output = executeQuery('editor.getSiteComponentList', $args);

            $db_list = $output->data;
            foreach($db_list as $key => $val) {
                if($val->component_name == $args->component_name) break;
            }

            if($mode=="up") {
                if($key == 2) return new Object(-1,'msg_component_is_first_order');

                $prev_args->component_name = $db_list[$key-1]->component_name;
                $prev_args->list_order = $db_list[$key]->list_order;
                $prev_args->site_srl = $args->site_srl;
                if(!$args->site_srl) $output = executeQuery('editor.updateComponent', $prev_args);
                else $output = executeQuery('editor.updateSiteComponent', $prev_args);

                $cur_args->component_name = $db_list[$key]->component_name;
                $cur_args->list_order = $db_list[$key-1]->list_order;
                if($prev_args->list_order == $cur_args->list_order) $cur_args->list_order--;
                $cur_args->site_srl = $args->site_srl;
                if(!$args->site_srl) $output = executeQuery('editor.updateComponent', $cur_args);
                else $output = executeQuery('editor.updateSiteComponent', $cur_args);
            } else {
                if($key == count($db_list)-1) return new Object(-1,'msg_component_is_last_order');

                $next_args->component_name = $db_list[$key+1]->component_name;
                $next_args->list_order = $db_list[$key]->list_order;
                $next_args->site_srl = $args->site_srl;
                if(!$args->site_srl) $output = executeQuery('editor.updateComponent', $next_args);
                else $output = executeQuery('editor.updateSiteComponent', $next_args);

                $cur_args->component_name = $db_list[$key]->component_name;
                $cur_args->list_order = $db_list[$key+1]->list_order;
                $cur_args->site_srl = $args->site_srl;
                if($next_args->list_order == $cur_args->list_order) $cur_args->list_order++;
                if(!$args->site_srl) $output = executeQuery('editor.updateComponent', $cur_args);
                else $output = executeQuery('editor.updateSiteComponent', $cur_args);
            }

            $oEditorController = &getController('editor');
            $oEditorController->removeCache($args->site_srl);

            $this->setMessage('success_updated');
        }

        /**
         * @brief 컴포넌트 설정
         **/
        function procEditorAdminSetupComponent() {
            $site_module_info = Context::get('site_module_info');

            $component_name = Context::get('component_name');
            $extra_vars = Context::getRequestVars();
            unset($extra_vars->component_name);
            unset($extra_vars->module);
            unset($extra_vars->act);
            unset($extra_vars->body);

            if($extra_vars->target_group) $extra_vars->target_group = explode('|@|', $extra_vars->target_group);
            if($extra_vars->mid_list) $extra_vars->mid_list = explode('|@|', $extra_vars->mid_list);

            $args->component_name = $component_name;
            $args->extra_vars = serialize($extra_vars);
            $args->site_srl = (int)$site_module_info->site_srl;

            if(!$args->site_srl) $output = executeQuery('editor.updateComponent', $args);
            else $output = executeQuery('editor.updateSiteComponent', $args);
            if(!$output->toBool()) return $output;

            $oEditorController = &getController('editor');
            $oEditorController->removeCache($args->site_srl);

            $this->setMessage('success_updated');
        }

        /**
         * @brief 컴포넌트를 DB에 추가
         **/
        function insertComponent($component_name, $enabled = false, $site_srl = 0) {
            if($enabled) $enabled = 'Y';
            else $enabled = 'N';

            $args->component_name = $component_name;
            $args->enabled = $enabled;
            $args->site_srl = $site_srl;

            // 컴포넌트가 있는지 확인
            if(!$site_srl) $output = executeQuery('editor.isComponentInserted', $args);
            else $output = executeQuery('editor.isSiteComponentInserted', $args);
            if($output->data->count) return new Object(-1, 'msg_component_is_not_founded');

            // 입력
            $args->list_order = getNextSequence();
            if(!$site_srl) $output = executeQuery('editor.insertComponent', $args);
            else $output = executeQuery('editor.insertSiteComponent', $args);

            $oEditorController = &getController('editor');
            $oEditorController->removeCache($site_srl);
            return $output;
        }
    }
?>
