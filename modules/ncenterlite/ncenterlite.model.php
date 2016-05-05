<?php
class ncenterliteModel extends ncenterlite
{
	private static $config = NULL;
	var $notify_args;
	var $notify_arguments;

	function getConfig()
	{
		if(self::$config === NULL)
		{
			$oModuleModel = getModel('module');
			$config = $oModuleModel->getModuleConfig('ncenterlite');

			if(!$config)
			{
				$config = new stdClass();
			}
			if(!$config->use)
			{
				$config->use = array('message' => 1);
			}
			if(!$config->display_use) $config->display_use = 'Y';

			if(!$config->mention_names) $config->mention_names = 'nick_name';
			if(!$config->mention_suffixes)
			{
				$config->mention_suffixes = array('님', '様', 'さん', 'ちゃん');
			}
			unset($config->mention_format);
			if(!$config->hide_module_srls) $config->hide_module_srls = array();
			if(!is_array($config->hide_module_srls)) $config->hide_module_srls = explode('|@|', $config->hide_module_srls);
			if(!$config->document_read) $config->document_read = 'Y';
			if(!$config->skin) $config->skin = 'default';
			if(!$config->colorset) $config->colorset = 'black';
			if(!$config->zindex) $config->zindex = '9999';

			self::$config = $config;
		}

		return self::$config;
	}

	function getNotifyTypebySrl($notify_srl='')
	{
		$args = new stdClass();
		$args->notify_type_srl = $notify_srl;

		$output = executeQuery('ncenterlite.getNotifyType',$args);

		return $output;
	}

	function getNotifyTypeString($notify_srl='',$notify_args)
	{
		$this->notify_args = $notify_args;

		$output = $this->getNotifyTypebySrl($notify_srl);

		$this->notify_arguments = explode("|",$output->data->notify_type_args);
		$string = preg_replace_callback("/%([^%]*)%/",array($this, 'replaceNotifyType'),$output->data->notify_string);

		return $string;
	}

	function replaceNotifyType($match)
	{
		//if replace string is not at arguments, return
		if(!in_array($match[1],$this->notify_arguments))
		{
			return $match[0];
		}

		//if replace string is not set, return
		if(!isset($this->notify_args->{$match[1]}))
		{
			return $match[0];
		}

		return $this->notify_args->{$match[1]};
	}

	function isNotifyTypeExistsbySrl($notify_srl='')
	{
		$args = new stdClass();
		$args->notify_type_srl = $notify_srl;

		$output = executeQuery('ncenterlite.getNotifyType',$args);

		return isset($output->data->notify_type_id);
	}

	function insertNotifyType($args)
	{
		return executeQuery('ncenterlite.insertNotifyType',$args);
	}

	function getMemberConfig($member_srl=null)
	{
		if(!$member_srl)
		{
			$logged_info = Context::get('logged_info');
			$member_srl = $logged_info->member_srl;
		}

		$args = new stdClass();
		$args->member_srl = $member_srl;
		$output = executeQuery('ncenterlite.getUserConfig', $args);
		if(!$output->data) return $output->data;

		return $output;
	}

	function getAllMemberConfig()
	{
		$output = executeQueryArray('ncenterlite.getAllUserConfig');

		return $output;
	}

	function getMyNotifyList($member_srl=null, $page=1, $readed='N')
	{
		global $lang;

		$act = Context::get('act');
		if($act=='dispNcenterliteNotifyList')
		{
			$output = $this->getMyDispNotifyList($member_srl);
		}
		else
		{
			$output = $this->_getMyNotifyList($member_srl, $page, $readed);
		}

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
					$str = sprintf($lang->ncenterlite_mentioned, $target_member, $v->target_browser, $v->target_summary);
					//$str = sprintf('<strong>%s</strong>님이 <strong>"%s" %s</strong>에서 회원님을 언급하였습니다.', $target_member,  $v->target_summary, $type);
				break;
				// 메시지. 쪽지
				case 'E':
					$str = sprintf($lang->ncenterlite_message_mention,$target_member, $v->target_summary);
				break;
				case 'T':
					$str = sprintf($lang->ncenterlite_test_noti, $target_member);
				break;
				case 'P':
					$str = sprintf($lang->ncenterlite_board, $target_member, $v->target_browser, $v->target_summary);
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
				case 'B':
					$str = sprintf($lang->ncenterlite_admin_content_message, $target_member, $v->target_browser, $v->target_summary);
				break;
			}

			if($v->type=='U')
			{
				$str = $this->getNotifyTypeString($v->notify_type,unserialize($v->target_body));
			}

			$v->text = $str;
			$v->ago = $this->getAgo($v->regdate);
			$v->url = getUrl('','act','procNcenterliteRedirect', 'notify', $v->notify, 'url', $v->target_url);
			if($v->target_member_srl)
			{
				$profileImage = $oMemberModel->getProfileImage($v->target_member_srl);
				$v->profileImage = $profileImage->src;
			}

			$list[$k] = $v;
		}

		$output->data = $list;

		if($page <= 1 && $output->flag_exists !== true)
		{
			$oNcenterliteController = getController('ncenterlite');
			$oNcenterliteController->updateFlagFile($member_srl, $output);
		}
		return $output;
	}

	function getMyNotifyListTpl()
	{
		$logged_info = Context::get('logged_info');
		if(!$logged_info) return new Object(-1, 'msg_not_permitted');

		$oMemberModel = getModel('member');
		$memberConfig = $oMemberModel->getMemberConfig();
		$page = Context::get('page');

		$member_srl = $logged_info->member_srl;
		$tmp = $this->getMyNotifyList($member_srl, $page);
		foreach($tmp->data as $key => $obj)
		{
			$tmp->data[$key]->url = str_replace('&amp;', '&', $obj->url);
		}

		$list = new stdClass();
		$list->data = $tmp->data;
		$list->page = $tmp->page_navigation;
		$this->add('list', $list);
		$this->add('useProfileImage', $memberConfig->profile_image);
	}

	function _getMyNotifyList($member_srl=null, $page=1, $readed='N')
	{
		if(!$member_srl)
		{
			$logged_info = Context::get('logged_info');
			if(!$logged_info) return array();

			$member_srl = $logged_info->member_srl;
		}
		$flag_path = \RX_BASEDIR . 'files/cache/ncenterlite/new_notify/' . getNumberingPath($member_srl) . $member_srl . '.php';

		if(FileHandler::exists($flag_path) && $page <= 1)
		{
			$output = require_once $flag_path;
			if(is_object($output))
			{
				$output->flag_exists = true;
				return $output;
			}
		}
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$args->page = $page ? $page : 1;
		if($readed) $args->readed = $readed;
		$output = executeQueryArray('ncenterlite.getNotifyList', $args);
		$output->flag_exists = false;
		if(!$output->data) $output->data = array();

		return $output;
	}

	function getMyDispNotifyList($member_srl = null)
	{
		if(!$member_srl)
		{
			$logged_info = Context::get('logged_info');
			$member_srl = $logged_info->member_srl;
		}

		$args = new stdClass();
		$args->page = Context::get('page');
		$args->list_count = '20';
		$args->page_count = '10';
		$args->member_srl = $member_srl;
		$output = executeQueryArray('ncenterlite.getDispNotifyList', $args);
		if(!$output->data) $output->data = array();

		return $output;
	}

	function getNcenterliteAdminList()
	{
		$args = new stdClass();
		$args->page = Context::get('page');
		$args->list_count = '20';
		$args->page_count = '10';
		$output = executeQueryArray('ncenterlite.getAdminNotifyList', $args);
		if(!$output->data) $output->data = array();

		return $output;
	}

	function getMemberAdmins()
	{
		$args = new stdClass();
		$args->is_admin = 'Y';
		$output = executeQueryArray('ncenterlite.getMemberAdmins', $args);
		$member_srl = array();
		foreach($output->data as $member)
		{
			$member_srl[] = $member->member_srl;
		}
		
		return $member_srl;
	}

	function _getNewCount($member_srl=null)
	{
		if(!$member_srl)
		{
			$logged_info = Context::get('logged_info');
			if(!$logged_info) return 0;

			$member_srl = $logged_info->member_srl;
		}

		$args = new stdClass();
		$args->member_srl = $member_srl;
		$output = executeQuery('ncenterlite.getNotifyNewCount', $args);
		if(!$output->data) return 0;
		return $output->data->cnt;
	}


	function getColorsetList()
	{
		$oModuleModel = getModel('module');
		$skin = Context::get('skin');

		$skin_info = $oModuleModel->loadSkinInfo($this->module_path, $skin);

		for($i=0, $c=count($skin_info->colorset); $i<$c; $i++)
		{
			$colorset = sprintf('%s|@|%s', $skin_info->colorset[$i]->name, $skin_info->colorset[$i]->title);
			$colorset_list[] = $colorset;
		}

		if(count($colorset_list)) $colorsets = implode("\n", $colorset_list);
		$this->add('colorset_list', $colorsets);
	}

	/**
	 * @brief 주어진 시간이 얼마 전 인지 반환
	 * @param string YmdHis
	 * @return string
	 **/
	function getAgo($datetime)
	{
		global $lang;
		$lang_type = Context::getLangType();

		$display = $lang->ncenterlite_date; // array('Year', 'Month', 'Day', 'Hour', 'Minute', 'Second')

		$ago = $lang->ncenterlite_ago; // 'Ago'

		$date = getdate(strtotime(zdate($datetime, 'Y-m-d H:i:s')));

		$current = getdate();
		$p = array('year', 'mon', 'mday', 'hours', 'minutes', 'seconds');
		$factor = array(0, 12, 30, 24, 60, 60);

		for($i = 0; $i < 6; $i++)
		{
			if($i > 0)
			{
				$current[$p[$i]] += $current[$p[$i - 1]] * $factor[$i];
				$date[$p[$i]] += $date[$p[$i - 1]] * $factor[$i];
			}

			if($current[$p[$i]] - $date[$p[$i]] > 1)
			{
				$value = $current[$p[$i]] - $date[$p[$i]];
				if($lang_type == 'en')
				{
					return $value . ' ' . $display[$i] . (($value != 1) ? 's' : '') . ' ' . $ago;
				}
				return $value . $display[$i] . ' ' . $ago;
			}
		}

		return zdate($datetime, 'Y-m-d');
	}
}
