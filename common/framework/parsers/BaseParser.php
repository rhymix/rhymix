<?php

namespace Rhymix\Framework\Parsers;

/**
 * This class provides common methods for other parser classes to use.
 */
abstract class BaseParser
{
	/**
	 * Get all attributes of an element as an associative array.
	 *
	 * @param SimpleXMLElement $element
	 * @param bool $normalize
	 * @return array
	 */
	protected static function _getAttributes(\SimpleXMLElement $element, bool $normalize = true): array
	{
		$result = array();
		foreach ($element->attributes() as $key => $val)
		{
			if ($normalize)
			{
				$key = strtolower(preg_replace('/[^a-zA-Z]/', '', $key));
			}
			$result[trim($key)] = trim($val);
		}
		return $result;
	}

	/**
	 * Get the string value of an XML attribute after normalizing its name.
	 *
	 * @param SimpleXMLElement $element
	 * @param string $name
	 * @return string
	 */
	protected static function _getAttributeString(\SimpleXMLElement $element, string $name): string
	{
		$normalized_name = strtolower(preg_replace('/[^a-zA-Z]/', '', $name));
		foreach ($element->attributes() as $key => $val)
		{
			$normalized_key = strtolower(preg_replace('/[^a-zA-Z]/', '', $key));
			if ($normalized_key === $normalized_name)
			{
				return trim($val);
			}
		}
		return '';
	}

	/**
	 * Get the boolean value of an XML attribute after normalizing its name.
	 *
	 * A value that is identical to the name of the attribute will be treated as true.
	 * Other values will be passed to toBool() for evaluation.
	 *
	 * @param SimpleXMLElement $element
	 * @param string $name
	 * @return bool
	 */
	protected static function _getAttributeBool(\SimpleXMLElement $element, string $name): bool
	{
		$normalized_name = strtolower(preg_replace('/[^a-zA-Z]/', '', $name));
		foreach ($element->attributes() as $key => $val)
		{
			$normalized_key = strtolower(preg_replace('/[^a-zA-Z]/', '', $key));
			if ($normalized_key === $normalized_name)
			{
				$normalized_val = strtolower(preg_replace('/[^a-zA-Z]/', '', $val));
				return ($normalized_key === $normalized_val) || toBool($val);
			}
		}
		return false;
	}

	/**
	 * Get the contents of child elements that match a language.
	 *
	 * @param SimpleXMLElement $parent
	 * @param string $tag_name
	 * @param string $lang
	 * @return string
	 */
	protected static function _getChildrenByLang(\SimpleXMLElement $parent, string $tag_name, string $lang): string
	{
		// If there is a child element that matches the language, return it.
		foreach ($parent->{$tag_name} as $child)
		{
			$attribs = $child->attributes('xml', true);
			if (strval($attribs['lang']) === $lang)
			{
				return trim($child);
			}
		}

		// Otherwise, return the first child element.
		foreach ($parent->{$tag_name} as $child)
		{
			return trim($child);
		}

		// If there are no child elements, return an empty string.
		return '';
	}

	/**
	 * Parse extra_vars.
	 *
	 * @param SimpleXMLElement $extra_vars
	 * @param string $lang
	 * @return object
	 */
	protected static function _getExtraVars(\SimpleXMLElement $extra_vars, string $lang): \stdClass
	{
		$result = new \stdClass;
		$group_name = $extra_vars->getName() === 'group' ? self::_getChildrenByLang($extra_vars, 'title', $lang) : null;
		foreach ($extra_vars->group ?: [] as $group)
		{
			$group_result = self::_getExtraVars($group, $lang);
			foreach ($group_result as $key => $val)
			{
				$result->{$key} = $val;
			}
		}
		foreach ($extra_vars->var ?: [] as $var)
		{
			$item = new \stdClass;
			$item->group = $group_name;
			$item->name = trim($var['name']);
			$item->type = trim($var['type']) ?: 'text';
			$item->title = self::_getChildrenByLang($var, 'title', $lang);
			$item->description = str_replace('\\n', "\n", self::_getChildrenByLang($var, 'description', $lang));
			$item->default = trim($var['default']) ?: null;
			$item->value = null;
			if ($var->options)
			{
				$item->options = array();
				foreach ($var->options as $option)
				{
					$option_item = new \stdClass;
					$option_item->title = self::_getChildrenByLang($option, 'title', $lang);
					$option_item->value = trim($option['value']);
					$item->options[$option_item->value] = $option_item;
				}
			}

			$result->{$item->name} = $item;
		}
		return $result;
	}
}
