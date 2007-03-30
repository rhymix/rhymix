<?php
    /**
     * @class  krzipView
     * @author zero (zero@nzeo.com)
     * @brief  krzip 모듈의 View class
     **/

    class krzipView extends krzip {

        /**
         * @brief 초기화
         **/
        function init() {
            $this->setTemplatePath($this->module_path.'tpl.admin');
        }

        /**
         * @brief 설정
         **/
        function dispKrzipAdminConfig() {
            // 설정 정보를 받아옴 (module model 객체를 이용)
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('krzip');
            Context::set('config',$config);

            // 템플릿 파일 지정
            $this->setTemplateFile('index');
        }


    }
?>
