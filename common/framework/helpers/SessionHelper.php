<?php

namespace Rhymix\Framework\Helpers;

/**
 * Session helper class.
 *
 * @property int $member_srl
 * @property ?string $user_id
 * @property ?string $password
 * @property ?string $email_address
 * @property ?string $email_id
 * @property ?string $email_host
 * @property ?string $phone_number
 * @property ?string $phone_country
 * @property ?string $phone_type
 * @property ?string $user_name
 * @property ?string $nick_name
 * @property ?string $find_account_question
 * @property ?string $find_account_answer
 * @property ?string $homepage
 * @property ?string $blog
 * @property ?string $birthday
 * @property 'Y'|'N'|null $allow_mailing
 * @property 'Y'|'N'|null $allow_message
 * @property 'Y'|'N' $is_admin
 * @property 'Y'|'N'|null $denied
 * @property ?string $status
 * @property ?string $regdate
 * @property ?string $ipaddress
 * @property ?string $last_login
 * @property ?string $last_login_ipaddress
 * @property ?string $change_password_date
 * @property ?string $limit_date
 * @property ?string $description
 * @property ?int $list_order
 * @property ?object{
 *     'width': int,
 *     'height': int,
 *     'src': string,
 *     'file': string,
 * } $profile_image
 * @property ?string $image_name
 * @property ?string $image_mark
 * @property ?string $signature
 * @property ?array<string, string> $group_list
 * @property ?array<string> $menu_list
 * @property ?bool $is_site_admin
 * @property ?string $refused_reason
 * @property ?string $limited_reason
 * @property ?array<string> $phone
 */
class SessionHelper
{
	/**
	 * @var array<string, mixed>
	 */
	protected $member_info = array(
		'member_srl' => 0,
		'is_admin' => 'N',
	);

	/**
	 * Constructor.
	 *
	 * @param int $member_srl (optional)
	 */
	public function __construct(int $member_srl = 0)
	{
		// Load member information.
		$member_srl = intval($member_srl);
		if ($member_srl)
		{
			$member_info = \MemberModel::getMemberInfo($member_srl);
			if (($member_info->member_srl ?? null) === $member_srl)
			{
				$this->member_info = get_object_vars($member_info);
				$this->member_info['menu_list'] = $this->member_info['menu_list'] ?? array();
			}
		}
	}

	/**
	 * Check if this user is a member.
	 *
	 * @return bool
	 */
	public function isMember(): bool
	{
		return $this->member_srl > 0;
	}

	/**
	 * Check if this user is an administrator.
	 *
	 * @return bool
	 */
	public function isAdmin(): bool
	{
		return $this->is_admin === 'Y';
	}

	/**
	 * Check if this user is an administrator of a module.
	 *
	 * @param ?int $module_srl (optional)
	 * @return bool
	 */
	public function isModuleAdmin(?int $module_srl = null): bool
	{
		return $this->is_admin === 'Y' || ($module_srl && \ModuleModel::isModuleAdmin($this, $module_srl));
	}

	/**
	 * Check if this user is valid (not denied or limited).
	 *
	 * @return bool
	 */
	public function isValid(): bool
	{
		if ($this->denied === 'N')
		{
			return false;
		}

		if (!!$this->limit_date && str_starts_with($this->limit_date, date('Ymd')))
		{
			return false;
		}

		return true;
	}

	/**
	 * Check if profile image exist
	 */
	public function profileImageExists(): bool
	{
		return !!($this->member_info['profile_image']->src ?? null);
	}

	/**
	 * Profile image URL
	 */
	public function profileImageUrl(): ?string
	{
		if (!($this->member_info['profile_image']->src ?? null))
		{
			return null;
		}

		return $this->member_info['profile_image']->src;
	}

	/**
	 * Profile image path
	 */
	public function profileImagePath(): ?string
	{
		if (!($this->member_info['profile_image']->file ?? null))
		{
			return null;
		}

		return \FileHandler::getRealPath($this->member_info['profile_image']->file);
	}

	/**
	 * Get the list of groups that this user belongs to.
	 *
	 * @return array
	 */
	public function getGroups(): array
	{
		return $this->member_info['group_list'] ?? [];
	}

	/**
	 * @return mixed
	 */
	public function &__get(string $name)
	{
		if (isset($this->member_info[$name])) {
			return $this->member_info[$name];
		}

		// Trigger the `Undefined property` warning
		trigger_error('Undefined property: ' . __CLASS__ . '::$' . $name, E_USER_WARNING);

		return $this->member_info[$name];
	}

	/**
	 * @param mixed $value
	 */
	public function __set(string $name, $value): void
	{
		$this->member_info[$name] = $value;
	}
}
