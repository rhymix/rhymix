<?php
    /**
     * @class  trackbackView
     * @author zero (zero@nzeo.com)
     * @brief  trackback모듈의 View class
     **/

    class trackbackView extends trackback {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 메세지 출력
         * 메세지를 출력하고 그냥 종료 시켜 버림
         **/
        function dispMessage($error, $message) {
            // 헤더 출력
            header("Content-Type: text/xml; charset=UTF-8");
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            header("Cache-Control: no-store, no-cache, must-revalidate");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");
            print '<?xml version="1.0" encoding="utf-8" ?>'."\n";
            print "<response>\n<error>{$error}</error><message>{$message}</message></response>";
            exit();
        }

    }
?>
