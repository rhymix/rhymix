<?php

namespace Rhymix\Modules\Module\Models;

class Filebox
{
	/**
	 * Attributes to match database columns.
	 */
	public int $module_filebox_srl;
	public int $member_srl;
	public string $filename;
	public string $fileextension;
	public int $filesize;
	public array $attributes = [];
	public $comment = null;
	public $regdate = null;

	/**
	 * Constructor. This is where we decode the legacy comment section.
	 */
	public function __construct()
	{
		if (!empty($this->comment))
		{
			$attributes = explode(';', $this->comment);
			foreach ($attributes as $attribute)
			{
				$values = array_map('trim', explode(':', $attribute));
				$count = count($values);
				if (($count % 2) == 1)
				{
					for ($i = 2; $i < $count; $i++)
					{
						$values[1] .= ':' . $values[$i];
					}
				}
				$this->attributes[$values[0]] = $values[1];
			}
		}
	}

	/**
	 * Get information about a file.
	 *
	 * @param int $filebox_srl
	 * @return ?self
	 */
	public static function getFile(int $filebox_srl): ?self
	{
		$output = executeQuery('module.getModuleFileBox', ['module_filebox_srl' => $filebox_srl], [], 'auto', self::class);
		if ($output->data instanceof self)
		{
			return $output->data;
		}
		else
		{
			return null;
		}
	}

	/**
	 * Get the list of files.
	 *
	 * @param int $count
	 * @param int $page
	 * @return array
	 */
	public static function getFileList(int $count = 5, int $page = 1): array
	{
		$args = new \stdClass;
		$args->list_count = $count;
		$args->page_count = 5;
		$args->page = $page;
		$output = executeQueryArray('module.getModuleFileBoxList', $args, [], self::class);
		return $output->data;
	}

	/**
	 * Insert a file.
	 */
	public static function insertFile()
	{

	}

	/**
	 * Update a file.
	 */
	public static function updateFile()
	{

	}

	/**
	 * Delete a file.
	 */
	public static function deleteFile()
	{

	}
}
