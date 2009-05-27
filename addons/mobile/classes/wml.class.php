<?php
    /**
     * WML Library ver 0.1
     * @author zero <zero@zeroboard.com> / lang_select : misol
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
            if($this->totalPage > $this->mobilePage) $titlePageStr = sprintf("(%d/%d)",$this->mobilePage, $this->totalPage);
            print("<?xml version=\"1.0\" encoding=\"".$this->charset."\"?><!DOCTYPE wml PUBLIC \"-//WAPFORUM//DTD WML 1.1//EN\" \"http://www.wapforum.org/DTD/wml_1.1.xml\">\n");
            // 카드제목
            printf("<wml>\n<card title=\"%s%s\">\n<p>\n",htmlspecialchars($this->title),htmlspecialchars($titlePageStr));
        }

        /**
         * @brief 제목을 출력
         **/
        function printTitle() {
            if($this->totalPage > $this->mobilePage) $titlePageStr = sprintf("(%d/%d)",$this->mobilePage, $this->totalPage);
            printf('&lt;%s%s&gt;<br/>%s', htmlspecialchars($this->title),htmlspecialchars($titlePageStr),"\n");
        }

        /**
         * @brief 내용을 출력
         * hasChilds()가 있으면 목록형을 그렇지 않으면 컨텐츠를 출력
         **/
        function printContent() {
            if($this->hasChilds()) {
                foreach($this->getChilds() as $key => $val) {
                    if(!$val['link']) continue;
                    printf('<do type="%s" label="%s"><go href="%s" /></do>%s', $this->getNo(), htmlspecialchars($val['text']), $val['href'], "\n");
                    if($val['extra']) printf("%s\n",$val['extra']);
                }
            } else {
                printf('%s<br/>%s', str_replace("<br>","<br/>",$this->getContent()),"\n");
            }
            print('<br/>');
        }

        /**
         * @brief 버튼을 출력함
         **/
        function printBtn() {
            if($this->nextUrl) {
                $url = $this->nextUrl;
                printf('<do type="vnd.next" label="%s"><go href="%s"/></do>%s', $url->text, $url->url, "\n");
            }
            if($this->prevUrl) {
                $url = $this->prevUrl;
                printf('<do type="vnd.prev" label="%s"><go href="%s"/></do>%s', $url->text, $url->url, "\n");
            }
            // 기타 해당사항 없는 버튼 출력 담당 (array로 전달) type??
            if($this->etcBtn) {
                if(is_array($this->etcBtn)) {
                    foreach($this->etcBtn as $key=>$val) {
                        printf('<do type="vnd.btn%s" label="%s"><go href="%s"/></do>%s', $key, $val['text'], $val['url'], "\n");
                    }
                }
            }
            // 언어선택
            if(!parent::isLangChange()){
                $url = getUrl('','lcm','1','sel_lang',Context::getLangType(),'return_uri',Context::get('current_url'));
                printf('<do type="vnd.lang" label="%s"><go href="%s"/></do>%s', 'Language : '.Context::getLang('select_lang'), $url, "\n");
            }
            else {
                printf('<do type="vnd.lang" label="%s"><go href="%s"/></do>%s', Context::getLang('lang_return'), Context::get('return_uri'), "\n");
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
            if(Context::get('mobile_skt')==1) {
                return "vnd.skmn".parent::getNo();
            }
            else {
                return parent::getNo();
            }
            return $str;
        }
    }
?>
