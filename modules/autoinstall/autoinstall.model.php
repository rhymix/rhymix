<?php

class AutoinstallModel extends Autoinstall
{
	/**
	 * Package list API URL
	 */
	public const PACKAGE_LIST_URL = 'https://api.rhymix.org/pds/index.json';

	/**
	 * Get module configuration
	 *
	 * @return object
	 */
	public static function getConfig()
	{
		return ModuleModel::getModuleConfig('autoinstall') ?: new stdClass();
	}

	/**
	 * Get package count
	 *
	 * @param string $type
	 * @return int
	 */
	public static function getPackageCount($type = 'all')
	{
		$args = new stdClass;
		$args->type = ($type === 'all' || $type === 'featured') ? null : $type;
		return executeQuery('autoinstall.getPackageCount', $args)->data->count ?? 0;
	}

	/**
	 * Get package information
	 *
	 * @param int $package_srl
	 * @return ?object
	 */
	public static function getPackage($package_srl)
	{
		$args = new stdClass;
		$args->package_srl = intval($package_srl);
		$output = executeQuery('autoinstall.getPackage', $args);
		if (!$output->toBool())
		{
			return null;
		}
		if (isset($output->data->extra_vars))
		{
			$output->data->extra_vars = json_decode($output->data->extra_vars);
		}
		return $output->data;
	}

	/**
	 * Get the lowest price of a package
	 *
	 * @param object $package
	 * @return ?int
	 */
	public static function getLowestPrice($package)
	{
		if (empty($package->extra_vars->pricing))
		{
			return null;
		}

		$prices = [];
		foreach ($package->extra_vars->pricing as $pricing)
		{
			$prices[] = intval($pricing->price);
		}
		sort($prices);
		return $prices[0] ?? null;
	}

	/**
	 * Search packages
	 *
	 * @param string $type
	 * @param ?string $search_keyword
	 * @param int $count
	 * @param int $page
	 * @return BaseObject
	 */
	public static function searchPackages($type, $search_keyword = null, $count = 20, $page = 1)
	{
		$args = new stdClass;
		$args->type = ($type === 'all' || $type === 'featured') ? null : $type;
		$args->search_keyword = $search_keyword ?: null;
		$args->sort_index = ($type === 'featured') ? 'featured_count' : 'updated';
		$args->list_count = $count;
		$args->page = $page;
		$output = executeQueryArray('autoinstall.getPackages', $args);
		foreach ($output->data as $row)
		{
			$row->extra_vars = json_decode($row->extra_vars);
		}

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
		$request = Rhymix\Framework\HTTP::get(self::PACKAGE_LIST_URL, null, [], [], ['timeout' => 10]);
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
			$args = new stdClass;
			$extra_vars = new stdClass;
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
}
