<?php
    /**
     * @class  ldapModel
     * @author zero (zero@nzeo.com)
     * @brief  ldap 모듈의 Model class
     **/

    class ldapModel extends ldap {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief LDAP 설정 정보 return
         **/
        function getConfig() {
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('ldap');
            if(!$config->ldap_port) $config->ldap_port = 389;
            if(!$config->ldap_email_entry) $config->ldap_email_entry = 'mail';
            if(!$config->ldap_nickname_entry) $config->ldap_nickname_entry = 'displayname';
            if(!$config->ldap_username_entry) $config->ldap_username_entry = 'description';
            if(!$config->ldap_group_entry) $config->ldap_group_entry = 'department';
            return $config;
        }


        /**
         * @brief LDAP 연동하여 정보를 return하는 method
         **/
        function ldap_conn($user_id, $password, $ldap_userdn_prefix, $ldap_userdn_suffix, $base_dn, $ldap_server, $ldap_port = 389) {
            if(!function_exists('ldap_connect')) return new Object(-1,'ldap module is not exists');

            $ds = @ldap_connect($ldap_server, $ldap_port);
            if(!$ds) return new Object(-1,'server not connected');

            if(!ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3)) return new Object(-1,'fail to set option');

            $userdn = $ldap_userdn_prefix.$user_id.$ldap_userdn_suffix;
            if(!@ldap_bind($ds, $userdn, $password)) return new Object(-1,'fail to bind');

            $ldap_sr = @ldap_search($ds, $base_dn, '(cn='.$user_id.')', array ('*'));
            if(!$ldap_sr) return new Object(-1,'fail to search');

            $info = ldap_get_entries($ds, $ldap_sr);

            if($info['count']<1 || !is_array($info) || !count($info[0]) ) return new Object(-1,'not found');


            $obj = new Object();
            foreach($info[0] as $key => $val) {
                if(preg_match('/^[0-9]*$/',$key) || $key == 'objectclass') continue;

                $obj->add($key, $val[0]);
            }

            return $obj;
        }

    }
?>
