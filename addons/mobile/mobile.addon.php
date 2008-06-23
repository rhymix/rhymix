<?php
    if(!defined("__ZBXE__")) exit();

    /**
     * @file mobile.addon.php
     * @author zero (zero@nzeo.com)
     * @brief 모바일XE 애드온
     *
     * 헤더정보를 가로채서 모바일에서의 접속일 경우 WAP 태그로 컨텐츠를 출력함
     **/

    // 재호출을 막기 위한 코드

    // 관련 클래스 파일을 읽음
    require_once(_XE_PATH_.'addons/mobile/classes/mobile.class.php');
    require_once(_XE_PATH_.'addons/mobile/classes/mobile.func.php');

    // 모바일 브라우저가 아니면 return
    if(!mobileXE::getBrowserType()) return;
    
    // 관리자 페이지이거나 입출력이 XMLRPC일 경우에도 return
    if(Context::get('module')=='admin' || Context::getRequestMethod()=='XMLRPC' || Context::getResponseMethod()=='XMLRPC' ) return;

    /**
     * 전처리
     * 게시판의 경우 목록과 내용을 제대로 보여주기 위해서 모듈 정보를 조작함
     * 게시판 외의 경우 그냥 첫페이지의 내용을 출력함
     * 게시판 이외의 모듈 결과값은 차후 적용예정
     **/
    if($called_position == 'before_module_proc' && $this->module == 'board') {
        $this->list_count = $this->module_info->list_count = 9;
        return;
    }

    /**
     * 동작 조건
     * 1. called_position == after_module_proc 일 경우에만 동작
     * 2. 관리자 페이지가 아닐 경우
     * 3. Context::getRequestMethod()!=='XMLRPC' 일 경우에만
     * 4. Context::getResponseMethod()!=='XMLRPC' 일 경우에만
     **/
    if($called_position != 'after_module_proc') return;

    $oMobile = &mobileXE::getInstance();
    if(!$oMobile) return;

    $oMobile->setCharSet($addon_info->charset);

    // 모듈의 정보를 구함
    $module_info = $this->module_info;

    // 메뉴 정보가 있는지 검사
    if($module_info->menu_srl) {

        // menu php cache 파일을 호출
        $menu_cache_file = sprintf(_XE_PATH_.'files/cache/menu/%d.php', $module_info->menu_srl);
        if(file_exists($menu_cache_file)) {
            include $menu_cache_file;
            
            // 정리된 menu들을 1차원으로 변경
            getListedItems($menu->list, $listed_items, $mid_list);

            // url request parameter에 mid값이 없을 경우, 즉 첫페이지 인경우 전체 목록을 구함
            if(!isset($_GET['mid'])) $childs = $menu->list;
            // mid가 명시되어 있으면 해당 mid의 childs를 구함
            else $childs = $listed_items[$module_info->mid]['list'];

            // 현재 메뉴의 depth가 1이상이면 상위 버튼을 지정
            if($module_info->is_default != 'Y') {
                $cur_menu_item = $listed_items[$module_info->mid];
                if($cur_menu_item['parent_srl']) {
                    $parent_srl = $cur_menu_item['parent_srl'];
                    if($parent_srl && $mid_list[$parent_srl]) {
                        $parent_item = $listed_items[$mid_list[$parent_srl]];
                        if($parent_item) $oMobile->setUpperUrl(getUrl('','mid',$parent_item['mid']), Context::getLang('cmd_go_upper') );
                    }
                } else {
                    $oMobile->setUpperUrl(getUrl(), Context::getLang('cmd_go_upper'));
                }
            }

            // childs 메뉴들을 지정
            $oMobile->setChilds($childs);
        }
    } 

    // 만약 childs가 없을 경우 컨텐츠 입력
    if(!$oMobile->hasChilds()) {

        // 현재 모듈이 게시판일 경우 (다른 모듈의 경우는 차후에..)
        if($module_info->module == 'board') {

            // 선택된 게시글이 있으면 게시글의 내용을 출력
            $oDocument = Context::get('oDocument');
            if($oDocument && $oDocument->isExists()) {
                // 내용 지정 (태그를 모두 제거한 내용을 설정)
                $content = strip_tags($oDocument->getContent(false,false,false));
                $oMobile->setContent( $content );

                // 상위 페이지를 목록으로 돌아가기로 지정
                $oMobile->setUpperUrl( getUrl('document_srl',''), Context::getLang('cmd_list') );

            // 선택된 게시글이 없으면 목록을 출력
            } else {
                $document_list = Context::get('document_list');
                $childs = array();
                if($document_list && count($document_list)) {
                    foreach($document_list as $key => $val) {
                        $href = getUrl('mid',$_GET['mid'],'document_srl',$val->document_srl);
                        $text = $val->getTitleText(10);
                        $obj = null;
                        $obj['href'] = $href;
                        $obj['link'] = $obj['text'] = $text;
                        $childs[] = $obj;
                    }
                    $oMobile->setChilds($childs);
                }

                $page_navigation = Context::get('page_navigation');
                $totalPage = $page_navigation->last_page;
                $page = (int)Context::get('page');
                if(!$page) $page = 1;

                // next/prevUrl 지정
                if($page>1) $oMobile->setPrevUrl(getUrl('mid',$_GET['mid'],'page',$page-1), sprintf('%s (%d/%d)', Context::getLang('cmd_prev'), $page-1, $totalPage));

                if($page<$totalPage) $oMobile->setNextUrl(getUrl('mid',$_GET['mid'],'page',$page+1), sprintf('%s (%d/%d)', Context::getLang('cmd_next'), $page+1, $totalPage));

                $oMobile->mobilePage = $page;
                $oMobile->totalPage = $totalPage;
            }
        // 게시판 이외의 경우
        } else {
            // 레이아웃은 사용하지 않도록 함
            Context::set('layout','none');

            // 템플릿 컴파일
            $oTemplate = new TemplateHandler();
            $oContext = &Context::getInstance();

            $content = $oTemplate->compile($this->getTemplatePath(), $this->getTemplateFile());
            $content = $oContext->transContent($content);
            $oMobile->setContent( $content);
        }
    }

    // 홈버튼 지정
    $oMobile->setHomeUrl(getUrl(), Context::getLang('cmd_go_home'));

    // 제목 지정
    $oMobile->setTitle(Context::getBrowserTitle());

    // 출력
    $oMobile->display();
?>
