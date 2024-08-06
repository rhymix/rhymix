<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  tagModel
 * @author NAVER (developers@xpressengine.com)
 * @brief tag model class of the module
 */
class TagModel extends Tag
{
	/**
	 * Separator regexp cache
	 */
	protected static $_separator_list = null;
	protected static $_separator_regexp = null;

	/**
	 * Generate and cache separator list and regexp.
	 */
	protected static function _generateSeparatorConfig()
	{
		$config = ModuleModel::getModuleConfig('tag');
		if (isset($config->separators) && count($config->separators))
		{
			self::$_separator_list = $config->separators;
		}
		else
		{
			self::$_separator_list = ['comma', 'hash'];
		}

		$regexp = '/[';
		$regexp_map = [
			'comma' => ',',
			'hash' => '#',
			'space' => '\\s',
		];
		foreach (self::$_separator_list as $separator)
		{
			$regexp .= $regexp_map[$separator];
		}
		$regexp .= ']+/';

		self::$_separator_regexp = $regexp;
	}

	/**
	 * Split a string of tags into an array.
	 *
	 * @param string $str
	 * @return array
	 */
	public static function splitString(string $str): array
	{
		if (!isset(self::$_separator_list))
		{
			self::_generateSeparatorConfig();
		}

		// Clean up the input string.
		$str = trim(utf8_normalize_spaces(utf8_clean($str)));
		if ($str === '')
		{
			return [];
		}

		// Split the input string and collect non-empty fragments.
		$fragments = preg_split(self::$_separator_regexp, $str, -1, PREG_SPLIT_NO_EMPTY);
		$tags = [];
		foreach ($fragments as $fragment)
		{
			$fragment = trim($fragment);
			if ($fragment !== '')
			{
				$tags[strtolower($fragment)] = $fragment;
			}
		}

		// Return a list of valid fragments with no duplicates.
		return array_values(array_unique($tags));
	}

	/**
	 * @brief Imported Tag List
	 * Many of the specified module in order to extract the number of tags
	 */
	public static function getTagList($obj)
	{
		if(!empty($obj->mid))
		{
			$obj->module_srl = ModuleModel::getModuleSrlByMid($obj->mid);
			unset($obj->mid);
		}

		// Module_srl passed the array may be a check whether the array
		$args = new stdClass;
		$args->module_srl = $obj->module_srl;
		$args->list_count = $obj->list_count ?? null;
		$args->sort_index = $obj->sort_index ?? null;
		$args->order_type = $obj->order_type ?? null;

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
