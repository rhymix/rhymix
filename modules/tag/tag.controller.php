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
		if(!$obj->tags) return new Object();
		// tags by variable
		$tag_list = explode(',', $obj->tags);
		$tag_count = count($tag_list);
		$tag_list = array_unique($tag_list);
		if(!count($tag_list)) return new Object();

		foreach($tag_list as $tag)
		{
			if(!trim($tag)) continue;
			$arranged_tag_list[] = trim($tag); 
		}
		if(!count($arranged_tag_list)) $obj->tags = null;
		else $obj->tags = implode(',',$arranged_tag_list);
		return new Object();
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
		if(!$document_srl) return new Object();
		// Remove all tags that article
		$output = $this->triggerDeleteTag($obj);
		if(!$output->toBool()) return $output;
		// Re-enter the tag
		$args = new stdClass();
		$args->module_srl = $module_srl;
		$args->document_srl = $document_srl;

		$tag_list = explode(',',$tags);
		$tag_count = count($tag_list);
		for($i=0;$i<$tag_count;$i++)
		{
			unset($args->tag);
			$args->tag = trim($tag_list[$i]);
			if(!$args->tag) continue;
			$output = executeQuery('tag.insertTag', $args);
			if(!$output->toBool()) return $output;
		}

		return new Object();
	}

	/**
	 * @brief Delete the tag trigger a specific article
	 * document_srl delete tag belongs to
	 */
	function triggerDeleteTag(&$obj)
	{
		$document_srl = $obj->document_srl;
		if(!$document_srl) return new Object();

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
		if(!$module_srl) return new Object();

		$oTagController = getAdminController('tag');
		return $oTagController->deleteModuleTags($module_srl);
	}
}
/* End of file tag.controller.php */
/* Location: ./modules/tag/tag.controller.php */
