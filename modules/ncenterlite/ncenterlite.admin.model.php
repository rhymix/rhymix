<?php

class ncenterliteAdminModel extends ncenterlite
{

	function getAdminNotifyList($member_srl=null, $page=1, $readed='N')
	{
		$oNcenterliteModel = getModel('ncenterlite');
		$config = $oNcenterliteModel->getConfig();

		global $lang;

		$act = Context::get('act');
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

			$list[$key]->member_info = $member_info;
		}

		$output->data = $list;
		return $output;
	}

}
