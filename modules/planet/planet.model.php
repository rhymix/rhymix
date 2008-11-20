<?php
    /**
     * @class  planetModel
     * @author sol (sol@ngleader.com)
     * @brief  planet 모듈의 Model class
     **/

    class planetModel extends planet {

        /**
         * @brief 초기화
         **/
        function init() {
        }


        /**
         * @brief 플래닛 기본 설정 return
         **/
        function getPlanetConfig() {
            static $config = null;
            if(is_null($config)) {
                // module config의 값을 구함
                $oModuleModel = &getModel('module');
                $config = $oModuleModel->getModuleConfig('planet');

                // planet dummy module의 is_default 값을 구함
                $dummy = $oModuleModel->getModuleInfoByMid($config->mid);
                $config->is_default = $dummy->is_default;
                $config->module_srl = $dummy->module_srl;
                $config->browser_title = $dummy->browser_title;
                if($config->logo_image) $config->logo_image = context::getFixUrl($config->logo_image);
            }
            return $config;
        }

        /**
         * @brief 회원 - 플래닛 접속 권한 return
         * 플래닛 서비스 컨텐츠에 대한 접속 권한을 확인
         **/
        function isAccessGranted() {
            $config = $this->getPlanetConfig();
            $grant = $config->grants['access'];
            if(!$grant || !count($grant)) return true;

            $logged_info = Context::get('logged_info');
            if($logged_info->is_admin == 'Y') return true;
            $group_list = $logged_info->group_list;
            if(count($group_list)) $group_srls = array_keys($group_list);
            else return false;

            foreach($grant as $srl) if(in_array($srl, $group_srls)) return true;
            return false;
        }

        /**
         * @brief 회원 - 플래닛 생성 권한 return
         **/
        function isCreateGranted() {
            if(!Context::get('is_logged')) return false;

            $config = $this->getPlanetConfig();
            $grant = $config->grants['create'];
            if(!$grant || !count($grant)) return true;

            $logged_info = Context::get('logged_info');
            if($logged_info->is_admin == 'Y') return true;
            $group_list = $logged_info->group_list;
            $group_srls = array_keys($group_list);

            foreach($grant as $srl) if(in_array($srl, $group_srls)) return true;
            return false;
        }

        /**
         * @brief 관리자 - 플래닛 모듈 전체 관리 권한 return
         **/
        function isManageGranted() {
            if(!Context::get('is_logged')) return false;

            $logged_info = Context::get('logged_info');
            if($logged_info->is_admin == 'Y') return true;

            $config = $this->getPlanetConfig();
            $grant = $config->grants['manage'];
            if(!$grant || !count($grant)) return false;

            $group_list = $logged_info->group_list;
            $group_srls = array_keys($group_list);

            foreach($grant as $srl) if(in_array($srl, $group_srls)) return true;
            return false;
        }

        /**
         * @brief 특정 회원의 플래닛 정보 얻기
         * 회원 번호를 입력하지 않으면 현재 로그인 사용자의 플래닛 정보를 구함
         **/
        function getMemberPlanet($member_srl = 0) {
            if(!$member_srl && !Context::get('is_logged')) return new PlanetInfo();

            if(!$member_srl) {
                $logged_info = Context::get('logged_info');
                $args->member_srl = $logged_info->member_srl;
            } else {
                $args->member_srl = $member_srl;
            }

            $output = executeQuery('planet.getMemberPlanet', $args);
            if(!$output->toBool() || !$output->data) return new PlanetInfo();

            $planet = $output->data;
            $output = $this->getSMSUser($planet);
            
            if(strlen($output->data->phone_number)==10){
                $planet->phone_number = array(substr($output->data->phone_number,0,3),substr($output->data->phone_number,3,3),substr($output->data->phone_number,6,4));
            }else if(strlen($output->data->phone_number)== 11){
                $planet->phone_number = array(substr($output->data->phone_number,0,3),substr($output->data->phone_number,3,4),substr($output->data->phone_number,7,4));
            }else{
                $planet->phone_number = array();
            }

            $oPlanet = new PlanetInfo();
            $oPlanet->setAttribute($planet);

            return $oPlanet;
        }

        /**
         * @brief 플래닛 목록 return
         **/
        function getPlanetList($list_count=20, $page=1, $sort_index = 'module_srl') {
            if(!in_array($sort_index, array('module_srl'))) $sort_index = 'module_srl';
            $args->sort_index = $sort_index;
            $args->list_count = $list_count;
            $args->page = $page;
            $output = executeQueryArray('planet.getPlanetList', $args);

            if(!$output->toBool()) return $output;

            if(count($output->data)) {
                foreach($output->data as $key => $val) {
                    $oPlanet = null;
                    $oPlanet = new PlanetInfo();
                    $oPlanet->setAttribute($val);
                    $output->data[$key] = null;
                    $output->data[$key] = $oPlanet;
                }
            }
            return $output;
        }

        /**
         * @brief 플래닛 개별 정보 return
         **/
        function getPlanet($module_srl) {
            return new PlanetInfo($module_srl);
        }

        /**
         * @brief 플래닛 태그 return
         **/
        function getPlanetTags($module_srl) {
            $args->module_srl = $module_srl;
            $output = executeQueryArray('planet.getPlanetTag', $args);
            if(!$output->toBool() || !$output->data) return array();

            $tags = array();
            foreach($output->data as $key => $val) $tags[] = $val->tag;
            return $tags;
        }

        /**
         * @brief 특정 회원의 플래닛 생성 개수 return
         **/
        function getPlanetCount($member_srl = null) {
            if(!$member_srl) {
                $logged_info = Context::get('logged_info');
                $member_srl = $logged_info->member_srl;
            }
            if(!$member_srl) return null;

            $args->member_srl = $member_srl;
            $output = executeQuery('planet.getPlanetCount',$args);
            return $output->data->count;
        }

        /**
         * @brief 최신 업데이트 글 추출
         * mid : 대상 플래닛, null이면 전체 글 대상
         * date : 선택된 일자(필수값, 없으면 오늘을 대상으로 함)
         * page : 페이지 번호
         * list_count : 추출 대상 수
         **/
        function getNewestContentList($mid = null, $date = null, $page=1, $list_count = 10, $sort_index = 'documents.list_order', $order = 'asc',$tag=null) {
            if(!$page) $page = 1;
            if(!$date) $date = date("Ymd");

            // 전체 글을 추출 (module='planet'에 대해서 추출해야 하기에 document 모델을 사용하지 않음)
            if($mid) $args->mid = $mid;
            $args->date = $date;
            $args->page = $page;
            $args->sort_index = $sort_index;
            $args->order = $order;
            $args->list_count = $list_count;
            if($args->sort_index == 'documents.voted_count') $args->voted_count = 1;
            elseif($args->sort_index == 'documents.comment_count') $args->comment_count = 1;

            if($tag){
                $args->tag = $tag;
                $output = executeQueryArray('planet.getPlanetNewestTagSearchContentList', $args);
            }else{
                $output = executeQueryArray('planet.getPlanetNewestContentList', $args);
            }
            if(!$output->toBool()) return $output;
            if(count($output->data)) {
                foreach($output->data as $key => $val) {
                    unset($oPlanet);
                    $oPlanet = new PlanetItem();
                    $oPlanet->setAttribute($val);
                    $output->data[$key] = $oPlanet;
                }
            }
            return $output;
        }

        /**
         * @brief 메인 추출용 각 플래닛별 최신글 추출
         **/
        function getHomeContentList($date, $page, $list_count = 10) {
            // 즐찾 플래닛 추출
            $args->date = $date;
            $args->page = $page?$page:1;
            $args->list_count = $list_count;
            $args->page_count = 10;
            $args->sort_index = "documents.list_order";

            $output = executeQueryArray('planet.getHomeContentList', $args);
            if(!$output->toBool()||!count($output->data)) return $output;

            $document_indexes = $document_srls = array();
            foreach($output->data as $key => $val) {
                $document_srls[] = $val->document_srl;
                $document_indexes[$val->document_srl] = $key;
            }

            $content_args->document_srls = implode(',',$document_srls);
            $content_output = executeQueryArray('planet.getContents', $content_args);
            if(!$content_output->toBool() || !$content_output->data) return $content_output;

            $output->data = null;
            foreach($content_output->data as $val) {
                $oPlanet = null;
                $oPlanet = new PlanetItem();
                $oPlanet->setAttribute($val);
                $output->data[ $document_indexes[$val->document_srl] ] = $oPlanet;
            }

            return $output;
        }

        /**
         * @brief 태그/글/인물태그 검색 결과 return
         **/
        function getSearchResultCount($module_srl, $search_keyword) {
            $result->tag = 0;
            $result->content = 0;
            $result->planetTag = 0;

            if(!$search_keyword) return $result;

            if($module_srl) $args->module_srl = $module_srl;

            $result->tag = $this->getTagSearchResultCount($module_srl, $search_keyword);
            $result->planetTag = $this->getPlanetTagSearchResultCount($module_srl, $search_keyword);
            $result->content = $this->getContentSearchResultCount($module_srl, $search_keyword);

            return $result;
        }


        function getTagSearchResultCount($module_srl, $search_keyword) {
            if(!$search_keyword) return $result;
            if($module_srl) $args->module_srl = $module_srl;
            $args->search_keyword = $search_keyword;
            $output = executeQuery('planet.getTagSearchResult', $args);
            return $output->data->count;
        }

        function getContentSearchResultCount($module_srl, $search_keyword) {
            if(!$search_keyword) return $result;
            if($module_srl) $args->module_srl = $module_srl;
            $args->search_keyword = $search_keyword;
            $search_keyword = str_replace(' ','%',$search_keyword);
            $args->search_keyword = $search_keyword;

            $output = executeQuery('planet.getContentSearchResult', $args);
            return $output->data->count;
        }


        function getPlanetTagSearchResultCount($module_srl, $search_keyword) {
            if(!$search_keyword) return $result;
            if($module_srl) $args->module_srl = $module_srl;
            $args->search_keyword = $search_keyword;
            $output = executeQuery('planet.getPlanetTagSearchResult', $args);
            return $output->data->count;
        }


        /**
         * @brief 태그/글 검색
         **/
        function getContentList($module_srl = 0, $search_target = 'tag', $search_keyword = '', $page = 1, $list_count = 10){
            if($module_srl) {
                if(is_array($module_srl)) $args->module_srl = implode(',', $module_srl);
                else $args->module_srl = $module_srl;
            }

            $args->page = $page?$page:1;
            $args->list_count = $list_count;
            $args->page_count = 10;

            // 검색 옵션 정리
            if($search_target && $search_keyword) {
                switch($search_target) {
                    case 'content' :
                            $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_content = $search_keyword;
                            $output = executeQueryArray('planet.getContentList', $args);
                        break;
                    case 'tag' :
                            $args->s_tags = str_replace(' ','%',$search_keyword);
                            $output = executeQueryArray('planet.getContentListSearchTag', $args);
                        break;
                }
            }else{
                $output = executeQueryArray('planet.getContentList', $args);
            }


            if(!$output->toBool()||!count($output->data)) return $output;

            $idx = 0;
            $data = $output->data;
            unset($output->data);

            if(!isset($virtual_number))
            {
                $keys = array_keys($data);
                $virtual_number = $keys[0];
            }

            foreach($data as $key => $attribute) {
                $document_srl = $attribute->document_srl;
                $oPlanet = null;
                $oPlanet = new PlanetItem();
                $oPlanet->setAttribute($attribute);
                if($this->grant->manager) $oPlanet->setGrant();

                $output->data[$virtual_number] = $oPlanet;
                $virtual_number --;

            }
            return $output;
        }

        /**
         * @brief 플래닛 태그 검색 return
         **/
        function getPlanetTagList($search_keyword, $page, $list_count = 10) {
            $args->page = $page?$page:1;
            $args->list_count = $list_count;
            $args->page_count = 10;
            $args->search_keyword = $search_keyword;

            $output = executeQueryArray('planet.getPlanetTagList', $args);
            if(!$output->toBool()||!count($output->data)) return $output;

            foreach($output->data as $key => $val) {
                $output->data[$key] = $this->getPlanet($val->module_srl);
            }

            return $output;
        }

        /**
         * @breif 회원 - 즐찾 return
         **/
        function getFavoriteContentList($module_srl, $page=1, $list_count =10) {
            // 즐찾 플래닛 추출
            $args->module_srl = $module_srl;
            $args->page = $page?$page:1;
            $args->list_count = $list_count;
            $args->page_count = 10;

            $output = executeQueryArray('planet.getFavoriteContentList', $args);
            if(!$output->toBool()||!count($output->data)) return $output;

            foreach($output->data as $key => $val) {
                $oPlanet = null;
                $oPlanet = new PlanetItem();
                $oPlanet->setAttribute($val);
                $output->data[$key] = $oPlanet;
            }

            return $output;
        }

        /**
         * @brief 즐찾에 추가되어 있는지를 확인
         **/
        function isInsertedFavorite($module_srl, $reg_planet_srl) {
            $args->module_srl = $module_srl;
            $args->reg_planet_srl = $reg_planet_srl;
            $output = executeQuery('planet.getMyFavorite', $args);
            if($output->data->count>0) return true;
            return false;
        }

        /**
         * @brief 회원 - 플래닛 메모 목록 return
         **/
        function getMemoList($module_srl, $page=1) {
            if(!$module_srl) return;

            $args->module_srl = $module_srl;
            $args->page = $page;
            return executeQueryArray('planet.getPlanetMemoList', $args);
        }

        /**
         * @brief 메모 목록 html return action
         **/
        function getPlanetMemoList() {
            $target_module_srl = Context::get('target_module_srl');
            if(!$target_module_srl) return;
            $page = Context::get('page');

            Context::set('planet', $this->getPlanet($target_module_srl));
            Context::set('myplanet', $this->getMemberPlanet());

            $this->add('tpl', $this->getMemoHtml($target_module_srl, $page));
        }

        /**
         * @brief 메모 목록 html 생성
         **/
        function getMemoHtml($module_srl, $page=1) {
            // 메모 목록을 구함
            $output = $this->getMemoList($module_srl, $page);
            Context::set('memo_list', $output->data);
            Context::set('memo_navigation', $output->page_navigation);

            $planet = $this->getPlanet($module_srl);
            $logged_info = Context::get('logged_info');
            Context::set('myplanet', $this->getMemberPlanet());
            Context::set('planet', $planet);

            // 스킨 경로를 구함
            $config = $this->getPlanetConfig();
            if(!$this->module_info->skin) $this->module_info->skin = $config->planet_default_skin;
            $template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);

            // template 파일을 직접 컴파일한후 tpl변수에 담아서 return한다.
            $oTemplate = &TemplateHandler::getInstance();
            $tpl = $oTemplate->compile($template_path, 'memo_list');
            return $tpl;
        }

        /**
         * @brief 관심태그 html 목록 return
         **/
        function getInterestTagsHtml($module_srl) {
            $interest_tags = $this->getInterestTags($module_srl);
            Context::set('interest_tags', $interest_tags);

            $logged_info = Context::get('logged_info');
            $planet = $this->getPlanet($module_srl);
            Context::set('planet', $planet);

            // 스킨 경로를 구함
            $config = $this->getPlanetConfig();
            if(!$this->module_info->skin) $this->module_info->skin = $config->planet_default_skin;
            $template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);

            // template 파일을 직접 컴파일한후 tpl변수에 담아서 return한다.
            $oTemplate = &TemplateHandler::getInstance();
            $tpl = $oTemplate->compile($template_path, 'interest_tags');
            return $tpl;
        }


        /**
         * @brief 플래닛 이미지 경로 return
         **/
        function getPlanetPhotoPath($module_srl) {
            return sprintf('files/attach/images/%d/%s', $module_srl, getNumberingPath($module_srl, 3));
        }

        /**
         * @brief 플래닛 이미지 유무 체크후 경로 return
         **/
        function getPlanetPhotoSrc($module_srl) {
            $path = $this->getPlanetPhotoPath($module_srl);
            if(!is_dir($path)) return sprintf("%s%s%s", Context::getRequestUri(), $this->module_path, 'tpl/images/blank_photo.gif');
            $filename = sprintf('%s/%d.jpg', $path, $module_srl);
            if(!file_exists($filename)) return sprintf("%s%s%s", Context::getRequestUri(), $this->module_path, 'tpl/images/blank_photo.gif');
            $src = Context::getRequestUri().$filename."?rnd=".filemtime($filename);
            return $src;
        }

        /**
         * @brief 관심태그 가져오기
         **/
        function getInterestTags($module_srl) {
            $args->module_srl = $module_srl;
            $output = executeQueryArray('planet.getInterestTags', $args);
            if(!$output->toBool()||!$output->data) return array();

            $result = array();
            foreach($output->data as $key => $val) $result[] = $val->tag;
            return $result;
        }


        /**
         * @brief 회원 - 플래닛 댓글 목록 return
         **/
        function getReplyList($document_srl, $page=1) {
            if(!$document_srl) return;

            // 해당 문서의 모듈에 해당하는 댓글 수를 구함
            $oDocumentModel = &getModel('document');
            $oDocument = $oDocumentModel->getDocument($document_srl);

            // 문서가 존재하지 않으면 return~
            if(!$oDocument->isExists()) return;

            // 정해진 수에 따라 목록을 구해옴
            $args->document_srl = $document_srl;
            $output = executeQueryArray('planet.getPlanetComments', $args);
            if($output->data) {
                foreach($output->data as $key => $val) {
                    $output->data[$key]->content = preg_replace('/"([^"]*)":(http|ftp|https|mms)([^ ]+)/is','<a href="$2$3" onclick="window.open(this.href);return false;">$1</a>$4', $val->content);
                    $output->data[$key]->content = str_replace('...', '…', $output->data[$key]->content);
                    $output->data[$key]->content = str_replace('--', '—', $output->data[$key]->content);

                }
            }

            $logged_info = Context::get('logged_info');
            if($oDocument->get('member_srl')==$logged_info->member_srl) {
                $args->module_srl = $oDocument->get('module_srl');
                $args->document_srl = $oDocument->get('document_srl');
                executeQuery('planet.deleteCatch', $args);
            }

            // 쿼리 결과에서 오류가 생기면 그냥 return
            if(!$output->toBool()) return;

            return $output;
        }

        /**
         * @brief 댓글  목록 html return action
         **/
        function getPlanetReplyList() {
            $document_srl = Context::get('document_srl');
            if(!$document_srl) return;
            $page = Context::get('page');

            Context::set('planet', $this->getPlanet($document_srl));
            Context::set('myplanet', $this->getMemberPlanet());

            $this->add('document_srl',$document_srl);
            $this->add('tpl', $this->getReplyHtml($document_srl));
        }

        /**
         * @brief 댓글  목록 html 생성
         **/
        function getReplyHtml($document_srl, $page=1) {
            // 메모 목록을 구함
            $output = $this->getReplyList($document_srl);
            Context::set('reply_list', $output->data);

            // 스킨 경로를 구함
            $config = $this->getPlanetConfig();
            if(!$this->module_info->skin) $this->module_info->skin = $config->planet_default_skin;
            $template_path = sprintf("%sskins/%s/",$this->module_path, $this->module_info->skin);

            // template 파일을 직접 컴파일한후 tpl변수에 담아서 return한다.
            $oTemplate = &TemplateHandler::getInstance();
            $tpl = $oTemplate->compile($template_path, 'reply_list');

            return $tpl;
        }


        /**
         * @brief SMS가 등록된 사용자를 가져온다
         * $args->phone_number 또는 $args->member_srl
         **/
        function getSMSUser($args){
            $output = executeQuery('planet.getSMSUser',$args);
            return $output;
        }


        function getSMSRecv($phone_number){
            $args->phone_number = $phone_number;
            $output = executeQueryArray('planet.getSMSRecv',$args);
            return $output;
        }
    }
?>
