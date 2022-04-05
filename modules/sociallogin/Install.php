<?php

namespace Rhymix\Modules\Sociallogin;

use ModuleController;
use ModuleModel;

class Install extends Base
{
	protected static $_insert_triggers = array(
		array('moduleObject.proc', 'after', 'Controllers\EventHandlers', 'triggerAfterModuleObject'),
		array('member.procMemberInsert', 'before', 'Controllers\EventHandlers', 'triggerBeforeInsertMember'),
		array('member.insertMember', 'after', 'Controllers\EventHandlers', 'triggerAfterInsertMember'),
		array('member.deleteMember', 'after', 'Controllers\EventHandlers', 'triggerAfterDeleteMember'),
		array('member.getMemberMenu', 'after', 'Controllers\EventHandlers', 'triggerMemberMenu'),
		array('document.insertDocument', 'after', 'Controllers\EventHandlers', 'triggerAfterInsertDocument'),
	);

	protected static $_delete_triggers = array(
		array('moduleObject.proc', 'after', 'controller', 'triggerModuleObjectAfter'),
		array('member.procMemberInsert', 'before', 'controller', 'triggerProcInsertMemberBefore'),
		array('member.insertMember', 'after', 'controller', 'triggerInsertMemberAfter'),
		array('member.deleteMember', 'after', 'controller', 'triggerDeleteMember'),
		array('member.getMemberMenu', 'after', 'controller', 'triggerMemberMenu'),
		array('document.insertDocument', 'after', 'controller', 'triggerInsertDocumentAfter'),
	);

	public function moduleInstall()
	{
		return new \BaseObject();
	}

	public function checkUpdate()
	{
		foreach (self::$_insert_triggers as $trigger)
		{
			if (!ModuleModel::getTrigger($trigger[0], $this->module, $trigger[2], $trigger[3], $trigger[1]))
			{
				return true;
			}
		}
		foreach (self::$_delete_triggers as $trigger)
		{
			if (ModuleModel::getTrigger($trigger[0], $this->module, $trigger[2], $trigger[3], $trigger[1]))
			{
				return true;
			}
		}
		return false;
	}

	public function moduleUpdate()
	{
		$oModuleController = ModuleController::getInstance();
		foreach (self::$_insert_triggers as $trigger)
		{
			if (!ModuleModel::getTrigger($trigger[0], $this->module, $trigger[2], $trigger[3], $trigger[1]))
			{
				$oModuleController->insertTrigger($trigger[0], $this->module, $trigger[2], $trigger[3], $trigger[1]);
			}
		}
		foreach (self::$_delete_triggers as $trigger)
		{
			if (ModuleModel::getTrigger($trigger[0], $this->module, $trigger[2], $trigger[3], $trigger[1]))
			{
				$oModuleController->deleteTrigger($trigger[0], $this->module, $trigger[2], $trigger[3], $trigger[1]);
			}
		}

		return new \BaseObject(0, 'success_updated');
	}

	public function moduleUninstall()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');

		foreach ($this->_triggers as $trigger)
		{
			if ($oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				$oModuleController->deleteTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
			}
		}

		return new \BaseObject();
	}

	public function recompileCache()
	{
		
	}
}
