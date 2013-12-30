<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class counter_status
 * @author NAVER (developers@xpressengine.com)
 * @version 0.1
 * @brief Display counter status by using data in the counter module
 */
class counter_status extends WidgetHandler
{
	/**
	 * @brief Widget execution
	 * Get extra_vars declared in ./widgets/widget/conf/info.xml as arguments
	 * After generating the result, do not print but return it.
	 */
	function proc($args)
	{
		// Get status of the accumulated, yesterday's, today's counts
		$oCounterModel = getModel('counter');

		$site_module_info = Context::get('site_module_info');
		$output = $oCounterModel->getStatus(array('00000000', date('Ymd', $_SERVER['REQUEST_TIME']-60*60*24), date('Ymd')), $site_module_info->site_srl);
		if(count($output))
		{
			foreach($output as $key => $val) 
			{
				if(!$key) Context::set('total_counter', $val);
				elseif($key == date("Ymd")) Context::set('today_counter', $val);
				else Context::set('yesterday_counter', $val);
			}
		}
		// Set a path of the template skin (values of skin, colorset settings)
		$tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
		Context::set('colorset', $args->colorset);
		// Specify a template file
		$tpl_file = 'counter_status';
		// Compile a template
		$oTemplate = &TemplateHandler::getInstance();
		return $oTemplate->compile($tpl_path, $tpl_file);
	}
}
/* End of file counter_status.class.php */
/* Location: ./widgets/counter_status/counter_status.class.php */
