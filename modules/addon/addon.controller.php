<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Addon module's controller class
 * @author NAVER (developers@xpressengine.com)
 */
class addonController extends addon
{
	public $addon_file_called = false;
	
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

		$addon_file = RX_BASEDIR . 'files/cache/addons/' . $type . '.php';
		if($this->addon_file_called)
		{
			return $addon_file;
		}

		$this->addon_file_called = TRUE;
		if(!file_exists($addon_file) || filemtime($addon_file) < filemtime(__FILE__))
		{
			$this->makeCacheFile(0, $type);
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
			if(Context::isBlacklistedPlugin($addon)
				|| ($type == "pc" && $val->is_used != 'Y') 
				|| ($type == "mobile" && $val->is_used_m != 'Y') 
				|| ($gtype == 'global' && $val->is_fixed != 'Y')
				|| !is_dir(RX_BASEDIR . 'addons/' . $addon))
			{
				continue;
			}
			
			$extra_vars = unserialize($val->extra_vars);
			if(!$extra_vars)
			{
				$extra_vars = new stdClass;
			}
			
			$mid_list = $extra_vars->mid_list ?? [];
			if(!is_array($mid_list))
			{
				$mid_list = array();
			}
			
			// Initialize
			$buff[] = '$before_time = microtime(true);';
			
			// Run method and mid list
			$run_method = ($extra_vars->xe_run_method ?? null) ?: 'run_selected';
			$buff[] = '$rm = \'' . $run_method . "';";
			$buff[] = '$ml = ' . var_export(array_fill_keys($mid_list, true), true) . ';';
			
			// Addon filename
			$buff[] = sprintf('$addon_file = RX_BASEDIR . \'addons/%s/%s.addon.php\';', $addon, $addon);
			
			// Addon configuration
			$buff[] = '$addon_info = unserialize(' . var_export(serialize($extra_vars), true) . ');';
			
			// Decide whether to run in this mid
			if ($run_method === 'no_run_selected')
			{
				$buff[] = '$run = !isset($ml[$_m]);';
			}
			elseif (!count($mid_list))
			{
				$buff[] = '$run = true;';
			}
			else
			{
				$buff[] = '$run = isset($ml[$_m]);';
			}
			
			// Write debug info
			$buff[] = 'if ($run && file_exists($addon_file)):';
			$buff[] = '  include($addon_file);';
			$buff[] = '  $after_time = microtime(true);';
			$buff[] = '  if (class_exists("Rhymix\\\\Framework\\\\Debug")):';
			$buff[] = '    Rhymix\\Framework\\Debug::addTrigger(array(';
			$buff[] = '      "name" => "addon." . $called_position,';
			$buff[] = '      "target" => "' . $addon . '",';
			$buff[] = '      "target_plugin" => "' . $addon . '",';
			$buff[] = '      "elapsed_time" => $after_time - $before_time,';
			$buff[] = '    ));';
			$buff[] = '  endif;';
			$buff[] = 'endif;';
			$buff[] = '';
		}
		
		// Write file in new location
		$addon_file = RX_BASEDIR . 'files/cache/addons/' . $type . '.php';
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
			$output = executeQuery('addon.updateAddon', $args);
		}
		else
		{
			$args->site_srl = $site_srl;
			$output = executeQuery('addon.updateSiteAddon', $args);
		}
		
		Rhymix\Framework\Cache::delete(sprintf('addonConfig:%s:%s', $addon, 'any'));
		Rhymix\Framework\Cache::delete(sprintf('addonConfig:%s:%s', $addon, 'pc'));
		Rhymix\Framework\Cache::delete(sprintf('addonConfig:%s:%s', $addon, 'mobile'));
		return $output;
	}

	/**
	 * Remove add-on information in the virtual site
	 *
	 * @param int $site_srl Site srl
	 * @return void
	 */
	function removeAddonConfig($site_srl)
	{
		$args = new stdClass();
		$args->site_srl = $site_srl;
		executeQuery('addon.deleteSiteAddons', $args);
		Rhymix\Framework\Cache::clearGroup('addonConfig');
	}

}
/* End of file addon.controller.php */
/* Location: ./modules/addon/addon.controller.php */
