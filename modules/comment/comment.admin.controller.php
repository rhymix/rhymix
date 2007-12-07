<?php
    /**
     * @class  commentAdminController
     * @author zero (zero@nzeo.com)
     * @brief  comment 모듈의 admin controller class
     **/

    class commentAdminController extends comment {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 관리자 페이지에서 선택된 댓글들을 삭제
         **/
        function procCommentAdminDeleteChecked() {

            // 선택된 글이 없으면 오류 표시
            $cart = Context::get('cart');
            if(!$cart) return $this->stop('msg_cart_is_null');
            $comment_srl_list= explode('|@|', $cart);
            $comment_count = count($comment_srl_list);
            if(!$comment_count) return $this->stop('msg_cart_is_null');

            $oCommentController = &getController('comment');

            $deleted_count = 0;

            // 글삭제
            for($i=0;$i<$comment_count;$i++) {
                $comment_srl = trim($comment_srl_list[$i]);
                if(!$comment_srl) continue;

                $output = $oCommentController->deleteComment($comment_srl, true);
                if(!$output->toBool()) continue;

                $deleted_count ++;
            }

            $this->setMessage( sprintf(Context::getLang('msg_checked_comment_is_deleted'), $deleted_count) );
        }

        /**
         * @brief 특정 모듈의 모든 댓글 삭제
         **/
        function deleteModuleComments($module_srl) {
            $args->module_srl = $module_srl;
            $output = executeQuery('comment.deleteModuleComments', $args);
            return $output;
        }

    }
?>
