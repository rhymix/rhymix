<?php

namespace Rhymix\Modules\Module\Models;

#[\AllowDynamicProperties]
class Permission
{
	/**
	 * Default properties.
	 */
	public $access;
	public $root;
	public $manager;

	/**
	 * Requirements for this module.
	 */
	protected $_spec = [];

	/**
	 * Scopes for module managers.
	 */
	protected $_scopes = [];

	/**
	 * Constructor will be called from ModuleModel::getGrant().
	 *
	 * @param array $xml_grant_list
	 * @param array $module_grants
	 * @param ?object $module_info
	 * @param ?object $member_info
	 */
	public function __construct(array $xml_grant_list, array $module_grants, ?object $module_info = null, ?object $member_info = null)
	{
		// Generate the list of default permissions.
		$this->_spec = [
			'access' => 'guest',
			'root' => 'root',
			'manager' => 'manager',
			'is_admin' => 'root',
			'is_site_admin' => 'root',
		];
		foreach ($xml_grant_list as $key => $val)
		{
			$this->_spec[$key] = $val->default ?? '';
		}

		// Override the defaults with user settings.
		foreach ($module_grants as $row)
		{
			$key = $row->name;
			if ($row->group_srl == 0)
			{
				$this->_spec[$key] = 'guest';
				continue;
			}
			if ($row->group_srl == -1 || $row->group_srl == -2)
			{
				$this->_spec[$key] = 'member';
				continue;
			}
			if ($row->group_srl == -4)
			{
				$this->_spec[$key] = 'not_member';
				continue;
			}
			if ($row->group_srl == -3)
			{
				$this->_spec[$key] = 'manager';
				continue;
			}
			if ($row->group_srl > 0)
			{
				if (!isset($this->_spec[$key]) || !is_array($this->_spec[$key]))
				{
					$this->_spec[$key] = [];
				}
				$this->_spec[$key][] = $row->group_srl;
				continue;
			}
		}

		// If the member is an administrator, grant all possible permissions.
		if ($member_info && $member_info->is_admin === 'Y')
		{
			$this->_scopes = true;
			foreach ($this->_spec as $key => $requirement)
			{
				$this->{$key} = true;
			}
			return;
		}

		// If the member is a module manager, fill the scope of management.
		$manager_scopes = !empty($module_info->module_srl) ? \ModuleModel::isModuleAdmin($member_info, $module_info->module_srl) : false;
		$member_groups = !empty($member_info->group_list) ? array_keys($member_info->group_list) : [];
		if ($manager_scopes)
		{
			$this->manager = true;
			$this->_scopes = $manager_scopes;
		}

		// Check if each permission is granted to the current user.
		foreach ($this->_spec as $key => $requirement)
		{
			if ($key === 'manager' && $this->manager)
			{
				continue;
			}
			elseif ($requirement === 'guest')
			{
				$this->{$key} = true;
			}
			elseif ($requirement === 'member')
			{
				$this->{$key} = ($member_info && $member_info->member_srl);
			}
			elseif ($requirement === 'not_member')
			{
				$this->{$key} = !($member_info && $member_info->member_srl) || $this->manager;
			}
			elseif ($requirement === 'manager')
			{
				$this->{$key} = $this->manager ? true : false;
			}
			elseif ($requirement === 'root')
			{
				$this->{$key} = $this->root ? true : false;
			}
			elseif (is_array($requirement))
			{
				$this->{$key} = array_intersect($member_groups, $requirement) ? true : false;
				if ($key === 'manager' && $this->{$key} === true)
				{
					$this->_scopes = true;
				}
			}
		}
	}

	/**
	 * Find out whether the current user is allowed to do something.
	 *
	 * This is more portable than accessing object attributes directly,
	 * and also supports manager scopes.
	 *
	 * @param string $scope
	 * @return bool
	 */
	public function can(string $scope): bool
	{
		if (isset($this->{$scope}) && $scope !== 'scopes')
		{
			return boolval($this->{$scope});
		}

		if ($this->manager && $this->_scopes && preg_match('/^(\w+):(.+)$/', $scope, $matches))
		{
			if ($this->_scopes === true)
			{
				return true;
			}
			if (is_array($this->_scopes) && in_array($scope, $this->_scopes))
			{
				return true;
			}
			if (is_array($this->_scopes) && in_array($matches[1] . ':*', $this->_scopes))
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * Find out who is allowed to do something.
	 *
	 * This method returns 'root', 'manager', 'member', 'guest',
	 * or an array of group_srls whose members are allowed.
	 *
	 * If you pass the name of a scope, the result might vary
	 * depending on whether you are a module manager.
	 *
	 * @param string key
	 * @return string|array
	 */
	public function whocan(string $key)
	{
		if (isset($this->_spec[$key]))
		{
			return $this->_spec[$key];
		}
		elseif (preg_match('/^(\w+):(\w+)$/', $key))
		{
			if ($this->manager)
			{
				return $this->can($key) ? 'manager' : 'root';
			}
			else
			{
				return 'manager';
			}
		}
		else
		{
			return 'nobody';
		}
	}

	/**
	 * Magic method to provide deprecated aliases.
	 *
	 * @param string $key
	 * @return mixed
	 */
	public function __get(string $key)
	{
		if ($key === 'is_admin' || $key === 'is_site_admin')
		{
			return $this->root;
		}
		else
		{
			return false;
		}
	}
}
