<?php

namespace Rhymix\Modules\Admin\Controllers\Maintenance;

use FileHandler;
use ModuleModel;
use ModuleObject;
use Rhymix\Framework\Cache;
use Rhymix\Framework\Config;
use Rhymix\Framework\Storage;
use Rhymix\Modules\Admin\Controllers\Base;

class CacheReset extends Base
{
	/**
	 * Regenerate all cache files
	 */
	public function procAdminRecompileCacheFile()
	{
		// rename cache dir
		$truncate_method = Config::get('cache.truncate_method');
		if ($truncate_method === 'empty')
		{
			$tmp_basedir = \RX_BASEDIR . 'files/cache/truncate_' . time();
			Storage::createDirectory($tmp_basedir);
			$dirs = Storage::readDirectory(\RX_BASEDIR . 'files/cache', true, false, false);
			if ($dirs)
			{
				foreach ($dirs as $dir)
				{
					Storage::moveDirectory($dir, $tmp_basedir . '/' . basename($dir));
				}
			}
		}
		else
		{
			Storage::move(\RX_BASEDIR . 'files/cache', \RX_BASEDIR . 'files/cache_' . time());
			Storage::createDirectory(\RX_BASEDIR . 'files/cache');
		}

		// remove module extend cache
		Storage::delete(RX_BASEDIR . 'files/config/module_extend.php');

		// remove debug files
		Storage::delete(RX_BASEDIR . 'files/_debug_message.php');
		Storage::delete(RX_BASEDIR . 'files/_debug_db_query.php');
		Storage::delete(RX_BASEDIR . 'files/_db_slow_query.php');

		$module_list = ModuleModel::getModuleList();

		// call recompileCache for each module
		foreach($module_list as $module)
		{
			$oModule = getClass($module->module);
			if (!$oModule)
			{
				$oModule = ModuleModel::getModuleInstallClass($module->module);
			}
			if ($oModule instanceof ModuleObject && method_exists($oModule, 'recompileCache'))
			{
				call_user_func([$oModule, 'recompileCache']);
			}
		}

		// remove object cache
		if (!in_array(Cache::getDriverName(), array('file', 'sqlite', 'dummy')))
		{
			Cache::clearAll();
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
					if (!Storage::isDirectory($tmp_dir))
					{
						continue;
					}

					// If possible, use system command to speed up recursive deletion
					if (function_exists('exec') && !preg_match('/(?<!_)exec/', ini_get('disable_functions')))
					{
						if (\RX_WINDOWS)
						{
							@exec('rmdir /S /Q ' . escapeshellarg($tmp_dir));
						}
						else
						{
							@exec('rm -rf ' . escapeshellarg($tmp_dir));
						}
					}

					// If the directory still exists, delete using PHP.
					Storage::deleteDirectory($tmp_dir);
				}
			}
		}

		// check autoinstall packages
		$oAutoinstallAdminController = getAdminController('autoinstall');
		$oAutoinstallAdminController->checkInstalled();

		// Opcache reset
		if (function_exists('opcache_reset'))
		{
			opcache_reset();
		}

		$this->setMessage('success_updated');
	}
}
