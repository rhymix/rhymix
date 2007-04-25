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
        function getComment($comment_srl, $is_admin = false) {
            $args->comment_srl = $comment_srl;
            $output = executeQuery('comment.getComment', $args);
            $comment = $output->data;

            // 첨부파일 가져오기
            if($comment->uploaded_count) {
                $oFileModel = &getModel('file');
                $file_list = $oFileModel->getFiles($comment_srl, $is_admin);
                $comment->uploaded_list = $file_list;
            }

            // 로그인 사용자의 경우 로그인 정보를 일단 구해 놓음
            $logged_info = Context::get('logged_info');

            if($is_admin || $this->isGranted($comment_srl) || $comment->member_srl == $logged_info->member_srl) $comment->is_granted = true;
            return $comment;
        }

        /**
         * @brief 여러개의 댓글들을 가져옴 (페이징 아님)
         **/
        function getComments($comment_srl_list) {
            if(is_array($comment_srl_list)) $comment_srls = implode(',',$comment_srl_list);

            $args->comment_srls = $comment_srls;
            $output = executeQuery('comment.getComments', $args);
            return $output->data;
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

            return $output;
        }

        /** 
         * @brief document_srl에 해당하는 문서의 댓글 목록을 가져옴
         **/
        function getCommentList($document_srl, $is_admin = false) {
            $args->document_srl = $document_srl;
            $args->list_order = 'list_order';
            $output = executeQuery('comment.getCommentList', $args);
            if(!$output->toBool()) return $output;

            $source_list= $output->data;
            if(!is_array($source_list)) $source_list = array($source_list);

            // 댓글를 계층형 구조로 정렬
            $comment_count = count($source_list);

            $root = NULL;
            $list = NULL;

            // 로그인 사용자의 경우 로그인 정보를 일단 구해 놓음
            $logged_info = Context::get('logged_info');

            // 첨부파일이 있을 경우를 대비한 File 모듈의 model객체 미리 생성 
            $oFileModel = &getModel('file');

            // loop를 돌면서 코멘트의 계층 구조 만듬 
            for($i=$comment_count-1;$i>=0;$i--) {
                $comment_srl = $source_list[$i]->comment_srl;
                $parent_srl = $source_list[$i]->parent_srl;
                $member_srl = $source_list[$i]->member_srl;
                if(!$comment_srl) continue;

                if($is_admin || $this->isGranted($comment_srl) || $member_srl == $logged_info->member_srl) $source_list[$i]->is_granted = true;

                // 첨부파일 가져오기
                if($source_list[$i]->uploaded_count) {
                    $file_list = $oFileModel->getFiles($comment_srl, $is_admin);
                    $source_list[$i]->uploaded_list = $file_list;
                }

                // 목록을 만듬
                $list[$comment_srl] = $source_list[$i];

                if($parent_srl) {
                    $list[$parent_srl]->child[] = &$list[$comment_srl];
                } else {
                    $root->child[] = &$list[$comment_srl];
                }
            }
            $this->_arrangeComment($comment_list, $root->child, 0);
            return $comment_list;
        }

        /**
         * @brief 댓글을 계층형으로 재배치
         **/
        function _arrangeComment(&$comment_list, $list, $depth) {
            if(!count($list)) return;
            foreach($list as $key => $val) {
                if($val->child) {
                    $tmp = $val;
                    $tmp->depth = $depth;
                    $comment_list[$tmp->comment_srl] = $tmp;
                    $this->_arrangeComment($comment_list,$val->child,$depth+1);
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
                }
            }

            // comment.getTotalCommentList 쿼리 실행
            $output = executeQuery($query_id, $args);

            // 결과가 없거나 오류 발생시 그냥 return
            if(!$output->toBool()||!count($output->data)) return $output;

            return $output;
        }
    }
?>
