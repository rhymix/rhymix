<?php
    /**
     * @class  refererAdminView
     * @author haneul (haneul0318@gmail.com)
     * @brief  referer 모듈의 Admin view class
     **/

    class refererAdminView extends referer {

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
        function dispRefererAdminIndex() {
	    $this->dispRefererAdminList();
        }

	function dispRefererAdminList() {
	    
            // 목록을 구하기 위한 옵션
            $args->page = Context::get('page'); ///< 페이지

            $args->sort_index = 'regdate'; ///< 소팅 값

	    $oRefererModel = &getModel('referer');
	    $output = $oRefererModel->getLogList($args);

            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('referer_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

	    $args2->sort_index = 'count';
	    $output2 = $oRefererModel->getRefererStatus($args2);
	    Context::set('referer_status', $output2->data);
            // 템플릿 지정
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('referer_list');
	}
	    

    }
?>
