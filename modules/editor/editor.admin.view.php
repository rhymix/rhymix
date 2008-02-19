<?php
    /**
     * @class  editorAdminView
     * @author zero (zero@nzeo.com)
     * @brief  editor 모듈의 admin view 클래스
     **/

    class editorAdminView extends editor {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 관리자 설정 페이지
         * 에디터 컴포넌트의 on/off 및 설정을 담당
         **/
        function dispEditorAdminIndex() {
            // 컴포넌트의 종류를 구해옴
            $oEditorModel = &getModel('editor');
            $component_list = $oEditorModel->getComponentList(false);

            Context::set('component_list', $component_list);

            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('admin_index');
        }

        /**
         * @brief 컴퍼넌트 setup
         **/
        function dispEditorAdminSetupComponent() {
            $component_name = Context::get('component_name');

            // 에디터 컴포넌트의 정보를 구함
            $oEditorModel = &getModel('editor');
            $component = $oEditorModel->getComponent($component_name);
            Context::set('component', $component);

            // 그룹 설정을 위한 그룹 목록을 구함
            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups();
            Context::set('group_list', $group_list);

            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('setup_component');
            $this->setLayoutFile("popup_layout");
        }

    }
?>
