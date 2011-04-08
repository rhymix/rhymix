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
         **/
        function dispSpamfilterAdminConfig() {
            // Get configurations (using module model object)
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('spamfilter');
            Context::set('config',$config);
            // Set a template file
            $this->setTemplateFile('index');
        }

        /**
         * @brief Output the list of banned IPs
         **/
        function dispSpamfilterAdminDeniedIPList() {
            // Get the list of banned IP addresses
            $oSpamFilterModel = &getModel('spamfilter');
            $ip_list = $oSpamFilterModel->getDeniedIPList();

            Context::set('ip_list', $ip_list);
            // Set a template file
            $this->setTemplateFile('denied_ip_list');
        }

        /**
         * @brief Output the list of prohibited words
         **/
        function dispSpamfilterAdminDeniedWordList() {
            // Get the list of prohibited words
            $oSpamFilterModel = &getModel('spamfilter');
            $word_list = $oSpamFilterModel->getDeniedWordList();

            Context::set('word_list', $word_list);
            // Set a template file
            $this->setTemplateFile('denied_word_list');
        }
    }
?>
