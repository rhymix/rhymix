<?php
    /**
     * @class  editorView
     * @author zero (zero@nzeo.com)
     * @brief  editor 모듈의 view 클래스
     **/

    class editorView extends editor {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 에디터를 return
         **/
        function getEditor($upload_target_srl, $allow_fileupload = false) {
            // 업로드를 위한 변수 설정
            Context::set('upload_target_srl', $upload_target_srl);
            Context::set('allow_fileupload', $allow_fileupload);

            // 에디터 컴포넌트를 구함
            if(!Context::get('component_list')) {
                $component_list = FileHandler::readDir($this->module_path.'components');
                arsort($component_list);
                Context::set('component_list', $component_list);
            }

            // 템플릿을 미리 컴파일해서 컴파일된 소스를 return
            $tpl_path = $this->module_path.'tpl';
            $tpl_file = 'editor.html';

            // editor_path를 지정
            Context::set('editor_path', $tpl_path);

            require_once("./classes/template/TemplateHandler.class.php");
            $oTemplate = new TemplateHandler();
            return $oTemplate->compile($tpl_path, $tpl_file);
        }

        /**
         * @brief 컴포넌트의 팝업 출력을 요청을 받는 action
         **/
        function dispPopup() {
            // 변수 정리
            $upload_target_srl = Context::get('upload_target_srl');
            $component = Context::get('component');

            // component 객체를 받음
            $oComponent = &$this->getComponentObject($component, $upload_target_srl);
            if(!$oComponent->toBool()) {
                Context::set('message', sprintf(Context::getLang('msg_component_is_not_founded'), $component));
                $this->setTemplatePath($this->module_path.'tpl');
                $this->setTemplateFile('component_not_founded');
            } else {

                // 컴포넌트의 popup url을 출력하는 method실행후 결과를 받음
                $popup_content = $oComponent->getPopupContent();
                Context::set('popup_content', $popup_content);

                // 레이아웃을 popup_layout으로 설정
                $this->setLayoutFile('popup_layout');

                // 템플릿 지정
                $this->setTemplatePath($this->module_path.'tpl');
                $this->setTemplateFile('popup');
            }
        }
    }
?>
