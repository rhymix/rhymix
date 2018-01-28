<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  tagController
 * @author NAVER (developers@xpressengine.com)
 * @brief tag module's controller class
 */
class tagController extends tag
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief , (Comma) to clean up the tags attached to the trigger
	 */
	function triggerArrangeTag(&$obj)
	{
		if(!$obj->tags) return;
		// tags by variable
		$arranged_tag_list = array();
		$tag_list = explode(',', $obj->tags);
		foreach($tag_list as $tag)
		{
			$tag = utf8_trim(utf8_normalize_spaces($tag));
			if($tag)
			{
				$arranged_tag_list[$tag] = $tag;
			}
		}
		if(!count($arranged_tag_list))
		{
			$obj->tags = null;
		}
		else
		{
			$obj->tags = implode(',', $arranged_tag_list);
		}
	}

	/**
	 * @brief Input trigger tag
	 * Enter a Tag to delete that article and then re-enter all the tags using a method
	 */
	function triggerInsertTag(&$obj)
	{
		$module_srl = $obj->module_srl;
		$document_srl = $obj->document_srl;
		$tags = $obj->tags;
		if(!$document_srl) return;
		// Remove all tags that article
		$output = $this->triggerDeleteTag($obj);
		if(!$output->toBool()) return $output;
		// Re-enter the tag
		$args = new stdClass();
		$args->module_srl = $module_srl;
		$args->document_srl = $document_srl;

		$tag_list = explode(',', $tags);
		foreach($tag_list as $tag)
		{
			$args->tag = utf8_trim(utf8_normalize_spaces($tag));
			if(!$args->tag) continue;
			$output = executeQuery('tag.insertTag', $args);
			if(!$output->toBool()) return $output;
		}
	}

	/**
	 * @brief Delete the tag trigger a specific article
	 * document_srl delete tag belongs to
	 */
	function triggerDeleteTag(&$obj)
	{
		$document_srl = $obj->document_srl;
		if(!$document_srl) return;

		$args = new stdClass();
		$args->document_srl = $document_srl;
		return executeQuery('tag.deleteTag', $args);
	}

	/**
	 * @brief module delete trigger to delete all the tags
	 */
	function triggerDeleteModuleTags(&$obj)
	{
		$module_srl = $obj->module_srl;
		if(!$module_srl) return;

		$oTagController = getAdminController('tag');
		return $oTagController->deleteModuleTags($module_srl);
	}
	
	function triggerMoveDocument($obj)
	{
		executeQuery('tag.updateTagModule', $obj);
	}
}
/* End of file tag.controller.php */
/* Location: ./modules/tag/tag.controller.php */
