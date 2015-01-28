<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Addon module's controller class
 * @author NAVER (developers@xpressengine.com)
 */
class addonController extends addon
{

	/**
	 * Initialization
	 *
	 * @return void
	 */
	function init()
	{
	}

	/**
	 * Returns a cache file path
	 *
	 * @param $type pc or mobile
	 * @return string Returns a path
	 */
	function getCacheFilePath($type = "pc")
	{
		static $addon_file;
		if(isset($addon_file))
		{
			return $addon_file;
		}

		$site_module_info = Context::get('site_module_info');
		$site_srl = $site_module_info->site_srl;

		$addon_path = _XE_PATH_ . 'files/cache/addons/';
		$addon_file = $addon_path . $site_srl . $type . '.acivated_addons.cache.php';

		if($this->addon_file_called)
		{
			return $addon_file;
		}

		$this->addon_file_called = TRUE;

		FileHandler::makeDir($addon_path);

		if(!file_exists($addon_file))
		{
			$this->makeCacheFile($site_srl, $type);
		}

		return $addon_file;
	}

	/**
	 * Returns mid list that addons is run
	 *
	 * @param string $selected_addon Name to get list
	 * @param int $site_srl Site srl
	 * @return string[] Returns list that contain mid
	 */
	function _getMidList($selected_addon, $site_srl = 0)
	{
		$oAddonAdminModel = getAdminModel('addon');
		$addon_info = $oAddonAdminModel->getAddonInfoXml($selected_addon, $site_srl);
		return $addon_info->mid_list;
	}

	/**
	 * Re-generate the cache file
	 *
	 * @param int $site_srl Site srl
	 * @param string $type pc or mobile
	 * @param string $gtype site or global
	 * @return void
	 */
	function makeCacheFile($site_srl = 0, $type = "pc", $gtype = 'site')
	{
		// Add-on module for use in creating the cache file
		$buff = array('<?php if(!defined("__XE__")) exit();', '$_m = Context::get(\'mid\');');
		$oAddonModel = getAdminModel('addon');
		$addon_list = $oAddonModel->getInsertedAddons($site_srl, $gtype);
		foreach($addon_list as $addon => $val)
		{
			if($val->addon == "smartphone"
				|| ($type == "pc" && $val->is_used != 'Y') 
				|| ($type == "mobile" && $val->is_used_m != 'Y') 
				|| ($gtype == 'global' && $val->is_fixed != 'Y')
				|| !is_dir(_XE_PATH_ . 'addons/' . $addon))
			{
				continue;
			}

			$extra_vars = unserialize($val->extra_vars);
			$mid_list = $extra_vars->mid_list;
			if(!is_array($mid_list) || count($mid_list) < 1)
			{
				$mid_list = NULL;
			}

			$buff[] = '$before_time = microtime(true);';
			$buff[] = '$rm = \'' . $extra_vars->xe_run_method . "';";
			$buff[] = '$ml = array(';
			if($mid_list)
			{
				foreach($mid_list as $mid)
				{
					$buff[] = "'$mid' => 1,";
				}
			}
			$buff[] = ');';
			$buff[] = sprintf('$addon_file = \'./addons/%s/%s.addon.php\';', $addon, $addon);

			if($val->extra_vars)
			{
				unset($extra_vars);
				$extra_vars = base64_encode($val->extra_vars);
			}
			$addon_include = sprintf('unset($addon_info); $addon_info = unserialize(base64_decode(\'%s\')); @include($addon_file);', $extra_vars);

			$buff[] = 'if(file_exists($addon_file)){';
			$buff[] = 'if($rm === \'no_run_selected\'){';
			$buff[] = 'if(!isset($ml[$_m])){';
			$buff[] = $addon_include;
			$buff[] = '}}else{';
			$buff[] = 'if(isset($ml[$_m]) || count($ml) === 0){';
			$buff[] = $addon_include;
			$buff[] = '}}}';
			$buff[] = '$after_time = microtime(true);';
			$buff[] = '$addon_time_log = new stdClass();';
			$buff[] = '$addon_time_log->caller = $called_position;';
			$buff[] = '$addon_time_log->called = "' . $addon . '";';
			$buff[] = '$addon_time_log->called_extension = "' . $addon . '";';
			$buff[] = 'writeSlowlog("addon",$after_time-$before_time,$addon_time_log);';
		}
		$addon_path = _XE_PATH_ . 'files/cache/addons/';
		FileHandler::makeDir($addon_path);
		$addon_file = $addon_path . ($gtype == 'site' ? $site_srl : '') . $type . '.acivated_addons.cache.php';
		FileHandler::writeFile($addon_file, join(PHP_EOL, $buff));
	}

	/**
	 * Save setup
	 *
	 * @param string $addon Addon name
	 * @param object $extra_vars Extra variables
	 * @param int $site_srl Site srl
	 * @param string $gtype site or global
	 * @return Object
	 */
	function doSetup($addon, $extra_vars, $site_srl = 0, $gtype = 'site')
	{
		if(!is_array($extra_vars->mid_list))
		{
			unset($extra_vars->mid_list);
		}

		$args = new stdClass();
		$args->addon = $addon;
		$args->extra_vars = serialize($extra_vars);
		if($gtype == 'global')
		{
			return executeQuery('addon.updateAddon', $args);
		}
		$args->site_srl = $site_srl;
		return executeQuery('addon.updateSiteAddon', $args);
	}

	/**
	 * Remove add-on information in the virtual site
	 *
	 * @param int $site_srl Site srl
	 * @return void
	 */
	function removeAddonConfig($site_srl)
	{
		$addon_path = _XE_PATH_ . 'files/cache/addons/';
		$addon_file = $addon_path . $site_srl . '.acivated_addons.cache.php';
		if(file_exists($addon_file))
		{
			FileHandler::removeFile($addon_file);
		}

		$args = new stdClass();
		$args->site_srl = $site_srl;
		executeQuery('addon.deleteSiteAddons', $args);
	}

}
/* End of file addon.controller.php */
/* Location: ./modules/addon/addon.controller.php */
