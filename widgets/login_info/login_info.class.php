<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class login_info
 * @author NAVER (developers@xpressengine.com)
 * @version 0.1
 * @brief Widget to display log-in form
 *
 * $Pre-configured by using $logged_info
 */
class login_info extends WidgetHandler
{
	/**
	 * @brief Widget execution
	 * Get extra_vars declared in ./widgets/widget/conf/info.xml as arguments
	 * After generating the result, do not print but return it.
	 */
	function proc($args)
	{
		// Set a path of the template skin (values of skin, colorset settings)
		$tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
		Context::set('colorset', $args->colorset);

		$is_logged = Context::get('is_logged');
		$oMemberModel = getModel('member');
		$memberConfig = $oMemberModel->getMemberConfig();

		$oNcenterliteModel = getModel('ncenterlite');
		$ncenter_config = $oNcenterliteModel->getConfig();
		if($is_logged)
		{
			if(!empty($ncenter_config->use) && $args->ncenter_use == 'yes')
			{
				$logged_info = Context::get('logged_info');
				$ncenter_list = $oNcenterliteModel->getMyNotifyList($logged_info->member_srl);
				$_latest_notify_id = $ncenter_list->data ? array_slice($ncenter_list->data, 0, 1) : [];
				$_latest_notify_id = isset($_latest_notify_id[0]) ? $_latest_notify_id[0]->notify : null;
				if($memberConfig->profile_image == 'Y')
				{
					$profileImage = $oMemberModel->getProfileImage($logged_info->member_srl);
					Context::set('profileImage', $profileImage);
				}
				Context::set('ncenterlite_latest_notify_id', $_latest_notify_id);
				if(isset($_COOKIE['_ncenterlite_hide_id']) && $_COOKIE['_ncenterlite_hide_id'] === $_latest_notify_id)
				{
					return;
				}
				setcookie('_ncenterlite_hide_id', '', 0, '/');
			}
			$tpl_file = 'login_info';
		}
		else
		{
			$tpl_file = 'login_form';
		}
		// Get the member configuration
		$oModuleModel = getModel('module');
		$this->member_config = $oModuleModel->getModuleConfig('member');
		if($ncenter_config->zindex)
		{
			Context::set('ncenterlite_zindex', ' style="z-index:' . $ncenter_config->zindex . ';" ');
		}
		Context::set('useProfileImage', ($memberConfig->profile_image == 'Y') ? true : false);
		Context::set('ncenterlite_list', $ncenter_list->data);
		Context::set('ncenterlite_page_navigation', $ncenter_list->page_navigation);
		Context::set('_ncenterlite_num', $ncenter_list->page_navigation->total_count);
		Context::set('member_config', $this->member_config);

		// Set a flag to check if the https connection is made when using SSL and create https url 
		$ssl_mode = false;
		$useSsl = Context::getSslStatus();
		if($useSsl != 'none')
		{
			if(strncasecmp('https://', Context::getRequestUri(), 8) === 0)
			{
				$ssl_mode = true;
			}
		}
		Context::set('ssl_mode', $ssl_mode);

		// Compile a template
		$oTemplate = &TemplateHandler::getInstance();
		return $oTemplate->compile($tpl_path, $tpl_file);
	}
}
/* End of file login_info.class.php */
/* Location: ./widgets/login_info/login_info.class.php */
