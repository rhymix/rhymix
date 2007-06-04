<?php
    /**
     * @class  spamfilterAdminView
     * @author zero (zero@nzeo.com)
     * @brief  spamfilter 모듈의 admin view class
     **/

    class spamfilterAdminView extends spamfilter {

        /**
         * @brief 초기화
         **/
        function init() {
            // 템플릿 경로 지정 
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * @brief 스팸필터의 설정 화면
         **/
        function dispSpamfilterAdminConfig() {
            // 설정 정보를 받아옴 (module model 객체를 이용)
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('spamfilter');
            Context::set('config',$config);

            // 템플릿 파일 지정
            $this->setTemplateFile('index');
        }

        /**
         * @brief 금지 목록 출력
         **/
        function dispSpamfilterAdminDeniedIPList() {
            // 등록된 금지 IP 목록을 가져옴
            $oSpamFilterModel = &getModel('spamfilter');
            $ip_list = $oSpamFilterModel->getDeniedIPList();

            Context::set('ip_list', $ip_list);

            // 템플릿 파일 지정
            $this->setTemplateFile('denied_ip_list');
        }

        /**
         * @brief 금지 목록 출력
         **/
        function dispSpamfilterAdminDeniedWordList() {
            // 등록된 금지 Word 목록을 가져옴
            $oSpamFilterModel = &getModel('spamfilter');
            $word_list = $oSpamFilterModel->getDeniedWordList();

            Context::set('word_list', $word_list);

            // 템플릿 파일 지정
            $this->setTemplateFile('denied_word_list');
        }
    }
?>
