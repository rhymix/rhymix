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
            if($extra_vars->mid_list) $extra_vars->mid_list = explode('|@|', $extra_vars->mid_list);

            $args->component_name = $component_name;
            $args->extra_vars = serialize($extra_vars);

            $output = executeQuery('editor.updateComponent', $args);
            if(!$output->toBool()) return $output;

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
