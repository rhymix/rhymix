<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  tagModel
 * @author NAVER (developers@xpressengine.com)
 * @brief tag model class of the module
 */
class tagModel extends tag
{
	/**
	 * @brief Initialization
	 */
	function init()
	{
	}

	/**
	 * @brief Imported Tag List
	 * Many of the specified module in order to extract the number of tags
	 */
	function getTagList($obj)
	{
		if($obj->mid)
		{
			$oModuleModel = getModel('module');
			$obj->module_srl = $oModuleModel->getModuleSrlByMid($obj->mid);
			unset($obj->mid);
		}

		// Module_srl passed the array may be a check whether the array
		$args = new stdClass;
		if(is_array($obj->module_srl))
		{
			$args->module_srl = implode(',', $obj->module_srl);
		}
		else
		{
			$args->module_srl = $obj->module_srl;
		}

		$args->list_count = $obj->list_count;
		$args->count = $obj->sort_index;

		$output = executeQueryArray('tag.getTagList', $args);
		if(!$output->toBool()) return $output;

		return $output;
	}

	/**
	 * @brief document_srl the import tag
	 */
	function getDocumentSrlByTag($obj)
	{
		$args = new stdClass;
		if(is_array($obj->module_srl))
		{
			$args->module_srl = implode(',', $obj->module_srl);
		}
		else
		{
			$args->module_srl = $obj->module_srl;
		}

		$args->tag = $obj->tag;
		$output = executeQueryArray('tag.getDocumentSrlByTag', $args);

		return $output;
	}

	/**
	 * @brief document used in the import tag
	 */
	function getDocumentsTagList($obj)
	{
		$args = new stdClass;
		if(is_array($obj->document_srl))
		{
			$args->document_srl = implode(',', $obj->document_srl);
		}
		else
		{
			$args->document_srl = $obj->document_srl;
		}

		$output = executeQueryArray('tag.getDocumentsTagList', $args);
		if(!$output->toBool()) return $output;

		return $output;
	}

	/**
	 * @brief Tag is used with a particular tag list
	 */
	function getTagWithUsedList($obj)
	{
		$args = new stdClass;
		if(is_array($obj->module_srl))
		{
			$args->module_srl = implode(',', $obj->module_srl);
		}
		else
		{
			$args->module_srl = $obj->module_srl;
		}

		$args->tag = $obj->tag;
		$output = $this->getDocumentSrlByTag($args);
		$document_srl = array();

		if($output->data)
		{
			foreach($output->data as $k => $v) $document_srl[] = $v->document_srl;
		}
		unset($args);

		$args = new stdClass;
		$args->document_srl = $document_srl;
		$output = $this->getDocumentsTagList($args);

		return $output;
	}
}
/* End of file tag.model.php */
/* Location: ./modules/tag/tag.model.php */
