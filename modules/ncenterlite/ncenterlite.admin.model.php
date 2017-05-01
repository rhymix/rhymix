<?php

class ncenterliteAdminModel extends ncenterlite
{
	function getAdminNotifyList()
	{
		$oNcenterliteModel = getModel('ncenterlite');

		$output = $oNcenterliteModel->getNcenterliteAdminList();

		$oMemberModel = getModel('member');
		$list = $output->data;

		foreach($list as $key => $value)
		{
			$value->text = $oNcenterliteModel->getNotificationText($value);
			$value->ago = $oNcenterliteModel->getAgo($value->regdate);
			$value->url = getUrl('','act','procNcenterliteRedirect', 'notify', $value->notify, 'url', $value->target_url);
			if($value->target_member_srl)
			{
				$profileImage = $oMemberModel->getProfileImage($value->target_member_srl);
				$value->profileImage = $profileImage->src;
			}

			$list[$key] = $value;
			$member_info = $oMemberModel->getMemberInfoByMemberSrl($value->member_srl);

			$list[$key]->nick_name = $member_info->nick_name;
		}

		$output->data = $list;
		return $output;
	}

	/**
	 * Get notify type list.
	 * @return object
	 */
	function getNotifyType()
	{
		$args = new stdClass();
		$args->page = Context::get('page');
		$args->list_count = '20';
		$args->page_count = '10';
		$output = executeQueryArray('ncenterlite.getNotifyTypeAdminList', $args);

		return $output;
	}
}
