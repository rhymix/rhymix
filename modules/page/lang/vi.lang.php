<?php
/*			░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░
			░░  * @File   :  common/lang/vi.lang.php                                              ░░
			░░  * @Author :  NHN (developers@xpressengine.com)                                                 ░░
			░░  * @Trans  :  Đào Đức Duy (ducduy.dao.vn@vietxe.net)								  ░░
			░░	* @Website:  http://vietxe.net													  ░░
			░░  * @Brief  :  Vietnamese Language Pack (Only basic words are included here)        ░░
			░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░	   		*/

    $lang->page = "Trang";
    $lang->about_page = "Là Module tạo ra một trang đầy đủ.\nSử dụng Widget mới nhất hoặc những Widget khác giúp bạn có thể tạo một trang động. Thông qua trình bên tập Component, bạn có thể tạo ra sự phong phú cho những trang khác nhau.\nĐịa chỉ của nó như một Module dạng '<b>mid=module name</b>'.\nNếu chọn là mặc định, nó sẽ là trang chủ của Website.";
    $lang->cmd_page_modify = "Sửa đổi";
    $lang->cmd_page_create = '페이지 생성';
    $lang->page_caching_interval = "Thời gian lưu trữ";
    $lang->about_page_caching_interval = "Đơn vị được tính bằng phút, nó sẽ là thời gian lưu trữ tạm thời.<br />Đó là khuyến cáo thời gian lưu trữ tạm thời thích hợp khi cần để hiển thị.<br />Nhập 0 nếu không sử dụng tính năng này.";
	$lang->about_mcontent = 'This is the page for the mobile view. If you do not write this page, the mobile view display reoragnized PC view\'s page.';
	$lang->page_management = '페이지 관리';

	/* add merge opage + page type and article create */
	$lang->page_type = '페이지 타입';
	$lang->click_choice = '선택해 주세요.';
	$lang->page_type_name = array('WIDGET' => '위젯'
								 ,'ARTICLE' => 'Article'
								 ,'OUTSIDE' => 'External Page');
	$lang->about_page_type = '페이지 타입을 선택하여 원하는 화면을 구성할 수 있습니다. <ol><li>위젯형 : 여러가지 위젯들을 생성하여 화면을 구성합니다.</li><li>문서형 : 제목, 내용, 태그를 갖는 문서를 제작하여 포스팅 형식의 페이지를 작성합니다. </li><li>외부페이지형 : 외부HTML또는 PHP 파일을 XE에서 사용할 수 있습니다.</li></ol>';

    $lang->opage_path = "Location of External Document";
    $lang->about_opage = "This module enables to use external html or php files in XE.<br />It allows absolute or relative path, and if the url starts with 'http://' , it can display the external page of the server.";
    $lang->about_opage_path= "Please input the location of external document.<br />Both absolute path such as '/path1/path2/sample.php' or relative path such as '../path2/sample.php' can be used.<br />If you input the path like 'http://url/sample.php', the result will be received and then displayed.<br />This is current XE's absolute path.<br />";
	$lang->opage_mobile_path = 'Location of External Document for Mobile View';
    $lang->about_opage_mobile_path= "Please input the location of external document for mobile view. If not inputted, it uses the external document specified above.<br />Both absolute path such as '/path1/path2/sample.php' or relative path such as '../path2/sample.php' can be used.<br />If you input the path like 'http://url/sample.php', the result will be received and then displayed.<br />This is current XE's absolute path.<br />";
?>
