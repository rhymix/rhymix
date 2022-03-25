<?php

namespace Rhymix\Modules\Sociallogin;

class Install extends Base
{
	protected $_triggers = array(
		array('moduleObject.proc', 'sociallogin', 'controller', 'triggerModuleObjectAfter', 'after'),
		array('document.insertDocument', 'sociallogin', 'controller', 'triggerInsertDocumentAfter', 'after'),
		array('member.procMemberInsert', 'sociallogin', 'controller', 'triggerProcInsertMemberBefore', 'before'),
		array('member.insertMember', 'sociallogin', 'controller', 'triggerInsertMemberAfter', 'after'),
		array('member.getMemberMenu', 'sociallogin', 'controller', 'triggerMemberMenu', 'after'),
		array('member.deleteMember', 'sociallogin', 'controller', 'triggerDeleteMember', 'after'),
	);

	public function moduleInstall()
	{
		return new \BaseObject();
	}

	public function checkUpdate()
	{
		$oModuleModel = getModel('module');

		foreach ($this->_triggers as $trigger)
		{
			if (!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				return true;
			}
		}

		return false;
	}

	public function moduleUpdate()
	{
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');

		foreach ($this->_triggers as $trigger)
		{
			if (!$oModuleModel->getTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]))
			{
				$oModuleController->insertTrigger($trigger[0], $trigger[1], $trigger[2], $trigger[3], $trigger[4]);
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
