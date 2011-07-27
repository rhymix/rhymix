<?php
    /**
     * @class  spamfilterAdminController
     * @author NHN (developers@xpressengine.com)
     * @brief The admin controller class of the spamfilter module
     **/

    class spamfilterAdminController extends spamfilter {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief Spam filter configurations
         **/
        function procSpamfilterAdminInsertConfig() {
            // Get the default information
            $args = Context::gets('interval','limit_count','check_trackback');
            if($args->check_trackback!='Y') $args->check_trackback = 'N';
            // Create and insert the module Controller object
            $oModuleController = &getController('module');
            $output = $oModuleController->insertModuleConfig('spamfilter',$args);
			if($output->toBool() && !in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSpamfilterAdminConfig');
				header('location:'.$returnUrl);
				return;
			}
            return $output;
        }
        
        /**
         * @brief Register the banned IP address
         **/
        function procSpamfilterAdminInsertDeniedIP() {
            $ipaddress = Context::get('ipaddress');
            $description = Context::get('description');

            $oSpamfilterController = &getController('spamfilter');
            return $oSpamfilterController->insertIP($ipaddress, $description);
        }

        /**
         * @brief Delete the banned IP
         **/
        function procSpamfilterAdminDeleteDeniedIP() {
            $ipaddress = Context::get('ipaddress');
            $output = $this->deleteIP($ipaddress);
			if($output->toBool() && !in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSpamfilterAdminDeniedIPList');
				header('location:'.$returnUrl);
				return;
			}
			return $output;
        }
        
        /**
         * @brief Register the prohibited word
         **/
        function procSpamfilterAdminInsertDeniedWord() {
            $word = Context::get('word');
            $output = $this->insertWord($word);
			if($output->toBool() && !in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSpamfilterAdminDeniedWordList');
				header('location:'.$returnUrl);
				return;
			}
			return $output;
        }

        /**
         * @brief Delete the prohibited Word
         **/
        function procSpamfilterAdminDeleteDeniedWord() {
            $word = base64_decode(Context::get('word'));
            $output = $this->deleteWord($word);
			if($output->toBool() && !in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSpamfilterAdminDeniedWordList');
				header('location:'.$returnUrl);
				return;
			}
			return $output;
        }

        /**
         * @brief Delete IP
         * Remove the IP address which was previously registered as a spammers
         **/
        function deleteIP($ipaddress) {
            if(!$ipaddress) return;

            $args->ipaddress = $ipaddress;
            return executeQuery('spamfilter.deleteDeniedIP', $args);
        }

        /**
         * @brief Register the spam word
         * The post, which contains the newly registered spam word, should be considered as a spam
         **/
        function insertWord($word) {
            if(!$word) return;

            $args->word = $word;
            return executeQuery('spamfilter.insertDeniedWord', $args);
        }

        /**
         * @brief Remove the spam word
         * Remove the word which was previously registered as a spam word
         **/
        function deleteWord($word) {
            if(!$word) return;

            $args->word = $word;
            return executeQuery('spamfilter.deleteDeniedWord', $args);
        }

    }
?>
