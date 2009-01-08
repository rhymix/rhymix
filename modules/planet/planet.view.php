<?php
    /**
     * @class  planetView
     * @author sol (sol@ngleader.com)
     * @brief  planet 모듈의 View class
     **/

    class planetView extends planet {

        /**
         * @brief 초기화
         **/
        function init() {

            if(!preg_match('/planet/i', $this->act) && !in_array($this->act, array('favorite','countContentTagSearch','dispReplyList'))) return;

            /**
             * @brief 플래닛 모듈의 기본 설정은 view에서는 언제든지 사용하도록 load하여 Context setting
             **/
            $oPlanetModel = &getModel('planet');
            Context::set('config',$this->config = $oPlanetModel->getPlanetConfig());
            $this->module_info->layout_srl = $this->config->layout_srl;

            /**
             * 스킨이 없으면 플래닛 기본 설정의 스킨으로 설정
             **/
            if(!$this->module_info->skin) $this->module_info->skin = $this->config->planet_default_skin;
            $template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);
            $this->setTemplatePath($template_path);

            /**
             * myplanet(접속자 플래닛), planet(접속된 페이지의 플래닛)을 Context setting 하여 모든 곳에서 사용
             **/
            // myplanet 을 무조건 Context::set(), $myplanet->isExists()에 따라서 나의 플래닛 유무 확인
            Context::set('myplanet', $this->myplanet = $oPlanetModel->getMemberPlanet());

            // 현재 mid의 플래닛을 세팅 (홈이건 개별 플래닛이건 모두 $planet 로 판별 가능, PlanetInfo::isHome() 참고
            Context::set('planet', $this->planet = $oPlanetModel->getPlanet($this->module_srl));

            // 메인 페이지 일 경우 특정 액션이 아니라면 무조건 메인 화면 뿌려줌
            if($this->planet->isHome() && !in_array($this->act, array('dispPlanetCreate','dispPlanetLogin','dispPlanetTagSearch','dispPlanetContentSearch','dispPlanetContentTagSearch')) ) {
                Context::set('act',$this->act = 'dispPlanetHome');
            }

            $this->grant->access = $oPlanetModel->isAccessGranted();
            $this->grant->create = $oPlanetModel->isCreateGranted();

            // 플래닛은 별도 레이아웃 동작하지 않도록 변경
            //Context::set('layout', 'none');
            if(!Context::get('mid')) Context::set('mid', $this->config->mid, true);
        }

        /**
         * @brief 로그인
         **/
        function dispPlanetLogin(){
            $this->setTemplateFile('login');
        }

        /**
         * @brief 플래닛 생성
         **/
        function dispPlanetCreate() {
            if(!Context::get('is_logged')) return $this->dispPlanetMessage("msg_not_logged");
            if(!$this->grant->create) return $this->dispPlanetMessage("msg_not_permitted");

            if($this->myplanet->isExists()) return $this->dispPlanetMessage("msg_planet_exists");

            $this->setTemplateFile('create');
        }

        /**
         * @biref 플래닛 메인 페이지
         **/
        function dispPlanetHome() {
            if(!$this->grant->access) return $this->dispPlanetMessage("msg_not_permitted");

            // 플래닛의 기본 단위인 날짜를 미리 계산 (지정된 일자의 이전/다음날도 미리 계산하여 세팅)
            $last_date = $this->planet->getContentLastDay();
            $date = Context::get('date');
            if(!$date || $date > $last_date) $date = $last_date;
            Context::set('date', $date);
            Context::set('prev_date', $this->planet->getPrevDate($date));
            Context::set('next_date', $this->planet->getNextDate($date));


            // 초기화면에서 tagtab이 나오기 위해 set type 한다  
            $type = Context::get('type');
            if(!$type){
                if(is_array($this->config->tagtab) && $this->config->tagtab[0]){
                    $type = 'tagtab';
                    Context::set('type',$type);
                    Context::set('tagtab',$this->config->tagtab[0]);
                }else{
                   $type = 'all';
                   Context::set('type',$type);
                }
            }

            $tagtab = null;
            if($type == 'tagtab'){
                $tagtab = Context::get('tagtab');
                $page = Context::get('page');
                $oPlanetModel = &getModel('planet');
                $sort_index = 'documents.list_order';
                $order = 'asc';
            }else{
                switch($type) {
                    case 'wantyou':
                            $sort_index = 'documents.voted_count';
                            $order = 'desc';
                        break;
                    case 'best':
                            $sort_index = 'documents.comment_count';
                            $order = 'desc';
                        break;

                    case 'all':
                            $sort_index = 'documents.list_order';
                            $order = 'asc';
                        break;
                }

                $page = Context::get('page');
                $oPlanetModel = &getModel('planet');
            }

            $output = $oPlanetModel->getNewestContentList(null, $date, $page, 10, $sort_index, $order,$tagtab );
            Context::set('content_list', $output->data);
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('page_navigation', $output->page_navigation);

            $args->date = $date;
            $output = executeQuery('planet.getTotalContents', $args);
            Context::set('total_content', $output->data->count);

            $args->voted_count = 1;
            $output = executeQuery('planet.getTotalContents', $args);
            Context::set('total_wantyou', $output->data->count);

            unset($args->voted_count);
            $args->comment_count = 1;
            $output = executeQuery('planet.getTotalContents', $args);
            Context::set('total_best', $output->data->count);


            // tagtab을 만든다
            if(is_array($this->config->tagtab) && $this->config->tagtab[0]){
                $tagtab_list = array();
                foreach($this->config->tagtab as $key => $val){
                    $args->tag = $val;
                    $output = executeQuery('planet.getTotalTagSearchContents', $args);
                    $tagtab_list[$val] = $output->data->count;
                }
                Context::set('tagtab_list', $tagtab_list);
            }

            // tagtab_after을 만든다
            if(is_array($this->config->tagtab_after) && $this->config->tagtab_after[0]){
                $tagtab_after_list = array();
                foreach($this->config->tagtab_after as $key => $val){
                    $args->tag = $val;
                    $output = executeQuery('planet.getTotalTagSearchContents', $args);
                    $tagtab_after_list[$val] = $output->data->count;
                }
                Context::set('tagtab_after_list', $tagtab_after_list);
            }

            // 템플릿 지정
            $this->setTemplateFile('main');
        }

        /**
         * @brief 개별 플래닛
         **/
        function dispPlanet(){
            if(!$this->grant->access) return $this->dispPlanetMessage("msg_not_permitted");

            $oPlanetModel = &getModel('planet');

            // 글 고유 링크가 있으면 처리
            if(Context::get('document_srl')) {
                $oDocumentModel = &getModel('document');
                $oDocument = $oDocumentModel->getDocument(Context::get('document_srl'));
            }

            // 플래닛의 기본 단위인 날짜를 미리 계산 (지정된 일자의 이전/다음날도 미리 계산하여 세팅)
            if($oDocument && $oDocument->isExists()) {
                $date = $oDocument->getRegdate('Ymd');
            } else {
                $last_date = $this->planet->getContentLastDay();
                $date = Context::get('date');
                if(!$date || $date > $last_date) $date = $last_date;
            }
            Context::set('date', $date);
            Context::set('prev_date', $this->planet->getPrevDate($date));
            Context::set('next_date', $this->planet->getNextDate($date));

            // 최신 업데이트 글 추출
            $page = Context::get('page');

            $type = Context::get('type');
            switch($type) {
                case 'catch':
                        $output = $this->planet->getCatchContentList($page);
                    break;
                case 'interest':
                        $output = $this->planet->getInterestTagContentList($date, $page);
                    break;
                default :
                        $sort_index = 'documents.list_order';
                        $order = 'asc';
                        $output = $this->planet->getNewestContentList($date, $page, 10,$sort_index,$order);
                    break;
            }
            Context::set('content_list', $output->data);
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('page_navigation', $output->page_navigation);

            // 오늘의 전체 글 수
            $args->date = $date;
            $args->module_srl = $this->planet->getModuleSrl();
            $output = executeQuery('planet.getTotalContents', $args);
            Context::set('total_content', $output->data->count);

            // 낚은 글 수
            $output = executeQuery('planet.getCatchContentCount', $args);
            Context::set('total_catch', $output->data->count);

            // 플래닛의 메모 가져오기
            $memo_output = $oPlanetModel->getMemoList($this->module_srl);
            Context::set('memo_list', $memo_output->data);
            Context::set('memo_navigation', $memo_output->page_navigation);

            // 플래닛의 관심태그 가져오기
            $interest_tags = $oPlanetModel->getInterestTags($this->module_srl);
            Context::set('interest_tags', $interest_tags);

            // 브라우저 타이틀 변경
            Context::setBrowserTitle($this->planet->getPlanetTitle().' - '.$this->planet->getBrowserTitle());

            // 템플릿 지정
            $this->setTemplateFile('myPlanet');
        }

        /**
         * @brief 즐겨찾기 보기
         **/
        function favorite() {
            if(!$this->grant->access) return $this->dispPlanetMessage("msg_not_permitted");

            $oPlanetModel = &getModel('planet');

            // 개별 플래닛의 정보를 세팅
            $planet = $oPlanetModel->getPlanet($this->module_srl);
            Context::set('planet', $planet);

            // 내플래닛인지 아닌지
            $logged_info = Context::get('logged_info');
            Context::set('isMyPlanet', $planet->getMemberSrl() == $logged_info->member_srl);

            // 플래닛의 메모 가져오기
            $memo_output = $oPlanetModel->getMemoList($this->module_srl);
            Context::set('memo_list', $memo_output->data);
            Context::set('memo_navigation', $memo_output->page_navigation);

            // 플래닛의 관심태그 가져오기
            $interest_tags = $oPlanetModel->getInterestTags($this->module_srl);
            Context::set('interest_tags', $interest_tags);

            // 브라우저 타이틀 변경
            Context::setBrowserTitle($planet->getPlanetTitle().' - '.$planet->getBrowserTitle());

            // 내 플래닛이 아닐 경우 즐찾에 포함되어 있는 대상인지 확인
            $myplanet = Context::get('myplanet');
            if(Context::get('isMyPlanet') || $oPlanetModel->isInsertedFavorite($myplanet->module_srl, $this->module_srl)) {
                Context::set('myFavoritePlanet',true);
            } else {
                Context::set('myFavoritePlanet',false);
            }

            // 즐찾 가져오기
            $page = Context::get('page');
            $output = $oPlanetModel->getFavoriteContentList($this->module_srl, $page, 10);
            Context::set('content_list', $output->data);
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('page_navigation', $output->page_navigation);

            // 템플릿 지정
            $this->setTemplateFile('favorite');
        }

        function dispPlanetContentTagSearch(){
            if(!$this->grant->access) return $this->dispPlanetMessage("msg_not_permitted");

            $keyword = urldecode(Context::get('keyword'));
            $page = Context::get('page');
            if(!$this->planet->isHome()) $module_srl = $this->module_srl;
            else $module_srl = null;

            $oPlanetModel = &getModel('planet');
            Context::set('search_result', $oPlanetModel->getSearchResultCount($module_srl, $keyword));

            if($keyword) {
                $output = $oPlanetModel->getContentList($module_srl,'tag',$keyword, $page, 10);
                Context::set('content_list', $output->data);
                Context::set('total_count', $output->total_count);
                Context::set('total_page', $output->total_page);
                Context::set('page', $output->page);
                Context::set('page_navigation', $output->page_navigation);
            }

            // 템플릿 지정
            $this->setTemplateFile('search');
        }

        function dispPlanetContentSearch(){
            if(!$this->grant->access) return $this->dispPlanetMessage("msg_not_permitted");

            $keyword = urldecode(Context::get('keyword'));
            $page = Context::get('page');
            if(!$this->planet->isHome()) $module_srl = $this->module_srl;
            else $module_srl = null;

            $oPlanetModel = &getModel('planet');

            Context::set('search_result', $oPlanetModel->getSearchResultCount($module_srl, $keyword));

            if($keyword) {
                $output = $oPlanetModel->getContentList($module_srl,'content',$keyword, $page, 10);
                Context::set('content_list', $output->data);
                Context::set('total_count', $output->total_count);
                Context::set('total_page', $output->total_page);
                Context::set('page', $output->page);
                Context::set('page_navigation', $output->page_navigation);
            }

            // 템플릿 지정
            $this->setTemplateFile('search');
        }

        function dispPlanetTagSearch(){
            if(!$this->grant->access) return $this->dispPlanetMessage("msg_not_permitted");

            $keyword = urldecode(Context::get('keyword'));
            $page = Context::get('page');
            if(!$this->planet->isHome()) $module_srl = $this->module_srl;
            else $module_srl = null;

            $oPlanetModel = &getModel('planet');

            Context::set('search_result', $oPlanetModel->getSearchResultCount($module_srl, $keyword));

            if($keyword) {
                $output = $oPlanetModel->getPlanetTagList($keyword, $page, 10);
                Context::set('planet_list', $output->data);
                Context::set('total_count', $output->total_count);
                Context::set('total_page', $output->total_page);
                Context::set('page', $output->page);
                Context::set('page_navigation', $output->page_navigation);
            }

            // 템플릿 지정
            $this->setTemplateFile('search_planet');
        }

        function dispReplyList(){
            $page = Context::get('page');
            $document_srl = Context::get('document_srl');
            $oPlanetModel = &getModel('planet');
            $output = $oPlanetModel->getReplyList($document_srl,$page);
            Context::set('reply_list',$output->data);
        }

        function dispPlanetMessage($msg_code) {
            $msg = Context::getLang($msg_code);
            if(!$msg) $msg = $msg_code;
            Context::set('message', $msg);
            $this->setTemplateFile('message');
        }

    }

?>
