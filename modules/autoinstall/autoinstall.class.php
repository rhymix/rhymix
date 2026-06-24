<?php

class Autoinstall extends ModuleObject
{
	/**
	 * Temporary directory path
	 */
	public $tmp_dir = './files/cache/autoinstall/';

	/**
	 * Deprecated tables
	 */
	public static $deprecated_tables = [
		'ai_remote_categories',
		'ai_installed_packages',
		'autoinstall_installed_packages',
		'autoinstall_remote_categories',
	];

	/**
	 * Supported package types
	 */
	public static $package_types = [
		'module',
		'addon',
		'layout',
		'widget',
		'module-skin',
		'editor-skin',
		'editor-component',
		'theme-package',
	];

	/**
	 * Check update function
	 *
	 * @return bool
	 */
	public function checkUpdate()
	{
		$oDB = DB::getInstance();

		// Delete deprecated tables.
		foreach (self::$deprecated_tables as $table)
		{
			if (!Rhymix\Framework\Storage::exists($this->module_path . 'schemas/' . $table . '.xml') && $oDB->isTableExists($table))
			{
				return true;
			}
		}

		// Check if the autoinstall_packages table is the Rhymix version.
		if (!$oDB->isColumnExists('autoinstall_packages', 'install_type'))
		{
			return true;
		}

		return false;
	}

	/**
	 * Update function
	 *
	 * @return Object
	 */
	public function moduleUpdate()
	{
		$oDB = DB::getInstance();

		// Delete deprecated tables.
		foreach (self::$deprecated_tables as $table)
		{
			if (!Rhymix\Framework\Storage::exists($this->module_path . 'schemas/' . $table . '.xml') && $oDB->isTableExists($table))
			{
				$oDB->dropTable($table);
			}
		}

		// Check if the autoinstall_packages table is the Rhymix version.
		if (!$oDB->isColumnExists('autoinstall_packages', 'install_type'))
		{
			$oDB->dropTable('autoinstall_packages');
			$oDB->createTable($this->module_path . 'schemas/autoinstall_packages.xml');
		}
	}
}
