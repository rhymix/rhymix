<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  tagController
 * @author NAVER (developers@xpressengine.com)
 * @brief tag module's controller class
 */
class TagController extends Tag
{
	/**
	 * Parse tags.
	 */
	function triggerArrangeTag(&$obj)
	{
		if (empty($obj->tags))
		{
			$obj->tags = '';
			return;
		}

		$tag_list = tagModel::splitString($obj->tags ?? '');
		if (count($tag_list))
		{
			$obj->tags = implode(', ', $tag_list);
		}
		else
		{
			$obj->tags = '';
		}
	}

	/**
	 * Replace all tags belonging to a document with a new list of tags.
	 *
	 * @param object $obj
	 * @return BaseObject
	 */
	function triggerInsertTag(&$obj)
	{
		if (!$obj->document_srl)
		{
			return new BaseObject;
		}

		// Remove all existing tags.
		$output = $this->triggerDeleteTag($obj);
		if(!$output->toBool())
		{
			return $output;
		}

		// Re-enter the tag
		$args = new stdClass();
		$args->module_srl = $obj->module_srl;
		$args->document_srl = $obj->document_srl;

		$tag_list = tagModel::splitString($obj->tags ?? '');
		foreach ($tag_list as $tag)
		{
			$args->tag = $tag;
			$output = executeQuery('tag.insertTag', $args);
			if(!$output->toBool())
			{
				return $output;
			}
		}
	}

	/**
	 * Delete all tags belonging to a document.
	 *
	 * @param object $obj
	 * @return BaseObject
	 */
	function triggerDeleteTag(&$obj)
	{
		if (!$obj->document_srl)
		{
			return new BaseObject;
		}

		$args = new stdClass();
		$args->document_srl = $obj->document_srl;
		return executeQuery('tag.deleteTag', $args);
	}

	/**
	 * @brief module delete trigger to delete all the tags
	 */
	function triggerDeleteModuleTags(&$obj)
	{
		if (!$obj->module_srl)
		{
			return;
		}

		$oTagController = getAdminController('tag');
		return $oTagController->deleteModuleTags($obj->module_srl);
	}

	function triggerMoveDocument($obj)
	{
		executeQuery('tag.updateTagModule', $obj);
	}
}
/* End of file tag.controller.php */
/* Location: ./modules/tag/tag.controller.php */
