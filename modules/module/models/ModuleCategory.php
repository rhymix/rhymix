<?php

namespace Rhymix\Modules\Module\Models;

use Rhymix\Framework\Cache;
use Rhymix\Framework\DB;
use Rhymix\Framework\Helpers\DBResultHelper;

class ModuleCategory
{
	/*
	 * Attributes to match database columns.
	 */
	public int $module_category_srl;
	public string $title;
	public string $regdate;

	/**
	 * Get a module category.
	 *
	 * @param int $module_category_srl
	 * @return ?self
	 */
	public static function getModuleCategory(int $module_category_srl): ?self
	{
		$args = ['module_category_srl' => $module_category_srl];
		$output = executeQuery('module.getModuleCategory', $args, [], 'auto', self::class);
		return $output->data ?: null;
	}

	/**
	 * @brief Get a list of module category
	 *
	 * @param array $module_category_srl
	 * @return array<self>
	 */
	public static function getModuleCategories(array $module_category_srl = []): array
	{
		$args = ['module_category_srl' => $module_category_srl];
		$output = executeQueryArray('module.getModuleCategories', $args, [], self::class);
		$category_list = [];
		foreach ($output->data ?? [] as $val)
		{
			$category_list[$val->module_category_srl] = $val;
		}
		return $category_list;
	}

	/**
	 * Insert a module category.
	 *
	 * @param string $title
	 * @return DBResultHelper
	 */
	public static function insertModuleCategory(string $title): DBResultHelper
	{
		$args = ['module_category_srl' => getNextSequence(), 'title' => $title];
		$output = executeQuery('module.insertModuleCategory', $args);
		if ($output->toBool())
		{
			$output->add('module_category_srl', $args['module_category_srl']);
		}
		return $output;
	}

	/**
	 * Update a module category.
	 *
	 * @param int $module_category_srl
	 * @param string $title
	 * @return DBResultHelper
	 */
	public static function updateModuleCategory(int $module_category_srl, string $title): DBResultHelper
	{
		$args = ['module_category_srl' => $module_category_srl, 'title' => $title];
		return executeQuery('module.updateModuleCategory', $args);
	}

	/**
	 * Delete a module category.
	 *
	 * @param int $module_category_srl
	 * @return DBResultHelper
	 */
	public static function deleteModuleCategory(int $module_category_srl): DBResultHelper
	{
		$args = ['module_category_srl' => $module_category_srl];
		return executeQuery('module.deleteModuleCategory', $args);
	}
}
