<?php
class ncenterlite extends ModuleObject
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

	var $triggers = array(
		array('comment.insertComment', 'ncenterlite', 'controller', 'triggerAfterInsertComment', 'after'),
		array('comment.deleteComment', 'ncenterlite', 'controller', 'triggerAfterDeleteComment', 'after'),
		array('document.insertDocument', 'ncenterlite', 'controller', 'triggerAfterInsertDocument', 'after'),
		array('document.deleteDocument', 'ncenterlite', 'controller', 'triggerAfterDeleteDocument', 'after'),
		array('display', 'ncenterlite', 'controller', 'triggerBeforeDisplay', 'before'),
		array('moduleHandler.proc', 'ncenterlite', 'controller', 'triggerAfterModuleHandlerProc', 'after'),
		array('member.deleteMember', 'ncenterlite', 'controller', 'triggerAfterDeleteMember', 'after'),
		array('communication.sendMessage', 'ncenterlite', 'controller', 'triggerAfterSendMessage', 'after'),
		array('document.updateVotedCount', 'ncenterlite', 'controller', 'triggerAfterDocumentVotedUpdate', 'after'),
		array('document.updateVotedCountCancel', 'ncenterlite', 'controller', 'triggerAfterDocumentVotedCancel', 'after'),
		array('member.procMemberScrapDocument', 'ncenterlite', 'controller', 'triggerAfterScrap', 'after'),
		array('moduleHandler.init', 'ncenterlite', 'controller', 'triggerAddMemberMenu', 'after'),
		array('document.moveDocumentToTrash', 'ncenterlite', 'controller', 'triggerAfterMoveToTrash', 'after'),
		array('comment.updateVotedCount', 'ncenterlite', 'controller', 'triggerAfterCommentVotedCount', 'after'),
		array('comment.updateVotedCountCancel', 'ncenterlite', 'controller', 'triggerAfterCommentVotedCancel', 'after'),
		// 2020. 05. 30 add menu when popup document menu called
		array('document.getDocumentMenu', 'ncenterlite', 'controller', 'triggerGetDocumentMenu', 'after'),
		array('comment.getCommentMenu', 'ncenterlite', 'controller', 'triggerGetCommentMenu', 'after'),
	);

	private $delete_triggers = array(
		array('moduleObject.proc', 'ncenterlite', 'controller', 'triggerBeforeModuleObjectProc', 'before')
	);

	function moduleInstall()
	{
		
	}

	function checkUpdate()
	{
		$oModuleModel = getModel('module');
		$oDB = &DB::getInstance();

		foreach($this->triggers as $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4])) return true;
		}

		foreach($this->delete_triggers as $trigger)
		{
			if($oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				return true;
			}
		}

		if(!$oDB->isColumnExists('ncenterlite_notify', 'readed'))
		{
			return true;
		}

		if(!$oDB->isColumnExists('ncenterlite_notify', 'target_body'))
		{
			return true;
		}

		if(!$oDB->isColumnExists('ncenterlite_notify', 'notify_type'))
		{
			return true;
		}

		if(!$oDB->isColumnExists('ncenterlite_notify', 'target_browser'))
		{
			return true;
		}

		if(!$oDB->isColumnExists('ncenterlite_notify', 'target_p_srl'))
		{
			return true;
		}

		if(!$oDB->isIndexExists('ncenterlite_notify', 'idx_srl'))
		{
			return true;
		}

		if(!$oDB->isIndexExists('ncenterlite_notify', 'idx_target_srl'))
		{
			return true;
		}

		if(!$oDB->isIndexExists('ncenterlite_notify', 'idx_target_p_srl'))
		{
			return true;
		}

		if(!$oDB->isIndexExists('ncenterlite_notify', 'idx_target_member_srl'))
		{
			return true;
		}

		// Composite index to speed up getNotifyList
		if(!$oDB->isIndexExists('ncenterlite_notify', 'idx_member_srl_and_readed'))
		{
			return true;
		}

		if(!$oDB->isColumnExists('ncenterlite_user_set', 'comment_comment_notify'))
		{
			return true;
		}

		if(!$oDB->isColumnExists('ncenterlite_user_set', 'vote_notify'))
		{
			return true;
		}

		if(!$oDB->isColumnExists('ncenterlite_user_set', 'scrap_notify'))
		{
			return true;
		}

		// PK duplicate
		if($oDB->isIndexExists('ncenterlite_notify', 'idx_notify'))
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
		$oDB = &DB::getInstance();

		foreach($this->triggers as $trigger)
		{
			if(!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
			}
		}

		foreach($this->delete_triggers as $trigger)
		{
			if($oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				$oModuleController->deleteTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
			}
		}

		if(!$oDB->isColumnExists('ncenterlite_notify','readed'))
		{
			$oDB->addColumn('ncenterlite_notify', 'readed', 'char', 1, 'N', true);
			$oDB->addIndex('ncenterlite_notify', 'idx_readed', array('readed'));
			$oDB->addIndex('ncenterlite_notify', 'idx_member_srl', array('member_srl'));
			$oDB->addIndex('ncenterlite_notify', 'idx_regdate', array('regdate'));
		}

		if(!$oDB->isColumnExists('ncenterlite_notify','target_browser'))
		{
			$oDB->addColumn('ncenterlite_notify', 'target_browser', 'varchar', 50, true);
		}

		if(!$oDB->isColumnExists('ncenterlite_notify','target_body'))
		{
			$oDB->addColumn('ncenterlite_notify', 'target_body', 'varchar', 255, true);
		}

		if(!$oDB->isColumnExists('ncenterlite_notify','notify_type'))
		{
			$oDB->addColumn('ncenterlite_notify', 'notify_type', 'number', 11, 0);
		}

		if(!$oDB->isColumnExists('ncenterlite_notify','target_p_srl'))
		{
			$oDB->addColumn('ncenterlite_notify', 'target_p_srl', 'number', 10, true);
		}

		if(!$oDB->isIndexExists('ncenterlite_notify', 'idx_srl'))
		{
			$oDB->addIndex('ncenterlite_notify', 'idx_srl', array('srl'));
		}

		if(!$oDB->isIndexExists('ncenterlite_notify', 'idx_target_srl'))
		{
			$oDB->addIndex('ncenterlite_notify', 'idx_target_srl', array('target_srl'));
		}

		if(!$oDB->isIndexExists('ncenterlite_notify', 'idx_target_p_srl'))
		{
			$oDB->addIndex('ncenterlite_notify', 'idx_target_p_srl', array('target_p_srl'));
		}

		if(!$oDB->isIndexExists('ncenterlite_notify', 'idx_target_member_srl'))
		{
			$oDB->addIndex('ncenterlite_notify', 'idx_target_member_srl', array('target_member_srl'));
		}

		// Composite index to speed up getNotifyList
		if(!$oDB->isIndexExists('ncenterlite_notify', 'idx_member_srl_and_readed'))
		{
			$oDB->addIndex('ncenterlite_notify', 'idx_member_srl_and_readed', array('member_srl', 'readed'));
		}

		// PK duplicate
		if($oDB->isIndexExists('ncenterlite_notify', 'idx_notify'))
		{
			$oDB->dropIndex('ncenterlite_notify', 'idx_notify');
		}

		if(!$oDB->isColumnExists('ncenterlite_user_set','comment_comment_notify'))
		{
			$oDB->addColumn('ncenterlite_user_set', 'comment_comment_notify', 'char', 1, null, true, 'comment_notify');
		}

		if(!$oDB->isColumnExists('ncenterlite_user_set','vote_notify'))
		{
			$oDB->addColumn('ncenterlite_user_set', 'vote_notify', 'char', 1, null, true, 'mention_notify');
		}

		if(!$oDB->isColumnExists('ncenterlite_user_set','scrap_notify'))
		{
			$oDB->addColumn('ncenterlite_user_set', 'scrap_notify', 'char', 1, null, true, 'vote_notify');
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
		$oModuleController = getController('module');

		foreach($this->triggers as $trigger)
		{
			$oModuleController->deleteTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
		}
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
