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
            $oDB = &DB::getInstance();
            $args->comment_srl = $comment_srl;
            $output = $oDB->executeQuery('comment.getChildCommentCount', $args);
            return (int)$output->data->count;
        }

        /**
         * @brief 댓글 가져오기
         **/
        function getComment($comment_srl, $is_admin = false) {
            $oDB = &DB::getInstance();
            $args->comment_srl = $comment_srl;
            $output = $oDB->executeQuery('comment.getComment', $args);
            if($is_admin || $this->isGranted($comment_srl)) $output->data->is_granted = true;
            return $output->data;
        }

        /**
         * @brief 여러개의 댓글들을 가져옴 (페이징 아님)
         **/
        function getComments($comment_srl_list) {
            if(is_array($comment_srl_list)) $comment_srls = implode(',',$comment_srl_list);

            $oDB = &DB::getInstance();
            $args->comment_srls = $comment_srls;
            $output = $oDB->executeQuery('comment.getComments', $args);
            return $output->data;
        }

        /**
         * @brief document_srl 에 해당하는 댓글의 전체 갯수를 가져옴
         **/
        function getCommentCount($document_srl) {
            $oDB = &DB::getInstance();
            $args->document_srl = $document_srl;
            $output = $oDB->executeQuery('comment.getCommentCount', $args);
            $total_count = $output->data->count;
            return (int)$total_count;
        }

        /** 
         * @brief document_srl에 해당하는 문서의 댓글 목록을 가져옴
         **/
        function getCommentList($document_srl, $is_admin = false) {
            $oDB = &DB::getInstance();

            $args->document_srl = $document_srl;
            $args->list_order = 'list_order';
            $output = $oDB->executeQuery('comment.getCommentList', $args);
            if(!$output->toBool()) return $output;

            $source_list= $output->data;
            if(!is_array($source_list)) $source_list = array($source_list);

            // 댓글를 계층형 구조로 정렬
            $comment_count = count($source_list);

            $root = NULL;
            $list = NULL;

            for($i=$comment_count-1;$i>=0;$i--) {
                $comment_srl = $source_list[$i]->comment_srl;
                $parent_srl = $source_list[$i]->parent_srl;
                if(!$comment_srl) continue;

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
    }
?>
