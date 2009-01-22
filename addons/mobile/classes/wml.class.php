<?php
    /**
     * WML Library ver 0.1
     * @author zero <zero@zeroboard.com>
     **/
    class wap extends mobileXE {

        /**
         * @brief constructor
         **/
        function wap() {
            parent::mobileXE();
        }

        /**
         * @brief wml 헤더 출력
         **/
        function printHeader() {
            header("Content-Type: text/vnd.wap.wml");
            header("charset: ".$this->charset);
            print("<?xml version=\"1.0\" encoding=\"".$this->charset."\"?><!DOCTYPE wml PUBLIC \"-//WAPFORUM//DTD WML 1.1//EN\" \"http://www.wapforum.org/DTD/wml_1.1.xml\">\n");
            print("<wml>\n<card>\n<p>\n");
        }

        /**
         * @brief 제목을 출력
         **/
        function printTitle() {
            if($this->totalPage > $this->mobilePage) $titlePageStr = sprintf("(%d/%d)",$this->mobilePage, $this->totalPage);
            printf('&lt;%s%s&gt;<br/>%s', $this->title,$titlePageStr,"\n");
        }

        /**
         * @brief 내용을 출력
         * hasChilds()가 있으면 목록형을 그렇지 않으면 컨텐츠를 출력
         **/
        function printContent() {
            if($this->hasChilds()) {
                foreach($this->getChilds() as $key => $val) {
                    if(!$val['link']) continue;
                    printf('<do type="%s" label="%s"><go href="%s" /></do>%s', $this->getNo(), $val['text'], $val['href'], "\n");
                }
            } else {
                printf('%s<br/>%s', str_replace("<br>","<br/>",$this->getContent()),"\n");
            } 
        }

        /**
         * @brief 버튼을 출력함 
         **/
        function printBtn() {
            if($this->nextUrl) {
                $url = $this->nextUrl;
                printf('<do type="%s" label="%s"><go href="%s"/></do>%s', $this->getNo(), $url->text, $url->url, "\n");
            }
            if($this->prevUrl) {
                $url = $this->prevUrl;
                printf('<do type="%s" label="%s"><go href="%s"/></do>%s', $this->getNo(), $url->text, $url->url, "\n");
            }
            if($this->homeUrl) {
                $url = $this->homeUrl;
                printf('<do type="access" label="%s"><go href="%s"/></do>%s', $url->text, $url->url, "\n");
            }
            if($this->upperUrl) {
                $url = $this->upperUrl;
                printf('<do type="vnd.up" label="%s"><go href="%s"/></do>%s', $url->text, $url->url, "\n");
            }
        }

        // 푸터 정보를 출력
        function printFooter() {
            print("</p>\n</card>\n</wml>");
        }

        // 목록등에서 일련 번호를 리턴한다
        function getNo() {
            return "vnd.skmn".parent::getNo();
            return $str;
        }
    }
?>
