<?php
    /**
     * @class  rssView
     * @author zero (zero@nzeo.com)
     * @brief  rss module의 view class
     * @todo   다양한 형식의 format 및 제어 기능 추가
     *
     * RSS 2.0형식으로 문서 출력
     *
     **/

    class rssView extends rss {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        function dispRss($info, $content, $type="rss2.0") { 
            switch($type) {
                case "rss2.0" :
                        $this->dispRss20($info, $content);
                    break;
            }

            exit();
        }

        /**
         * @brief content를 받아서 rss 형식으로 출력
         **/
        function dispRss20($info, $content) {
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
        }
    }
?>
