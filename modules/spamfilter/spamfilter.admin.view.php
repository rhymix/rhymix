<?php
    /**
     * @class  spamfilterAdminView
     * @author NHN (developers@xpressengine.com)
     * @brief The admin view class of the spamfilter module
     **/

    class spamfilterAdminView extends spamfilter {

        /**
         * @brief Initialization
         **/
        function init() {
            // Set template path
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * @brief Spam Filter configurations
		 *        Output the list of banned IPs and words
         **/
		function dispSpamfilterAdminSetting() {
            // Get configurations (using module model object)
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('spamfilter');
			
			// Get the list of denied IP addresses and words
			$oSpamFilterModel = &getModel('spamfilter');
            $ip_list = $oSpamFilterModel->getDeniedIPList();
            $word_list = $oSpamFilterModel->getDeniedWordList();

            Context::set('config',$config);
            Context::set('ip_list', $ip_list);
            Context::set('word_list', $word_list);
            
			$security = new Security();
			$security->encodeHTML('word_list..word');
			$security->encodeHTML('ip_list..');

			// Set a template file
            $this->setTemplateFile('index');
		}
    }
?>
