<?php

namespace Rhymix\Modules\Module\Models;

use Rhymix\Framework\Cache;
use Rhymix\Framework\DB;
use Rhymix\Framework\Helpers\DBResultHelper;

class ModuleCategory
{
	/**
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
		$args = new \stdClass;
		$args->module_category_srl = $module_category_srl;
		$output = executeQuery('module.getModuleCategory', $args, [], 'auto', self::class);
		return $output->data ?: null;
	}

	/**
	 * @brief Get a list of module category
	 *
	 * @param array $module_category_srl
	 * @return array
	 */
	public static function getModuleCategories(array $module_category_srl = []): array
	{
		$args = new \stdClass;
		$args->module_category_srl = $module_category_srl;
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
	 */
	public static function insertModuleCategory(object $args): DBResultHelper
	{

	}

	/*
	 * Update a module category.
	 */
	public static function updateModuleCategory(object $args): DBResultHelper
	{

	}

	/*
	 * Delete a module category.
	 */
	public static function deleteModuleCategory(int $module_category_srl): DBResultHelper
	{

	}
}
