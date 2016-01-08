<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class language_select 
 * @author NAVER (developers@xpressengine.com)
 * @brief Language selector
 * @version 0.1
 */
class pollWidget extends WidgetHandler
{
	/**
	 * @brief Widget execution
	 *
	 * Get extra_vars declared in ./widgets/widget/conf/info.xml as arguments
	 * After generating the result, do not print but return it.
	 */
	function proc($args)
	{
		// Set a path of the template skin (values of skin, colorset settings)
		$tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
		$tpl_file = 'pollview';

		Context::set('colorset', $args->colorset);
		Context::set('poll_srl', $args->poll_srl);
		Context::set('style', $args->style);

		// Compile a template
		$oTemplate = &TemplateHandler::getInstance();
		return $oTemplate->compile($tpl_path, $tpl_file);
	}
}
/* End of file language_select.class.php */
/* Location: ./widgets/language_select/language_select.class.php */
