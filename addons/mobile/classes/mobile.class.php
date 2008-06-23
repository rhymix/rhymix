<?php
    /**
     * Mobile XE Library Class ver 0.1
     * @author zero <zero@zeroboard.com>
     * @brief WAP 태그 출력을 위한 라이브러리
     **/

    class mobileXE {

        // 기본 url
        var $homeUrl = NULL;
        var $upperUrl = NULL;
        var $nextUrl = NULL;
        var $prevUrl = NULL;

        // 메뉴 네비게이션을 위한 변수
        var $childs = null;

        // 기본 변수
        var $title = NULL;
        var $content = NULL;
        var $mobilePage = 0;
        var $totalPage = 1;
        var $charset = 'euc-kr';
        var $no = 0;

        // Deck size
        var $deckSize = 500;

        // getInstance
        function &getInstance() {
            static $instance = null;

            if(!$instance) {

                $browserType = mobileXE::getBrowserType();
                if(!$browserType) return;

                $class_file = sprintf('%saddons/mobile/classes/%s.class.php', _XE_PATH_, $browserType);
                require_once($class_file);

                Context::loadLang(_XE_PATH_.'addons/mobile/lang');

                $instance = new wap();

                $mobilePage = (int)Context::get('mpage');
                if(!$mobilePage) $mobilePage = 1;
                $instance->setMobilePage($mobilePage);
            }

            return $instance;
        }

        /**
         * @brief 접속 브라우저의 헤더를 판단하여 브라우저 타입을 return
         * 모바일 브라우저가 아닐 경우 null return
         **/
        function getBrowserType() {
            // 브라우저 타입을 판별
            $browserAccept = $_SERVER['HTTP_ACCEPT'];
            $userAgent = $_SERVER['HTTP_USER_AGENT'];
            $wap_sid = $_SERVER['HTTP_X_UP_SUBNO'];

            if(eregi("SKT11", $userAgent)) $browserType = "wml";
            elseif(eregi("hdml", $browserAccept)) $browserType = "hdml";
            elseif(eregi("CellPhone", $userAgent)) $browserType = "mhtml";
            else $browserType = "html";

$browserType = 'mhtml';
            // class 지정 (html일 경우 동작 하지 않도록 함)
            if($browserType == 'html') return null;

            return $browserType;
        }

        // charset 지정
        function setCharSet($charset = 'utf-8') {
            if(!$charset) $charset = 'utf-8';
            $this->charset = $charset;
        }

        // 모바일 기기의 용량 제한에 다른 가상 페이지 지정
        function setMobilePage($page=1) {
            if(!$page) $page = 1;
            $this->mobilePage = $page;
        }

        // 목록형 데이터 설정을 위한 child menu지정
        function setChilds($childs) {
            // menu개수가 9개 이상일 경우 자체 페이징 처리
            $menu_count = count($childs);
            if($menu_count>9) {
                $startNum = ($this->mobilePage-1)*9;
                $idx = 0;
                $new_childs = array();
                foreach($childs as $k => $v) {
                    if($idx >= $startNum && $idx < $startNum+9) {
                        $new_childs[$k] = $v;
                    }
                    $idx ++;
                }
                $childs = $new_childs;

                $this->totalPage = (int)(($menu_count-1)/9)+1;

                // next/prevUrl 지정
                if($this->mobilePage>1) {
                    $url = getUrl('mid',$_GET['mid'],'mpage',$this->mobilePage-1);
                    $text = sprintf('%s (%d/%d)', Context::getLang('cmd_prev'), $this->mobilePage-1, $this->totalPage);
                    $this->setPrevUrl($url, $text);
                }

                if($this->mobilePage<$this->totalPage) {
                    $url = getUrl('mid',$_GET['mid'],'mpage',$this->mobilePage+1);
                    $text = sprintf('%s (%d/%d)', Context::getLang('cmd_next'), $this->mobilePage+1, $this->totalPage);
                    $this->setNextUrl($url, $text);
                }
            } 
            $this->childs = $childs;
        }

        // menu 출력대상이 있는지 확인
        function hasChilds() {
            return count($this->childs)?true:0;
        }

        // child menu반환
        function getChilds() {
            return $this->childs;
        }

        // title 지정
        function setTitle($title) {
            $this->title = $title;
        }

        // title 반환
        function getTitle() {
            return $this->title;
        }

        // 컨텐츠 지정
        function setContent($content) {
            $content = str_replace(array('&','<','>','"','&amp;nbsp;'), array('&amp;','&lt;','&gt;','&quot;',' '), strip_tags($content));

            // 모바일의 경우 한 덱에 필요한 사이즈가 적어서 내용을 모두 페이지로 나눔
            $contents = array();
            while($content) {
                $tmp = cut_str($content, $this->deckSize, '');
                $contents[] = $tmp;
                $content = substr($content, strlen($tmp));
            }

            $this->totalPage = count($contents);

            // next/prevUrl 지정
            if($this->mobilePage>1) {
                $url = getUrl('mid',$_GET['mid'],'mpage',$this->mobilePage-1);
                $text = sprintf('%s (%d/%d)', Context::getLang('cmd_prev'), $this->mobilePage-1, $this->totalPage);
                $this->setPrevUrl($url, $text);
            }

            if($this->mobilePage<$this->totalPage) {
                $url = getUrl('mid',$_GET['mid'],'mpage',$this->mobilePage+1);
                $text = sprintf('%s (%d/%d)', Context::getLang('cmd_next'), $this->mobilePage+1, $this->totalPage);
                $this->setNextUrl($url, $text);
            }

            $this->content = $contents[$this->mobilePage-1];
        }

        // 컨텐츠 반환
        function getContent() {
            return $this->content;
        }

        // home url 지정
        function setHomeUrl($url, $text) {
            if(!$url) $url = '#';
            $this->homeUrl->url = $url;
            $this->homeUrl->text = $text;
        }

        // upper url 지정
        function setUpperUrl($url, $text) {
            if(!$url) $url = '#';
            $this->upperUrl->url = $url;
            $this->upperUrl->text = $text;
        }

        // prev url 지정
        function setPrevUrl($url, $text) {
            if(!$url) $url = '#';
            $this->prevUrl->url = $url;
            $this->prevUrl->text = $text;
        }

        // next url 지정
        function setNextUrl($url, $text) {
            if(!$url) $url = '#';
            $this->nextUrl->url = $url;
            $this->nextUrl->text = $text;
        }

        // display
        function display() {
            ob_start();

            // 헤더를 출력
            $this->printHeader();

            // 제목을 출력
            $this->printTitle();

            // 내용 출력
            $this->printContent();

            // 버튼 출력
            $this->printBtn();

            // 푸터를 출력
            $this->printFooter();

            $content = ob_get_clean();

            debugPrint($content);
            // 변환 후 출력
            if(strtolower($this->charset) == 'utf-8') print $content;
            else print iconv('UTF-8',$this->charset, $content);

            exit();
        }

        // 페이지 이동
        function movepage($url) {
            header("location:$url");
            exit();
        }

        // 목록등에서 일련 번호를 리턴한다
        function getNo() {
            $this->no++;
            $str = $this->no;
            return $str;
        }
    }
?>
