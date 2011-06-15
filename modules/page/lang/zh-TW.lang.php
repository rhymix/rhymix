<?php
    /**
     * @file   modules/page/lang/zh-TW.lang.php
     * @author NHN (developers@xpressengine.com) 翻譯：royallin
     * @brief  頁面(page) 模組正體中文語言
     **/

    $lang->page = "頁面";
    $lang->about_page = "可製作完整頁面的模組。\n利用最新主題列表或其他 Widgets 可以建立動態的頁面，且通過網頁編輯器做出多樣化的頁面。\n連結頁面網址和其他模組連結的方式相同。即：mid=模組名稱。選擇預設選項時，此頁面將變為首頁。";
    $lang->cmd_page_modify = "頁面編輯";
    $lang->cmd_page_create = '建立頁面';
    $lang->page_caching_interval = "暫存時間設置";
    $lang->about_page_caching_interval = "單位為分。暫存時間內頁面將輸出臨時儲存的資料。<br />輸出外部主機訊息或資料時，如消耗資源很大，盡量把暫存時間設大一點。<br />『0』表示不暫存。";
	$lang->about_mcontent = '此頁面為手機瀏覽頁面。如果沒有編輯此頁面，則會將預設頁面改編重新顯示。';
	$lang->page_management = '頁面管理';

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
