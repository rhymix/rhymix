<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Model class of counter module
 *
 * @author NAVER (developers@xpressengine.com)
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
	function isLogged($site_srl = 0)
	{
		$date = date('Ymd');
		if (isset($_SESSION['counter_logged'][$date]) && $_SESSION['counter_logged'][$date])
		{
			return true;
		}
		
		$args = new stdClass();
		$args->regdate = $date;
		$args->ipaddress = \RX_CLIENT_IP;
		$args->site_srl = $site_srl;
		$output = executeQuery('counter.getCounterLog', $args);
		$iplogged = $output->data->count ? true : false;
		if ($iplogged)
		{
			$_SESSION['counter_logged'][$date] = true;
		}
		
		return $iplogged;
	}

	/**
	 * Check if a row of today's counter status exists 
	 *
	 * @param integer $site_srl Site_srl
	 * @return bool
	 */
	function isInsertedTodayStatus($site_srl = 0)
	{
		$args = new stdClass;
		$args->regdate = date('Ymd');

		$cache_key = 'counter:insertedTodayStatus:' . $site_srl . '_' . $args->regdate;
		$insertedTodayStatus = Rhymix\Framework\Cache::get($cache_key);

		if(!$insertedTodayStatus)
		{
			if($site_srl)
			{
				$args->site_srl = $site_srl;
				$output = executeQuery('counter.getSiteTodayStatus', $args);
			}
			else
			{
				$output = executeQuery('counter.getTodayStatus', $args);
			}

			$insertedTodayStatus = !!$output->data->count;

			if($insertedTodayStatus)
			{
				Rhymix\Framework\Cache::set($cache_key, true, 0, true);
				$_old_date = date('Ymd', strtotime('-1 day'));
				Rhymix\Framework\Cache::delete('counter:insertedTodayStatus:' . $site_srl . '_' . $_old_date);
			}
		}

		return $insertedTodayStatus;
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
		$args = new stdClass();
		$args->regdate = is_array($selected_date) ? join(',', $selected_date) : $selected_date;
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

		if(!is_array($selected_date))
		{
			return $status;
		}

		if(!is_array($status)) $status = array($status);
		$output = array();
		foreach($status as $val)
		{
			$output[substr($val->regdate, 0, 8)] = $val;
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
	function getHourlyStatus($type = 'hour', $selected_date, $site_srl = 0, $isPageView = false)
	{
		$max = 0;
		$sum = 0;

		$status = new stdClass();
		switch($type)
		{
			case 'year' :
				// Get a date to start counting
				if($site_srl)
				{
					$args = new stdClass();
					$args->site_srl = $site_srl;
					$output = executeQuery('counter.getSiteStartLogDate', $args);
				}
				else
				{
					$output = executeQuery('counter.getStartLogDate');
				}

				if(!($start_year = substr($output->data->regdate, 0, 4)))
				{
					$start_year = date("Y");
				}

				for($i = $start_year, $y = date("Y"); $i <= $y; $i++)
				{
					$args = new stdClass();
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

					$count = (int)($isPageView ? $output->data->pageview : $output->data->unique_visitor);
					$status->list[$i] = $count;

					if($count > $max) $max = $count;

					$sum += $count;
				}
				break;

			case 'week' :
				$time = strtotime($selected_date);
				$w = date("D");

				while(date("D", $time) != "Sun")
				{
					$time += 60 * 60 * 24;
				}

				$time -= 60 * 60 * 24;

				while(date("D", $time) != "Sun")
				{
					$thisWeek[] = date("Ymd", $time);
					$time -= 60 * 60 * 24;
				}

				$thisWeek[] = date("Ymd", $time);

				asort($thisWeek);

				foreach($thisWeek as $day)
				{
					$args = new stdClass();
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

					$count = (int)($isPageView ? $output->data->pageview : $output->data->unique_visitor);
					$status->list[$day] = $count;

					if($count > $max) $max = $count;

					$sum += $count;
				}
				break;

			case 'month' :
				$year = substr($selected_date, 0, 4);
				for($i = 1; $i <= 12; $i++)
				{
					$args = new stdClass();
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

					$count = (int)($isPageView ? $output->data->pageview : $output->data->unique_visitor);
					$status->list[$i] = $count;

					if($count > $max) $max = $count;

					$sum += $count;
				}
				break;

			case 'hour' :
				for($i = 0; $i < 24; $i++)
				{
					$args = new stdClass();
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

					$count = (int) $output->data->count;
					$status->list[$i] = $count;

					if($count > $max) $max = $count;

					$sum += $count;
				}
				break;

			default :
				$year = substr($selected_date, 0, 4);
				$month = substr($selected_date, 4, 2);
				$end_day = date('t', mktime(0, 0, 0, $month, 1, $year));

				for($i = 1; $i <= $end_day; $i++)
				{
					$args = new stdClass();
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

					$count = (int)($isPageView ? $output->data->pageview : $output->data->unique_visitor);
					$status->list[$i] = $count;

					if($count > $max) $max = $count;

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
		foreach($output1->list as $key => $value)
		{
			$tmp["'" . $key . "'"] = $value;
		}
		$output1->list = $tmp;

		//for this week
		$date2 = date('Ymd');
		$output2 = $this->getHourlyStatus('week', $date2);

		$tmp = array();
		foreach($output2->list as $key => $value)
		{
			$tmp["'" . $key . "'"] = $value;
		}
		$output2->list = $tmp;

		$this->add('last_week', $output1);
		$this->add('this_week', $output2);
	}

	public function getWeeklyPageView()
	{
		//for last week
		$date1 = date('Ymd', strtotime('-1 week'));
		$output1 = $this->getHourlyStatus('week', $date1, 0, TRUE);

		$tmp = array();
		foreach($output1->list as $key => $value)
		{
			$tmp["'" . $key . "'"] = $value;
		}
		$output1->list = $tmp;

		//for this week
		$date2 = date('Ymd');
		$output2 = $this->getHourlyStatus('week', $date2, 0, TRUE);

		$tmp = array();
		foreach($output2->list as $key => $value)
		{
			$tmp["'" . $key . "'"] = $value;
		}
		$output2->list = $tmp;

		$this->add('last_week', $output1);
		$this->add('this_week', $output2);
	}

}
/* End of file counter.model.php */
/* Location: ./modules/counter/counter.model.php */
