<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  tag
 * @author NAVER (developers@xpressengine.com)
 * @brief high class of the tag module
 */
class Tag extends ModuleObject
{
	/**
	 * @brief Implement if additional tasks are necessary when installing
	 */
	public function moduleInstall()
	{

	}

	/**
	 * @brief a method to check if successfully installed
	 */
	public function checkUpdate()
	{
		// Convert unnecessary composite index into a single-column index.
		$oDB = DB::getInstance();
		$index = $oDB->getIndexInfo('tags', 'idx_tag');
		if (!$index || count($index->columns) > 1)
		{
			return true;
		}
		return false;
	}

	/**
	 * @brief Execute update
	 */
	public function moduleUpdate()
	{
		// Convert unnecessary composite index into a single-column index.
		$oDB = DB::getInstance();
		$index = $oDB->getIndexInfo('tags', 'idx_tag');
		if (!$index || count($index->columns) > 1)
		{
			if ($index)
			{
				$oDB->dropIndex('tags', 'idx_tag');
			}
			$oDB->addIndex('tags', 'idx_tag', ['tag']);
		}
	}
}
/* End of file tag.class.php */
/* Location: ./modules/tag/tag.class.php */
