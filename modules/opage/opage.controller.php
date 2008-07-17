<?php
    /**
     * @class  documentController
     * @author zero (zero@nzeo.com)
     * @brief  document 모듈의 controller 클래스
     **/

    class opageController extends opage {

        var $target_path = '';

        /**
         * @brief 초기화
         **/
        function init() { }

        /**
         * @brief 타이틀 추출
         **/
        function getTitle($content) {
            preg_match('!<title([^>]*)>(.*?)<\/title>!is', $content, $buff);
            return trim($buff[2]);
        }

        /**
         * @brief header script 추출
         **/
        function getHeadScript($content) {
            // title 태그 제거
            $content = preg_replace('!<title([^>]*)>(.*?)<\/title>!is','', $content);

            // meta 태그 제거
            $content = preg_replace('!<(\/){0,1}meta([^>]*)>!is','', $content);

            // <link, <style, <script 등의 정보를 추출
            preg_match_all('!<link([^>]*)>!is', $content, $link_buff);
            for($i=0;$i<count($link_buff[0]);$i++) {
                $tmp_str = trim($link_buff[0][$i]);
                if(!$tmp_str) continue;
                $header_script .=  $tmp_str."\n";
            }

            preg_match_all('!<(style|script)(.*?)<\/(style|script)>!is', $content, $script_buff);
            for($i=0;$i<count($script_buff[0]);$i++) {
                $tmp_str = trim($script_buff[0][$i]);
                if(!$tmp_str) continue;
                $header_script .=  $tmp_str."\n";
            }

            return $header_script;
        }

        /**
         * @brief body의 내용을 추출
         **/
        function getBodyScript($content) {
            // 내용 추출
            preg_match('!<body([^>]*)>(.*?)<\/body>!is', $content, $body_buff);
            $body_script = $body_buff[2];

            // link, style, script등 제거
            $body_script = preg_replace('!<link([^>]*)>!is', '', $body_script);
            $body_script = preg_replace('!<(style|script)(.*?)<\/(style|script)>!is', '', $body_script);
            return $body_script;
        }

        /**
         * @brief 내용에 포함된 src, href의 값을 변경
         **/
        function replaceSrc($content, $path) {
            if(substr($path,-1)!='/') $path.='/';
            $this->target_path = $path;

            // element의 속성중 value에 " 로 안 묶여 있는 것을 검사하여 묶어줌
            $content = preg_replace_callback('/([^=^"^ ]*)=([^ ^>]*)/i', fixQuotation, $content);

            // img, input, a, link등의 href, src값 변경
            $content = preg_replace_callback('!(script|link|a|img|input)([^>]*)(href|src)=[\'"](.*?)[\'"]!is', array($this, '_replaceSrc'), $content);

            // background:url의 값 변경
            $content = preg_replace_callback('!url\((.*?)\)!is', array($this, '_replaceBackgroundUrl'), $content);

            return $content;
        }

        function _replaceSrc($matches) {
            $href = $matches[4];
            if(preg_match("/^http/i", $href) || $href == '#' || preg_match("/javascript:/i",$href)) return $matches[0];

            if(substr($href,0,1)=='/') $href = substr($href,1);
            $href = $this->target_path.$href;

            $buff = sprintf('%s%s%s="%s"', $matches[1], $matches[2], $matches[3], $href);
            return $buff;
        }

        function _replaceBackgroundUrl($matches) {
            $href = $matches[1];
            if(preg_match("/^http/i",$href) || $href == '#' || preg_match("/javascript:/i",$href)) return $matches[0];

            if(substr($href,0,1)=='/') $href = substr($href,1);
            $href = $this->target_path.$href;

            $buff = sprintf('url(%s)', $href);
            return $buff;
        }
    }
?>
