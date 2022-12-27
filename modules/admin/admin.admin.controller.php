<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * adminAdminController class
 * admin controller class of admin module
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/admin
 * @version 0.1
 */
class AdminAdminController extends Admin
{
	/**
	 * initialization
	 * @return void
	 */
	public function init()
	{
		// forbit access if the user is not an administrator
		if (!$this->user->isAdmin())
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted('admin.msg_is_not_administrator');
		}
	}

	/**
	 * Admin menu reset
	 * @return void
	 */
	public function procAdminMenuReset()
	{
		$oMenuAdminModel = getAdminModel('menu');
		$oMenuAdminController = getAdminController('menu');
		for ($i = 0; $i < 100; $i++)
		{
			$output = $oMenuAdminModel->getMenuByTitle($this->getAdminMenuName());
			$admin_menu_srl = $output->menu_srl ?? 0;
			if ($admin_menu_srl)
			{
				$output = $oMenuAdminController->deleteMenu($admin_menu_srl);
				if (!$output->toBool())
				{
					return $output;
				}
			}
			else
			{
				break;
			}
		}

		Rhymix\Framework\Cache::delete('admin_menu_langs:' . Context::getLangType());
		Rhymix\Framework\Storage::deleteDirectory(\RX_BASEDIR . 'files/cache/menu/admin_lang/');

		$this->setRedirectUrl(Context::get('error_return_url'));
	}

	/**
	 * Regenerate all cache files
	 * @return void
	 */
	public function procAdminRecompileCacheFile()
	{
		// rename cache dir
		$truncate_method = Rhymix\Framework\Config::get('cache.truncate_method');
		if ($truncate_method === 'empty')
		{
			$tmp_basedir = \RX_BASEDIR . 'files/cache/truncate_' . time();
			Rhymix\Framework\Storage::createDirectory($tmp_basedir);
			$dirs = Rhymix\Framework\Storage::readDirectory(\RX_BASEDIR . 'files/cache', true, false, false);
			if ($dirs)
			{
				foreach ($dirs as $dir)
				{
					Rhymix\Framework\Storage::moveDirectory($dir, $tmp_basedir . '/' . basename($dir));
				}
			}
		}
		else
		{
			Rhymix\Framework\Storage::move(\RX_BASEDIR . 'files/cache', \RX_BASEDIR . 'files/cache_' . time());
			Rhymix\Framework\Storage::createDirectory(\RX_BASEDIR . 'files/cache');
		}

		// remove module extend cache
		Rhymix\Framework\Storage::delete(RX_BASEDIR . 'files/config/module_extend.php');

		// remove debug files
		Rhymix\Framework\Storage::delete(RX_BASEDIR . 'files/_debug_message.php');
		Rhymix\Framework\Storage::delete(RX_BASEDIR . 'files/_debug_db_query.php');
		Rhymix\Framework\Storage::delete(RX_BASEDIR . 'files/_db_slow_query.php');

		$oModuleModel = getModel('module');
		$module_list = $oModuleModel->getModuleList();

		// call recompileCache for each module
		foreach($module_list as $module)
		{
			$oModule = NULL;
			$oModule = getClass($module->module);
			if($oModule && method_exists($oModule, 'recompileCache'))
			{
				$oModule->recompileCache();
			}
		}

		// remove object cache
		if (!in_array(Rhymix\Framework\Cache::getDriverName(), array('file', 'sqlite', 'dummy')))
		{
			Rhymix\Framework\Cache::clearAll();
		}

		// remove old cache dir
		if ($truncate_method === 'empty')
		{
			$tmp_cache_list = FileHandler::readDir(\RX_BASEDIR . 'files/cache', '/^(truncate_[0-9]+)/');
			$tmp_cache_prefix = \RX_BASEDIR . 'files/cache/';
		}
		else
		{
			$tmp_cache_list = FileHandler::readDir(\RX_BASEDIR . 'files', '/^(cache_[0-9]+)/');
			$tmp_cache_prefix = \RX_BASEDIR . 'files/';
		}
		
		if($tmp_cache_list)
		{
			foreach($tmp_cache_list as $tmp_dir)
			{
				if(strval($tmp_dir) !== '')
				{
					$tmp_dir = $tmp_cache_prefix . $tmp_dir;
					if (!Rhymix\Framework\Storage::isDirectory($tmp_dir))
					{
						continue;
					}
					
					// If possible, use system command to speed up recursive deletion
					if (function_exists('exec') && !preg_match('/(?<!_)exec/', ini_get('disable_functions')))
					{
						if (strncasecmp(\PHP_OS, 'win', 3) == 0)
						{
							@exec('rmdir /S /Q ' . escapeshellarg($tmp_dir));
						}
						else
						{
							@exec('rm -rf ' . escapeshellarg($tmp_dir));
						}
					}
					
					// If the directory still exists, delete using PHP.
					Rhymix\Framework\Storage::deleteDirectory($tmp_dir);
				}
			}
		}

		// check autoinstall packages
		$oAutoinstallAdminController = getAdminController('autoinstall');
		$oAutoinstallAdminController->checkInstalled();

		$this->setMessage('success_updated');
	}

	public function procAdminInsertDefaultDesignInfo()
	{
		$vars = Context::getRequestVars();

		// create a DesignInfo file
		$this->updateDefaultDesignInfo($vars);
		$this->setRedirectUrl(Context::get('error_return_url'));
	}

	public function updateDefaultDesignInfo($vars)
	{
		$siteDesignPath = RX_BASEDIR . 'files/site_design/';

		$vars->module_skin = json_decode($vars->module_skin);

		if(!is_dir($siteDesignPath))
		{
			FileHandler::makeDir($siteDesignPath);
		}

		$siteDesignFile = RX_BASEDIR . 'files/site_design/design_0.php';

		$layoutTarget = 'layout_srl';
		$skinTarget = 'skin';

		if($vars->target_type == 'M')
		{
			$layoutTarget = 'mlayout_srl';
			$skinTarget = 'mskin';
		}

		if(is_readable($siteDesignFile))
		{
			include($siteDesignFile);
		}
		else
		{
			$designInfo = new stdClass();
		}

		$layoutSrl = (!$vars->layout_srl) ? 0 : $vars->layout_srl;

		$designInfo->{$layoutTarget} = $layoutSrl;

		foreach($vars->module_skin as $moduleName => $skinName)
		{
			if($moduleName == 'ARTICLE')
			{
				$moduleName = 'page';
			}

			if(!isset($designInfo->module->{$moduleName})) $designInfo->module->{$moduleName} = new stdClass();
			$designInfo->module->{$moduleName}->{$skinTarget} = $skinName;
		}

		$this->makeDefaultDesignFile($designInfo);
	}

	public function makeDefaultDesignFile($designInfo)
	{
		$buff = array();
		$buff[] = '<?php if(!defined("__XE__")) exit();';
		$buff[] = '$designInfo = new stdClass;';

		if($designInfo->layout_srl)
		{
			$buff[] = sprintf('$designInfo->layout_srl = %s; ', var_export(intval($designInfo->layout_srl), true));
		}

		if($designInfo->mlayout_srl)
		{
			$buff[] = sprintf('$designInfo->mlayout_srl = %s;', var_export(intval($designInfo->mlayout_srl), true));
		}

		$buff[] = '$designInfo->module = new stdClass;';

		foreach($designInfo->module as $moduleName => $skinInfo)
		{
			$buff[] = sprintf('$designInfo->module->{%s} = new stdClass;', var_export(strval($moduleName), true));
			foreach($skinInfo as $target => $skinName)
			{
				$buff[] = sprintf('$designInfo->module->{%s}->{%s} = %s;', var_export(strval($moduleName), true), var_export(strval($target), true), var_export(strval($skinName), true));
			}
		}

		$siteDesignFile = RX_BASEDIR . 'files/site_design/design_0.php';
		FileHandler::writeFile($siteDesignFile, implode(PHP_EOL, $buff));
	}

	/**
	 * Toggle favorite
	 * @return void
	 */
	public function procAdminToggleFavorite()
	{
		$moduleName = Context::get('module_name');

		// check favorite exists
		$output = Rhymix\Modules\Admin\Models\Favorite::isFavorite($moduleName);
		if(!$output->toBool())
		{
			return $output;
		}

		// if exists, delete favorite
		if($output->get('result'))
		{
			$favoriteSrl = $output->get('favoriteSrl');
			$output = $this->_deleteFavorite($favoriteSrl);
			$result = 'off';
		}
		// if not exists, insert favorite
		else
		{
			$output = $this->_insertFavorite(0, $moduleName);
			$result = 'on';
		}

		if(!$output->toBool())
		{
			return $output;
		}

		$this->add('result', $result);

		return $this->setRedirectUrl(Context::get('error_return_url'), $output);
	}

	/**
	 * Cleanning favorite
	 * @return object|void
	 */
	public function cleanFavorite()
	{
		$output = Rhymix\Modules\Admin\Models\Favorite::getFavorites();
		if(!$output->toBool())
		{
			return $output;
		}

		$favoriteList = $output->get('favoriteList');
		if(!$favoriteList)
		{
			return;
		}

		$deleteTargets = array();
		foreach($favoriteList as $favorite)
		{
			if($favorite->type == 'module')
			{
				$modulePath = RX_BASEDIR . 'modules/' . $favorite->module;
				if(!is_dir($modulePath))
				{
					$deleteTargets[] = $favorite->admin_favorite_srl;
				}
			}
		}

		if(!count($deleteTargets))
		{
			return;
		}

		$args = new stdClass();
		$args->admin_favorite_srls = $deleteTargets;
		$output = executeQuery('admin.deleteFavorites', $args);
		if(!$output->toBool())
		{
			return $output;
		}
	}

	/**
	 * Admin logo delete
	 * @return void
	 */
	public function procAdminDeleteLogo()
	{
		$oModuleModel = getModel('module');
		$oAdminConfig = $oModuleModel->getModuleConfig('admin');

		Rhymix\Framework\Storage::delete(RX_BASEDIR . $oAdminConfig->adminLogo);
		unset($oAdminConfig->adminLogo);

		$oModuleController = getController('module');
		$oModuleController->insertModuleConfig('admin', $oAdminConfig);

		$this->setMessage('success_deleted', 'info');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdminSetup');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * Remove admin icon
	 * @return object|void
	 */
	public function procAdminRemoveIcons()
	{
		$site_info = Context::get('site_module_info');
		$virtual_site = '';
		if($site_info->site_srl) 
		{
			$virtual_site = $site_info->site_srl . '/';
		}

		$iconname = Context::get('iconname');
		$file_exist = FileHandler::readFile(RX_BASEDIR . 'files/attach/xeicon/' . $virtual_site . $iconname);
		if($file_exist)
		{
			@Rhymix\Framework\Storage::delete(RX_BASEDIR . 'files/attach/xeicon/' . $virtual_site . $iconname);
		}
		else
		{
			throw new Exception('fail_to_delete');
		}
		$this->setMessage('success_deleted');
	}
	
	/**
	 * Update FTP configuration.
	 * 
	 * @deprecated
	 */
	public function procAdminUpdateFTPInfo()
	{
		throw new Rhymix\Framework\Exceptions\FeatureDisabled;
	}
	
	/**
	 * Remove FTP configuration.
	 * 
	 * @deprecated
	 */
	public function procAdminRemoveFTPInfo()
	{
		throw new Rhymix\Framework\Exceptions\FeatureDisabled;
	}

	/**
	 * Enviroment gathering agreement
	 * 
	 * @deprecated
	 */
	public function procAdminEnviromentGatheringAgreement()
	{
		$redirectUrl = getNotEncodedUrl('', 'module', 'admin');
		$this->setRedirectUrl($redirectUrl);
	}

	/**
	 * Update admin module config
	 * 
	 * @deprecated
	 */
	public function procAdminUpdateConfig()
	{
		return new BaseObject;
	}

	/**
	 * Insert favorite.
	 * 
	 * @deprecated
	 */
	public function _insertFavorite($site_srl, $module, $type = 'module')
	{
		return Rhymix\Modules\Admin\Models\Favorite::insertFavorite($module, $type);
	}

	/**
	 * Delete favorite.
	 * 
	 * @deprecated
	 */
	public function _deleteFavorite($favoriteSrl)
	{
		return Rhymix\Modules\Admin\Models\Favorite::deleteFavorite($favoriteSrl);
	}

	/**
	 * Delete all favorites.
	 * 
	 * @deprecated
	 */
	public function _deleteAllFavorite()
	{
		return Rhymix\Modules\Admin\Models\Favorite::deleteAllFavorites();
	}
}
