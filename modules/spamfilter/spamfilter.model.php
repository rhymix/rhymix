<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  spamfilterModel
 * @author NAVER (developers@xpressengine.com)
 * @brief The Model class of the spamfilter module
 */
class spamfilterModel extends spamfilter
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief Return the user setting values of the Spam filter module
	 */
	function getConfig()
	{
		// Get configurations (using the module model object)
		$oModuleModel = getModel('module');
		return $oModuleModel->getModuleConfig('spamfilter');
	}

	/**
	 * @brief Return the list of registered IP addresses which were banned
	 */
	function getDeniedIPList()
	{
		$args = new stdClass();
		$args->sort_index = "regdate";
		$args->page = Context::get('page')?Context::get('page'):1;
		$output = executeQuery('spamfilter.getDeniedIPList', $args);
		if(!$output->data) return;
		if(!is_array($output->data)) return array($output->data);
		return $output->data;
	}

	/**
	 * @brief Check if the ipaddress is in the list of banned IP addresses
	 */
	function isDeniedIP()
	{
		$ipaddress = $_SERVER['REMOTE_ADDR'];

		$ip_list = $this->getDeniedIPList();
		if(!count($ip_list)) return new Object();

		$count = count($ip_list);
		for($i=0;$i<$count;$i++)
		{
			$ip = str_replace('.', '\.', str_replace('*','(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)',$ip_list[$i]->ipaddress));
			if(preg_match('/^'.$ip.'$/', $ipaddress, $matches)) return new Object(-1,'msg_alert_registered_denied_ip');
		}

		return new Object();
	}

	/**
	 * @brief Return the list of registered Words which were banned
	 */
	function getDeniedWordList()
	{
		$args = new stdClass();
		$args->sort_index = "hit";
		$output = executeQuery('spamfilter.getDeniedWordList', $args);
		if(!$output->data) return;
		if(!is_array($output->data)) return array($output->data);
		return $output->data;
	}

	/**
	 * @brief Check if the text, received as a parameter, is banned or not
	 */
	function isDeniedWord($text)
	{
		$word_list = $this->getDeniedWordList();
		if(!count($word_list)) return new Object();

		$count = count($word_list);
		for($i=0;$i<$count;$i++)
		{
			$word = $word_list[$i]->word;
			if(preg_match('/'.preg_quote($word,'/').'/is', $text))
			{
				$args->word = $word;
				$output = executeQuery('spamfilter.updateDeniedWordHit', $args);
				return new Object(-1,sprintf(Context::getLang('msg_alert_denied_word'), $word));
			}
		}

		return new Object();
	}

	/**
	 * @brief Check the specified time
	 */
	function checkLimited($isMessage = FALSE)
	{
		$config = $this->getConfig();

		if($config->limits != 'Y') return new Object(); 
		$limit_count = '3';
		$interval = '10';

		$count = $this->getLogCount($interval);

		$ipaddress = $_SERVER['REMOTE_ADDR'];
		// Ban the IP address if the interval is exceeded
		if($count>=$limit_count)
		{
			$oSpamFilterController = getController('spamfilter');
			$oSpamFilterController->insertIP($ipaddress, 'AUTO-DENIED : Over limit');
			return new Object(-1, 'msg_alert_registered_denied_ip');
		}
		// If the number of limited posts is not reached, keep creating.
		if($count)
		{
			if($isMessage)
			{
				$message = sprintf(Context::getLang('msg_alert_limited_message_by_config'), $interval);
			}
			else
			{
				$message = sprintf(Context::getLang('msg_alert_limited_by_config'), $interval);
			}

			$oSpamFilterController = getController('spamfilter');
			$oSpamFilterController->insertLog();

			return new Object(-1, $message);
		}
		return new Object();
	}

	/**
	 * @brief Check if the trackbacks have already been registered to a particular article
	 */
	function isInsertedTrackback($document_srl)
	{
		$oTrackbackModel = getModel('trackback');
		$count = $oTrackbackModel->getTrackbackCountByIPAddress($document_srl, $_SERVER['REMOTE_ADDR']);
		if($count>0) return new Object(-1, 'msg_alert_trackback_denied');

		return new Object();
	}

	/**
	 * @brief Return the number of logs recorded within the interval for the specified IPaddress
	 */
	function getLogCount($time = 60, $ipaddress='')
	{
		if(!$ipaddress) $ipaddress = $_SERVER['REMOTE_ADDR'];

		$args->ipaddress = $ipaddress;
		$args->regdate = date("YmdHis", $_SERVER['REQUEST_TIME']-$time);
		$output = executeQuery('spamfilter.getLogCount', $args);
		$count = $output->data->count;
		return $count;
	}
}
/* End of file spamfilter.model.php */
/* Location: ./modules/spamfilter/spamfilter.model.php */
