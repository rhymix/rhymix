<?php

class AddonModel extends Addon
{
	/**
	 * Check if addon is activated
	 *
	 * @param string $addon_name
	 * @param string $type
	 * @return bool
	 */
	public static function isActivated(string $addon_name, string $type = 'any'): bool
	{
		$output = executeQuery('addon.getSiteAddonInfo', ['addon' => $addon_name, 'site_srl' => 0]);
		$addon = $output->data ?: null;
		if (!$addon)
		{
			return false;
		}
		elseif ($type === 'pc')
		{
			return $addon->is_used === 'Y';
		}
		elseif ($type === 'mobile')
		{
			return $addon->is_used_m === 'Y';
		}
		else
		{
			return $addon->is_used === 'Y' || $addon->is_used_m === 'Y';
		}
	}

	/**
	 * Get configuration for addon
	 *
	 * @param string $addon_name
	 * @param string $type
	 * @return object|null
	 */
	public static function getAddonConfig(string $addon_name, string $type = 'any')
	{
		if (!in_array($type, ['any', 'pc', 'mobile']))
		{
			$type = 'any';
		}

		$cache_key = sprintf('addonConfig:%s:%s', $addon_name, $type);
		$config = Rhymix\Framework\Cache::get($cache_key);
		if ($config !== null)
		{
			return $config;
		}

		$args = new stdClass();
		$args->addon = $addon_name;
		$args->site_srl = 0;
		$output = executeQueryArray('addon.getSiteAddonInfo', $args);
		if (!$output->toBool() || !count($output->data))
		{
			return null;
		}

		$result = array_first($output->data);
		if ($type === 'pc' && $result->is_used !== 'Y')
		{
			return null;
		}
		if ($type === 'mobile' && $result->is_used_m !== 'Y')
		{
			return null;
		}
		if (!$result->extra_vars)
		{
			return null;
		}

		$config = unserialize($result->extra_vars);
		unset($config->xe_validator_id);
		if (!isset($config->mid_list))
		{
			$config->mid_list = [];
		}
		$config->use_pc = $result->is_used;
		$config->use_mobile = $result->is_used_m;

		Rhymix\Framework\Cache::set($cache_key, $config, 0, true);
		return $config;
	}
}
