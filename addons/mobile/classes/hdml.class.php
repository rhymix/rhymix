<?php
    /**
     * HDML Library ver 0.1
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
         * @brief hdml 헤더 출력
         **/
        function printHeader() {
            header("Content-Type:text/x-hdml; charset=".$this->charset);
            header("Cache-Control: no-store, no-cache, must-revalidate");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");

            print '<hdml version=3.0 ttl=0 markable=true>';
            print "\n";
            print $this->hasChilds()?'<choice name=main>':'<display>';
            print "\n";

            if($this->upperUrl) {
                $url = $this->upperUrl;
                printf('<action type=soft1 task=go dest="%s" label="%s">%s', $url->url, $url->text, "\n");
            }
        }

        /**
         * @brief 제목을 출력
         **/
        function printTitle() {
            if($this->totalPage > $this->mobilePage) $titlePageStr = sprintf("(%d/%d)",$this->mobilePage, $this->totalPage);
            printf('<b>&lt;%s%s&gt;%s', $this->title,$titlePageStr,"\n");
        }

        /**
         * @brief 내용을 출력
         * hasChilds()가 있으면 목록형을 그렇지 않으면 컨텐츠를 출력
         **/
        function printContent() {
            if($this->hasChilds()) {
                foreach($this->getChilds() as $key => $val) {
                    if(!$val['link']) continue;
                    printf('<ce task=go label="%s" dest="%s">%s%s',Context::getLang('cmd_select'), $val['href'], $val['text'], "\n");
                }
            } else {
                printf('<wrap>%s<br>%s', $this->getContent(),"\n");
            } 
        }

        /**
         * @brief 버튼을 출력함 
         **/
        function printBtn() {
            // 메뉴 형식
            if($this->hasChilds()) {
                if($this->nextUrl) {
                    $url = $this->nextUrl;
                    printf('<ce task=go label="%s" dest="%s">%s%s', $url->text, $url->url, $url->text, "\n");
                }
                if($this->prevUrl) {
                    $url = $this->prevUrl;
                    printf('<ce task=go label="%s" dest="%s">%s%s', $url->text, $url->url, $url->text, "\n");
                }
                if($this->homeUrl) {
                    $url = $this->homeUrl;
                    printf('<ce task=go label="%s" dest="%s">%s%s', $url->text, $url->url, $url->text, "\n");
                }
            // 컨텐츠 형식
            } else {
                if($this->nextUrl) {
                    $url = $this->nextUrl;
                    printf('<a task=gosub label="%s" dest="%s">%s</a>', $url->text, $url->url, $url->text);
                }
                if($this->prevUrl) {
                    $url = $this->prevUrl;
                    printf('<a task=gosub label="%s" dest="%s">%s</a>', $url->text, $url->url, $url->text);
                }
                if($this->homeUrl) {
                    $url = $this->homeUrl;
                    printf('<a task=gosub label="%s" dest="%s">%s</a>', $url->text, $url->url, $url->text);
                }
            }
        }

        /**
         * @brief 푸터 정보를 출력
         **/
        function printFooter() {
            print $this->hasChilds()?'</choice>':'</display>';
            print "\n";
            print("</hdml>");
        }

    }
?>
