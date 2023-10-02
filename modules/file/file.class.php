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

		// Check columns
		if(!$oDB->isColumnExists('files', 'upload_target_type'))
		{
			return true;
		}
		if($oDB->getColumnInfo('files', 'upload_target_type')->size < 20)
		{
			return true;
		}
		if(!$oDB->isColumnExists('files', 'cover_image'))
		{
			return true;
		}

		if(!$oDB->isColumnExists('files', 'thumbnail_filename'))
		{
			return true;
		}
		if(!$oDB->isColumnExists('files', 'mime_type') || !$oDB->isIndexExists('files', 'idx_mime_type'))
		{
			return true;
		}
		if($oDB->getColumnInfo('files', 'mime_type')->size < 100)
		{
			return true;
		}
		if(!$oDB->isColumnExists('files', 'original_type'))
		{
			return true;
		}
		if($oDB->getColumnInfo('files', 'original_type')->size < 100)
		{
			return true;
		}
		if(!$oDB->isColumnExists('files', 'width'))
		{
			return true;
		}
		if(!$oDB->isColumnExists('files', 'height'))
		{
			return true;
		}
		if(!$oDB->isColumnExists('files', 'duration'))
		{
			return true;
		}

		// Check indexes
		if (!$oDB->isIndexExists('files', 'idx_upload_target_type'))
		{
			return true;
		}
		if (!$oDB->isIndexExists('files', 'idx_cover_image'))
		{
			return true;
		}
		if ($oDB->isIndexExists('files', 'idx_list_order'))
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

		// Check columns
		if(!$oDB->isColumnExists('files', 'upload_target_type'))
		{
			$oDB->addColumn('files', 'upload_target_type', 'varchar', '20', null, false, 'upload_target_srl');
		}
		if($oDB->getColumnInfo('files', 'upload_target_type')->size < 20)
		{
			$oDB->modifyColumn('files', 'upload_target_type', 'varchar', 20, null, false);
		}
		if(!$oDB->isColumnExists('files', 'cover_image'))
		{
			$oDB->addColumn('files', 'cover_image', 'char', '1', 'N', false, 'isvalid');
		}
		if(!$oDB->isColumnExists('files', 'thumbnail_filename'))
		{
			$oDB->addColumn('files', 'thumbnail_filename', 'varchar', '250', null, false, 'uploaded_filename');
		}
		if(!$oDB->isColumnExists('files', 'mime_type'))
		{
			$oDB->addColumn('files', 'mime_type', 'varchar', '100', '', true, 'file_size');
		}
		if($oDB->getColumnInfo('files', 'mime_type')->size < 100)
		{
			$oDB->modifyColumn('files', 'mime_type', 'varchar', 100, '', true);
		}
		if(!$oDB->isIndexExists('files', 'idx_mime_type'))
		{
			$oDB->addIndex('files', 'idx_mime_type', 'mime_type');
		}
		if(!$oDB->isColumnExists('files', 'original_type'))
		{
			$oDB->addColumn('files', 'original_type', 'varchar', '100', null, false, 'mime_type');
		}
		if($oDB->getColumnInfo('files', 'original_type')->size < 100)
		{
			$oDB->modifyColumn('files', 'original_type', 'varchar', 100, '', false);
		}
		if(!$oDB->isColumnExists('files', 'width'))
		{
			$oDB->addColumn('files', 'width', 'number', '11', null, false, 'original_type');
		}
		if(!$oDB->isColumnExists('files', 'height'))
		{
			$oDB->addColumn('files', 'height', 'number', '11', null, false, 'width');
		}
		if(!$oDB->isColumnExists('files', 'duration'))
		{
			$oDB->addColumn('files', 'duration', 'number', '11', null, false, 'height');
		}

		// Check indexes
		if (!$oDB->isIndexExists('files', 'idx_upload_target_type'))
		{
			$oDB->addIndex('files', 'idx_upload_target_type', ['upload_target_type']);
		}
		if (!$oDB->isIndexExists('files', 'idx_cover_image'))
		{
			$oDB->addIndex('files', 'idx_cover_image', ['cover_image']);
		}
		if ($oDB->isIndexExists('files', 'idx_list_order'))
		{
			$oDB->dropIndex('files', 'idx_list_order');
		}
	}
}
/* End of file file.class.php */
/* Location: ./modules/file/file.class.php */
