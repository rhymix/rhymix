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

            if(Context::get('is_logged')) {
                $logged_info = Context::get('logged_info');
                $args->member_srl = $logged_info->member_srl;
            } else {
                $args->ipaddress = $_SERVER['REMOTE_ADDR'];
            }

            // 필요한 데이터가 없으면 pass
            if(!$args->document_srl || (!$args->title && !$args->content)) return new Object(0,'');

            // 저장
            $output = executeQuery('editor.insertSavedDoc', $args);

            $this->setMessage('msg_auto_saved');
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

            $output = call_user_method($method, $oComponent);
            if((is_a($output, 'Object') || is_subclass_of($output, 'Object')) && !$output->toBool()) return $output;

            $this->setError($oComponent->getError());
            $this->setMessage($oComponent->getMessage());

            $vars = $oComponent->getVariables();
            if(count($vars)) {
                foreach($vars as $key=>$val) $this->add($key, $val);
            }
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
            executeQuery('editor.deleteSavedDoc', $args);
        }
    }
?>
