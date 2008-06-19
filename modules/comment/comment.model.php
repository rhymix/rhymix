<?php
    /**
     * @class  commentModel
     * @author zero (zero@nzeo.com)
     * @brief  comment 모듈의 model class
     **/

    class commentModel extends comment {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 선택된 게시물의 팝업메뉴 표시
         *
         * 인쇄, 스크랩, 추천, 비추천, 신고 기능 추가
         **/
        function getCommentMenu() {

            // 요청된 게시물 번호와 현재 로그인 정보 구함
            $comment_srl = Context::get('target_srl');
            $mid = Context::get('cur_mid');
            $logged_info = Context::get('logged_info');
            $act = Context::get('cur_act');
            
            // menu_list 에 "표시할글,target,url" 을 배열로 넣는다
            $menu_list = array();

            // trigger 호출
            ModuleHandler::triggerCall('comment.getCommentMenu', 'before', $menu_list);

            $oCommentController = &getController('comment');

            // 회원이어야만 가능한 기능
            if($logged_info->member_srl) {

                // 추천 버튼 추가
                $url = sprintf("doCallModuleAction('comment','procCommentVoteUp','%s')", $comment_srl);
                $oCommentController->addCommentPopupMenu($url,'cmd_vote','./modules/document/tpl/icons/vote_up.gif','javascript');

                // 비추천 버튼 추가
                $url = sprintf("doCallModuleAction('comment','procCommentVoteDown','%s')", $comment_srl);
                $oCommentController->addCommentPopupMenu($url,'cmd_vote_down','./modules/document/tpl/icons/vote_down.gif','javascript');

                // 신고 기능 추가
                $url = sprintf("doCallModuleAction('comment','procCommentDeclare','%s')", $comment_srl);
                $oCommentController->addCommentPopupMenu($url,'cmd_declare','./modules/document/tpl/icons/declare.gif','javascript');
            }

            // trigger 호출 (after)
            ModuleHandler::triggerCall('comment.getCommentMenu', 'after', $menu_list);

            // 관리자일 경우 ip로 글 찾기
            if($logged_info->is_admin == 'Y') {
                $oCommentModel = &getModel('comment');
                $oComment = $oCommentModel->getComment($comment_srl);

                if($oComment->isExists()) {
                    // ip주소에 해당하는 글 찾기
                    $url = getUrl('','module','admin','act','dispCommentAdminList','search_target','ipaddress','search_keyword',$oComment->get('ipaddress'));
                    $icon_path = './modules/member/tpl/images/icon_management.gif';
                    $oCommentController->addCommentPopupMenu($url,'cmd_search_by_ipaddress',$icon_path,'TraceByIpaddress');
                }
            }

            // 팝업메뉴의 언어 변경
            $menus = Context::get('comment_popup_menu_list');
            $menus_count = count($menus);
            for($i=0;$i<$menus_count;$i++) {
                $menus[$i]->str = Context::getLang($menus[$i]->str);
            }

            // 최종적으로 정리된 팝업메뉴 목록을 구함
            $this->add('menus', $menus);
        }


        /**
         * @brief comment_srl에 권한이 있는지 체크
         *
         * 세션 정보만 이용
         **/
        function isGranted($comment_srl) {
            return $_SESSION['own_comment'][$comment_srl];
        }

        /**
         * @brief 자식 답글의 갯수 리턴
         **/
        function getChildCommentCount($comment_srl) {
            $args->comment_srl = $comment_srl;
            $output = executeQuery('comment.getChildCommentCount', $args);
            return (int)$output->data->count;
        }

        /**
         * @brief 댓글 가져오기
         **/
        function getComment($comment_srl=0, $is_admin = false) {
            $oComment = new commentItem($comment_srl);
            if($is_admin) $oComment->setGrant();

            return $oComment;
        }

        /**
         * @brief 여러개의 댓글들을 가져옴 (페이징 아님)
         **/
        function getComments($comment_srl_list) {
            if(is_array($comment_srl_list)) $comment_srls = implode(',',$comment_srl_list);

            // DB에서 가져옴
            $args->comment_srls = $comment_srls;
            $output = executeQuery('comment.getComments', $args);
            if(!$output->toBool()) return;
            $comment_list = $output->data;
            if(!$comment_list) return;
            if(!is_array($comment_list)) $comment_list = array($comment_list);

            $comment_count = count($comment_list);
            foreach($comment_list as $key => $attribute) {
                if(!$attribute->comment_srl) continue;
                $oComment = null;
                $oComment = new commentItem();
                $oComment->setAttribute($attribute);
                if($is_admin) $oComment->setGrant();

                $result[$attribute->comment_srl] = $oComment;
            }
            return $result;
        }

        /**
         * @brief document_srl 에 해당하는 댓글의 전체 갯수를 가져옴
         **/
        function getCommentCount($document_srl) {
            $args->document_srl = $document_srl;
            $output = executeQuery('comment.getCommentCount', $args);
            $total_count = $output->data->count;
            return (int)$total_count;
        }

        /** 
         * @brief mid 에 해당하는 댓글을 가져옴
         **/
        function getNewestCommentList($obj) {
            if($obj->mid) {
                $oModuleModel = &getModel('module');
                $obj->module_srl = $oModuleModel->getModuleSrlByMid($obj->mid);
                unset($obj->mid);
            }

            // 넘어온 module_srl은 array일 수도 있기에 array인지를 체크 
            if(is_array($obj->module_srl)) $args->module_srl = implode(',', $obj->module_srl);
            else $args->module_srl = $obj->module_srl;
            $args->list_count = $obj->list_count;

            $output = executeQuery('comment.getNewestCommentList', $args);
            if(!$output->toBool()) return $output;

            $comment_list = $output->data;
            if($comment_list) {
                if(!is_array($comment_list)) $comment_list = array($comment_list);
                $comment_count = count($comment_list);
                foreach($comment_list as $key => $attribute) {
                    if(!$attribute->comment_srl) continue;
                    $oComment = null;
                    $oComment = new commentItem();
                    $oComment->setAttribute($attribute);

                    $result[$key] = $oComment;
                }
                $output->data = $result;
            }
            return $result;
        }

        /** 
         * @brief document_srl에 해당하는 문서의 댓글 목록을 가져옴
         **/
        function getCommentList($document_srl, $page = 0, $is_admin = false) {
            // 해당 문서의 모듈에 해당하는 댓글 수를 구함
            $oDocumentModel = &getModel('document');
            $oDocument = $oDocumentModel->getDocument($document_srl);

            // 문서가 존재하지 않으면 return~
            if(!$oDocument->isExists()) return;

            // 댓글수가 없으면 return~
            if($oDocument->getCommentCount()<1) return;

            // 정해진 댓글수에 따른 댓글 목록 구함
            $module_srl = $oDocument->get('module_srl');
            $comment_config = $this->getCommentConfig($module_srl);
            $comment_count = $comment_config->comment_count;
            if(!$comment_count) $comment_count = 50;

            // 페이지가 없으면 제일 뒤 페이지를 구함
            if(!$page) $page = (int)( ($oDocument->getCommentCount()-1) / $comment_count) + 1;                

            // 정해진 수에 따라 목록을 구해옴
            $args->document_srl = $document_srl;
            $args->list_count = $comment_count;
            $args->page = $page;
            $args->page_count = 10;
            $output = executeQueryArray('comment.getCommentPageList', $args);

            // 쿼리 결과에서 오류가 생기면 그냥 return
            if(!$output->toBool()) return;

            // 만약 구해온 결과값이 저장된 댓글수와 다르다면 기존의 데이터로 판단하고 댓글 목록 테이블에 데이터 입력
            if(!$output->data) {
                $this->fixCommentList($oDocument->get('module_srl'), $document_srl);
                $output = executeQueryArray('comment.getCommentPageList', $args);
                if(!$output->toBool()) return;
            }

            return $output;
        }
            
        /**
         * @brief document_srl에 해당하는 댓글 목록을 갱신
         * 정식버전 이전에 사용되던 데이터를 위한 처리
         **/
        function fixCommentList($module_srl, $document_srl) {
            // 일괄 작업이라서 lock 파일을 생성하여 중복 작업이 되지 않도록 한다
            $lock_file = "./files/cache/tmp/lock.".$document_srl;
            if(file_exists($lock_file) && filemtime($lock_file)+60*60*10<time()) return;
            FileHandler::writeFile($lock_file, '');

            // 목록을 구함
            $args->document_srl = $document_srl;
            $args->list_order = 'list_order';
            $output = executeQuery('comment.getCommentList', $args);
            if(!$output->toBool()) return $output;

            $source_list = $output->data;
            if(!is_array($source_list)) $source_list = array($source_list);

            // 댓글를 계층형 구조로 정렬
            $comment_count = count($source_list);

            $root = NULL;
            $list = NULL;
            $comment_list = array();

            // 로그인 사용자의 경우 로그인 정보를 일단 구해 놓음
            $logged_info = Context::get('logged_info');


            // loop를 돌면서 코멘트의 계층 구조 만듬 
            for($i=$comment_count-1;$i>=0;$i--) {
                $comment_srl = $source_list[$i]->comment_srl;
                $parent_srl = $source_list[$i]->parent_srl;
                if(!$comment_srl) continue;

                // 목록을 만듬
                $list[$comment_srl] = $source_list[$i];

                if($parent_srl) {
                    $list[$parent_srl]->child[] = &$list[$comment_srl];
                } else {
                    $root->child[] = &$list[$comment_srl];
                }
            }
            $this->_arrangeComment($comment_list, $root->child, 0, null);

            // 구해진 값을 db에 입력함
            if(count($comment_list)) {
                foreach($comment_list as $comment_srl => $item) {
                    $comment_args = null;
                    $comment_args->comment_srl = $comment_srl;
                    $comment_args->document_srl = $document_srl;
                    $comment_args->head = $item->head;
                    $comment_args->arrange = $item->arrange;
                    $comment_args->module_srl = $module_srl;
                    $comment_args->regdate = $item->regdate;
                    $comment_args->depth = $item->depth;

                    executeQuery('comment.insertCommentList', $comment_args);
                }
            }

            // 성공시 lock파일 제거
            @unlink($lock_file);
        }

        /**
         * @brief 댓글을 계층형으로 재배치
         **/
        function _arrangeComment(&$comment_list, $list, $depth, $parent = null) {
            if(!count($list)) return;
            foreach($list as $key => $val) {

                if($parent) $val->head = $parent->head;
                else $val->head = $val->comment_srl;
                $val->arrange = count($comment_list)+1;

                if($val->child) {
                    $val->depth = $depth;
                    $comment_list[$val->comment_srl] = $val;
                    $this->_arrangeComment($comment_list,$val->child,$depth+1, $val);
                    unset($val->child);
                } else {
                    $val->depth = $depth;
                    $comment_list[$val->comment_srl] = $val;
                }
            }
        }

        /**
         * @brief 모든 댓글를 시간 역순으로 가져옴 (관리자용)
         **/
        function getTotalCommentList($obj) {
            $query_id = 'comment.getTotalCommentList';

            // 변수 설정
            $args->sort_index = 'list_order';
            $args->page = $obj->page?$obj->page:1;
            $args->list_count = $obj->list_count?$obj->list_count:20;
            $args->page_count = $obj->page_count?$obj->page_count:10;
            $args->s_module_srl = $obj->module_srl;

            // 검색 옵션 정리
            $search_target = trim(Context::get('search_target'));
            $search_keyword = trim(Context::get('search_keyword'));
            if($search_target && $search_keyword) {
                switch($search_target) {
                    case 'content' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_content = $search_keyword;
                        break;
                    case 'user_id' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_user_id = $search_keyword;
                            $query_id = 'comment.getTotalCommentListWithinMember';
                            $args->sort_index = 'comments.list_order';
                        break;
                    case 'user_name' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_user_name = $search_keyword;
                        break;
                    case 'nick_name' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_nick_name = $search_keyword;
                        break;
                    case 'email_address' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_email_address = $search_keyword;
                        break;
                    case 'homepage' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_homepage = $search_keyword;
                        break;
                    case 'regdate' :
                            $args->s_regdate = $search_keyword;
                        break;
                    case 'last_update' :
                            $args->s_last_upate = $search_keyword;
                        break;
                    case 'ipaddress' :
                            $args->s_ipaddress= $search_keyword;
                        break;
                    case 'member_srl' :
                            $args->{"s_".$search_target} = (int)$search_keyword;
                        break;
                }
            }

            // comment.getTotalCommentList 쿼리 실행
            $output = executeQuery($query_id, $args);

            // 결과가 없거나 오류 발생시 그냥 return
            if(!$output->toBool()||!count($output->data)) return $output;

            return $output;
        }

        /**
         * @brief 모듈별 댓글 설정을 return
         **/
        function getCommentConfig($module_srl) {
            if(!$GLOBLAS['__comment_module_config__']) {
                // 선택된 모듈의 trackback설정을 가져옴
                $oModuleModel = &getModel('module');
                $GLOBLAS['__comment_module_config__'] = $oModuleModel->getModuleConfig('comment');
            }

            $comment_config = $GLOBLAS['__comment_module_config__']->module_config[$module_srl];

            if(!is_object($comment_config)) $comment_config = null;

            if(!isset($comment_config->comment_count)) $comment_count->comment_count = 50;

            return $comment_config;
        }

    }
?>
