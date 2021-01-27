<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

require_once(RX_BASEDIR . 'modules/addon/addon.controller.php');

/**
 * Admin controller class of addon modules
 * @author NAVER (developers@xpressengine.com)
 */
class addonAdminController extends addonController
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
	 * Set addon activate
	 *
	 * @return Object
	 */
	function procAddonAdminSaveActivate()
	{
		$pcOnList = Context::get('pc_on');
		$mobileOnList = Context::get('mobile_on');
		$fixed = Context::get('fixed');

		$site_module_info = Context::get('site_module_info');

		if($site_module_info->site_srl)
		{
			$site_srl = $site_module_info->site_srl;
		}
		else
		{
			$site_srl = 0;
		}

		if(!$pcOnList)
		{
			$pcOnList = array();
		}
		if(!$mobileOnList)
		{
			$mobileOnList = array();
		}
		if(!$fixed)
		{
			$fixed = array();
		}

		if(!is_array($pcOnList))
		{
			$pcOnList = array($pcOnList);
		}
		if(!is_array($mobileOnList))
		{
			$pcOnList = array($mobileOnList);
		}
		if(!is_array($fixed))
		{
			$pcOnList = array($fixed);
		}

		// get current addon info
		$oModel = getAdminModel('addon');
		$currentAddonList = $oModel->getAddonList($site_srl, 'site');

		// get need update addon list
		$updateList = array();
		foreach($currentAddonList as $addon)
		{
			if($addon->activated !== in_array($addon->addon_name, $pcOnList))
			{
				$updateList[] = $addon->addon_name;
				continue;
			}

			if($addon->mactivated !== in_array($addon->addon_name, $mobileOnList))
			{
				$updateList[] = $addon->addon_name;
				continue;
			}

			if($addon->fixed !== in_array($addon->addon_name, $fixed))
			{
				$updateList[] = $addon->addon_name;
				continue;
			}
		}

		// update
		foreach($updateList as $targetAddon)
		{
			$args = new stdClass();

			if(in_array($targetAddon, $pcOnList))
			{
				$args->is_used = 'Y';
			}
			else
			{
				$args->is_used = 'N';
			}

			if(in_array($targetAddon, $mobileOnList))
			{
				$args->is_used_m = 'Y';
			}
			else
			{
				$args->is_used_m = 'N';
			}

			if(in_array($targetAddon, $fixed))
			{
				$args->fixed = 'Y';
			}
			else
			{
				$args->fixed = 'N';
			}

			$args->addon = $targetAddon;
			$args->site_srl = $site_srl;

			$output = executeQuery('addon.updateSiteAddon', $args);
			if(!$output->toBool())
			{
				return $output;
			}
		}

		if(count($updateList))
		{
			$this->makeCacheFile($site_srl, 'pc', 'site');
			$this->makeCacheFile($site_srl, 'mobile', 'site');
			Rhymix\Framework\Cache::clearGroup('addonConfig');
		}
		
		$this->setMessage('success_updated', 'info');
		if(Context::get('success_return_url'))
		{
			$this->setRedirectUrl(Context::get('success_return_url'));
		}
		else
		{
			$this->setRedirectUrl(getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAddonAdminIndex'));
		}
	}

	/**
	 * Add active/inactive change
	 *
	 * @return Object
	 */
	function procAddonAdminToggleActivate()
	{
		$oAddonModel = getAdminModel('addon');

		$site_module_info = Context::get('site_module_info');
		// batahom addon values
		$addon = Context::get('addon');
		$type = Context::get('type');
		if(!$type)
		{
			$type = "pc";
		}
		if($addon)
		{
			// If enabled Disables
			if($oAddonModel->isActivatedAddon($addon, $site_module_info->site_srl, $type))
			{
				$this->doDeactivate($addon, $site_module_info->site_srl, $type);
			}
			// If it is disabled Activate
			else
			{
				$this->doActivate($addon, $site_module_info->site_srl, $type);
			}
		}

		$this->makeCacheFile($site_module_info->site_srl, $type);
		Rhymix\Framework\Cache::clearGroup('addonConfig');
	}

	/**
	 * Add the configuration information input
	 *
	 * @return Object
	 */
	function procAddonAdminSetupAddon()
	{
		$args = Context::getRequestVars();
		$module = $args->module;
		$addon_name = $args->addon_name;
		unset($args->module);
		unset($args->act);
		unset($args->addon_name);
		unset($args->body);
		unset($args->error_return_url);

		$site_module_info = Context::get('site_module_info');

		$output = $this->doSetup($addon_name, $args, $site_module_info->site_srl, 'site');
		if(!$output->toBool())
		{
			return $output;
		}

		$this->makeCacheFile($site_module_info->site_srl, "pc", 'site');
		$this->makeCacheFile($site_module_info->site_srl, "mobile", 'site');
		Rhymix\Framework\Cache::delete(sprintf('addonConfig:%s:%s', $addon_name, 'any'));
		Rhymix\Framework\Cache::delete(sprintf('addonConfig:%s:%s', $addon_name, 'pc'));
		Rhymix\Framework\Cache::delete(sprintf('addonConfig:%s:%s', $addon_name, 'mobile'));

		$this->setRedirectUrl(getNotEncodedUrl('', 'module', $module, 'act', 'dispAddonAdminSetup', 'selected_addon', $addon_name), $output);
	}

	/**
	 * Adds addon to DB
	 *
	 * @param string $addon Addon name
	 * @param int $site_srl Site srl
	 * @param string $gtype site or global
	 * @param string $isUsed Whether to use
	 * @return Object
	 */
	function doInsert($addon, $site_srl = 0, $gtype = 'site', $isUsed = 'N', $extra_vars = null)
	{
		$args = new stdClass;
		$args->addon = $addon;
		if (strlen($isUsed) == 2)
		{
			$args->is_used = substr($isUsed, 0, 1) === 'Y' ? 'Y' : 'N';
			$args->is_used_m = substr($isUsed, 1, 1) === 'Y' ? 'Y' : 'N';
		}
		else
		{
			$args->is_used = $isUsed === 'Y' ? 'Y' : 'N';
		}
		
		if($gtype == 'global')
		{
			$output = executeQuery('addon.insertAddon', $args);
		}
		else
		{
			$args->extra_vars = serialize($extra_vars);
			$args->site_srl = $site_srl;
			$output = executeQuery('addon.insertSiteAddon', $args);
		}
		
		Rhymix\Framework\Cache::delete(sprintf('addonConfig:%s:%s', $addon, 'any'));
		Rhymix\Framework\Cache::delete(sprintf('addonConfig:%s:%s', $addon, 'pc'));
		Rhymix\Framework\Cache::delete(sprintf('addonConfig:%s:%s', $addon, 'mobile'));
		return $output;
	}

	/**
	 * Activate addon
	 *
	 * @param string $addon Addon name
	 * @param int $site_srl Site srl
	 * @param string $type pc or modile
	 * @param string $gtype site or global
	 * @return Object
	 */
	function doActivate($addon, $site_srl = 0, $type = "pc", $gtype = 'site')
	{
		$args = new stdClass();
		$args->addon = $addon;
		if($type == "pc")
		{
			$args->is_used = 'Y';
		}
		else
		{
			$args->is_used_m = "Y";
		}
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
	 * Deactivate Addon
	 *
	 * @param string $addon Addon name
	 * @param int $site_srl Site srl
	 * @param string $type pc or mobile
	 * @param string $gtype site or global
	 */
	function doDeactivate($addon, $site_srl = 0, $type = "pc", $gtype = 'site')
	{
		$args = new stdClass();
		$args->addon = $addon;
		if($type == "pc")
		{
			$args->is_used = 'N';
		}
		else
		{
			$args->is_used_m = 'N';
		}
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

}
/* End of file addon.admin.controller.php */
/* Location: ./modules/addon/addon.admin.controller.php */