<?php
    /**
     * @class  ldapController
     * @author zero (zero@nzeo.com)
     * @brief  ldap 모듈의 controller class
     **/

    class ldapController extends ldap {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
        /**
         * @brief LDAP 연동하여 정보를 return하는 method
         **/
        function triggerLdapLogin(&$obj) {
            // ldap 관련 설정 정보를 구함
            $oLdapModel = &getModel('ldap');
            $config = $oLdapModel->getConfig();
            if($config->use_ldap != 'Y') return new Object();

            // 사용자 아이디와 비밀번호를 trigger obj 변수에서 구함
            $user_id = $obj->user_id;
            $password = $obj->password;

            // 인증 시도
            $output = $oLdapModel->ldap_conn($user_id, $password, $config->ldap_userdn_prefix, $config->ldap_userdn_suffix, $config->ldap_basedn, $config->ldap_server, $config->ldap_port);

            // 인증 실패시 아무 event없이 그냥 return하여 기존 인증을 계속 유지
            if(!$output->toBool()) return new Object();

            // 설정정보를 바탕으로 기본 정보를 구함
            $ldap_info = $output->getVariables();

            $info->user_id = $user_id;
            $info->password = md5($password);
            $info->email_address = $ldap_info[$config->ldap_email_entry];
            $info->nick_name = $ldap_info[$config->ldap_nickname_entry];
            $info->user_name = $ldap_info[$config->ldap_username_entry];
            list($info->email_id, $info->email_host) = explode('@', $info->email_address);
            $group = $ldap_info[$config->ldap_group_entry];

            // 이미 존재하는 회원인지 확인
            $oMemberModel = &getModel('member');
            $member = $oMemberModel->getMemberInfoByUserID($info->user_id);

            // 이미 존재하면 메일주소/닉네임/사용자이름/그룹중에 변경된 것이 있는지 확인
            if($member->user_id == $info->user_id) {
                $info->member_srl = $member->member_srl;

                if($info->password != $member->password || $info->email_address != $member->email_address || $info->nick_name != $member->nick_name || $info->user_name != $member->user_name) {
                    $output = executeQuery('member.updateMember', $info);
                } else $output = new Object();

            // 존재하지 않으면 회원정보 추가
            } else {
                $info->member_srl = getNextSequence();
                $info->allow_mailing = 'Y';
                $info->allow_message = 'Y';
                $info->denied = 'N';
                $info->is_admin = 'N';

                // 아이디, 닉네임, email address 의 중복 체크
                $member_srl = $oMemberModel->getMemberSrlByNickName($info->nick_name);
                if($member_srl) return new Object(-1,'msg_exists_nick_name');

                $member_srl = $oMemberModel->getMemberSrlByEmailAddress($info->email_address);
                if($member_srl) return new Object(-1,'msg_exists_email_address');

                $output = executeQuery('member.insertMember', $info);
            }

            if(!$output->toBool()) return new Object();

            $group_list = $member->group_list;
            if(!$group_list || !is_array($group_list)) $group_list = array();

            // 수정 또는 입력 결과가 이상없다면 그룹 정보 작업
            if($group && !in_array($group, $group_list)) {
                $group_srl = 0;
                $groups = $oMemberModel->getGroups();
                foreach($groups as $k => $v) {
                    if($v->title == $group) {
                        $group_srl = $v->group_srl;
                        break;
                    }
                }

                // 그룹 추가
                if(!$group_srl) {
                    $oMemberAdminController = &getAdminController('member');
                    $group_srl = $group_args->group_srl = getNextSequence();
                    $group_args->title = $group;
                    $oMemberAdminController->insertGroup($group_args);
                }
                
                // 그룹 설정
                $oMemberController = &getController('member');
                $oMemberController->addMemberToGroup($info->member_srl, $group_srl);
            }

            return new Object();
        }

    }
?>
