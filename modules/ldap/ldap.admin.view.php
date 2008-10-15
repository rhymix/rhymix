<?php
    /**
     * @class  ldapAdminView
     * @author zero (zero@nzeo.com)
     * @brief  ldap 모듈의 admin view class
     **/

    class ldapAdminView extends ldap {

        /**
         * @brief 초기화
         **/
        function init() {
            // 템플릿 경로 지정 
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * @brief 스팸필터의 설정 화면
         **/
        function dispLdapAdminConfig() {
            $oModel = &getModel('ldap');
            Context::set('config',$oModel->getConfig());

            // 템플릿 파일 지정
            $this->setTemplateFile('index');
        }
    }
?>
