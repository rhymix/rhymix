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
			$target_member = $v->target_nick_name;

			switch($v->type)
			{
				case 'D':
					$type = $lang->ncenterlite_document; //$type = '글';
				break;
				case 'C':
					$type = $lang->ncenterlite_comment; //$type = '댓글';
				break;
				// 메시지. 쪽지
				case 'E':
					$type = $lang->ncenterlite_type_message; //$type = '쪽지';
				break;
			}

			switch($v->target_type)
			{
				case 'C':
					$str = sprintf($lang->ncenterlite_commented, $target_member, $type, $v->target_summary);
					//$str = sprintf('<strong>%s</strong>님이 회원님의 %s에 <strong>"%s" 댓글</strong>을 남겼습니다.', $target_member, $type, $v->target_summary);
				break;
				case 'A':
					$str = sprintf($lang->ncenterlite_commented_board, $target_member, $v->target_browser, $v->target_summary);
					//$str = sprintf('<strong>%1$s</strong>님이 게시판 <strong>"%2$s"</strong>에 <strong>"%3$s"</strong>라고 댓글을 남겼습니다.', $target_member, $type, $v->target_summary);
				break;
				case 'M':
					$str = sprintf($lang->ncenterlite_mentioned, $target_member,  $v->target_summary, $type);
					//$str = sprintf('<strong>%s</strong>님이 <strong>"%s" %s</strong>에서 회원님을 언급하였습니다.', $target_member,  $v->target_summary, $type);
				break;
				// 메시지. 쪽지
				case 'E':
					if(version_compare(__XE_VERSION__, '1.7.4', '>='))
					{
						$str = sprintf($lang->ncenterlite_message_mention, $target_member, $v->target_summary);
						//<strong>%s</strong>님께서 <strong>"%s"</strong>라고 메세지를 보내셨습니다.
					}
					else
					{
						$str = sprintf($lang->ncenterlite_message_string, $v->target_summary);
					}
				break;
				case 'T':
					$str = sprintf($lang->ncenterlite_test_noti, $target_member);
				break;
				case 'P':
					$str = sprintf($lang->ncenterlite_board, $target_member, $v->target_browser, $v->target_summary);
					//<strong>%1$s</strong>님이 게시판 <strong>"%2$s"</strong>에 <strong>"%3$s"</strong>라고 글을 남겼습니다.
				break;
				case 'S':
					if($v->target_browser)
					{
						$str = sprintf($lang->ncenterlite_board, $target_member, $v->target_browser, $v->target_summary);
					}
					else
					{
						$str = sprintf($lang->ncenterlite_article, $target_member, $v->target_summary);
					}
				break;
				case 'V':
					$str = sprintf($lang->ncenterlite_vote, $target_member, $v->target_summary);
				break;
			}

			if($v->type=='U')
			{
				$str = $oNcenterliteModel->getNotifyTypeString($v->notify_type,unserialize($v->target_body));
			}
			$v->text = $str;
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