<<<<<<< .working
<?php
    /**
     * @class  messageAdminView
     * @author NHN (developers@xpressengine.com)
     * @brief admin view class of the message module
     **/

    class messageAdminView extends message {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief Configuration
         **/
        function dispMessageAdminConfig() {
            // Get a list of skins(themes)
            $oModuleModel = &getModel('module');
            $skin_list = $oModuleModel->getskins($this->module_path);
            Context::set('skin_list', $skin_list);
            // Get configurations (using module model object)
            $config = $oModuleModel->getModuleConfig('message');
            Context::set('config',$config);
            // Set a template file
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('config');
        }

    }
?>
=======
<?php
    /**
     * @class  messageAdminView
     * @author NHN (developers@xpressengine.com)
     * @brief  message모듈의 admin view class
     **/

    class messageAdminView extends message {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 설정 
         **/
        function dispMessageAdminConfig() {
            // 스킨 목록을 구해옴
            $oModuleModel = &getModel('module');
            $skin_list = $oModuleModel->getskins($this->module_path);
            Context::set('skin_list', $skin_list);

            // 설정 정보를 받아옴 (module model 객체를 이용)
            $config = $oModuleModel->getModuleConfig('message');
            Context::set('config',$config);

            // 템플릿 파일 지정
            $this->setTemplatePath($this->module_path.'tpl');
			
			//Security
			$security = new Security();
			$security->encodeHTML('skin_list..title');				
			
            $this->setTemplateFile('config');
        }

    }
?>
>>>>>>> .merge-right.r9269
