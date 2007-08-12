<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file openid_delegation_id.addon.php
     * @author zero (zero@nzeo.com)
     * @brief OpenID Delegation ID 애드온
     *
     * 오픈아이디를 자신의 홈페이지나 블로그 주소로 이용할 수 있도록 해줍니다.
     * 꼭 설정을 통해서 사용하시는 오픈아이디 서비스에 해당하는 정보를 입력해주세요.
     **/

    // called_position이 before_module_init일때만 실행
    if($called_position != 'before_module_init') return;

    // openid_delegation_id 애드온 설정 정보를 가져옴
    if(!$addon_info->server||!$addon_info->delegate||!$addon_info->xrds) return;

    $header_script = sprintf(
        '<link rel="openid.server" href="%s" />'."\n".
        '<link rel="openid.delegate" href="%s" />'."\n".
        '<meta http-equiv="X-XRDS-Location" content="%s" />',
        $addon_info->server,
        $addon_info->delegate,
        $addon_info->xrds
    );

    Context::addHtmlHeader($header_script);
?>
