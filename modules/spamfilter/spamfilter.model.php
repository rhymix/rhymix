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
		$output = executeQueryArray('spamfilter.getDeniedIPList', $args);
		if(!$output->data) return;
		return $output->data;
	}

	/**
	 * @brief Check if the ipaddress is in the list of banned IP addresses
	 */
	function isDeniedIP()
	{
		$ip_list = $this->getDeniedIPList();
		if(!count($ip_list)) return new Object();
		
		$ip_ranges = array();
		foreach ($ip_list as $ip_range)
		{
			$ip_ranges[] = $ip_range->ipaddress;
		}
		
		if (Rhymix\Framework\Filters\IpFilter::inRanges(\RX_CLIENT_IP, $ip_ranges))
		{
			return new Object(-1, 'msg_alert_registered_denied_ip');
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

		foreach ($word_list as $word_item)
		{
			$word = $word_item->word;
			if (strpos($text, $word) !== false)
			{
				$args = new stdClass();
				$args->word = $word;
				$output = executeQuery('spamfilter.updateDeniedWordHit', $args);
				return new Object(-1,sprintf(lang('msg_alert_denied_word'), $word));
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
		$limit_count = $config->limits_count ?: 3;
		$interval = $config->limits_interval ?: 10;

		$count = $this->getLogCount($interval);

		// Ban the IP address if the interval is exceeded
		if($count>=$limit_count)
		{
			if (\RX_CLIENT_IP_VERSION == 4)
			{
				$suffix = $config->ipv4_block_range ?: '';
			}
			else
			{
				$suffix = $config->ipv6_block_range ?: '';
			}
			
			$oSpamFilterController = getController('spamfilter');
			$oSpamFilterController->insertIP(\RX_CLIENT_IP .  $suffix, 'AUTO-DENIED : Over limit');
			return new Object(-1, 'msg_alert_registered_denied_ip');
		}

		// If the number of limited posts is not reached, keep creating.
		if($count)
		{
			if($isMessage)
			{
				$message = sprintf(lang('msg_alert_limited_message_by_config'), $interval);
			}
			else
			{
				$message = sprintf(lang('msg_alert_limited_by_config'), $interval);
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
		if (is_object($oTrackbackModel) && method_exists($oTrackbackModel, 'getTrackbackCountByIPAddress'))
		{
			$count = $oTrackbackModel->getTrackbackCountByIPAddress($document_srl, \RX_CLIENT_IP);
			if ($count > 0)
			{
				return new Object(-1, 'msg_alert_trackback_denied');
			}
		}
		return new Object();
	}

	/**
	 * @brief Return the number of logs recorded within the interval for the specified IPaddress
	 */
	function getLogCount($time = 60, $ipaddress='')
	{
		if(!$ipaddress) $ipaddress = \RX_CLIENT_IP;

		$args = new stdClass();
		$args->ipaddress = $ipaddress;
		$args->regdate = date("YmdHis", time() - $time);
		$output = executeQuery('spamfilter.getLogCount', $args);
		$count = $output->data->count;
		return $count;
	}
}
/* End of file spamfilter.model.php */
/* Location: ./modules/spamfilter/spamfilter.model.php */
