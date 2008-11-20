<?php
    /**
     * @file   ko.lang.php
     * @author zero (zero@nzeo.com)
     * @brief  외부 페이지(opage) 모듈의 기본 언어팩
     **/

    $lang->opage = "외부 페이지";
    $lang->opage_path = "외부 문서 위치";
    $lang->opage_caching_interval = "캐싱 시간 설정";

    $lang->about_opage = "XE가 아닌 외부 html 또는 php파일을 XE에서 사용할 수 있도록 하는 모듈입니다.<br />절대경로, 상대경로를 이용할 수 있으며 http:// 로 시작할 경우 서버 외부의 페이지도 표시할 수 있습니다";
    $lang->about_opage_path= "외부문서의 위치를 입력해주세요.<br />/path1/path2/sample.php 와 같이 절대경로나 ../path2/sample.php와 같은 상대경로 모두 사용가능합니다.<br />http://url/sample.php 와 같이 사용하면 결과를 받아다가 출력을 하게 됩니다.<br />현재 XE가 설치된 절대경로는 다음과 같습니다.<br />";
    $lang->about_opage_caching_interval = "단위는 분이며 정해진 시간동안은 임시 저장한 데이터를 출력하도록 합니다.<br />다른 서버의 정보를 출력하거나 데이터 출력시 많은 자원이 필요하면 원하시는 분 단위로 캐싱하시는 것을 추천해드립니다.<br />0 으로 하시면 캐싱을 하지 않습니다";
?>
