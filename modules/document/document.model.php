<?php
    /**
     * @class  documentModel
     * @author zero (zero@nzeo.com)
     * @brief  document 모듈의 model 클래스
     **/

    class documentModel extends document {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief document에 대한 권한을 세션값으로 체크
         **/
        function isGranted($document_srl) {
            return $_SESSION['own_document'][$document_srl];
        }

        /**
         * @brief 문서 가져오기
         **/
        function getDocument($document_srl=0, $is_admin = false) {
            if(!$document_srl) return new documentItem();

            if(!$GLOBALS['__DocumentItem__'][$document_srl]) {
                $oDocument = new documentItem($document_srl);
                if($is_admin) $oDocument->setGrant();
                $GLOBALS['__DocumentItem__'][$document_srl] = $oDocument;
            }

            return $GLOBALS['__DocumentItem__'][$document_srl];
       }

        /**
         * @brief 선택된 게시물의 팝업메뉴 표시
         *
         * 인쇄, 스크랩, 추천, 비추천, 신고 기능 추가
         **/
        function getDocumentMenu() {

            // 요청된 게시물 번호와 현재 로그인 정보 구함
            $document_srl = Context::get('target_srl');
            $mid = Context::get('cur_mid');
            $logged_info = Context::get('logged_info');
            $act = Context::get('cur_act');
            
            // menu_list 에 "표시할글,target,url" 을 배열로 넣는다
            $menu_list = array();

            // trigger 호출
            ModuleHandler::triggerCall('document.getDocumentMenu', 'before', $menu_list);

            $oDocumentController = &getController('document');

            // 회원이어야만 가능한 기능
            if($logged_info->member_srl) {

                // 추천 버튼 추가
                $url = sprintf("doCallModuleAction('document','procDocumentVoteUp','%s')", $document_srl);
                $oDocumentController->addDocumentPopupMenu($url,'cmd_vote','./modules/document/tpl/icons/vote_up.gif','javascript');

                // 비추천 버튼 추가
                $url= sprintf("doCallModuleAction('document','procDocumentVoteDown','%s')", $document_srl);
                $oDocumentController->addDocumentPopupMenu($url,'cmd_vote_down','./modules/document/tpl/icons/vote_down.gif','javascript');

                // 신고 기능 추가
                $url = sprintf("doCallModuleAction('document','procDocumentDeclare','%s')", $document_srl);
                $oDocumentController->addDocumentPopupMenu($url,'cmd_declare','./modules/document/tpl/icons/declare.gif','javascript');

                // 스크랩 버튼 추가
                $url = sprintf("doCallModuleAction('member','procMemberScrapDocument','%s')", $document_srl);
                $oDocumentController->addDocumentPopupMenu($url,'cmd_scrap','./modules/document/tpl/icons/scrap.gif','javascript');
            }

            // 인쇄 버튼 추가
            $url = getUrl('','module','document','act','dispDocumentPrint','document_srl',$document_srl);
            $oDocumentController->addDocumentPopupMenu($url,'cmd_print','./modules/document/tpl/icons/print.gif','printDocument');

            // trigger 호출 (after)
            ModuleHandler::triggerCall('document.getDocumentMenu', 'after', $menu_list);

            // 관리자일 경우 ip로 글 찾기
            if($logged_info->is_admin == 'Y') {
                $oDocumentModel = &getModel('document');
                $oDocument = $oDocumentModel->getDocument($document_srl);

                if($oDocument->isExists()) {
                    // ip주소에 해당하는 글 찾기
                    $url = getUrl('','module','admin','act','dispDocumentAdminList','search_target','ipaddress','search_keyword',$oDocument->get('ipaddress'));
                    $icon_path = './modules/member/tpl/images/icon_management.gif';
                    $oDocumentController->addDocumentPopupMenu($url,'cmd_search_by_ipaddress',$icon_path,'TraceByIpaddress');

                    $url = sprintf("var params = new Array(); params['ipaddress']='%s'; exec_xml('spamfilter', 'procSpamfilterAdminInsertDeniedIP', params, completeCallModuleAction)", $oDocument-> getIpAddress());
                    $oDocumentController->addDocumentPopupMenu($url,'cmd_add_ip_to_spamfilter','./modules/document/tpl/icons/declare.gif','javascript');
                }
            }

            // 팝업메뉴의 언어 변경
            $menus = Context::get('document_popup_menu_list');
            $menus_count = count($menus);
            for($i=0;$i<$menus_count;$i++) {
                $menus[$i]->str = Context::getLang($menus[$i]->str);
            }

            // 최종적으로 정리된 팝업메뉴 목록을 구함
            $this->add('menus', $menus);
        }

        /**
         * @brief 여러개의 문서들을 가져옴 (페이징 아님)
         **/
        function getDocuments($document_srls, $is_admin = false) {
            if(is_array($document_srls)) {
                $list_count = count($document_srls);
                $document_srls = implode(',',$document_srls);
            } else {
                $list_count = 1;
            }
            $args->document_srls = $document_srls;
            $args->list_count = $list_count;

            $output = executeQuery('document.getDocuments', $args);
            $document_list = $output->data;
            if(!$document_list) return;
            if(!is_array($document_list)) $document_list = array($document_list);

            $document_count = count($document_list);
            foreach($document_list as $key => $attribute) {
                if(!$attribute->document_srl) continue;
                $oDocument = null;
                $oDocument = new documentItem();
                $oDocument->setAttribute($attribute);
                if($is_admin) $oDocument->setGrant();

                $result[$attribute->document_srl] = $oDocument;
            }
            return $result;
        }

        /**
         * @brief module_srl값을 가지는 문서의 목록을 가져옴
         **/
        function getDocumentList($obj, $except_notice = false) {
            // 정렬 대상과 순서 체크 
            if(!in_array($obj->sort_index, array('list_order','regdate','last_update','update_order','readed_count','voted_count','comment_count','trackback_count','uploaded_count','title'))) $obj->sort_index = 'list_order'; 
            if(!in_array($obj->order_type, array('desc','asc'))) $obj->order_type = 'asc'; 

            // module_srl 대신 mid가 넘어왔을 경우는 직접 module_srl을 구해줌
            if($obj->mid) {
                $oModuleModel = &getModel('module');
                $obj->module_srl = $oModuleModel->getModuleSrlByMid($obj->mid);
                unset($obj->mid);
            }

            // 넘어온 module_srl은 array일 수도 있기에 array인지를 체크 
            if(is_array($obj->module_srl)) $args->module_srl = implode(',', $obj->module_srl);
            else $args->module_srl = $obj->module_srl;

            // 변수 체크
            $args->category_srl = $obj->category_srl?$obj->category_srl:null;
            $args->sort_index = $obj->sort_index;
            $args->order_type = $obj->order_type;
            $args->page = $obj->page?$obj->page:1;
            $args->list_count = $obj->list_count?$obj->list_count:20;
            $args->page_count = $obj->page_count?$obj->page_count:10;
            $args->start_date = $obj->start_date?$obj->start_date:null;
            $args->end_date = $obj->end_date?$obj->end_date:null;
            $args->member_srl = $obj->member_srl;

            // 카테고리가 선택되어 있으면 하부 카테고리까지 모두 조건에 추가
            if($args->category_srl) {
                $category_list = $this->getCategoryList($args->module_srl);
                $category_info = $category_list[$args->category_srl];
                $category_info->childs[] = $args->category_srl;
                $args->category_srl = implode(',',$category_info->childs);
            }

            // 기본으로 사용할 query id 지정 (몇가지 검색 옵션에 따라 query id가 변경됨)
            $query_id = 'document.getDocumentList';

            // 내용검색일 경우 document division을 지정하여 검색하기 위한 처리
            $use_division = false;

            // 검색 옵션 정리
            $search_target = $obj->search_target;
            $search_keyword = $obj->search_keyword;
            if($search_target && $search_keyword) {
                switch($search_target) {
                    case 'title' :
                    case 'content' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->{"s_".$search_target} = $search_keyword;
                            $use_division = true;
                        break;
                    case 'title_content' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_title = $search_keyword;
                            $args->s_content = $search_keyword;
                            $use_division = true;
                        break;
                    case 'user_id' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_user_id = $search_keyword;
                            $args->sort_index = 'documents.'.$args->sort_index;
                        break;
                    case 'user_name' :
                    case 'nick_name' :
                    case 'email_address' :
                    case 'homepage' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->{"s_".$search_target} = $search_keyword;
                        break;
                    case 'is_notice' :
                    case 'is_secret' :
                            if($search_keyword=='N') $args->{"s_".$search_target} = 'N';
                            elseif($search_keyword=='Y') $args->{"s_".$search_target} = 'Y';
                            else $args->{"s_".$search_target} = '';
                        break;
                    case 'member_srl' :
                    case 'readed_count' :
                    case 'voted_count' :
                    case 'comment_count' :
                    case 'trackback_count' :
                    case 'uploaded_count' :
                            $args->{"s_".$search_target} = (int)$search_keyword;
                        break;
                    case 'regdate' :
                    case 'last_update' :
                    case 'ipaddress' :
                            $args->{"s_".$search_target} = $search_keyword;
                        break;
                    case 'comment' :
                            $args->s_comment = $search_keyword;
                            $query_id = 'document.getDocumentListWithinComment';
                            $use_division = true;
                        break;
                    case 'tag' :
                            $args->s_tags = str_replace(' ','%',$search_keyword);
                            $query_id = 'document.getDocumentListWithinTag';
                        break;
                    default :
                            preg_match('/^extra_vars([0-9]+)$/',$search_target,$matches);
                            if($matches[1]) {
                                $args->{"s_extra_vars".$matches[1]} = $search_keyword;
                                $use_division = true;
                            }
                        break;
                }
            }

            /**
             * division은 list_order의 asc 정렬일때만 사용할 수 있음
             **/
            if($args->sort_index != 'list_order' || $args->order_type != 'asc') $use_division = false;

            /**
             * 만약 use_division이 true일 경우 document division을 이용하도록 변경
             **/
            if($use_division) {
                // 시작 division 
                $division = (int)Context::get('division');

                // division값이 없다면 제일 상위 
                if(!$division) {
                    $division_args->module_srl = $args->module_srl;
                    $division_args->list_count = 1;
                    $division_args->sort_index = $args->sort_index;
                    $division_args->order_type = $args->order_type;
                    $output = executeQuery("document.getDocumentList", $division_args);
                    if($output->data) {
                        $item = array_pop($output->data);
                        $division = $item->list_order;
                    }
                    $division_args = null;
                }

                // 마지막 division 
                $last_division = (int)Context::get('last_division');

                // 지정된 division에서부터 5000개 후의 division값을 구함
                if(!$last_division) {
                    $last_division_args->module_srl = $args->module_srl;
                    $last_division_args->list_count = 1;
                    $last_division_args->sort_index = $args->sort_index;
                    $last_division_args->order_type = $args->order_type;
                    $last_division_args->list_order = $division;
                    $last_division_args->page = 5001;
                    $output = executeQuery("document.getDocumentDivision", $last_division_args);
                    if($output->data) {
                        $item = array_pop($output->data);
                        $last_division = $item->list_order;
                    }

                }

                // last_division 이후로 글이 있는지 확인
                if($last_division) {
                    $last_division_args = null;
                    $last_division_args->module_srl = $args->module_srl;
                    $last_division_args->list_order = $last_division;
                    $output = executeQuery("document.getDocumentDivisionCount", $last_division_args);
                    if($output->data->count<1) $last_division = null;
                }

                $args->division = $division;
                $args->last_division = $last_division;
                Context::set('division', $division);
                Context::set('last_division', $last_division);
            }

            // document.getDocumentList 쿼리 실행
            // 만약 query_id가 getDocumentListWithinComment 또는 getDocumentListWithinTag일 경우 group by 절 사용 때문에 쿼리를 한번더 수행
            if(in_array($query_id, array('document.getDocumentListWithinComment', 'document.getDocumentListWithinTag'))) {
                $group_args = clone($args);
                $group_args->sort_index = 'documents.'.$args->sort_index;
                $output = executeQueryArray($query_id, $group_args);
                if(!$output->toBool()||!count($output->data)) return $output;

                foreach($output->data as $key => $val) {
                    if($val->document_srl) $target_srls[] = $val->document_srl;
                }

                $page_navigation = $output->page_navigation;
                $keys = array_keys($output->data);
                $virtual_number = $keys[0];

                $target_args->document_srls = implode(',',$target_srls);
                $target_args->list_order = $args->sort_index;
                $target_args->order = $args->order_type;
                $target_args->list_count = $args->list_count;
                $target_args->page = 1;
                $output = executeQueryArray('document.getDocuments', $target_args);
                $output->page_navigation = $page_navigation;
                $output->total_count = $page_navigation->total_count;
                $output->total_page = $page_navigation->total_page;
                $output->page = $page_navigation->cur_page;
            } else {
                $output = executeQueryArray($query_id, $args);
            }

            // 결과가 없거나 오류 발생시 그냥 return
            if(!$output->toBool()||!count($output->data)) return $output;

            $idx = 0;
            $data = $output->data;
            unset($output->data);
            
            if(!isset($virtual_number))
            {
                $keys = array_keys($data);
                $virtual_number = $keys[0];
            }

            if($except_notice) {
                foreach($data as $key => $attribute) {
                    if($attribute->is_notice == 'Y') $virtual_number --;
                }
            }

            foreach($data as $key => $attribute) {
                if($except_notice && $attribute->is_notice == 'Y') continue;
                $document_srl = $attribute->document_srl;
                $oDocument = null;
                $oDocument = new documentItem();
                $oDocument->setAttribute($attribute);
                if($is_admin) $oDocument->setGrant();

                $output->data[$virtual_number] = $oDocument;
                $virtual_number --;
            
            }
            return $output;
        }

        /**
         * @brief module_srl값을 가지는 문서의 공지사항만 가져옴
         **/
        function getNoticeList($obj) {
            $args->module_srl = $obj->module_srl;
            $args->category_srl = $obj->category_srl;
            $args->sort_index = 'list_order';
            $args->order_type = 'asc';

            $output = executeQueryArray('document.getNoticeList', $args);

            // 결과가 없거나 오류 발생시 그냥 return
            if(!$output->toBool()||!count($output->data)) return $output;

            foreach($output->data as $key => $attribute) {
                $document_srl = $attribute->document_srl;

                $oDocument = null;
                $oDocument = new documentItem();
                $oDocument->setAttribute($attribute);

                $output->data[$key] = $oDocument;
            
            }
            return $output;
        }

        /**
         * @brief module_srl에 해당하는 문서의 전체 갯수를 가져옴
         **/
        function getDocumentCount($module_srl, $search_obj = NULL) {
            // 검색 옵션 추가
            $args->module_srl = $module_srl;
            $args->s_title = $search_obj->s_title;
            $args->s_content = $search_obj->s_content;
            $args->s_user_name = $search_obj->s_user_name;
            $args->s_member_srl = $search_obj->s_member_srl;
            $args->s_ipaddress = $search_obj->s_ipaddress;
            $args->s_regdate = $search_obj->s_regdate;
            $args->category_srl = $search_obj->category_srl;

            $output = executeQuery('document.getDocumentCount', $args);

            // 전체 갯수를 return
            $total_count = $output->data->count;
            return (int)$total_count;
        }
        /**
         * @brief 해당 document의 page 가져오기, module_srl이 없으면 전체에서..
         **/
        function getDocumentPage($oDocument, $opt) {
            // 정렬 형식에 따라서 query args 변경
            switch($opt->sort_index) {
                case 'update_order' :
                        if($opt->order_type == 'desc') $args->rev_update_order = $oDocument->get('update_order');
                        else $args->update_order = $oDocument->get('update_order');
                    break;
                case 'regdate' :
                        if($opt->order_type == 'asc') $args->rev_regdate = $oDocument->get('regdate');
                        else $args->regdate = $oDocument->get('regdate');
                    break;
                case 'voted_count' :
                case 'readed_count' :
                case 'comment_count' :
                case 'title' :
                        return 1;
                    break;
                default :
                        if($opt->order_type == 'desc') $args->rev_list_order = $oDocument->get('list_order');
                        else $args->list_order = $oDocument->get('list_order');
                    break;
            }
            $args->module_srl = $oDocument->get('module_srl');
            $args->sort_index = $opt->sort_index;
            $args->order_type = $opt->order_type;

            // 전체 갯수를 구한후 해당 글의 페이지를 검색
            $output = executeQuery('document.getDocumentPage', $args);
            $count = $output->data->count;
            $page = (int)(($count-1)/$opt->list_count)+1;
            return $page;
        }

        /**
         * @brief 카테고리의 정보를 가져옴
         **/
        function getCategory($category_srl) {
            $args->category_srl = $category_srl;
            $output = executeQuery('document.getCategory', $args);

            $node = $output->data;
            if(!$node) return;

            if($node->group_srls) {
                $group_srls = explode(',',$node->group_srls);
                unset($node->group_srls);
                $node->group_srls = $group_srls;
            } else {
                unset($node->group_srls);
                $node->group_srls = array();
            }
            return $node;
        }

        /**
         * @brief 특정 카테고리에 child가 있는지 체크
         **/
        function getCategoryChlidCount($category_srl) {
            $output = executeQuery('document.getChildCategoryCount');
            if($output->data->count > 0) return true;
            return false;
        }

        /**
         * @brief 특정 모듈의 카테고리 목록을 가져옴
         * 속도나 여러가지 상황을 고려해서 카테고리 목록은 php로 생성된 script를 include하여 사용하는 것을 원칙으로 함
         **/
        function getCategoryList($module_srl) {
            // 한 페이지에서 여러번 호출될 경우를 대비해서 static var로 보관 (php4때문에 다른 방법으로 구현)
            if(!isset($this->category_list[$module_srl])) {

                // 대상 모듈의 카테고리 파일을 불러옴
                $filename = sprintf("./files/cache/document_category/%s.php", $module_srl);

                // 대상 파일이 없으면 카테고리 캐시 파일을 재생성
                if(!file_exists($filename)) {
                    $oDocumentController = &getController('document');
                    if(!$oDocumentController->makeCategoryFile($module_srl)) return array();
                }

                @include($filename);

                // 카테고리의 정리
                $document_category = array();
                $this->_arrangeCategory($document_category, $menu->list, 0);
                $this->category_list[$module_srl] = $document_category;
            }
            return $this->category_list[$module_srl];
        }

        /**
         * @brief 카테고리를 1차 배열 형식으로 변경하는 내부 method
         **/
        function _arrangeCategory(&$document_category, $list, $depth) {
            if(!count($list)) return;
            $idx = 0;
            $list_order = array();
            foreach($list as $key => $val) {
                $obj = null;
                $obj->mid = $val['mid'];
                $obj->module_srl = $val['module_srl'];
                $obj->category_srl = $val['category_srl'];
                $obj->parent_srl = $val['parent_srl'];
                $obj->title = $obj->text = $val['text'];
                $obj->expand = $val['expand']=='Y'?true:false;
                $obj->color = $val['color'];
                $obj->document_count = $val['document_count'];
                $obj->depth = $depth;
                $obj->child_count = 0;
                $obj->childs = array();
                $obj->grant = $val['grant'];

                if(Context::get('mid') == $obj->mid && Context::get('category') == $obj->category_srl) $selected = true;
                else $selected = false;

                $obj->selected = $selected;

                $list_order[$idx++] = $obj->category_srl;

                // 부모 카테고리가 있으면 자식노드들의 데이터를 적용
                if($obj->parent_srl) {

                    $parent_srl = $obj->parent_srl;
                    $document_count = $obj->document_count;
                    $expand = $obj->expand;
                    if($selected) $expand = true;

                    while($parent_srl) {
                        $document_category[$parent_srl]->document_count += $document_count;
                        $document_category[$parent_srl]->childs[] = $obj->category_srl;
                        $document_category[$parent_srl]->child_count = count($document_category[$parent_srl]->childs);
                        if($expand) $document_category[$parent_srl]->expand = $expand;

                        $parent_srl = $document_category[$parent_srl]->parent_srl;
                    }
                }

                $document_category[$key] = $obj;

                if(count($val['list'])) $this->_arrangeCategory($document_category, $val['list'], $depth+1);
            }
            $document_category[$list_order[0]]->first = true;
            $document_category[$list_order[count($list_order)-1]]->last = true;
        }

        /**
         * @brief 카테고리에 속한 문서의 갯수를 구함
         **/
        function getCategoryDocumentCount($category_srl) {
            $args->category_srl = $category_srl;
            $output = executeQuery('document.getCategoryDocumentCount', $args);
            return (int)$output->data->count;
        }

        /**
         * @brief 문서 category정보의 xml 캐시 파일을 return
         **/
        function getCategoryXmlFile($module_srl) {
            $xml_file = sprintf('files/cache/document_category/%s.xml.php',$module_srl);
            if(!file_exists($xml_file)) {
                $oDocumentController = &getController('document');
                $oDocumentController->makeCategoryFile($module_srl);
            }
            return $xml_file;
        } 

        /**
         * @brief 문서 category정보의 php 캐시 파일을 return
         **/
        function getCategoryPhpFile($module_srl) {
            $php_file = sprintf('files/cache/document_category/%s.php',$module_srl);
            if(!file_exists($php_file)) {
                $oDocumentController = &getController('document');
                $oDocumentController->makeCategoryFile($module_srl);
            }
            return $php_file;
        } 

        /**
         * @brief 월별 글 보관현황을 가져옴
         **/
        function getMonthlyArchivedList($obj) {
            if($obj->mid) {
                $oModuleModel = &getModel('module');
                $obj->module_srl = $oModuleModel->getModuleSrlByMid($obj->mid);
                unset($obj->mid);
            }

            // 넘어온 module_srl은 array일 수도 있기에 array인지를 체크 
            if(is_array($obj->module_srl)) $args->module_srl = implode(',', $obj->module_srl);
            else $args->module_srl = $obj->module_srl;

            $output = executeQuery('document.getMonthlyArchivedList', $args);
            if(!$output->toBool()||!$output->data) return $output;

            if(!is_array($output->data)) $output->data = array($output->data);

            return $output;
        }

        /**
         * @brief 특정달의 일별 글 현황을 가져옴
         **/
        function getDailyArchivedList($obj) {
            if($obj->mid) {
                $oModuleModel = &getModel('module');
                $obj->module_srl = $oModuleModel->getModuleSrlByMid($obj->mid);
                unset($obj->mid);
            }

            // 넘어온 module_srl은 array일 수도 있기에 array인지를 체크 
            if(is_array($obj->module_srl)) $args->module_srl = implode(',', $obj->module_srl);
            else $args->module_srl = $obj->module_srl;
            $args->regdate = $obj->regdate;

            $output = executeQuery('document.getDailyArchivedList', $args);
            if(!$output->toBool()) return $output;

            if(!is_array($output->data)) $output->data = array($output->data);

            return $output;
        }

        /**
         * @brief 특정 모듈의 분류를 구함
         **/
        function getDocumentCategories() {
            $module_srl = Context::get('module_srl');
            $categories= $this->getCategoryList($module_srl);
            $lang = Context::get('lang');

            // 분류 없음 추가
            $output = "0,0,{$lang->none_category}\n";
            if($categories){
                foreach($categories as $category_srl => $category) {
                    $output .= sprintf("%d,%d,%s\n",$category_srl, $category->depth,$category->title);
                }
            }
            $this->add('categories', $output);
        }

        /**
         * @brief 문서 설정 정보를 구함
         **/
        function getDocumentConfig() {
            if(!$GLOBLAS['__document_config__']) {
                $oModuleModel = &getModel('module');
                $config = $oModuleModel->getModuleConfig('document');
                if(!$config->thumbnail_type) $config->thumbnail_type = 'crop';
                $GLOBLAS['__document_config__'] = $config;
            }
            return $GLOBLAS['__document_config__'];
        }
    }
?>
