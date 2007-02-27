<?php
    /**
     * @class  krzip
     * @author zero (zero@nzeo.com)
     * @brief  우편번호 검색 모듈인 krzip의 상위 클래스
     **/

    class krzip extends ModuleObject {

        var $hostname = 'kr.zip.zeroboard.com';
        var $port = 80;
        var $query = '/server.php?addr=%s";

    }
?>
