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
		$oDB = DB::getInstance();
		$oDB->addIndex('tags', 'idx_tag_document_srl', array('tag', 'document_srl'));
	}

	/**
	 * @brief a method to check if successfully installed
	 */
	public function checkUpdate()
	{
		$oDB = DB::getInstance();
		if ($oDB->isIndexExists('tags', 'idx_tag'))
		{
			return true;
		}
		if (!$oDB->isIndexExists('tags', 'idx_tag_document_srl'))
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
		$oDB = DB::getInstance();
		if ($oDB->isIndexExists('tags', 'idx_tag'))
		{
			$oDB->dropIndex('tags', 'idx_tag');
		}
		if (!$oDB->isIndexExists('tags', 'idx_tag_document_srl'))
		{
			$oDB->addIndex('tags', 'idx_tag_document_srl', array('tag', 'document_srl'));
		}
	}
}
/* End of file tag.class.php */
/* Location: ./modules/tag/tag.class.php */
