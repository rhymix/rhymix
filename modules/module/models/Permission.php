<?php

namespace Rhymix\Modules\Module\Models;

#[\AllowDynamicProperties]
class Permission
{
	/**
	 * Default properties.
	 *
	 * Note that $is_admin is an alias to $root,
	 * and $is_site_admin is an alias to $manager.
	 */
	public $access;
	public $root;
	public $manager;
	public $scopes;

	/**
	 * Alias to $root, kept for backward compatibility only.
	 *
	 * @deprecated
	 */
	public $is_admin;

	/**
	 * Alias to $manager, kept for backward compatibility only.
	 *
	 * @deprecated
	 */
	public $is_site_admin;

	/**
	 * Primary method to determine whether a user is allowed to do something.
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

		if ($this->manager && $this->scopes && preg_match('/^(\w+):(.+)$/', $scope, $matches))
		{
			if ($this->scopes === true)
			{
				return true;
			}
			if (is_array($this->scopes) && in_array($scope, $this->scopes))
			{
				return true;
			}
			if (is_array($this->scopes) && in_array($matches[1] . ':*', $this->scopes))
			{
				return true;
			}
		}

		return false;
	}
}
