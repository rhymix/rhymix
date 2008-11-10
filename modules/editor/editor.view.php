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
         * @brief 컴포넌트의 팝업 출력을 요청을 받는 action
         **/
        function dispEditorPopup() {
            // css 파일 추가
            Context::addCssFile($this->module_path."tpl/css/editor.css");

            // 변수 정리
            $editor_sequence = Context::get('editor_sequence ');
            $component = Context::get('component');

            // component 객체를 받음
            $oEditorModel = &getModel('editor');
            $oComponent = &$oEditorModel->getComponentObject($component, $editor_sequence);
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

        /**
         * @brief 컴퍼넌트 정보 보기 
         **/
        function dispEditorComponentInfo() {
            $component_name = Context::get('component_name');

            $oEditorModel = &getModel('editor');
            $component = $oEditorModel->getComponent($component_name);
            Context::set('component', $component);

            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('view_component');
            $this->setLayoutFile("popup_layout");
        }

        /**
         * @brief 모듈의 추가 설정에서 에디터 설정을 하는 form 추가
         **/
        function triggerDispEditorAdditionSetup(&$obj) {
            $current_module_srl = Context::get('module_srl');
            $current_module_srls = Context::get('module_srls');

            if(!$current_module_srl && !$current_module_srls) {
                // 선택된 모듈의 정보를 가져옴
                $current_module_info = Context::get('current_module_info');
                $current_module_srl = $current_module_info->module_srl;
                if(!$current_module_srl) return new Object();
            }

            // 에디터 설정을 구함
            $oEditorModel = &getModel('editor');
            $editor_config = $oEditorModel->getEditorConfig($current_module_srl);

            Context::set('editor_config', $editor_config);

            // 에디터 스킨 목록을 구함
            $editor_skin_list = FileHandler::readDir('./modules/editor/skins');
            Context::set('editor_skin_list', $editor_skin_list);
            

            // 그룹 목록을 구함
            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups();
            Context::set('group_list', $group_list);

            // 템플릿 파일 지정
            $oTemplate = &TemplateHandler::getInstance();
            $tpl = $oTemplate->compile($this->module_path.'tpl', 'editor_module_config');
            $obj .= $tpl;

            return new Object();
        }


        function dispEditorPreview(){
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('preview');
        }
    }
?>