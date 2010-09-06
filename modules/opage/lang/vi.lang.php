<?php
/*			░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░
			░░  * @File   :  common/lang/vi.lang.php                                              ░░
			░░  * @Author :  NHN (developers@xpressengine.com)                                                 ░░
			░░  * @Trans  :  Đào Đức Duy (ducduy.dao.vn@vietxe.net)								  ░░
			░░	* @Website:  http://vietxe.net													  ░░
			░░  * @Brief  :  Vietnamese Language Pack (Only basic words are included here)        ░░
			░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░░	   		*/

    $lang->opage = "Trang ngoài";
    $lang->opage_path = "Đường dẫn thư mục";
    $lang->opage_caching_interval = "Thời gian lưu trữ";

    $lang->about_opage = "Module này tạo ra một trang từ bên ngoài tại XE thông qua File HTML hoặc PHP.<br />Nó cho phép đường dẫn tuyệt đối hay tương đối, nếu bắt đầu bằng 'http://' , nó sẽ hiển thị một trang từ bên ngoài Server.";
    $lang->about_opage_path= "Xin hãy nhập đường dẫn của thư mục.<br />Có thể sử dụng đường dẫn tuyệt đối dạng '/path1/path2/sample.php' hay tương đối dạng '../path2/sample.php'.<br />Nếu nhập đường dẫn dạng 'http://url/sample.php', nó sẽ nhận và hiển thị nội dung của File đó.<br />Đây là đường dẫn tuyệt đối thư mục cài đặt XE.<br />";
    $lang->about_opage_caching_interval = "Đơn vị được tính bằng phút, nó sẽ là thời gian lưu trữ tạm thời.<br />Đó là khuyến cáo thời gian lưu trữ tạm thời thích hợp khi cần để hiển thị.<br />Nhập 0 nếu không sử dụng tính năng này.";
	$lang->opage_mobile_path = 'Location of External Document for Mobile View';
    $lang->about_opage_mobile_path= "Please input the location of external document for mobile view. If not inputted, it uses the the external document specified above.<br />Both absolute path such as '/path1/path2/sample.php' or relative path such as '../path2/sample.php' can be used.<br />If you input the path like 'http://url/sample.php' , the result will be received and then displayed.<br />This is current XE's absolute path.<br />";
?>
