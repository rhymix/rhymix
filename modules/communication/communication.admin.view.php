<?php
    /**
     * @class  communicationAdminView
     * @author zero (zero@nzeo.com)
     * @brief  communication module의 admin view class
     **/

    class communicationAdminView extends communication {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 쪽지 및 친구등의 관리를 위한 설정
         **/
        function dispCommunicationAdminConfig() {
            // 객체 생성
            $oEditorModel = &getModel('editor');
            $oModuleModel = &getModel('module');
            $oCommunicationModel = &getModel('communication');

            // communication 모듈의 모듈설정 읽음
            Context::set('communication_config', $oCommunicationModel->getConfig() );

            // 에디터 스킨 목록을 구함
            Context::set('editor_skin_list', $oEditorModel->getEditorSkinList() );

            // 커뮤니케이션 스킨 목록을 구함
            Context::set('communication_skin_list', $oModuleModel->getSkins($this->module_path) );

            // template 지정
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('index');
        }

    }
?>
