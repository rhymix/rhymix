<?php
    /**
     * @file   modules/ldap/lang/ko.lang.php
     * @author zero <zero@nzeo.com>
     * @brief  한국어 언어팩 (기본적인 내용만 수록)
     **/

    $lang->ldap = 'LDAP 인증 연동';
    $lang->use_ldap = 'LDAP 인증 사용';
    $lang->ldap_server = 'LDAP 서버 주소';
    $lang->ldap_port = 'LDAP 서버 포트번호';
    $lang->ldap_userdn_suffix = '사용자 접미사';
    $lang->ldap_basedn = 'base DN';

    $lang->ldap_email_entry = 'Email 대상 컬럼';
    $lang->ldap_nickname_entry = '닉네임 대상 컬럼';
    $lang->ldap_username_entry = '사용자 이름 대상 컬럼';
    $lang->ldap_group_entry = '그룹 대상 컬럼';

    $lang->about_use_ldap = 'LDAP 인증 연동을 위해서는 서버 정보등을 입력 후 사용에 체크를 하셔야 합니다';
    $lang->about_ldap_server = '인증과 정보를 요청할 수 있는 LDAP 서버 정보를 입력해주세요'; 
    $lang->about_ldap_port = 'LDAP 서버의 port 정보를 입력해주세요';
    $lang->about_ldap_userdn_suffix = '인증을 위한 사용자 접미사를 입력해주세요. (예: @abc.com)';
    $lang->about_ldap_basedn = '디렉토리의 base DN을 입력해주세요. (예: dc=abc,dc=com)';

    $lang->about_ldap_email_entry = 'LDAP정보중 이메일 정보로 사용할 컬럼명을 입력해주세요. (중복 금지)';
    $lang->about_ldap_username_entry = 'LDAP정보중 사용자 이름 사용할 컬럼명을 입력해주세요. (중복 가능)';
    $lang->about_ldap_nickname_entry = 'LDAP정보중 닉네임으로 사용할 컬럼명을 입력해주세요. (중복 금지)';
    $lang->about_ldap_group_entry = 'LDAP정보중 사용자의 그룹으로 지정될 컬럼명을 입력해주세요.';

?>
