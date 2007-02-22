<?php
    /**
     * @class  messageView
     * @author zero (zero@nzeo.com)
     * @brief  message모듈의 view class
     **/

    class messageView extends message {

        /**
         * @brief 초기화
         **/
        function init() {
            // 템플릿 경로를 지정
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * @brief 메세지 출력 
         **/
        function dispContent() {
            Context::set('system_message', $this->getMessage());
            $this->setTemplateFile('system_message');
        }
    }
?>
