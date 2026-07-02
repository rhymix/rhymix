<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * High class of the file module
 * @author NAVER (developers@xpressengine.com)
 */
class File extends ModuleObject
{
	/**
	 * Implement if additional tasks are necessary when installing
	 *
	 * @return Object
	 */
	function moduleInstall()
	{
		// Generate a directory for the file module
		FileHandler::makeDir('./files/attach/images');
		FileHandler::makeDir('./files/attach/binaries');
	}

	/**
	 * A method to check if successfully installed
	 *
	 * @return bool
	 */
	function checkUpdate()
	{
		$oDB = DB::getInstance();
		$table = $oDB->getTable('files');

		// Check columns
		if (!$table->columnExists('upload_target_type'))
		{
			return true;
		}
		elseif ($table->getColumnInfo('upload_target_type')->size < 20)
		{
			return true;
		}

		if (!$table->columnExists('cover_image'))
		{
			return true;
		}

		if (!$table->columnExists('thumbnail_filename'))
		{
			return true;
		}

		if (!$table->columnExists('mime_type'))
		{
			return true;
		}
		elseif ($table->getColumnInfo('mime_type')->size < 100)
		{
			return true;
		}

		if (!$table->columnExists('original_type'))
		{
			return true;
		}
		elseif ($table->getColumnInfo('original_type')->size < 100)
		{
			return true;
		}

		if (!$table->columnExists('width'))
		{
			return true;
		}
		if (!$table->columnExists('height'))
		{
			return true;
		}
		if (!$table->columnExists('duration'))
		{
			return true;
		}

		// Check indexes
		if (!$table->indexExists('idx_mime_type'))
		{
			return true;
		}
		if (!$table->indexExists('idx_upload_target_type'))
		{
			return true;
		}
		if (!$table->indexExists('idx_cover_image'))
		{
			return true;
		}

		// Remove old columns and indexes
		if ($table->columnExists('list_order'))
		{
			return true;
		}
		elseif ($table->indexExists('idx_list_order'))
		{
			return true;
		}

		return false;
	}

	/**
	 * Execute update
	 *
	 * @return Object
	 */
	function moduleUpdate()
	{
		$oDB = DB::getInstance();
		$table = $oDB->getTable('files');

		// Check columns
		if (!$table->columnExists('upload_target_type'))
		{
			$table->addColumn('upload_target_type', 'varchar', 20, ['after' => 'upload_target_srl']);
		}
		elseif ($table->getColumnInfo('upload_target_type')->size < 20)
		{
			$table->modifyColumn('upload_target_type', 'varchar', 20);
		}

		if (!$table->columnExists('cover_image'))
		{
			$table->addColumn('cover_image', 'char', '1', ['default' => 'N', 'notnull' => true, 'after' => 'isvalid']);
		}

		if (!$table->columnExists('thumbnail_filename'))
		{
			$table->addColumn('thumbnail_filename', 'varchar', '250', ['after' => 'uploaded_filename']);
		}

		if (!$table->columnExists('mime_type'))
		{
			$table->addColumn('mime_type', 'varchar', '100', ['after' => 'file_size']);
		}
		elseif ($table->getColumnInfo('mime_type')->size < 100)
		{
			$table->modifyColumn('mime_type', 'varchar', 100);
		}

		if (!$table->columnExists('original_type'))
		{
			$table->addColumn('original_type', 'varchar', '100', ['after' => 'mime_type']);
		}
		elseif ($table->getColumnInfo('original_type')->size < 100)
		{
			$table->modifyColumn('original_type', 'varchar', 100);
		}

		if (!$table->columnExists('width'))
		{
			$table->addColumn('width', 'number', '11', ['after' => 'original_type']);
		}
		if (!$table->columnExists('height'))
		{
			$table->addColumn('height', 'number', '11', ['after' => 'width']);
		}
		if (!$table->columnExists('duration'))
		{
			$table->addColumn('duration', 'number', '11', ['after' => 'height']);
		}

		// Check indexes
		if (!$table->indexExists('idx_mime_type'))
		{
			$table->addIndex('idx_mime_type', 'mime_type');
		}
		if (!$table->indexExists('idx_upload_target_type'))
		{
			$table->addIndex('idx_upload_target_type', ['upload_target_type']);
		}
		if (!$table->indexExists('idx_cover_image'))
		{
			$table->addIndex('idx_cover_image', ['cover_image']);
		}

		// Remove old columns and indexes
		if ($table->columnExists('list_order'))
		{
			$table->dropColumn('list_order');
		}
		elseif ($table->indexExists('idx_list_order'))
		{
			$table->dropIndex('idx_list_order');
		}

		// Apply changes
		$table->applyChanges();
	}
}
