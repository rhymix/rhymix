<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  tag
 * @author NAVER (developers@xpressengine.com)
 * @brief high class of the tag module
 */
class tag extends ModuleObject
{
	/**
	 * @brief Implement if additional tasks are necessary when installing
	 */
	public function moduleInstall()
	{
		$oDB = DB::getInstance();
		$oDB->addIndex('tags', 'idx_tag', array('document_srl', 'tag'));
	}

	/**
	 * @brief a method to check if successfully installed
	 */
	public function checkUpdate()
	{
		// tag in the index column of the table tag
		$oDB = DB::getInstance();
		if (!$oDB->isIndexExists('tags', 'idx_tag'))
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
		// tag in the index column of the table tag
		$oDB = DB::getInstance();
		if (!$oDB->isIndexExists('tags', 'idx_tag'))
		{
			$oDB->addIndex('tags', 'idx_tag', array('document_srl', 'tag'));
		}
	}
}
/* End of file tag.class.php */
/* Location: ./modules/tag/tag.class.php */
