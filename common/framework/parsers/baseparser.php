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
	 * @param bool $remove_symbols
	 * @return array
	 */
	protected static function _getAttributes(\SimpleXMLElement $element, $remove_symbols = true): array
	{
		$result = array();
		foreach ($element->attributes() as $key => $val)
		{
			if ($remove_symbols)
			{
				$key = preg_replace('/[^a-z]/', '', $key);
			}
			$result[trim($key)] = trim($val);
		}
		return $result;
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
}
