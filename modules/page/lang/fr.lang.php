<?php
    /**
     * @file   modules/page/lang/fr.lang.php
     * @author NHN (developers@xpressengine.com) Traduit par Pierre Duvent (PierreDuvent@gmail.com)
     * @brief  Paquet du langage en français pour le module de Page
     **/

    $lang->page = "Page";
    $lang->about_page = "C'est un module qui peut créer une page complet.\nVous pouvez créer une page dynamique en utilisant des gadgets des Documents derniers ou d'autres. Vous pouvez aussi créer une page avec variété par le composant d'editeur.\nL'URL d'accès est égal celui d'autre module comme mid=module.\nSi c'est choisi par défaut, ce sera la première page du site.";
    $lang->cmd_page_modify = "Modifier";
    $lang->cmd_page_create = '페이지 생성';
    $lang->page_caching_interval = "Temps de antémémoire";
    $lang->about_page_caching_interval = "L'unité est minute, et ça exposera des données conservées temporairement pendant le temps assigné.<br />Il est recommandé d'utiliser l'antémémoire pendant le temps convenable si beaucoup de ressource est nécessaire pour représenter les données ou l'information d'autre serveur.<br />La valeur 0 signifie de ne pas utiliser antémémoire.";
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
