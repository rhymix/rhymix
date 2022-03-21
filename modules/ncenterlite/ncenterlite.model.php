<?php
class ncenterliteModel extends ncenterlite
{
	protected static $_config = NULL;
	protected static $_user_config = [];
	public $notify_args;
	public $notify_arguments;

	public static function getConfig()
	{
		if(self::$_config === NULL)
		{
			$oModuleModel = getModel('module');
			$config = $oModuleModel->getModuleConfig('ncenterlite');
			
			if(!$config)
			{
				$config = new stdClass();
			}
			
			$config->use = $config->use ?? array('message' => array('web' => 1));
			$config->display_use = $config->display_use ?? 'all';
			$config->always_display = $config->always_display ?? 'N';
			$config->user_config_list = $config->user_config_list ?? 'N';
			$config->user_notify_setting = $config->user_notify_setting ?? 'N';
			$config->document_read = $config->document_read ?? 'Y';
			$config->variable_name = $config->variable_name ?? 0;
			$config->mention_names = $config->mention_names ?? 'nick_name';
			$config->mention_suffixes = $config->mention_suffixes ?? array('님', '様', 'さん', 'ちゃん');
			$config->mention_suffix_always_cut = $config->mention_suffix_always_cut ?? 'N';
			$config->mention_limit = $config->mention_limit ?? 20;
			$config->anonymous_voter = $config->anonymous_voter ?? 'N';
			$config->anonymous_scrap = $config->anonymous_scrap ?? 'N';
			$config->highlight_effect = $config->highlight_effect ?? 'Y';
			$config->unsubscribe = $config->unsubscribe ?? 'N';
			$config->comment_all = $config->comment_all ?? 'N';
			$config->comment_all_notify_module_srls = $config->comment_all_notify_module_srls ?? array();
			$config->hide_module_srls = $config->hide_module_srls ?? array();
			$config->admin_notify_module_srls = $config->admin_notify_module_srls ?? array();
			$config->layout_srl = $config->layout_srl ?? 0;
			$config->mlayout_srl = $config->mlayout_srl ?? 0;
			$config->skin = $config->skin ?? 'default';
			$config->colorset = $config->colorset ?? 'black';
			$config->mskin = $config->mskin ?? 'default';
			$config->mcolorset = $config->mcolorset ?? 'black';
			$config->zindex = $config->zindex ?? '9999';
			$config->notify_count = $config->notify_count ?? 5;
			
			if(!isset($config->hide_module_srls))
			{
				$config->hide_module_srls = array();
			}
			elseif(!is_array($config->hide_module_srls))
			{
				$config->hide_module_srls = explode('|@|', $config->hide_module_srls);
			}
			
			// Convert old config format
			if($config->use === 'Y')
			{
				$config->use = array();
				foreach (self::getNotifyTypes() as $type => $srl)
				{
					$config->use[$type] = array('web' => 1);
				}
			}
			elseif(is_array($config->use) && !is_array(array_first($config->use)))
			{
				foreach($config->use as $key => $value)
				{
					$config->use[$key] = array();
					$config->use[$key]['web'] = $value;
				}
				getController('module')->insertModuleConfig('ncenterlite', $config);
			}
			unset($config->mention_format);
			
			self::$_config = $config;
		}

		return self::$_config;
	}
	
	public static function getNotifyTypes()
	{
		$default = array(
			'comment' => 0,
			'comment_comment' => 0,
			'mention' => 0,
			'vote' => 0,
			'scrap' => 0,
			'message' => 0,
		);
		if (Context::get('logged_info')->is_admin === 'Y')
		{
			$default['admin_content'] = 0;
		}
		
		$custom_types = Rhymix\Framework\Cache::get('ncenterlite:notify_types');
		if (!$custom_types)
		{
			$custom_types = executeQueryArray('ncenterlite.getNotifyType', [])->data;
			Rhymix\Framework\Cache::set('ncenterlite:notify_types', $custom_types, 0, true);
		}
		foreach ($custom_types as $type)
		{
			if (!isset($default[$type->notify_type_id]))
			{
				$default[$type->notify_type_id] = $type->notify_type_srl;
			}
		}
		
		$default['custom'] = 0;
		return $default;
	}
	
	public static function getUserSetNotifyTypes()
	{
		return array(
			'comment' => 0,
			'comment_comment' => 0,
			'mention' => 0,
			'vote' => 0,
			'scrap' => 0,
			'message' => 0,
		);
	}

	function getNotifyTypebySrl($notify_srl)
	{
		$args = new stdClass();
		$args->notify_type_srl = $notify_srl;

		$output = executeQuery('ncenterlite.getNotifyType',$args);

		return $output;
	}

	function getNotifyTypeString($notify_srl, $notify_args)
	{
		$this->notify_args = $notify_args;

		$output = $this->getNotifyTypebySrl($notify_srl);

		$this->notify_arguments = explode("|",$output->data->notify_type_args);
		$string = preg_replace_callback("/%([^%]*)%/",array($this, 'replaceNotifyType'),$output->data->notify_string);

		return $string;
	}

	function replaceNotifyType($match)
	{
		if(!in_array($match[1],$this->notify_arguments))
		{
			return $match[0];
		}

		if(!isset($this->notify_args->{$match[1]}))
		{
			return $match[0];
		}

		return $this->notify_args->{$match[1]};
	}

	function isNotifyTypeExistsbySrl($notify_srl)
	{
		$args = new stdClass();
		$args->notify_type_srl = $notify_srl;

		$output = executeQuery('ncenterlite.getNotifyType',$args);

		return isset($output->data->notify_type_id);
	}

	function insertNotifyType($args)
	{
		$output = executeQuery('ncenterlite.insertNotifyType', $args);
		Rhymix\Framework\Cache::delete('ncenterlite:notify_types');
		return $output;
	}

	/**
	 * @brief Get user notify config.
	 * @param null $member_srl
	 * @return object|bool
	 */
	public static function getUserConfig($member_srl = null)
	{
		if(!$member_srl)
		{
			$logged_info = Context::get('logged_info');
			$member_srl = $logged_info->member_srl ?? 0;
			if (!$member_srl)
			{
				return false;
			}
		}
		
		$member_srl = intval($member_srl);
		$config = self::$_user_config[$member_srl] ?? null;
		if ($config !== null)
		{
			return $config;
		}
		
		$config = Rhymix\Framework\Cache::get('ncenterlite:user_config:' . $member_srl);
		if ($config !== null)
		{
			return $config;
		}
		
		$args = new stdClass();
		$args->member_srl = $member_srl;
		$output = executeQuery('ncenterlite.getUserConfig', $args);
		if (!$output->data)
		{
			$config = false;
		}
		else
		{
			$config = new stdClass;
			foreach (self::getNotifyTypes() as $type => $srl)
			{
				$disabled_list = $output->data->{$type . '_notify'} ?? '';
				if ($disabled_list === 'N')
				{
					$config->{$type} = [];
				}
				elseif ($disabled_list === 'Y' || $disabled_list === '')
				{
					$config->{$type} = ['web', 'mail', 'sms', 'push'];
				}
				else
				{
					$disabled_list = array_map(function($str) { return substr($str, 1); }, explode(',', $disabled_list));
					$config->{$type} = array_diff(['web', 'mail', 'sms', 'push'], $disabled_list);
				}
			}
		}
		
		self::$_user_config[$member_srl] = $config;
		Rhymix\Framework\Cache::set('ncenterlite:user_config:' . $member_srl, $config);
		return $config;
	}

	function getAllMemberConfig()
	{
		$output = executeQueryArray('ncenterlite.getAllUserConfig');

		return $output;
	}

	function getMyNotifyList($member_srl = null, $page = 1, $readed = 'N', $disp = false)
	{
		if(!$member_srl && !Context::get('is_logged'))
		{
			return false;
		}

		if (!$member_srl)
		{
			$member_srl = Context::get('logged_info')->member_srl;
		}

		if($disp)
		{
			$output = $this->getMyDispNotifyList($member_srl);
		}
		else
		{
			$output = $this->_getMyNotifyList($member_srl, $page, $readed);
		}
		
		$config = $this->getConfig();
		$oMemberModel = getModel('member');
		$list = $output->data;
		
		foreach($list as $k => $v)
		{
			$v->text = $this->getNotificationText($v);
			$v->ago = $this->getAgo($v->regdate);
			$v->url = getUrl('','act','procNcenterliteRedirect', 'notify', $v->notify);
			
			if($v->target_member_srl < 0)
			{
				$v->target_member_srl = 0;
				$v->target_nick_name = lang('anonymous');
				$v->target_user_id = $v->target_email_address = 'anonymous';
			}
			
			if(($v->target_type === $this->_TYPE_VOTED && $config->anonymous_voter === 'Y') || ($v->target_type === $this->_TYPE_SCRAPPED && $config->anonymous_scrap === 'Y'))
			{
				$v->target_member_srl = 0;
				$v->target_nick_name = lang('anonymous');
				$v->target_user_id = $v->target_email_address = 'anonymous';
			}
			
			if($v->target_member_srl)
			{
				$profileImage = $oMemberModel->getProfileImage($v->target_member_srl);
				$v->profileImage = $profileImage ? $profileImage->src : null;
			}
			$list[$k] = $v;
		}

		$output->data = $list;
		return $output;
	}

	function getMyNotifyListTpl()
	{
		if (!Context::get('is_logged'))
		{
			return new BaseObject(-1, 'msg_not_permitted');
		}

		$memberConfig = getModel('member')->getMemberConfig();
		$page = Context::get('page');

		$logged_info = Context::get('logged_info');
		$member_srl = $logged_info->member_srl;
		$tmp = $this->getMyNotifyList($member_srl, $page);
		foreach($tmp->data as $key => $obj)
		{
			unset($tmp->data[$key]->target_email_address);
			unset($tmp->data[$key]->target_user_id);
			unset($tmp->data[$key]->target_member_srl);
			$tmp->data[$key]->url = str_replace('&amp;', '&', $obj->url);
		}

		$list = new stdClass();
		$list->data = $tmp->data;
		$list->page = $tmp->page_navigation;
		$this->add('list', $list);
		$this->add('useProfileImage', $memberConfig->profile_image);
	}

	function _getMyNotifyList($member_srl = null, $page = 1, $readed = 'N')
	{
		$oNcenterliteController = getController('ncenterlite');

		if(!$member_srl)
		{
			if (!Context::get('is_logged'))
			{
				return array();
			}
			$logged_info = Context::get('logged_info');
			$member_srl = $logged_info->member_srl;
		}

		$cache_key = sprintf('ncenterlite:notify_list:%d', $member_srl);

		if ($page <= 1 && $readed == 'N')
		{
			$output = Rhymix\Framework\Cache::get($cache_key);
			if($output !== null)
			{
				return $output;
			}
		}

		$flag_path = \RX_BASEDIR . 'files/cache/ncenterlite/new_notify/' . getNumberingPath($member_srl) . $member_srl . '.php';
		if($page <= 1 && $readed == 'N' && FileHandler::exists($flag_path))
		{
			$deleteFlagPath = \RX_BASEDIR . 'files/cache/ncenterlite/new_notify/delete_date.php';

			$deleteOutput = Rhymix\Framework\Storage::readPHPData($deleteFlagPath);
			if($deleteOutput !== false)
			{
				$create_time = filemtime($flag_path);

				if($create_time <= $deleteOutput->regdate)
				{
					$oNcenterliteController->removeFlagFile($member_srl);
				}
				else
				{
					$output = Rhymix\Framework\Storage::readPHPData($flag_path);
					if($output !== false)
					{
						Rhymix\Framework\Cache::set($cache_key, $output);
						return $output;
					}
				}
			}
		}

		$args = new stdClass();
		$args->member_srl = $member_srl;
		
		$args->page = $page ? $page : 1;
		if ($readed)
		{
			$args->readed = $readed;
		}
		
		$notify_count = intval(self::getConfig()->notify_count);
		if($notify_count)
		{
			$args->list_count = $notify_count;
		}
		
		$output = executeQueryArray('ncenterlite.getNotifyList', $args);
		if (!$output->data)
		{
			$output->data = array();
		}

		if (Rhymix\Framework\Cache::getDriverName() !== 'dummy')
		{
			if($page <= 1 && $readed == 'N')
			{
				Rhymix\Framework\Cache::set($cache_key, $output);
			}
		}
		else
		{
			if($page <= 1 && $readed == 'N')
			{
				$oNcenterliteController->updateFlagFile($member_srl, $output);
			}
		}

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
		$args->page = max(1, intval(Context::get('page')));
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

	function _getNewCount($member_srl = null)
	{
		if($member_srl === null)
		{
			if (!Context::get('is_logged'))
			{
				return 0;
			}

			$logged_info = Context::get('logged_info');
			$member_srl = $logged_info->member_srl;
		}

		$cache_key = sprintf('ncenterlite:notify_list:%d', $member_srl);
		$output = Rhymix\Framework\Cache::get($cache_key);
		if($output !== null)
		{
			return $output->total_count;
		}
		elseif (Rhymix\Framework\Cache::getDriverName() !== 'dummy')
		{
			$output = $this->_getMyNotifyList($member_srl);
			return $output->total_count;
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
	 * Get information about a single notification.
	 * 
	 * @param string $notify
	 * @param int $member_srl
	 * @return object|false
	 */
	public function getNotification($notify, $member_srl)
	{
		$args = new stdClass;
		$args->notify = $notify;
		$args->member_srl = $member_srl;
		$output = executeQuery('ncenterlite.getNotify', $args);
		if ($output->toBool() && $output->data)
		{
			return $output->data;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Return the notification text.
	 * 
	 * @param object $notification
	 * @return string
	 */
	public function getNotificationText($notification)
	{
		// Get the type of notification.
		switch ($notification->type)
		{
			// Document.
			case 'D':
				$type = lang('ncenterlite_document');
				break;

			// Comment.
			case 'C':
				$type = lang('ncenterlite_comment');
				break;

			// Message.
			case 'E':
				$type = lang('member_message');
				break;

			// Test.
			case 'T':
				$type = lang('ncenterlite_type_test');
				break;

			// Custom string.
			case 'X':
				return $notification->target_body;

			// Insert member
			case 'I':
				$type = lang('cmd_signup');
				break;

			// Custom language.
			case 'Y':
				return lang($notification->target_body);

			// Custom language with string interpolation.
			case 'Z':
				return vsprintf(lang($notification->target_body), array(
					$notification->target_member_srl,     // %1$d
					$notification->target_nick_name,      // %2$s
					$notification->target_user_id,        // %3$s
					$notification->target_email_address,  // %4$s
					$notification->target_browser,        // %5$s
					$notification->target_summary,        // %6$s
					$notification->target_url,            // %7$s
				));
			
			// Other.
			case 'U':
			default:
				return $this->getNotifyTypeString($notification->notify_type, unserialize($notification->target_body)) ?: lang('ncenterlite');
		}
		
		$config = $this->getConfig();
		
		// Get the notification text.
		switch ($notification->target_type)
		{
			// Comment on your document.
			case 'C':
				$str = sprintf(lang('ncenterlite_commented'), $notification->target_nick_name, $type, $notification->target_summary);
				break;

			// Comment on a board.
			case 'A':
				$str = sprintf(lang('ncenterlite_commented_board'), $notification->target_nick_name, $notification->target_browser, $notification->target_summary);
				break;

			// Mentioned.
			case 'M':
				$str = sprintf(lang('ncenterlite_mentioned'), $notification->target_nick_name, $notification->target_browser, $notification->target_summary, $type);
				break;

			// Message arrived.
			case 'E':
				$str = sprintf(lang('ncenterlite_message_mention'), $notification->target_nick_name, $notification->target_summary);
				break;

			// Test notification.
			case 'T':
				$str = sprintf(lang('ncenterlite_test_noti'), $notification->target_nick_name);
				break;

			// New document on a board.
			case 'P':
				$str = sprintf(lang('ncenterlite_board'), $notification->target_nick_name, $notification->target_browser, $notification->target_summary);
				break;

			// New document.
			case 'S':
				if($notification->target_browser)
				{
					$str = sprintf(lang('ncenterlite_board'), $notification->target_nick_name, $notification->target_browser, $notification->target_summary);
				}
				else
				{
					$str = sprintf(lang('ncenterlite_article'), $notification->target_nick_name, $notification->target_summary);
				}
				break;

			// Voted.
			case 'V':
				if($config->anonymous_voter !== 'N' || $notification->target_member_srl == 0)
				{
					$str = sprintf(lang('ncenterlite_vote_anonymous'), $notification->target_summary, $type);
				}
				else
				{
					$str = sprintf(lang('ncenterlite_vote'), $notification->target_nick_name, $notification->target_summary, $type);
				}
				break;

			// Scrapped.
			case 'R':
				if($config->anonymous_scrap !== 'N' || $notification->target_member_srl == 0)
				{
					$str = sprintf(lang('ncenterlite_scrap_anonymous'), $notification->target_summary, $type);
				}
				else
				{
					$str = sprintf(lang('ncenterlite_scrap'), $notification->target_nick_name, $notification->target_summary, $type);
				}
				break;

			// Admin notification.
			case 'B':
				$str = sprintf(lang('ncenterlite_admin_content_message'), $notification->target_nick_name, $notification->target_browser, $notification->target_summary);
				break;

			case 'I':
				$str = sprintf(lang('ncenterlite_insert_member_message'), $notification->target_nick_name);
				break;
				
			case 'G':
				$str = sprintf(lang('ncenterlite_commented'), $notification->target_nick_name, $type, $notification->target_summary);
				break;

			// Other.
			default:
				$str = lang('ncenterlite');
		}

		return $str;
	}

	/**
	 * @brief 주어진 시간이 얼마 전 인지 반환
	 * @param $datetime string YmdHis
	 * @return string
	 */
	function getAgo($datetime)
	{
		$lang_type = Context::getLangType();

		$display = lang('ncenterlite_date');
		$ago = lang('ncenterlite_ago');

		$date = getdate(strtotime(zdate($datetime, 'Y-m-d H:i:s')));

		$current = getdate(time() + zgap());
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

	function getNotifyListByDocumentSrl($document_srl = null)
	{
		if($document_srl === null)
		{
			return false;
		}
		$args = new stdClass();
		$args->document_srl = $document_srl;
		$output = executeQueryArray('ncenterlite.getNotifyListByDocumentSrl', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		return $output->data;
	}

	function getNotifyMemberSrlByCommentSrl($comment_srl)
	{
		if(!$comment_srl === null)
		{
			return false;
		}
		$args = new stdClass();
		$args->srl = $comment_srl;
		$output = executeQueryArray('ncenterlite.getNotifyMemberSrlByCommentSrl', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		return $output->data;
	}

	function getUserUnsubscribeConfigByUnsubscribeSrl($unsubscribe_srl = 0)
	{
		$args = new stdClass();
		$args->unsubscribe_srl = $unsubscribe_srl;
		$output = executeQuery('ncenterlite.getUserUnsubscribeConfigByUnsubscribeSrl', $args);

		return $output->data;
	}

	function getUserUnsubscribeConfigByTargetSrl($target_srl = 0, $member_srl = null)
	{
		if(!$member_srl)
		{
			$member_srl = $this->user->member_srl;
		}
		
		$args = new stdClass();
		$args->target_srl = $target_srl;
		$args->member_srl = $member_srl;
		$output = executeQuery('ncenterlite.getUserUnsubscribeConfigByTargetSrl', $args);

		return $output->data;
	}
}
