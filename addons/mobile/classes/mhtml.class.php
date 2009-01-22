<?php
    /**
     * mhtml Library ver 0.1
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
            print("<html>\n");
        }

        // 제목을 출력
        function printTitle() {
            if($this->totalPage > $this->mobilePage) $titlePageStr = sprintf("(%d/%d)",$this->mobilePage, $this->totalPage);
            printf('&lt;%s%s&gt;<br>%s', $this->title,$titlePageStr,"\n");
        }

        /**
         * @brief 내용을 출력
         * hasChilds()가 있으면 목록형을 그렇지 않으면 컨텐츠를 출력
         **/
        function printContent() {
            if($this->hasChilds()) {
                foreach($this->getChilds() as $key => $val) {
                    if(!$val['link']) continue;
                    printf('<a href="%s" accesskey="%s">%s</a><br>%s', $val['href'], $this->getNo(), $val['text'], "\n");
                }
            } else {
                print $this->getContent()."\n";
            } 
        }

        /**
         * @brief 버튼을 출력함 
         **/
        function printBtn() {
            if($this->nextUrl) {
                $url = $this->nextUrl;
                printf('<a href="%s">%s</a><br>%s', $url->url, $url->text, "\n");
            }
            if($this->prevUrl) {
                $url = $this->prevUrl;
                printf('<a href="%s">%s</a><br>%s', $url->url, $url->text, "\n");
            }
            if($this->upperUrl) {
                $url = $this->upperUrl;
                printf('<btn href="%s" name="%s">%s', $url->url, $url->text, "\n");
            }
            if($this->homeUrl) {
                $url = $this->homeUrl;
                printf('<a btn="%s" href="%s">%s</a>%s', $url->text, $url->url, $url->text, "\n");
            }
        }

        // 푸터 정보를 출력
        function printFooter() {
            print("</html>\n");
        }
    }
?>
