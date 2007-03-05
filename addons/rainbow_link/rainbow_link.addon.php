<?php
    if(!__ZB5__) exit();

    /**
    * @file spamfilter.addon.php
    * @author zero (zero@nzeo.com)
    * @brief Rainbow link addon
    *
    * 링크가 걸린 텍스트에 마우스 오버를 하면 무지개색으로 변하게 하는 애드온입니다.
    * rainbow.js는 http://www.dynamicdrive.com에서 제작하였으며 저작권을 가지고 있습니다.
    **/

    // point가 before일때만 실행
    if($this->point != 'before') return;

    // 현재 애드온의 위치를 구함
    $oAddOnModel = &getModel('addon');
    $path = $oAddOnModel->getAddonPath('rainbow_link');

    // Context::addJsFile()을 이용하면 끝
    Context::addJsFile($path.'js/rainbow.js');
?>
