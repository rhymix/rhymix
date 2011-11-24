<?php
    /**
     * @class  sessionAdminView
     * @author NHN (developers@xpressengine.com)
     * @brief The admin view class of the session module
     **/

    class sessionAdminView extends session {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief Configure
         **/
        function dispSessionAdminIndex() {
            // Set the template file
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('index');
        }

    }
?>
