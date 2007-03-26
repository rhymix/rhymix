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
         * @brief 컴포넌트를 DB에 추가
         **/
        function insertComponent($component_name, $enabled = false) {
            if($enabled) $enabled = 'Y';
            else $enabled = 'N';

            $oDB = &DB::getInstance();

            $args->component_name = $component_name;
            $args->enabled = $enabled;

            // 컴포넌트가 있는지 확인
            $output = $oDB->executeQuery('editor.isComponentInserted', $args);
            if($output->data->count) return new Object(-1, 'msg_component_is_not_founded');

            // 입력
            $args->list_order = $oDB->getNextSequence();
            $output = $oDB->executeQuery('editor.insertComponent', $args);
            return $output;
        }

        /**
         * @brief 컴포넌트의 활성화
         **/
        function procEnableComponent() {
            $args->component_name = Context::get('component_name');
            $args->enabled = 'Y';

            $oDB = &DB::getInstance();
            $output = $oDB->executeQuery('editor.updateComponent', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_updated');
        }

        /**
         * @brief 컴포넌트의 비활성화
         **/
        function procDisableComponent() {
            $args->component_name = Context::get('component_name');
            $args->enabled = 'N';

            $oDB = &DB::getInstance();
            $output = $oDB->executeQuery('editor.updateComponent', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_updated');
        }

        /**
         * @brief 컴포넌트의 위치 변경
         **/
        function procMoveListOrder() {
            $args->component_name = Context::get('component_name');
            $mode = Context::get('mode');

            // DB에서 전체 목록 가져옴
            $oDB = &DB::getInstance();
            $output = $oDB->executeQuery('editor.getComponentList', $args);
            $db_list = $output->data;
            foreach($db_list as $key => $val) {
                if($val->component_name == $args->component_name) break;
            }

            if($mode=="up") {
                if($key == 2) return new Object(-1,'msg_component_is_first_order');

                $prev_args->component_name = $db_list[$key-1]->component_name;
                $prev_args->list_order = $db_list[$key]->list_order;
                $oDB->executeQuery('editor.updateComponent', $prev_args);

                $cur_args->component_name = $db_list[$key]->component_name;
                $cur_args->list_order = $db_list[$key-1]->list_order;
                $oDB->executeQuery('editor.updateComponent', $cur_args);
            } else {
                if($key == count($db_list)-1) return new Object(-1,'msg_component_is_last_order');

                $next_args->component_name = $db_list[$key+1]->component_name;
                $next_args->list_order = $db_list[$key]->list_order;
                $oDB->executeQuery('editor.updateComponent', $next_args);

                $cur_args->component_name = $db_list[$key]->component_name;
                $cur_args->list_order = $db_list[$key+1]->list_order;
                $oDB->executeQuery('editor.updateComponent', $cur_args);
            }

            $this->setMessage('success_updated');
        }

        /**
         * @brief 컴포넌트에서 ajax요청시 해당 컴포넌트의 method를 실행 
         **/
        function procCall() {
            $component = Context::get('component');
            $method = Context::get('method');
            if(!$component) return new Object(-1, sprintf(Context::getLang('msg_component_is_not_founded'), $component));

            $oEditorModel = &getModel('editor');
            $oComponent = &$oEditorModel->getComponentObject($component);
            if(!$oComponent->toBool()) return $oComponent;

            if(!method_exists($oComponent, $method)) return new Object(-1, sprintf(Context::getLang('msg_component_is_not_founded'), $component));

            $output = call_user_method($method, $oComponent);
            if((is_a($output, 'Object') || is_subclass_of($output, 'Object')) && !$output->toBool()) return $output;

            $this->setError($oComponent->getError());
            $this->setMessage($oComponent->getMessage());

            $vars = $oComponent->getVariables();
            if(count($vars)) {
                foreach($vars as $key=>$val) $this->add($key, $val);
            }
        }
    }
?>
