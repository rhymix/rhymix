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
        function doMoveListOrder() {
            $args->component_name = Context::get('component_name');
            $args->mode = Context::get('mode');

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
