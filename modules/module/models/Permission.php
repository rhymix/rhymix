<?php

namespace Rhymix\Modules\Module\Models;

use Rhymix\Framework\Cache;

#[\AllowDynamicProperties]
class Permission
{
	/*
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
	 * Constructor will be called from create().
	 *
	 * @param array $xml_grant_list
	 * @param array $module_grants
	 * @param ?object $module_info
	 * @param ?object $member_info
	 */
	protected function __construct(array $xml_grant_list, array $module_grants, ?object $module_info = null, ?object $member_info = null)
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

	/**
	 * Generate a Permission object for the given module and member.
	 *
	 * @param object $module_info
	 * @param object $member_info
	 * @param ?object $xml_info
	 * @return self
	 */
	public static function get(object $module_info, object $member_info, ?object $xml_info = null): self
	{
		// Check cache
		$module_srl = intval($module_info->module_srl ?? 0);
		$member_srl = intval($member_info->member_srl ?? 0);
		if (isset(ModuleCache::$modulePermissions[$module_info->module][$module_srl][$member_srl]))
		{
			$grant = ModuleCache::$modulePermissions[$module_info->module][$module_srl][$member_srl];
			if ($grant instanceof self && !$xml_info)
			{
				return $grant;
			}
		}

		// Get module grant information
		if (!$xml_info)
		{
			$xml_info = ModuleDefinition::getModuleActionXml($module_info->module);
		}

		// Generate a Permission object
		$xml_grant_list = isset($xml_info->grant) ? (array)($xml_info->grant) : array();
		$module_grants = ModuleInfo::getGrants($module_srl)->data ?: [];
		$grant = new self($xml_grant_list, $module_grants, $module_info, $member_info ?: null);
		ModuleCache::$modulePermissions[$module_info->module][$module_srl][$member_srl] = $grant;
		return $grant;
	}

	/**
	 * Generate a Permission object for the given target (document, comment, etc.) and member.
	 *
	 * @param string $target_type
	 * @param int $target_srl
	 * @param object $member_info
	 * @return ?self
	 * */
	public static function findByTargetType(string $target_type, int $target_srl, object $member_info): ?self
	{
		$module_srl = null;

		switch ($target_type)
		{
			case 'document':
				$document = \DocumentModel::getDocument($target_srl, false, false);
				if ($document->isExists() && $document->get('module_srl'))
				{
					$module_srl = intval($document->get('module_srl'));
				}
				break;
			case 'comment':
				$comment = \CommentModel::getComment($target_srl);
				if ($comment->isExists() && $comment->get('module_srl'))
				{
					$module_srl = intval($comment->get('module_srl'));
				}
				break;
			case 'file':
				$file = \FileModel::getFile($target_srl);
				if ($file && isset($file->module_srl))
				{
					$module_srl = intval($file->module_srl);
				}
				break;
			case 'module':
				$module_srl = $target_srl;
				break;
			default:
				return null;
		}

		if ($module_srl)
		{
			$module_info = ModuleInfo::getModuleInfo($module_srl);
			if (!$module_info)
			{
				return null;
			}
		}
		else
		{
			return null;
		}

		return self::get($module_info, $member_info);
	}

	/**
	 * Get the list of modules that a member can access.
	 *
	 * @param object $member_info
	 * @return array
	 */
	public static function listModulesAccessibleBy(object $member_info): array
	{
		$result = Cache::get(sprintf('site_and_module:accessible_modules:%d', $member_info->member_srl));
		if ($result === null)
		{
			$result = [];
			$module_list = ModuleInfo::getModuleInstanceList();
			foreach ($module_list as $module_info)
			{
				$grant = self::get($module_info, $member_info);
				if (!$grant->access)
				{
					continue;
				}
				if (isset($grant->{'list'}) && $grant->{'list'} === false)
				{
					continue;
				}
				if (isset($grant->{'view'}) && $grant->{'view'} === false)
				{
					continue;
				}
				$result[$module_info->module_srl] = $module_info;
			}
			ksort($result);

			Cache::set(sprintf('site_and_module:accessible_modules:%d', $member_info->member_srl), $result);
		}

		return $result;
	}

	/**
	 * Get the list of modules that a member can access.
	 *
	 * @param object $member_info
	 * @param ?string $module
	 * @return array
	 */
	public static function listModulesManagedBy(object $member_info, ?string $module = null): array
	{
		$result = [];
		$module_list = ModuleInfo::getModuleInstanceList();
		foreach ($module_list as $module_info)
		{
			if ($module && $module_info->module != $module)
			{
				continue;
			}

			$grant = self::get($module_info, $member_info);
			if ($grant->manager)
			{
				$result[$module_info->module_srl] = $module_info;
			}
		}
		ksort($result);
		return $result;
	}
}
