<?php

namespace Rhymix\Modules\Sociallogin\Controllers;

use Context;
use FileHandler;
use MemberController;
use SocialloginController;
use SocialloginModel;
use Rhymix\Modules\Sociallogin\Base;

class EventHandlers extends Base
{
	/**
	 * Add member menu for SNS management.
	 */
	public function triggerAfterModuleObject()
	{
		if ($this->user->isMember())
		{
			MemberController::getInstance()->addMemberMenu('dispSocialloginSnsManage', 'sns_manage');
		}
	}

	/**
	 * Trigger before inserting new member.
	 **/
	public function triggerBeforeInsertMember(&$config)
	{
		// SNS 로그인시에는 메일인증을 사용안함
		if (Context::get('act') == 'procSocialloginCallback' || $_SESSION['tmp_sociallogin_input_add_info'])
		{
			$config->enable_confirm = 'N';
		}
	}
	
	/**
	 * Trigger after inserting new member.
	 */
	public function triggerAfterInsertMember($obj)
	{
		$oMemberController = getController('member');
		$oSocialData = SocialloginModel::getSocialSignUpUserData();
		
		if(isset($_SESSION['tmp_sociallogin_input_add_info']['profile_dir']))
		{
			$oMemberController->insertProfileImage($obj->member_srl, $_SESSION['tmp_sociallogin_input_add_info']['profile_dir']);

			FileHandler::removeFile($_SESSION['tmp_sociallogin_input_add_info']['profile_dir']);
		}

		if($oSocialData && isset($_SESSION['sociallogin_access_data']))
		{
			SocialloginController::getInstance()->insertMemberSns($obj->member_srl, $_SESSION['sociallogin_access_data']);
		}
	}

	/**
	 * Trigger after deleting a member.
	 */
	public function triggerAfterDeleteMember($obj)
	{
		$args = new \stdClass;
		$args->member_srl = $obj->member_srl;
		$output = executeQueryArray('sociallogin.getMemberSns', $args);

		$sns_id = array();

		foreach ($output->data as $key => $val)
		{
			$sns_id[] = '[' . $val->service . '] ' . $val->service_id;

			if (!$oDriver = $this->getDriver($val->service))
			{
				continue;
			}

			// 토큰 넣기
			$tokenData = SocialloginModel::setAvailableAccessToken($oDriver, $val, false);

			// 토큰 파기
			$oDriver->revokeToken($tokenData['access']);
		}

		executeQuery('sociallogin.deleteMemberSns', $args);

		// 로그 기록
		$info = new \stdClass;
		$info->service_id = implode(' | ', $sns_id);
		$info->nick_name = Context::get('logged_info')->nick_name;
		$info->member_srl = $obj->member_srl;
		SocialloginModel::logRecord('delete_member', $info);
	}

	/**
	 * Add an entry to the member menu.
	 */
	public function triggerMemberMenu()
	{
		if (!($member_srl = Context::get('target_srl')) || self::getConfig()->sns_profile != 'Y')
		{
			return;
		}

		if (!SocialloginModel::memberUserSns($member_srl))
		{
			return;
		}

		getController('member')->addMemberPopupMenu(getUrl('', 'mid', Context::get('cur_mid'), 'act', 'dispSocialloginSnsProfile', 'member_srl', $member_srl), 'sns_profile', '');
	}

	/**
	 * Trigger after inserting new document.
	 */
	public function triggerAfterInsertDocument($obj)
	{
		$config = self::getConfig();
		
		if($config->sns_share_on_write !== 'Y')
		{
			return;
		}
		
		if (!$this->user->isMember())
		{
			return;
		}

		// 설정된 모듈 제외
		if ($config->linkage_module_srl)
		{
			$module_srl_list = explode(',', $config->linkage_module_srl);

			if ($config->linkage_module_target == 'exclude' && in_array($obj->module_srl, $module_srl_list) || $config->linkage_module_target != 'exclude' && !in_array($obj->module_srl, $module_srl_list))
			{
				return;
			}
		}

		if (!SocialloginModel::memberUserSns())
		{
			return;
		}

		foreach ($config->sns_services as $key => $val)
		{
			if (!($sns_info = SocialloginModel::getMemberSnsByService($val)) || $sns_info->linkage != 'Y')
			{
				continue;
			}

			if (!$oDriver = $this->getDriver($val))
			{
				continue;
			}

			// 토큰 넣기
			SocialloginModel::setAvailableAccessToken($oDriver, $sns_info);

			$args = new \stdClass;
			$args->title = $obj->title;
			$args->content = $obj->content;
			$args->url = getNotEncodedFullUrl('', 'document_srl', $obj->document_srl);
			$oDriver->post($args);

			// 로그 기록
			$info = new \stdClass;
			$info->sns = $val;
			$info->title = $obj->title;
			SocialloginModel::logRecord('linkage', $info);
		}
	}
}
