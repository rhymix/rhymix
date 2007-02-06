<?php
  /**
   * @file   : modules/rss/rss.module.php
   * @author : zero <zero@nzeo.com>
   * @desc   : 기본 모듈중의 하나인 rss module
   *           RSS 2.0형식으로 문서 출력
   **/

  class rss extends Module {

    /**
     * 모듈의 정보
     **/
    var $cur_version = "20070130_0.01";

    /**
     * 기본 action 지정
     * $act값이 없거나 잘못된 값이 들어올 경우 $default_act 값으로 진행
     **/
    var $default_act = '';

    /**
     * 현재 모듈의 초기화를 위한 작업을 지정해 놓은 method
     * css/js파일의 load라든지 lang파일 load등을 미리 선언
     *
     * Init() => 공통 
     * dispInit() => disp시에
     * procInit() => proc시에
     *
     * $this->module_path는 현재 이 모듈파일의 위치를 나타낸다
     * (ex: $this->module_path = "./modules/system_install/";
     **/

    // 초기화
    function init() {/*{{{*/
    }/*}}}*/

    // disp 초기화
    function dispInit() {/*{{{*/
    }/*}}}*/
    
    // proc 초기화
    function procInit() {/*{{{*/
    }/*}}}*/

    /**
     * 여기서부터는 action의 구현
     * request parameter의 경우 각 method의 첫번째 인자로 넘어온다
     *
     * dispXXXX : 출력을 위한 method, output에 tpl file이 지정되어야 한다
     * procXXXX : 처리를 위한 method, output에는 error, message가 지정되어야 한다
     **/

    // 출력 부분

    // 실행 부분

    /**
     * 여기부터는 이 모듈과 관련된 라이브러리 개념의 method들
     **/
    function printRssDocument($info, $content) { /*{{{*/
      header("Content-Type: text/xml; charset=UTF-8");
      header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
      header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
      header("Cache-Control: no-store, no-cache, must-revalidate");
      header("Cache-Control: post-check=0, pre-check=0", false);
      header("Pragma: no-cache");
      print '<?xml version="1.0" encoding="utf-8" ?>'."\n";
      print "<!-- // RSS2.0 -->\n";
?><rss version="2.0" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:taxo="http://purl.org/rss/1.0/modules/taxonomy/">
<channel>
  <title><![CDATA[<?=$info->title?>]]></title>
  <link><![CDATA[<?=$info->link?>]]></link>
  <description><![CDATA[<?=$info->description?>]]></description>
  <language><?=$info->language?></language>
  <pubDate><?=$info->date?></pubDate>
<?
  if(count($content)) {
    foreach($content as $item) {
      $year = substr($item->regdate,0,4);
      $month = substr($item->regdate,4,2);
      $day = substr($item->regdate,6,2);
      $hour = substr($item->regdate,8,2);
      $min = substr($item->regdate,10,2);
      $sec = substr($item->regdate,12,2);
      $time = mktime($hour,$min,$sec,$month,$day,$year);

      $title = $item->title;
      $author = $item->user_name;
      $link = sprintf("%s?document_srl=%d", Context::getRequestUri(), $item->document_srl);
      $description = $item->content;
      $date = gmdate("D, d M Y H:i:s", $time);
?>
  <item>
    <title><![CDATA[<?=$title?>]]></title>
    <author><![CDATA[<?=$author?>]]></author>
    <link><![CDATA[<?=$link?>]]></link>
    <description><![CDATA[<?=$description?>]]></description>
    <pubDate><?=$date?></pubDate>
  </item>
<?
    }
  }
?>
</channel>
</rss>
      <?
    }/*}}}*/
  }

?>
