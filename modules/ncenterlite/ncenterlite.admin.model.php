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

		foreach($list as $k => $v)
		{
			$v->text = $oNcenterliteModel->getNotificationText($v);
			$v->ago = $oNcenterliteModel->getAgo($v->regdate);
			$v->url = getUrl('','act','procNcenterliteRedirect', 'notify', $v->notify, 'url', $v->target_url);
			if($v->target_member_srl)
			{
				$profileImage = $oMemberModel->getProfileImage($v->target_member_srl);
				$v->profileImage = $profileImage->src;
			}

			$list[$k] = $v;
		}

		$output->data = $list;
		return $output;
	}

}
