<?php
    /**
     * @file   modules/opage/lang/ko.lang.php
     * @author NHN (developers@xpressengine.com)
     * @brief External page (opage) basic language of the module
     **/
    $lang->opage = '외부 페이지';
    $lang->opage_path = '외부 문서 위치';
    $lang->opage_caching_interval = '캐싱 시간 설정';
    // If you start with the pages can be displayed outside of the server ';
    $lang->about_opage = 'XE가 아닌 외부 HTML 또는 PHP파일을 XE에서 사용할 수 있도록 하는 모듈입니다.<br />절대경로, 상대경로를 이용할 수 있으며 http:
    // url/sample.php as you receive output that will be sent to the Web page. <br /> XE is installed, the current absolute path as follows. <br /> ';
    $lang->about_opage_path= '외부문서의 위치를 입력해주세요.<br />/path1/path2/sample.php 와 같이 절대경로나 ../path2/sample.php와 같은 상대경로 모두 사용가능합니다.<br />http:
    $lang->about_opage_caching_interval = '분 단위이며 정해진 시간동안은 임시 저장한 데이터를 출력합니다.<br />다른 서버의 정보를 출력하거나, 데이터 출력하는데 많은 자원이 필요한 경우, 원하시는 분 단위 시간 간격으로 캐싱하는 것을 추천합니다.<br />0 으로 하시면 캐싱을 하지 않습니다.';
	$lang->opage_mobile_path = '모바일용 외부 문서 위치';
    // url/sample.php as you receive output that will be sent to the Web page. <br /> XE is installed, the current absolute path as follows. <br /> ';
    $lang->about_opage_mobile_path= '모바일용 외부문서의 위치를 입력해주세요. 입력하지 않으면 위에서 지정한 외부문서 위치의 페이지를 이용합니다. <br />/path1/path2/sample.php 와 같이 절대경로나 ../path2/sample.php와 같은 상대경로 모두 사용가능합니다.<br />http:
?>
