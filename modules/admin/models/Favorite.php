<?php

namespace Rhymix\Modules\Admin\Models;

use BaseObject;
use ModuleModel;
use Rhymix\Framework\Storage;
use Rhymix\Framework\Helpers\DBResultHelper;

class Favorite
{
	/**
	 * Get admin favorite list
	 *
	 * @param bool $add_module_info
	 * @return BaseObject
	 */
	public static function getFavorites(bool $add_module_info = false): BaseObject
	{
		$output = executeQueryArray('admin.getFavoriteList', []);
		if (!$output->toBool())
		{
			return $output;
		}
		if (!$output->data)
		{
			return new BaseObject;
		}

		if ($add_module_info && is_array($output->data))
		{
			foreach ($output->data as $key => $value)
			{
				$module_info = ModuleModel::getModuleInfoXml($value->module);
				$output->data[$key]->admin_index_act = $module_info->admin_index_act ?? null;
				$output->data[$key]->title = $module_info->title ?? null;
			}
		}

		$result = new BaseObject;
		$result->add('favoriteList', $output->data);
		return $result;
	}

	/**
	 * Check if a module is already favorite
	 *
	 * @param string $module
	 * @return BaseObject
	 */
	public static function isFavorite(string $module): BaseObject
	{
		$output = executeQuery('admin.getFavorite', ['module' => $module]);
		if(!$output->toBool())
		{
			return $output;
		}

		$result = new BaseObject;
		if ($output->data)
		{
			$result->add('result', true);
			$result->add('favoriteSrl', $output->data->admin_favorite_srl);
		}
		else
		{
			$result->add('result', false);
		}
		return $result;
	}

	/**
	 * Insert a favorite.
	 *
	 * @param string $module
	 * @param string $type
	 * @return DBResultHelper
	 */
	public static function insertFavorite(string $module, string $type = 'module'): DBResultHelper
	{
		$args = new \stdClass;
		$args->admin_favorite_srl = getNextSequence();
		$args->module = $module;
		$args->type = $type;
		$output = executeQuery('admin.insertFavorite', $args);
		return $output;
	}

	/**
	 * Delete a favorite.
	 *
	 * @param int $favorite_srl
	 * @return DBResultHelper
	 */
	public static function deleteFavorite(int $favorite_srl): DBResultHelper
	{
		$args = new \stdClass;
		$args->admin_favorite_srl = $favorite_srl;
		$output = executeQuery('admin.deleteFavorite', $args);
		return $output;
	}

	/**
	 * Delete all favorites.
	 *
	 * @return DBResultHelper
	 */
	public static function deleteAllFavorites(): DBResultHelper
	{
		$args = new \stdClass;
		$output = executeQuery('admin.deleteAllFavorite', $args);
		return $output;
	}

	/**
	 * Delete all favorites that don't point to an existing module.
	 *
	 * @return BaseObject
	 */
	public static function deleteInvalidFavorites(): BaseObject
	{
		$output = self::getFavorites();
		if (!$output->toBool())
		{
			return $output;
		}

		$favorites = $output->get('favoriteList');
		if (!$favorites)
		{
			return new BaseObject;
		}

		$delete_favorite_srls = array();
		foreach ($favorites as $favorite)
		{
			if ($favorite->type === 'module' && !Storage::isDirectory(\RX_BASEDIR . 'modules/' . $favorite->module))
			{
				$delete_favorite_srls[] = $favorite->admin_favorite_srl;
			}
		}

		if( !count($delete_favorite_srls))
		{
			return new BaseObject;
		}

		$args = new \stdClass;
		$args->admin_favorite_srls = $delete_favorite_srls;
		$output = executeQuery('admin.deleteFavorites', $args);
		return $output;
	}
}
