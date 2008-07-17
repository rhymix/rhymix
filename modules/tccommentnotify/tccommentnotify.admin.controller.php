<?php
    /**
     * @class  commentnotifyAdminView
     * @author haneul (haneul0318@gmail.com)
     * @brief  commentnotify 모듈의 Admin view class
     **/

    class tccommentnotifyAdminController extends tccommentnotify {
        /**
         * @brief 초기화
         **/
        function init() {
        }

        function procCommentNotifyAdminDeleteChecked()
        {
            $cart = Context::get('cart');
            if(!$cart) return $this->stop('msg_cart_is_null');
            $comment_srl_list= explode('|@|', $cart);
            $comment_count = count($comment_srl_list);
            if(!$comment_count) return $this->stop('msg_cart_is_null');

            for($i=0;$i<$comment_count;$i++) {
                $notified_srl = trim($comment_srl_list[$i]);
                if(!$notified_srl) continue;

                $output = $this->deleteParent($notified_srl);
                if(!$output->toBool()) continue;

                $deleted_count ++;
            }

            $this->setMessage( sprintf(Context::getLang('msg_checked_comment_is_deleted'), $deleted_count) );
        }

        function deleteParent($parent_srl)
        {
            $args->notified_srl = $parent_srl;
            executeQuery('tccommentnotify.deleteParent', $args);
            $newargs->parent_srl = $args->notified_srl;
            return executeQuery('tccommentnotify.deleteChildren', $newargs);
        }

        function procCommentNotifyAdminDeleteParent()
        {
            $notified_srl = Context::get('notified_srl');
            $this->deleteParent($notified_srl);
        }

        function procCommentNotifyAdminDeleteChild()
        {
            $args->notified_srl = Context::get('notified_srl');
            $oModel = &getModel('tccommentnotify');
            $output = $oModel->GetChild($args->notified_srl);
            if(!$output->toBool())
            {
                return;
            }
            $parent_srl = $output->data->parent_srl;
            executeQuery('tccommentnotify.deleteChild', $args);
            if(!$oModel->GetChildren($parent_srl))
            {
                $newarg->notified_srl = $parent_srl;
                executeQuery('tccommentnotify.deleteParent', $newarg);
            }
        }
    }
?>
