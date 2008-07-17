<?php
    /**
     * @class  commentnotifyAdminView
     * @author haneul (haneul0318@gmail.com)
     * @brief  commentnotify 모듈의 Admin view class
     **/

    class tccommentnotifyAdminView extends tccommentnotify {

        /**
         * @brief 초기화
         **/
        function init() {
            // 템플릿 경로 지정 
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * @brief 관리자 페이지 초기화면
         **/
        function dispCommentNotifyAdminIndex() {
            $this->dispCommentNotifyAdminList();
        }

        function dispCommentNotifyAdminList() {
	    
            // 목록을 구하기 위한 옵션
            $args->page = Context::get('page'); ///< 페이지

            $args->sort_index = 'list_order'; ///< 소팅 값

            $oCommentNotifyModel = &getModel('tccommentnotify');
            $output = $oCommentNotifyModel->GetNotifiedList($args);

            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            $notify_list = array();
            if(!$output->data)
            {
                $output->data = array();
            }
            foreach($output->data as $notifyparent)
            {
                $item = null;
                $item->parent = $notifyparent;
                $item->children = $oCommentNotifyModel->GetChildren($notifyparent->notified_srl);
                $notify_list[] = $item;
            }
            Context::set('notify_list', $notify_list);
            Context::set('page_navigation', $output->page_navigation);

            // 템플릿 지정
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('commentnotify_list');
        }

        function dispCommentNotifyAdminDeleteChild()
        {
            $notified_srl = Context::get('notified_srl');
            $this->setTemplateFile('delete_child');
        }
    }
?>
