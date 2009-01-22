<?php
    /**
     * @class  pageView
     * @author zero (zero@nzeo.com)
     * @brief  page 모듈의 view 클래스
     **/

    class pageView extends page {

        var $module_srl = 0;
        var $list_count = 20;
        var $page_count = 10;

        /**
         * @brief 초기화
         **/
        function init() {
            // 템플릿 경로 구함 (page의 경우 tpl에 관리자용 템플릿 모아놓음)
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * @brief 일반 요청시 출력
         **/
        function dispPageIndex() {
            // 권한 체크
            if(!$this->grant->view) return $this->stop('msg_not_permitted'); 

            // 템플릿에서 사용할 변수를 Context::set()
            if($this->module_srl) Context::set('module_srl',$this->module_srl);

            Context::set('module_info', $this->module_info);
            Context::set('page_content', $this->module_info->content);

            $this->setTemplateFile('content');
        }
    }
?>
