<?php

namespace Rhymix\Modules\Module\Models;

use Rhymix\Framework\Exception;
use Rhymix\Framework\Helpers\DBResultHelper;
use Rhymix\Framework\Security;
use Rhymix\Framework\Storage;
use FileController;
use FileHandler;

class Filebox
{
	/*
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
	 * @return DBResultHelper
	 */
	public static function getFileList(int $count = 5, int $page = 1): DBResultHelper
	{
		$args = new \stdClass;
		$args->list_count = $count;
		$args->page_count = 5;
		$args->page = $page;
		return executeQueryArray('module.getModuleFileBoxList', $args, [], self::class);
	}

	/**
	 * Insert a file.
	 *
	 * @param object $args
	 * @return DBResultHelper
	 */
	public static function insertFile(object $args): DBResultHelper
	{
		// set module_filebox_srl
		$args->module_filebox_srl = getNextSequence();

		// get file path
		$path = self::getStoragePath($args->module_filebox_srl);
		FileHandler::makeDir($path);

		$random = Security::getRandom(32, 'hex');
		$ext = substr(strrchr($args->addfile['name'], '.'), 1);
		$save_filename = sprintf('%s%s.%s', $path, $random, $ext);
		$tmp = $args->addfile['tmp_name'];

		// upload
		if(!move_uploaded_file($tmp, $save_filename))
		{
			throw new Exception('Cannot move uploaded file');
		}

		// insert
		$output = executeQuery('module.insertModuleFileBox', [
			'module_filebox_srl' => $args->module_filebox_srl,
			'member_srl' => $args->member_srl,
			'comment' => $args->comment,
			'filename' => $save_filename,
			'fileextension' => $ext,
			'filesize' => $args->addfile['size'],
		]);
		if ($output->toBool())
		{
			$output->add('module_filebox_srl', $args->module_filebox_srl);
			$output->add('save_filename', $save_filename);
		}
		return $output;
	}

	/**
	 * @brief Update a file into the file box
	 *
	 * @param object $args
	 * @return DBResultHelper
	 */
	public static function updateFile(object $args): DBResultHelper
	{
		// have file
		if($args->addfile['tmp_name'] && is_uploaded_file($args->addfile['tmp_name']))
		{
			$output = self::getFile($args->module_filebox_srl);
			FileHandler::removeFile($output->data->filename);

			$path = self::getStoragePath($args->module_filebox_srl);
			FileHandler::makeDir($path);

			$random = Security::getRandom(32, 'hex');
			$ext = substr(strrchr($args->addfile['name'], '.'), 1);
			$save_filename = sprintf('%s%s.%s', $path, $random, $ext);
			$tmp = $args->addfile['tmp_name'];

			if(!move_uploaded_file($tmp, $save_filename))
			{
				throw new Exception('Cannot move uploaded file');
			}

			$args->fileextension = $ext;
			$args->filename = $save_filename;
			$args->filesize = $args->addfile['size'];
		}

		$args->module_filebox_srl = $args->module_filebox_srl;
		$args->comment = $args->comment;

		$output = executeQuery('module.updateModuleFileBox', $args);
		if (isset($save_filename))
		{
			$output->add('save_filename', $save_filename);
		}
		return $output;
	}

	/**
	 * Delete a file.
	 *
	 * @param int $module_filebox_srl
	 * @return DBResultHelper
	 */
	public static function deleteFile(int $module_filebox_srl): DBResultHelper
	{
		// delete real file
		$output = self::getFile($module_filebox_srl);
		FileHandler::removeFile($output->data->filename);

		return executeQuery('module.deleteModuleFileBox', [
			'module_filebox_srl' => $module_filebox_srl,
		]);
	}

	/**
	 * Get the storage path for a file.
	 *
	 * @param int $module_filebox_srl
	 * @return string
	 */
	public static function getStoragePath(int $module_filebox_srl): string
	{
		return FileController::getStoragePath('filebox', 0, $module_filebox_srl, 0, '', false);
	}
}
