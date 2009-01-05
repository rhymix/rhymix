<?php
    /**
     * @class  ldapAdminController
     * @author zero (zero@nzeo.com)
     * @brief  ldap 모듈의 admin controller class
     **/

    class ldapAdminController extends ldap {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief LDAP 인증 연동  설정
         **/
        function procLdapAdminInsertConfig() {
            // 기본 정보를 받음
            $args = Context::gets('use_ldap','ldap_server','ldap_port','ldap_userdn_prefix', 'ldap_userdn_suffix','ldap_basedn','ldap_email_entry','ldap_nickname_entry','ldap_username_entry','ldap_group_entry');
            if($args->use_ldap !='Y') $args->use_ldap = 'N';
            if(!$args->ldap_port) $args->ldap_port = 389;

            // module Controller 객체 생성하여 입력
            $oModuleController = &getController('module');
            $output = $oModuleController->insertModuleConfig('ldap',$args);
            return $output;
        }
    }
?>
