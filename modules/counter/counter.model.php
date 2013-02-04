<?php
/**
 * Model class of counter module
 *
 * @author NHN (developers@xpressengine.com)
 */
class counterModel extends counter
{
	/**
	 * Initialization
	 *
	 * @return void
	 */
	function init()
	{
	}

	/**
	 * Verify logs
	 *
	 * @param integer $site_srl Site_srl
	 * @return bool
	 */
	function isLogged($site_srl=0)
	{
		$args = new stdClass;
		$args->regdate = date('Ymd');
		$args->ipaddress = $_SERVER['REMOTE_ADDR'];
		$args->site_srl = $site_srl;

		$oCacheHandler = &CacheHandler::getInstance('object');
		if($oCacheHandler->isSupport())
		{
			$object_key = 'object:' . $site_srl . '_' . str_replace('.', '-', $args->ipaddress);
			$cache_key = $oCacheHandler->getGroupKey('counterIpLogged_' . $args->regdate, $object_key);
			if($oCacheHandler->isValid($cache_key)) return $oCacheHandler->get($cache_key);
		}

		$output = executeQuery('counter.getCounterLog', $args);

		$result = $output->data->count ? TRUE : FALSE;
		if($result && $oCacheHandler->isSupport()) $oCacheHandler->put($cache_key, $result);

		return $result;
	}

	/**
	 * Check if a row of today's counter status exists 
	 *
	 * @param integer $site_srl Site_srl
	 * @return bool
	 */
	function isInsertedTodayStatus($site_srl=0)
	{
		$args = new stdClass;
		$args->regdate = date('Ymd');

		$oCacheHandler = &CacheHandler::getInstance('object', NULL, TRUE);
		if($oCacheHandler->isSupport())
		{
			$cache_key = 'object:insertedTodayStatus:' . $site_srl . '_' . $args->regdate;
			if($oCacheHandler->isValid($cache_key)) return $oCacheHandler->get($cache_key);
		}

		if($site_srl)
		{
			$args->site_srl = $site_srl;
			$output = executeQuery('counter.getSiteTodayStatus', $args);
		}
		else $output = executeQuery('counter.getTodayStatus', $args);
		
		$result = $output->data->count ? TRUE : FALSE;
		if($result && $oCacheHandler->isSupport())
		{
			$oCacheHandler->put($cache_key, $result);
			$_old_date = date('Ymd', strtotime('-1 day'));
			$oCacheHandler->delete('object:insertedTodayStatus:' . $site_srl . '_' . $_old_date);
		}

		return $result;
	}

	/**
	 * Get access statistics for a given date
	 *
	 * @param mixed $selected_date Date(YYYYMMDD) list 
	 * @param integer $site_srl Site_srl
	 * @return Object
	 */
	function getStatus($selected_date, $site_srl = 0)
	{
		// If more than one date logs are selected
		if(is_array($selected_date))
		{
			$date_count = count($selected_date);
			$args->regdate = implode(',',$selected_date);
			// If a single date log is selected
		}
		else
		{
			if(strlen($selected_date) == 8) $selected_date = $selected_date;
			$args->regdate = $selected_date;
		}

		if($site_srl)
		{
			$args->site_srl = $site_srl;
			$output = executeQuery('counter.getSiteCounterStatusDays', $args);
		}
		else
		{
			$output = executeQuery('counter.getCounterStatusDays', $args);
		}
		$status = $output->data;

		if(!is_array($selected_date)) return $status;

		if(!is_array($status)) $status = array($status);
		unset($output);

		foreach($status as $key => $val)
		{
			$output[substr($val->regdate,0,8)] = $val;
		}
		return $output;
	}

	/**
	 * Select hourly logs of a given date
	 *
	 * @param string $type Choice time interval (year, week, month, hour or DEFAULT)
	 * @param integer $selected_date Date(YYYYMMDD)
	 * @param integer $site_srl Site_srl
	 * @return Object
	 */
	function getHourlyStatus($type='hour', $selected_date, $site_srl=0, $isPageView=false)
	{
		$max = 0;
		$sum = 0;
		switch($type)
		{
			case 'year' :
				// Get a date to start counting
				if($site_srl)
				{
					$args->site_srl = $site_srl;
					$output = executeQuery('counter.getSiteStartLogDate', $args);
				}
				else
				{
					$output = executeQuery('counter.getStartLogDate');
				}
				$start_year = substr($output->data->regdate,0,4);
				if(!$start_year) $start_year = date("Y");
				for($i=$start_year;$i<=date("Y");$i++)
				{
					unset($args);
					$args->start_date = sprintf('%04d0000', $i);
					$args->end_date = sprintf('%04d1231', $i);
					if($site_srl)
					{
						$args->site_srl = $site_srl;
						$output = executeQuery('counter.getSiteCounterStatus', $args);
					}
					else
					{
						$output = executeQuery('counter.getCounterStatus', $args);
					}

					if(!$isPageView)
					{
						$count = (int)$output->data->unique_visitor;
					}
					else
					{
						$count = (int)$output->data->pageview;
					}
					$status->list[$i] = $count;
					if($count>$max) $max = $count;
					$sum += $count;
				}
				break;
			case 'week' :
				$time = strtotime($selected_date);
				$w = date("D");
				while(date("D",$time) != "Sun")
				{
					$time += 60*60*24;
				}
				$time -= 60*60*24;
				while(date("D",$time)!="Sun")
				{
					$thisWeek[] = date("Ymd",$time);
					$time -= 60*60*24;
				}
				$thisWeek[] = date("Ymd",$time);
				asort($thisWeek);
				foreach($thisWeek as $day)
				{
					unset($args);
					$args->start_date = $day;
					$args->end_date = $day;
					if($site_srl)
					{
						$args->site_srl = $site_srl;
						$output = executeQuery('counter.getSiteCounterStatus', $args);
					}
					else
					{
						$output = executeQuery('counter.getCounterStatus', $args);
					}

					if(!$isPageView)
					{
						$count = (int)$output->data->unique_visitor;
					}
					else
					{
						$count = (int)$output->data->pageview;
					}
					$status->list[$day] = (int)$count;
					if($count>$max) $max = $count;
					$sum += $count;
				}
				break;
			case 'month' :
				$year = substr($selected_date, 0, 4);
				for($i=1;$i<=12;$i++)
				{
					unset($args);
					$args->start_date = sprintf('%04d%02d00', $year, $i);
					$args->end_date = sprintf('%04d%02d31', $year, $i);
					if($site_srl)
					{
						$args->site_srl = $site_srl;
						$output = executeQuery('counter.getSiteCounterStatus', $args);
					}
					else
					{
						$output = executeQuery('counter.getCounterStatus', $args);
					}

					if(!$isPageView)
					{
						$count = (int)$output->data->unique_visitor;
					}
					else
					{
						$count = (int)$output->data->pageview;
					}
					$status->list[$i] = (int)$count;
					if($count>$max) $max = $count;
					$sum += $count;
				}
				break;
			case 'hour' :
				for($i=0;$i<24;$i++)
				{
					unset($args);
					$args->start_date = sprintf('%08d%02d0000', $selected_date, $i);
					$args->end_date = sprintf('%08d%02d5959', $selected_date, $i);
					if($site_srl)
					{
						$args->site_srl = $site_srl;
						$output = executeQuery('counter.getSiteCounterLogStatus', $args);
					}
					else
					{
						$args->site_srl = 0;
						$output = executeQuery('counter.getCounterLogStatus', $args);
					}
					$count = (int)$output->data->count;
					$status->list[$i] = $count;
					if($count>$max) $max = $count;
					$sum += $count;
				}
				break;
			default : 
				$year = substr($selected_date, 0, 4);
				$month = substr($selected_date, 4, 2);
				$end_day = date('t', mktime(0,0,0,$month,1,$year));
				for($i=1;$i<=$end_day;$i++)
				{
					unset($args);
					$args->start_date = sprintf('%04d%02d%02d', $year, $month, $i);
					$args->end_date = sprintf('%04d%02d%02d', $year, $month, $i);
					if($site_srl)
					{
						$args->site_srl = $site_srl;
						$output = executeQuery('counter.getSiteCounterStatus', $args);
					}
					else
					{
						$output = executeQuery('counter.getCounterStatus', $args);
					}

					if(!$isPageView)
					{
						$count = (int)$output->data->unique_visitor;
					}
					else
					{
						$count = (int)$output->data->pageview;
					}
					$status->list[$i] = $count;
					if($count>$max) $max = $count;
					$sum += $count;
				}
				break;
		}

		$status->max = $max;
		$status->sum = $sum;
		return $status;
	}

	public function getWeeklyUniqueVisitor()
	{
		//for last week
		$date1 = date('Ymd', strtotime('-1 week'));
		$output1 = $this->getHourlyStatus('week', $date1);

		$tmp = array();
		foreach($output1->list AS $key=>$value)
		{
			$tmp["'".$key."'"] = $value;
		}
		$output1->list = $tmp;

		//for this week
		$date2 = date('Ymd');
		$output2 = $this->getHourlyStatus('week', $date2);

		$tmp = array();
		foreach($output2->list AS $key=>$value)
		{
			$tmp["'".$key."'"] = $value;
		}
		$output2->list = $tmp;

		$this->add('last_week', $output1);
		$this->add('this_week', $output2);
	}

	public function getWeeklyPageView()
	{
		//for last week
		$date1 = date('Ymd', strtotime('-1 week'));
		$output1 = $this->getHourlyStatus('week', $date1, 0, true);

		$tmp = array();
		foreach($output1->list AS $key=>$value)
		{
			$tmp["'".$key."'"] = $value;
		}
		$output1->list = $tmp;

		//for this week
		$date2 = date('Ymd');
		$output2 = $this->getHourlyStatus('week', $date2, 0, true);

		$tmp = array();
		foreach($output2->list AS $key=>$value)
		{
			$tmp["'".$key."'"] = $value;
		}
		$output2->list = $tmp;

		$this->add('last_week', $output1);
		$this->add('this_week', $output2);
	}
}
/* End of file counter.model.php */
/* Location: ./modules/counter/counter.model.php */
