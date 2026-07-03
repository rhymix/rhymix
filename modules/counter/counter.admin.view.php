<?php

class CounterAdminView extends Counter
{
	/**
	 * Admin page
	 */
	function dispCounterAdminIndex()
	{
		// set today's if no date is given
		$selected_date = (int)Context::get('selected_date');
		if (!$selected_date || !ctype_digit($selected_date))
		{
			$selected_date = date('Ymd');
		}
		Context::set('selected_date', $selected_date);

		// get a total count and daily count
		$status = CounterModel::getStatus(array(0, $selected_date));
		Context::set('total_counter', $status[0]);
		Context::set('selected_day_counter', $status[$selected_date]);

		// get data by time, day, month, and year
		$type = Context::get('type');
		if (!$type)
		{
			$type = 'day';
			Context::set('type', $type);
		}

		$detail_status = CounterModel::getHourlyStatus($type, $selected_date);
		Context::set('detail_status', $detail_status);

		// display
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('index');
	}

	/**
	 * Admin config
	 */
	function dispCounterAdminConfig()
	{
		// get config
		$config = CounterModel::getConfig();
		Context::set('config', $config);

		// display
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('config');
	}
}
