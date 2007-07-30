<?php
    /**
     * @class  pointAdminView
     * @author zero (zero@nzeo.com)
     * @brief  point모듈의 admin view class
     **/

    class pointAdminView extends point {

        /**
         * @brief 초기화
         **/
        function init() {
            // 설정 정보 가져오기
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');

            // 설정 변수 지정
            Context::set('config', $config);

            // template path지정
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * @brief 기본 설정
         **/
        function dispPointAdminConfig() {
            // 레벨 아이콘 목록 구함
            $level_icon_list = FileHandler::readDir("./modules/point/icons");
            Context::set('level_icon_list', $level_icon_list);

            // 템플릿 지정
            $this->setTemplateFile('config');
        }

        /**
         * @brief 모듈별 점수 지정
         **/
        function dispPointAdminModuleConfig() {
            // mid 목록 가져오기
            $oModuleModel = &getModel('module');
            $mid_list = $oModuleModel->getMidList();
            Context::set('mid_list', $mid_list);

            // 템플릿 지정
            $this->setTemplateFile('module_config');
        }

        /**
         * @brief 기능별 act 설정
         **/
        function dispPointAdminActConfig() {
            // 템플릿 지정
            $this->setTemplateFile('action_config');
        }

        /**
         * @brief 회원 포인트순 목록 가져오기
         **/
        function dispPointAdminPointList() {
            $oPointModel = &getModel('point');
            $args->list_count = 20;
            $args->page = Context::get('page');

            $output = $oPointModel->getMemberList($args);
            // 템플릿에 쓰기 위해서 context::set
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('member_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            // 템플릿 지정
            $this->setTemplateFile('member_list');
        }
    }
?>
