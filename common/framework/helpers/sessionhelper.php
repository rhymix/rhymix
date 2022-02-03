<?php

namespace Rhymix\Framework\Helpers;

/**
 * Session helper class.
 */
class SessionHelper
{
	/**
	 * Instance properties.
	 */
	public $member_srl = 0;
	public $is_admin = 'N';
	public $group_list = array();
	public $menu_list = array();
	
	/**
	 * Constructor.
	 * 
	 * @param int $member_srl (optional)
	 * @return void
	 */
	public function __construct($member_srl = 0)
	{
		// Load member information.
		$member_srl = intval($member_srl);
		if ($member_srl)
		{
			$member_info = \MemberModel::getMemberInfo($member_srl);
			if (isset($member_info->member_srl) && intval($member_info->member_srl) === $member_srl)
			{
				foreach (get_object_vars($member_info) as $key => $value)
				{
					$this->{$key} = $value;
				}
				$this->member_srl = $member_srl;
				$this->group_list = \MemberModel::getMemberGroups($member_srl);
			}
		}
	}
	
	/**
	 * Check if this user is a member.
	 * 
	 * @return bool
	 */
	public function isMember()
	{
		return $this->member_srl > 0;
	}
	
	/**
	 * Check if this user is an administrator.
	 * 
	 * @return bool
	 */
	public function isAdmin()
	{
		return $this->is_admin === 'Y';
	}
	
	/**
	 * Check if this user is an administrator of a module.
	 * 
	 * @param int $module_srl (optional)
	 * @return bool
	 */
	public function isModuleAdmin($module_srl = null)
	{
		return $this->is_admin === 'Y' || \ModuleModel::isModuleAdmin($this, $module_srl);
	}
	
	/**
	 * Check if this user is valid (not denied or limited).
	 * 
	 * @return bool
	 */
	public function isValid()
	{
		if ($this->denied === 'N' && (!$this->limit_date || substr($this->limit_date, 0, 8) < date('Ymd')))
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	/**
	 * Get the list of groups that this user belongs to.
	 * 
	 * @return array
	 */
	public function getGroups()
	{
		return $this->group_list;
	}
}
