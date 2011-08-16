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


		function procSpamfilterAdminInsertSetting() {
			debugPrint('Setting!!');

            // Get the default information
            $argsConfig = Context::gets('limits','check_trackback');
			$ipaddressList = Context::get('ipaddressList');
			$wordList = Context::get('wordList');
			$flag = Context::get('flag');
			//interval, limit_count
			debugPrint($argsConfig);

			if(!$flag){
	            if($argsConfig->check_trackback && $argsConfig->check_trackback!='Y') $argsConfig->check_trackback = 'N';
				//컬럼 변경하거나. 변경이 불가능하다면 룰셋에서 값을 고정 할 수 잇는지 알아볼 것 (config에서 값을 10,2f로 세팅할 것)
        	    if($argsConfig->limits && $argsConfig->limits!='Y') $argsConfig->limits = 'N';
	            // Create and insert the module Controller object
    	        $oModuleController = &getController('module');
        	    $moduleConfigOutput = $oModuleController->insertModuleConfig('spamfilter',$argsConfig);
				if(!$moduleConfigOutput->toBool()) return $moduleConfigOutput;
			}
			
			//스팸IP  추가
            $oSpamfilterController = &getController('spamfilter');
			if($ipaddressList){
            	$insertIPOutput = $oSpamfilterController->insertIP($ipaddressList);
				if(!$insertIPOutput->toBool()) return $insertIPOutput;
			}

			//스팸 키워드 추가
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
        /**CUT!
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
        
        /**	CUT!
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
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSpamfilterAdminSetting');
				header('location:'.$returnUrl);
				return;
			}
			return $output;
        }
        
        /**	CUT!
         * @brief Register the prohibited word
         **/
        function procSpamfilterAdminInsertDeniedWord() {
            $word = Context::get('word');
            $output = $this->insertWord($word);
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
