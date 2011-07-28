<?php
    /**
     * @class  communicationAdminController
     * @author NHN (developers@xpressengine.com)
     * @brief communication module of the admin controller class
     **/

    class communicationAdminController extends communication {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief save configurations of the communication module
         **/
        function procCommunicationAdminInsertConfig() {
            // get the default information
            $args = Context::gets('skin','colorset','editor_skin','editor_colorset');

            if(!$args->skin) $args->skin = "default";
            if(!$args->colorset) $args->colorset = "white";
            if(!$args->editor_skin) $args->editor_skin = "default";
            // create the module module Controller object
            $oModuleController = &getController('module');
            $output = $oModuleController->insertModuleConfig('communication',$args);

			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispCommunicationAdminConfig');
				$this->setRedirectUrl($returnUrl);
				return;
			}
			else return $output;
        }

    }
?>
