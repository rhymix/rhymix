<?php
    /**
     * @file   modules/page/lang/jp.lang.php
     * @author NHN (developers@xpressengine.com) 翻訳：RisaPapa、ミニミ
     * @brief  ページ（page）モジュールの基本言語パッケージ
     **/

    $lang->page = 'ページ';
    $lang->about_page = "一枚のページを作成出来るモジュールです。\n最新書き込みウィジェットや他のウィジェットを用いて動的なページが作成が出来、さらにエディターのコンポネントで様々なデザインも出来ます。\n接続URLは、他のモジュールと同様に、「mid=モジュール名」でアクセスし、デフォルトとして指定するとサイトにアクセスする際、メインページとして使われます。";
    $lang->cmd_page_modify = 'ページ修正';
    $lang->cmd_page_create = 'ページ作成';
    $lang->page_caching_interval = 'キャッシング時間設定';
    $lang->about_page_caching_interval = '分単位で指定出来、設定された時間の間は、臨時保存されたデータを出力します。<br />他のサーバの情報を出力したり、データを出力する際、リソースが多く使われるため、数分単位でキャッシングすることをお勧めします。<br />「0」に指定するとキャッシングされません。';
	$lang->about_mcontent = 'モバイルスキン用のページです。作成しないとPC向けのページを再構成して表示します。';
	$lang->page_management = 'ページ管理';

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
