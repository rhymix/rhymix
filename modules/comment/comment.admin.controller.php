<?php
    /**
     * @class  commentAdminController
     * @author NHN (developers@xpressengine.com)
     * @brief admin controller class of the comment module
     **/

    class commentAdminController extends comment {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief Delete the selected comment from the administrator page
         **/
        function procCommentAdminDeleteChecked() {
            // Error display if none is selected
            $cart = Context::get('cart');
            if(!$cart) return $this->stop('msg_cart_is_null');
            $comment_srl_list= explode('|@|', $cart);
            $comment_count = count($comment_srl_list);
            if(!$comment_count) return $this->stop('msg_cart_is_null');

            $oCommentController = &getController('comment');

            $deleted_count = 0;
            // Delete the comment posting
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
         * @brief cancel the blacklist of abused comments reported by other users
         **/
        function procCommentAdminCancelDeclare() {
            $comment_srl = trim(Context::get('comment_srl'));

            if($comment_srl) {
                $args->comment_srl = $comment_srl;
                $output = executeQuery('comment.deleteDeclaredComments', $args);
                if(!$output->toBool()) return $output;
            }
        }

        /**
         * @brief delete all comments of the specific module
         **/
        function deleteModuleComments($module_srl) {
            $args->module_srl = $module_srl;
            $output = executeQuery('comment.deleteModuleComments', $args);
            if(!$output->toBool()) return $output;

            $output = executeQuery('comment.deleteModuleCommentsList', $args);
            return $output;
        }

    }
?>
