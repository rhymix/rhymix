<?php
    /**
     * @file   modules/page/lang/en.lang.php
     * @author NHN (developers@xpressengine.com)
     * @brief  page module / basic language pack
     **/

    $lang->page = "Sayfa";
    $lang->about_page = "Eksiksiz sayfa oluşturabileceğiniz bir blog modülüdür.\nEnson veya diğer widgetları kullanarak, dinamik sayfa oluşturabilirsiniz. Editör bileşeni yoluyla, çok farklı sayıda sayfalar oluşturabilirsiniz.\nURL\'si diğer modülleriyle aynıdır(mid=module name).\n Varsayılan olarak seçilirse, sitenin anasayfası olacaktır.";
    $lang->cmd_page_modify = "Değiştir";
    $lang->cmd_page_create = 'Sayfa Oluştur';
    $lang->page_caching_interval = "Önbelleğe Alma Zamanı";
    $lang->about_page_caching_interval = "Birim dakikadır ve belirlenen zaman için geçici olarak kaydedilmiş veriyi gösterir.<br />Diğer sunucuların veri veya bilgileri gösterilirken, eğer fazla sayıda kaynağın gösterilmesi gerekiyorsa, uygun zamanın önbelleğe alınması önerilmiştir.<br />0 değeri verilirse, önbelleğe alınmayacaktır.";
	$lang->about_mcontent = 'Bu sayfa, sizin hareketli cihazlardan göreceğiniz sayfadır. Eğer bu sayfayı ayarlamadıysanız, düzenlenmiş varsayılan sayfa gösterilecektir.';
	$lang->page_management = 'Sayfa Yönetimi';

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
