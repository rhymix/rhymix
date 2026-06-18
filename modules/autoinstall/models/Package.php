<?php

namespace Rhymix\Modules\Autoinstall\Models;

use Rhymix\Framework\DB;
use Rhymix\Framework\Helpers\DBResultHelper;
use Rhymix\Framework\HTTP;
use Rhymix\Framework\Storage;
use Context;

#[\AllowDynamicProperties]
class Package
{
	/**
	 * Package list API URL
	 */
	public const PACKAGE_LIST_URL = 'https://api.rhymix.org/pds/index.json';

	/**
	 * Install path format of each type of package
	 */
	public const INSTALL_PATHS = [
		'module' => '!^\./modules/(?<name>[a-zA-Z0-9_]+)$!',
		'addon' => '!^\./addons/(?<name>[a-zA-Z0-9_]+)$!',
		'layout' => '!^\./layouts/(?<name>[a-zA-Z0-9_]+)$!',
		'widget' => '!^\./widgets/(?<name>[a-zA-Z0-9_]+)$!',
		'module-skin' => '!^\./modules/(?<module_name>[a-zA-Z0-9_]+)/(?<skin_type>(?:m\.)?skins)/(?<skin_name>[a-zA-Z0-9_]+)$!',
		'widget-skin' => '!^\./widgets/(?<widget_name>[a-zA-Z0-9_]+)/(?<skin_type>(?:m\.)?skins)/(?<skin_name>[a-zA-Z0-9_]+)$!',
		'editor-skin' => '!^\./modules/editor/skins/(?<name>[a-zA-Z0-9_]+)$!',
		'editor-component' => '!^\./modules/editor/components/(?<name>[a-zA-Z0-9_]+)$!',
	];

	/**
	 * Path to info file of each type of package
	 */
	public const INFO_FILES = [
		'module' => 'conf/info.xml',
		'addon' => 'conf/info.xml',
		'layout' => 'conf/info.xml',
		'widget' => 'conf/info.xml',
		'module-skin' => 'skin.xml',
		'widget-skin' => 'skin.xml',
		'editor-skin' => 'skin.xml',
		'editor-component' => 'info.xml',
	];

	/**
	 * Reserved names.
	 */
	public const RESERVED_SKIN_NAMES = [
		'admin',
		'default',
		'xedition',
	];

	/*
	 * Properties for DB columns
	 */
	public $package_srl;
	public $type;
	public $title;
	public $description;
	public $author;
	public $license;
	public $last_release_version;
	public $install_path;
	public $install_enabled;
	public $sale_enabled;
	public $featured_count;
	public $created;
	public $updated;
	public $extra_vars;

	/**
	 * Get type name of the current package.
	 *
	 * @return string
	 */
	public function getType()
	{
		return lang('autoinstall.typename.' . $this->type);
	}

	/**
	 * Get the lowest price of the current package.
	 *
	 * @return ?int
	 */
	public function getLowestPrice()
	{
		if (empty($this->extra_vars->pricing))
		{
			return null;
		}

		$prices = [];
		foreach ($this->extra_vars->pricing as $pricing)
		{
			$prices[] = intval($pricing->price);
		}
		sort($prices);
		return $prices[0] ?? null;
	}

	/**
	 * Get installation environment of the current package.
	 *
	 * @param string $type 'core' or 'php'
	 * @return string
	 */
	public function getInstallEnvironment($type)
	{
		if ($type === 'core')
		{
			$min = $this->extra_vars->install_env->core_min ?? null;
			$max = $this->extra_vars->install_env->core_max ?? null;
		}
		elseif ($type === 'php')
		{
			$min = $this->extra_vars->install_env->php_min ?? null;
			$max = $this->extra_vars->install_env->php_max ?? null;
		}
		else
		{
			return '';
		}

		if ($min && $max)
		{
			return sprintf('%s - %s', $min, $max);
		}
		elseif ($min)
		{
			return sprintf('%s %s', $min, lang('autoinstall.install_environment.min'));
		}
		elseif ($max)
		{
			return sprintf('%s %s', $max, lang('autoinstall.install_environment.max'));
		}
		else
		{
			return '';
		}
	}

	/**
	 * Check if the current package is installable.
	 *
	 * @return bool
	 */
	public function isInstallable()
	{
		if ($this->install_enabled !== 'Y')
		{
			return false;
		}
		if ($this->license === 'FOR SALE' && $this->sale_enabled !== 'Y')
		{
			return false;
		}
		if (!self::_validateInstallPath($this->install_path, $this->type))
		{
			return false;
		}
		if (!empty($this->extra_vars->install_env->core_min) && version_compare(\RX_VERSION, $this->extra_vars->install_env->core_min, '<'))
		{
			return false;
		}
		if (!empty($this->extra_vars->install_env->core_max) && version_compare(\RX_VERSION, $this->extra_vars->install_env->core_max, '>'))
		{
			return false;
		}
		if (!empty($this->extra_vars->install_env->php_min) && version_compare(\PHP_VERSION, $this->extra_vars->install_env->php_min, '<'))
		{
			return false;
		}
		if (!empty($this->extra_vars->install_env->php_max) && version_compare(\PHP_VERSION, $this->extra_vars->install_env->php_max, '>'))
		{
			return false;
		}

		return true;
	}

	/**
	 * Check if the current package is installed.
	 *
	 * @return bool
	 */
	public function isInstalled()
	{
		if (!self::_validateInstallPath($this->install_path, $this->type))
		{
			return false;
		}

		$basedir = \RX_BASEDIR . rtrim(ltrim($this->install_path, './'), '/') . '/';
		$info_filename = self::INFO_FILES[$this->type] ?? null;
		if (Storage::exists($basedir . $info_filename))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Get cached package count.
	 *
	 * @param string $type
	 * @return int
	 */
	public static function getPackageCount($type = 'all')
	{
		$args = new \stdClass;
		$args->type = ($type === 'all' || $type === 'featured') ? null : $type;
		return executeQuery('autoinstall.getPackageCount', $args)->data->count ?? 0;
	}

	/**
	 * Get package information
	 *
	 * @param int $package_srl
	 * @return ?self
	 */
	public static function getPackage($package_srl)
	{
		$args = new \stdClass;
		$args->package_srl = intval($package_srl);
		$output = executeQuery('autoinstall.getPackage', $args, [], 'auto', self::class);
		if (!$output->toBool())
		{
			return null;
		}
		return $output->data;
	}

	/**
	 * Search packages
	 *
	 * @param string $type
	 * @param ?string $search_keyword
	 * @param int $count
	 * @param int $page
	 * @return DBResultHelper
	 */
	public static function searchPackages($type, $search_keyword = null, $count = 20, $page = 1)
	{
		$args = new \stdClass;
		$args->type = ($type === 'all' || $type === 'featured') ? null : $type;
		$args->search_keyword = $search_keyword ?: null;
		$args->sort_index = ($type === 'featured') ? 'featured_count' : 'updated';
		$args->list_count = $count;
		$args->page = $page;
		$output = executeQueryArray('autoinstall.getPackages', $args, [], self::class);

		// Cap featured list to 3 pages
		if ($type === 'featured' && $output->total_page > 3)
		{
			$output->total_count = $count * 3;
			$output->total_page = 3;
			$output->page_navigation->total_count = $count * 3;
			$output->page_navigation->total_page = 3;
			$output->page_navigation->page_count = 3;
			$output->page_navigation->last_page = 3;
		}

		return $output;
	}

	/**
	 * Update package list from official repository
	 *
	 * @return bool
	 */
	public static function updatePackageList()
	{
		// Fetch the latest package list.
		$request = HTTP::get(self::PACKAGE_LIST_URL, null, [], [], ['timeout' => 10]);
		if ($request->getStatusCode() !== 200)
		{
			return false;
		}
		$response = $request->getBody()->getContents();
		if ($response === '')
		{
			return false;
		}
		$response = json_decode($response);
		if (!$response || !isset($response->category))
		{
			return false;
		}

		// Store the package list in the database.
		$oDB = DB::getInstance();
		$oDB->begin();
		$oDB->query('TRUNCATE TABLE autoinstall_packages');
		foreach (array_reverse($response->packages) as $package)
		{
			$args = new \stdClass;
			$extra_vars = new \stdClass;
			$args->package_srl = $package->package_srl;
			$args->type = $package->type;
			$args->title = $package->title;
			$args->description = $package->description;
			$args->author = $package->author;
			$args->license = $package->license ?: '';
			$args->last_release_version = $package->last_release_version ?: '';
			$args->install_path = $package->install_path ?: '';
			$args->install_enabled = $package->install_enabled ? 'Y' : 'N';
			$args->sale_enabled = $package->sale_enabled ? 'Y' : 'N';
			$args->created = $package->created;
			$args->updated = $package->updated;

			$featured_count = sqrt($package->download_count) + $package->like_count;
			$age = (time() - ztime($args->updated)) / (86400 * 365);
			$featured_count = $featured_count / (1 + $age);
			$args->featured_count = round($featured_count);

			foreach ($package as $key => $val)
			{
				if (!isset($args->{$key}) && isset($val))
				{
					$extra_vars->{$key} = $val;
				}
			}
			$args->extra_vars = json_encode($extra_vars, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
			$output = executeQuery('autoinstall.insertPackage', $args);
			if (!$output->toBool())
			{
				$oDB->rollback();
				return false;
			}
		}
		$oDB->commit();

		return true;
	}

	/**
	 * Decoding an instance of the Package class from a database row
	 */
	public function __construct()
	{
		if ($this->extra_vars)
		{
			$this->extra_vars = json_decode($this->extra_vars);
		}
	}

	/**
	 * Validate the install path.
	 *
	 * @param string $path
	 * @param string $type
	 * @return bool
	 */
	protected static function _validateInstallPath($path, $type)
	{
		if (!$path)
		{
			return false;
		}

		$pattern = self::INSTALL_PATHS[$type] ?? null;
		if (!$pattern || !preg_match($pattern, $path, $matches))
		{
			return false;
		}

		$name = $matches['skin_name'] ?? $matches['name'] ?? null;
		if (preg_match('!-(?:skin|component)$!', $type))
		{
			if (in_array($name, self::RESERVED_SKIN_NAMES, true))
			{
				return false;
			}
		}
		else
		{
			if (Context::isDefaultPlugin($name, $type))
			{
				return false;
			}
			if (Context::isBlacklistedPlugin($name, $type))
			{
				return false;
			}
		}

		return true;
	}
}
