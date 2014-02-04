<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  spamfilterAdminController
 * @author NAVER (developers@xpressengine.com)
 * @brief The admin controller class of the spamfilter module
 */
class spamfilterAdminController extends spamfilter
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	function procSpamfilterAdminInsertConfig()
	{
		// Get the default information
		$argsConfig = Context::gets('limits','check_trackback');
		$flag = Context::get('flag');
		//interval, limit_count
		if($argsConfig->check_trackback!='Y') $argsConfig->check_trackback = 'N';
		if($argsConfig->limits!='Y') $argsConfig->limits = 'N';
		// Create and insert the module Controller object
		$oModuleController = getController('module');
		$moduleConfigOutput = $oModuleController->insertModuleConfig('spamfilter',$argsConfig);
		if(!$moduleConfigOutput->toBool()) return $moduleConfigOutput;

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSpamfilterAdminConfigBlock');
		$this->setRedirectUrl($returnUrl);
	}

	function procSpamfilterAdminInsertDeniedIP()
	{
		//스팸IP  추가
		$ipaddress_list = Context::get('ipaddress_list');
		$oSpamfilterController = getController('spamfilter');
		if($ipaddress_list)
		{
			$output = $oSpamfilterController->insertIP($ipaddress_list);
			if(!$output->toBool() && !$output->get('fail_list')) return $output;

			if($output->get('fail_list')) $message_fail = '<em>'.sprintf(Context::getLang('msg_faillist'),$output->get('fail_list')).'</em>';
			$this->setMessage(Context::getLang('success_registed').$message_fail);
		}


		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSpamfilterAdminDeniedIPList');
		$this->setRedirectUrl($returnUrl);
	}

	function procSpamfilterAdminInsertDeniedWord()
	{
		//스팸 키워드 추가
		$word_list = Context::get('word_list');
		if($word_list)
		{
			$output = $this->insertWord($word_list);
			if(!$output->toBool() && !$output->get('fail_list')) return $output;

			if($output->get('fail_list')) $message_fail = '<em>'.sprintf(Context::getLang('msg_faillist'),$output->get('fail_list')).'</em>';
			$this->setMessage(Context::getLang('success_registed').$message_fail);
		}

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSpamfilterAdminDeniedWordList');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * @brief Delete the banned IP
	 */
	function procSpamfilterAdminDeleteDeniedIP()
	{
		$ipAddressList = Context::get('ipaddress');
		if($ipAddressList) $this->deleteIP($ipAddressList);

		$this->setMessage(Context::getLang('success_deleted'));

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSpamfilterAdminDeniedIPList');
		return $this->setRedirectUrl($returnUrl);
	}

	/**
	 * @brief Delete the prohibited Word
	 */
	function procSpamfilterAdminDeleteDeniedWord()
	{
		$wordList = Context::get('word');
		$this->deleteWord($wordList);

		$this->setMessage(Context::getLang('success_deleted'));

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSpamfilterAdminDeniedWordList','active','word');
		return $this->setRedirectUrl($returnUrl);
	}

	/**
	 * @brief Delete IP
	 * Remove the IP address which was previously registered as a spammers
	 */
	function deleteIP($ipaddress)
	{
		if(!$ipaddress) return;

		$args = new stdClass;
		$args->ipaddress = $ipaddress;
		return executeQuery('spamfilter.deleteDeniedIP', $args);
	}

	/**
	 * @brief Register the spam word
	 * The post, which contains the newly registered spam word, should be considered as a spam
	 */
	function insertWord($word_list)
	{

		$word_list = str_replace("\r","",$word_list);
		$word_list = explode("\n",$word_list);

		foreach($word_list as $word)
		{
			if(!preg_match("/^(.{2,40}[\r\n]+)*.{2,40}$/", $word))
			{
				return new Object(-1, 'msg_invalid');
			}
		}

		$fail_word = '';
		foreach($word_list as $word)
		{
			$args = new stdClass;
			if(trim($word)) $args->word = $word;
			$output = executeQuery('spamfilter.insertDeniedWord', $args);
			if(!$output->toBool()) $fail_word .= $word.'<br />';
		}
		$output->add('fail_list',$fail_word);
		return $output;
	}

	/**
	 * @brief Remove the spam word
	 * Remove the word which was previously registered as a spam word
	 */
	function deleteWord($word)
	{
		if(!$word) return;
		$args = new stdClass;
		$args->word = $word;
		return executeQuery('spamfilter.deleteDeniedWord', $args);
	}
}
/* End of file spamfilter.admin.controller.php */
/* Location: ./modules/spamfilter/spamfilter.admin.controller.php */
