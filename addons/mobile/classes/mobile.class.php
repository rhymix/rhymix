<?php
    /**
     * Mobile XE Library Class ver 0.1
     * @author zero <zero@zeroboard.com>
     * @brief WAP 태그 출력을 위한 XE 라이브러리
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

        // 네비게이션 관련 변수
        var $menu = null;
        var $listed_items = null;
        var $node_list = null;
        var $index_mid = null;

        // Navigation On/ Off 상태 값
        var $navigationMode = 0;

        // 현재 요청된 XE 모듈 정보 
        var $module_info = null;

        // 현재 실행중인 모듈의 instance
        var $oModule = null;

        // Deck size
        var $deckSize = 1024;

        /**
         * @brief getInstance
         **/
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
         * @brief constructor
         **/
        function mobileXE() {
            // navigation mode 체크
            if(Context::get('nm')) $this->navigationMode = 1;
            $this->cmid = (int)Context::get('cmid');
        }

        /**
         * @brief navigation mode 체크
         * navigationMode 세팅과 모듈 정보의 menu_srl이 있어야 navigation mode = true로 return
         **/
        function isNavigationMode() {
            return ($this->navigationMode && $this->module_info->menu_srl)?true:false;
        }

        /**
         * @brief 현재 요청된 모듈 정보 세팅
         **/
        function setModuleInfo(&$module_info) {
            if($this->module_info) return; 
            $this->module_info = $module_info;
        }

        /**
         * @brief 현재 실행중인 모듈 instance 세팅
         **/
        function setModuleInstance(&$oModule) {
            if($this->oModule) return;

            // instance 저장
            $this->oModule = $oModule;

            // 현재 모듈의 메뉴가 설정되어 있으면 메뉴 정리
            $menu_cache_file = sprintf(_XE_PATH_.'files/cache/menu/%d.php', $this->module_info->menu_srl);
            if(!file_exists($menu_cache_file)) return;

            include $menu_cache_file;
                
            // 정리된 menu들을 1차원으로 변경
            $this->getListedItems($menu->list, $listed_items, $node_list);

            $this->listed_items = $listed_items;
            $this->node_list = $node_list;
            $this->menu = $menu->list;

            $k = array_keys($node_list);
            $v = array_values($node_list);
            $this->index_mid = $k[0];

            // 현재 메뉴의 depth가 1이상이면 상위 버튼을 지정
            $cur_menu_item = $listed_items[$node_list[$this->module_info->mid]];
            if($cur_menu_item['parent_srl']) {
                $parent_srl = $cur_menu_item['parent_srl'];
                if($parent_srl && $listed_items[$parent_srl]) {
                    $parent_item = $listed_items[$parent_srl];
                    if($parent_item) $this->setUpperUrl(getUrl('','mid',$parent_item['mid']), Context::getLang('cmd_go_upper'));
                }
            } elseif (!$this->isNavigationMode()) {
                $this->setUpperUrl(getUrl('','mid',$this->index_mid,'nm','1','cmid',0), Context::getLang('cmd_view_sitemap'));
            }
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

            if(eregi("SKT11", $userAgent)) return "wml";
            elseif(eregi("skt", $browserAccept)) return "wml";
            elseif(eregi("hdml", $browserAccept)) return "hdml";
            elseif(eregi("CellPhone", $userAgent)) return  "mhtml";
            return null;
        }

        /**
         * @brief charset 지정
         **/
        function setCharSet($charset = 'euc-kr') {
            if(!$charset) $charset = 'euc-kr';
            $this->charset = $charset;
        }

        /**
         * @brief 모바일 기기의 용량 제한에 다른 가상 페이지 지정
         **/
        function setMobilePage($page=1) {
            if(!$page) $page = 1;
            $this->mobilePage = $page;
        }

        /**
         * @brief 목록형 데이터 설정을 위한 child menu지정
         **/
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

        /**
         * @brief menu 출력대상이 있는지 확인
         **/
        function hasChilds() {
            return count($this->childs)?true:0;
        }

        /**
         * @brief child menu반환
         **/
        function getChilds() {
            return $this->childs;
        }

        /**
         * @brief title 지정
         **/
        function setTitle($title) {
            $this->title = $title;
        }

        /**
         * @brief title 반환
         **/
        function getTitle() {
            return $this->title;
        }

        /**
         * @brief 컨텐츠 정리
         * HTML 컨텐츠에서 텍스트와 링크만 추출하는 기능
         **/
        function setContent($content) {
            // 링크/줄바꿈을 임의의 문자열로 변경하고 태그 모두 제거
            $content = strip_tags(preg_replace('/<(\/?)(a|br)/i','[$1$2', $content));

            // 링크/줄바꿈을 다시 원위치
            $content = preg_replace('/\[(\/?)(a|br)/i','<$1$2', $content);

            // 탭 여백 제거
            $content = str_replace("\t", "", $content);

            // 2번 이상 반복되는 공백과 줄나눔을 제거
            $content = preg_replace('/( ){2,}/s', '', $content);
            $content = preg_replace("/([\r\n]+)/s", "\r\n", $content);
            $content = str_replace(array("<A","<BR","<Br","<br>","<BR>","<br />"), array("<a","<br","<br","<br/>","<br/>","<br/>"), $content);
            while(strpos($content, '<br/><br/>')) {
                $content = str_replace('<br/><br/>','<br/>',$content);
            }

            // 모바일의 경우 한 덱에 필요한 사이즈가 적어서 내용을 모두 페이지로 나눔
            $contents = array();
            while($content) {
                $tmp = $this->cutStr($content, $this->deckSize, '');
                $contents[] = $tmp;
                $content = substr($content, strlen($tmp));

                //$content = str_replace(array('&','<','>','"','&amp;nbsp;'), array('&amp;','&lt;','&gt;','&quot;',' '), $content);

                $tag_open_pos = strpos($content, '<a');
                $tag_close_pos = strpos($content, '</a>');
                if($tag_open_pos!==false && $tag_close_pos || $tag_close_pos < $tag_open_pos) {
                    $contents[count($contents)-1] .= substr($content, 0, $tag_close_pos+4);
                    $content = substr($content, $tag_close_pos+4);
                }
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

        /**
         * @brief byte수로 자르는 함수
         **/
        function cutStr($string, $cut_size) {
            return preg_match('/.{'.$cut_size.'}/su', $string, $arr) ? $arr[0] : $string; 
        }

        /**
         * @brief 컨텐츠 반환
         **/
        function getContent() {
            return $this->content;
        }

        /**
         * @brief home url 지정
         **/
        function setHomeUrl($url, $text) {
            if(!$url) $url = '#';
            $this->homeUrl->url = $url;
            $this->homeUrl->text = $text;
        }

        /**
         * @brief upper url 지정
         **/
        function setUpperUrl($url, $text) {
            if(!$url) $url = '#';
            $this->upperUrl->url = $url;
            $this->upperUrl->text = $text;
        }

        /**
         * @brief prev url 지정
         **/
        function setPrevUrl($url, $text) {
            if(!$url) $url = '#';
            $this->prevUrl->url = $url;
            $this->prevUrl->text = $text;
        }

        /**
         * @brief next url 지정
         **/
        function setNextUrl($url, $text) {
            if(!$url) $url = '#';
            $this->nextUrl->url = $url;
            $this->nextUrl->text = $text;
        }

        /**
         * @brief display
         **/
        function display() {
            // 홈버튼 지정
            $this->setHomeUrl(getUrl(), Context::getLang('cmd_go_home'));

            // 제목 지정
            $this->setTitle(Context::getBrowserTitle());

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

            // 변환 후 출력
            if(strtolower($this->charset) == 'utf-8') print $content;
            else print iconv('UTF-8',$this->charset, $content);

            exit();
        }

        /**
         * @brief 페이지 이동
         **/
        function movepage($url) {
            header("location:$url");
            exit();
        }

        /**
         * @brief 목록등에서 일련 번호를 리턴한다
         **/
        function getNo() {
            $this->no++;
            $str = $this->no;
            return $str;
        }

        /**
         * @brief XE의 Menu 모듈이 값을 사용하기 쉽게 정리해주는 함수
         **/
        function getListedItems($menu, &$listed_items, &$node_list) {
            if(!count($menu)) return;
            foreach($menu as $node_srl => $item) {
                if(preg_match('/^([a-zA-Z0-9\_\-]+)$/', $item['url'])) {
                    $mid = $item['mid'] = $item['url'];
                    $node_list[$mid] = $node_srl;
                } else {
                    $mid = $item['mid'] = null;
                }

                $listed_items[$node_srl] = $item;
                $this->getListedItems($item['list'], $listed_items, $node_list);
            }
        }

        /**
         * @brief XE 네비게이션 출력
         **/
        function displayNavigationContent() {
            $childs = array();

            if($this->cmid) {
                $cur_item = $this->listed_items[$this->cmid];
                $upper_srl = $cur_item['parent_srl'];;
                $list = $cur_item['list'];;
                $this->setUpperUrl(getUrl('cmid',$upper_srl), Context::getLang('cmd_go_upper'));
                if(preg_match('/^([a-zA-Z0-9\_\-]+)$/', $cur_item['url'])) {
                    $obj = null;
                    $obj['href'] = getUrl('','mid',$cur_item['url']);
                    $obj['link'] = $obj['text'] = '['.$cur_item['text'].']';
                    $childs[] = $obj;
                }

            } else {
                $list = $this->menu;
                $upper_srl = 0;
            }


            if(count($list)) {
                foreach($list as $key => $val) {
                    if(!$val['text']) continue;
                    $obj = null;
                    if(!count($val['list'])) {
                        $obj['href'] = getUrl('','mid',$val['url']);
                    } else {
                        $obj['href'] = getUrl('cmid',$val['node_srl']);
                    }
                    $obj['link'] = $obj['text'] = $val['text'];
                    $childs[] = $obj;
                }
                $this->setChilds($childs);
            }

            // 출력
            $this->display();
        }

        /**
         * @brief 모듈의 WAP 클래스 객체 생성하여 WAP 준비
         **/
        function displayModuleContent() {
            // 선택된 모듈의 WAP class 객체 생성
            $oModule = &getWap($this->module_info->module);
            if(!$oModule || !method_exists($oModule, 'procWAP') ) return;

            $vars = get_object_vars($this->oModule);
            if(count($vars)) foreach($vars as $key => $val) $oModule->{$key}  = $val;

            // 실행
            $oModule->procWAP($this);

            // 출력
            $this->display();
        }

        /**
         * @brief WAP 컨텐츠를 별도로 구할 수 없으면 최종 결과물을 출력
         **/
        function displayContent() {
            Context::set('layout','none');

            // 템플릿 컴파일
            $oTemplate = new TemplateHandler();
            $oContext = &Context::getInstance();

            $content = $oTemplate->compile($this->oModule->getTemplatePath(), $this->oModule->getTemplateFile());
            $content = $oContext->transContent($content);
            $this->setContent($content);

            // 출력
            $this->display();
        }
    }
?>
