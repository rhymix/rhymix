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


		function procSpamfilterAdminInsertConfig() {

            // Get the default information
            $argsConfig = Context::gets('limits','check_trackback');
			$flag = Context::get('flag');
			//interval, limit_count
            if($argsConfig->check_trackback!='Y') $argsConfig->check_trackback = 'N';
       	    if($argsConfig->limits!='Y') $argsConfig->limits = 'N';
            // Create and insert the module Controller object
   	        $oModuleController = &getController('module');
       	    $moduleConfigOutput = $oModuleController->insertModuleConfig('spamfilter',$argsConfig);
			if(!$moduleConfigOutput->toBool()) return $moduleConfigOutput;

			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSpamfilterAdminSetting');
				header('location:'.$returnUrl);
				return;
			}
            return false;
		}

		function procSpamfilterAdminInsertDeniedIP(){
			//스팸IP  추가
			$ipaddressList = Context::get('ipaddressList');
            $oSpamfilterController = &getController('spamfilter');
			if($ipaddressList){
            	$insertIPOutput = $oSpamfilterController->insertIP($ipaddressList);
				if(!$insertIPOutput->toBool()) return $insertIPOutput;
			}
			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSpamfilterAdminSetting');
				header('location:'.$returnUrl);
				return;
			}
            return false;

		}
		function procSpamfilterAdminInsertDeniedWord(){
			//스팸 키워드 추가
			$wordList = Context::get('wordList');
          	if($wordList){
				$insertWordOutput = $this->insertWord($wordList);
				if(!$insertWordOutput->toBool()) return $insertWordOutput;
			}
			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSpamfilterAdminSetting');
				header('location:'.$returnUrl);
				return;
			}
            return false;
		}
        
        /**
         * @brief Delete the banned IP
         **/
        function procSpamfilterAdminDeleteDeniedIP() {
            $ipaddress = Context::get('ipaddress');
            $output = $this->deleteIP($ipaddress);
			if($output->toBool() && !in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSpamfilterAdminSetting');
				header('location:'.$returnUrl);
				return;
			}
			return $output;
        }
        
        /**
         * @brief Delete the prohibited Word
         **/
        function procSpamfilterAdminDeleteDeniedWord() {
            $word = Context::get('word');
            //$word = base64_decode(Context::get('word'));
            $output = $this->deleteWord($word);
			
			if($output->toBool() && !in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSpamfilterAdminSetting');
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
        function insertWord($wordList) {
			$wordList = str_replace("\r","",$wordList);
            $wordList = explode("\n",$wordList);
            foreach($wordList as $wordKey => $word) {
				if(trim($word)) $args->word = $word;
        		$output = executeQuery('spamfilter.insertDeniedWord', $args);
            	if(!$output->toBool()) return $output;
			}
			return $output;
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
