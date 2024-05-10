<?php
class Ncenterlite extends ModuleObject
{
	var $_TYPE_DOCUMENT = 'D'; // 댓글
	var $_TYPE_COMMENT = 'C'; // 댓글의 댓글
	var $_TYPE_COMMENT_ALL = 'G';
	var $_TYPE_ADMIN_COMMENT = 'A'; // 어드민 댓글 알림
	var $_TYPE_MENTION = 'M'; // 멘션
	var $_TYPE_MESSAGE = 'E'; // 쪽지 mEssage
	var $_TYPE_DOCUMENTS = 'P'; // 글 작성 알림
	var $_TYPE_VOTED = 'V'; // 추천글 안내 알림
	var $_TYPE_SCRAPPED = 'R'; // 스크랩 알림
	var $_TYPE_TEST = 'T'; // Test Notify create.
	var $_TYPE_ADMIN_DOCUMENT = 'B'; // Admin Document Alert
	var $_TYPE_CUSTOM = 'U'; //Updated alert(uses type table)
	var $_TYPE_INSERT_MEMBER = 'I'; // Insert Member

	function moduleInstall()
	{
	}

	function checkUpdate()
	{
		$oModuleModel = getModel('module');
		$oDB = DB::getInstance();

		foreach(['notify_type', 'readed', 'target_body', 'target_browser', 'target_p_srl'] as $column_name)
		{
			if(!$oDB->isColumnExists('ncenterlite_notify', $column_name))
			{
				return true;
			}
		}
		foreach(['idx_srl', 'idx_member_srl', 'idx_regdate', 'idx_readed', 'idx_target_srl', 'idx_target_p_srl', 'idx_target_member_srl', 'idx_member_srl_and_readed'] as $index_name)
		{
			if(!$oDB->isIndexExists('ncenterlite_notify', $index_name))
			{
				return true;
			}
		}

		foreach(NcenterliteModel::getUserSetNotifyTypes() as $type => $srl)
		{
			if(!$oDB->isColumnExists('ncenterlite_user_set', $type . '_notify'))
			{
				return true;
			}
			else
			{
				$column_info = $oDB->getColumnInfo('ncenterlite_user_set', $type . '_notify');
				if (strtolower($column_info->dbtype) !== 'varchar' || $column_info->size < 40)
				{
					return true;
				}
			}
		}

		// #1903 #1906
		foreach(['target_browser', 'target_summary'] as $column_name)
		{
			$column_info = $oDB->getColumnInfo('ncenterlite_notify', $column_name);
			if ($column_info->size < 80)
			{
				return true;
			}
		}

		// PK duplicate
		if($oDB->isIndexExists('ncenterlite_notify', 'idx_notify'))
		{
			return true;
		}

		if($oDB->isColumnExists('ncenterlite_user_set','admin_content_notify'))
		{
			return true;
		}

		if($oDB->isColumnExists('ncenterlite_user_set','custom_notify'))
		{
			return true;
		}

		// Extra data column
		if (!$oDB->isColumnExists('ncenterlite_notify', 'data'))
		{
			return true;
		}

		$config = getModel('ncenterlite')->getConfig();

		$member_config = getModel('member')->getMemberConfig();
		$variable_name = array();
		foreach($member_config->signupForm as $value)
		{
			if(isset($value->type) && $value->type == 'tel')
			{
				$variable_name[] = $value->name;
			}
		}

		if(!$config->variable_name && count($variable_name) == 1)
		{
			return true;
		}

		return false;
	}

	function moduleUpdate()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');
		$oDB = DB::getInstance();

		if(!$oDB->isColumnExists('ncenterlite_notify','notify_type'))
		{
			$oDB->addColumn('ncenterlite_notify', 'notify_type', 'number', 11, 0);
		}
		if(!$oDB->isColumnExists('ncenterlite_notify','readed'))
		{
			$oDB->addColumn('ncenterlite_notify', 'readed', 'char', 1, 'N', true);
		}
		if(!$oDB->isColumnExists('ncenterlite_notify','target_body'))
		{
			$oDB->addColumn('ncenterlite_notify', 'target_body', 'varchar', 255, true);
		}
		if(!$oDB->isColumnExists('ncenterlite_notify','target_browser'))
		{
			$oDB->addColumn('ncenterlite_notify', 'target_browser', 'varchar', 80, true);
		}
		if(!$oDB->isColumnExists('ncenterlite_notify','target_p_srl'))
		{
			$oDB->addColumn('ncenterlite_notify', 'target_p_srl', 'number', 10, true);
		}

		foreach(['idx_srl', 'idx_member_srl', 'idx_regdate', 'idx_readed', 'idx_target_srl', 'idx_target_p_srl', 'idx_target_member_srl'] as $index_name)
		{
			if(!$oDB->isIndexExists('ncenterlite_notify', $index_name))
			{
				$oDB->addIndex('ncenterlite_notify', $index_name, array(substr($index_name, 4)));
			}
		}

		$prev_type = '';
		foreach(NcenterliteModel::getUserSetNotifyTypes() as $type => $srl)
		{
			if(!$oDB->isColumnExists('ncenterlite_user_set', $type . '_notify'))
			{
				$oDB->addColumn('ncenterlite_user_set', $type . '_notify', 'varchar', 40, null, true, $prev_type ? ($prev_type . '_notify') : 'member_srl');
			}
			else
			{
				$column_info = $oDB->getColumnInfo('ncenterlite_user_set', $type . '_notify');
				if (strtolower($column_info->dbtype) !== 'varchar' || $column_info->size < 40)
				{
					$oDB->modifyColumn('ncenterlite_user_set', $type . '_notify', 'varchar', 40, null, true, $prev_type ? ($prev_type . '_notify') : 'member_srl');
				}
			}
			$prev_type = $type;
		}

		// Not use the column
		if($oDB->isColumnExists('ncenterlite_user_set','admin_content_notify'))
		{
			$oDB->dropColumn('ncenterlite_user_set', 'admin_content_notify');
		}

		if($oDB->isColumnExists('ncenterlite_user_set','custom_notify'))
		{
			$oDB->dropColumn('ncenterlite_user_set', 'custom_notify');
		}

		// Composite index to speed up getNotifyList
		if(!$oDB->isIndexExists('ncenterlite_notify', 'idx_member_srl_and_readed'))
		{
			$oDB->addIndex('ncenterlite_notify', 'idx_member_srl_and_readed', array('member_srl', 'readed'));
		}

		// #1903 #1906
		foreach(['target_browser', 'target_summary'] as $column_name)
		{
			$column_info = $oDB->getColumnInfo('ncenterlite_notify', $column_name);
			if ($column_info->size < 80)
			{
				$oDB->modifyColumn('ncenterlite_notify', $column_name, 'varchar', 80, null, false);
			}
		}

		// PK duplicate
		if($oDB->isIndexExists('ncenterlite_notify', 'idx_notify'))
		{
			$oDB->dropIndex('ncenterlite_notify', 'idx_notify');
		}

		// Extra data column
		if (!$oDB->isColumnExists('ncenterlite_notify', 'data'))
		{
			$oDB->addColumn('ncenterlite_notify', 'data', 'longtext', null, null, false, 'target_url');
		}

		$config = getModel('ncenterlite')->getConfig();
		if(!$config)
		{
			$config = new stdClass();
		}

		if(!$config->variable_name)
		{
			$member_config = getModel('member')->getMemberConfig();
			$variable_name = array();
			foreach($member_config->signupForm as $value)
			{
				if($value->type === 'tel')
				{
					$variable_name[] = $value->name;
				}
			}
			if(count($variable_name) === 1)
			{
				foreach($variable_name as $item)
				{
					$config->variable_name = $item;
				}
				$output = $oModuleController->insertModuleConfig('ncenterlite', $config);
				if(!$output->toBool())
				{
					return new BaseObject(-1, 'fail_module_install');
				}
			}
		}
	}

	function recompileCache()
	{
		return new BaseObject();
	}

	function moduleUninstall()
	{
		return new BaseObject();
	}

	public static function getSmsHandler()
	{
		static $oSmsHandler = null;
		$config = getModel('ncenterlite')->getConfig();

		if($oSmsHandler === null)
		{
			$oSmsHandler = new Rhymix\Framework\SMS;

			if($oSmsHandler::getDefaultDriver()->getName() === 'Dummy')
			{
				$oSmsHandler = false;
				return $oSmsHandler;
			}

			if($config->variable_name === '#')
			{
				return $oSmsHandler;
			}

			$variable_name = array();
			$member_config = getModel('member')->getMemberConfig();
			foreach($member_config->signupForm as $value)
			{
				if($value->type == 'tel')
				{
					$variable_name[] = $value->name;
				}
			}

			if(empty($variable_name))
			{
				$oSmsHandler = false;
				return $oSmsHandler;
			}
		}

		return $oSmsHandler;
	}
}
