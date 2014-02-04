<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  pageWap
 * @author NAVER (developers@xpressengine.com)
 * @brief wap class page of the module
 */
class pageWap extends page
{
	/**
	 * @brief wap procedure method
	 *
	 * Page module does not include the following items on the full content control and output from the mobile class
	 */
	function procWAP(&$oMobile)
	{
		// Check permissions
		if(!$this->grant->access) return $oMobile->setContent(Context::getLang('msg_not_permitted'));
		// The contents of the widget chuchulham
		$oWidgetController = getController('widget');
		$content = $oWidgetController->transWidgetCode($this->module_info->content);
		$oMobile->setContent($content);
	}
}
/* End of file page.wap.php */
/* Location: ./modules/page/page.wap.php */
