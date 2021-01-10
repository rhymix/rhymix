<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Admin view class of counter module
 *
 * @author NAVER (developers@xpressengine.com)
 */
class counterAdminView extends counter
{

	/**
	 * Initialization
	 *
	 * @return void
	 */
	function init()
	{
		// set the template path
		$this->setTemplatePath($this->module_path . 'tpl');
	}

	/**
	 * Admin page 
	 *
	 * @return Object
	 */
	function dispCounterAdminIndex()
	{
		// set today's if no date is given
		$selected_date = (int)Context::get('selected_date');

		if(!$selected_date)
		{
			$selected_date = date("Ymd");
		}

		Context::set('selected_date', $selected_date);

		// create the counter model object
		$oCounterModel = getModel('counter');

		// get a total count and daily count
		$status = $oCounterModel->getStatus(array(0, $selected_date));
		Context::set('total_counter', $status[0]);
		Context::set('selected_day_counter', $status[$selected_date]);

		// get data by time, day, month, and year
		$type = Context::get('type');
		if(!$type)
		{
			$type = 'day';
			Context::set('type', $type);
		}

		$detail_status = $oCounterModel->getHourlyStatus($type, $selected_date);
		Context::set('detail_status', $detail_status);

		// display
		$this->setTemplateFile('index');
	}

}
/* End of file counter.admin.view.php */
/* Location: ./modules/counter/counter.admin.view.php */
