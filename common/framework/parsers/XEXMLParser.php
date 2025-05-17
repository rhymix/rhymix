<?php

namespace Rhymix\Framework\Parsers;

/**
 * Generic XML parser that produces output identical to XE's XML parser.
 */
#[\AllowDynamicProperties]
class XEXMLParser
{
	/**
	 * Load an XML file.
	 *
	 * @param string $filename
	 * @param string $lang
	 * @return ?self
	 */
	public static function loadXMLFile(string $filename, string $lang = ''): ?self
	{
		$content = file_get_contents($filename);
		return self::loadXMLString($content, $lang);
	}

	/**
	 * Load an XML file.
	 *
	 * @param string $filename
	 * @param string $lang
	 * @return ?self
	 */
	public static function loadXMLString(string $content, string $lang = ''): ?self
	{
		// Apply transformations identical to XE's XML parser.
		$content = str_replace([chr(1), chr(2)], ['', ''], $content);
		$xml = simplexml_load_string($content);
		if ($xml === false)
		{
			return null;
		}

		// Get the current language.
		$lang = $lang ?: (\Context::getLangType() ?: 'en');

		// Create the result object.
		$result = new self;
		$root_name = $xml->getName();
		$result->$root_name = self::_recursiveConvert($xml, $lang);
		return $result;
	}

	/**
	 * Convert an XML node recursively.
	 *
	 * @param \SimpleXMLElement $element
	 * @param string $lang
	 * @return self
	 */
	protected static function _recursiveConvert(\SimpleXMLElement $element, string $lang): self
	{
		// Create the basic structure of the node.
		$node = new self;
		$node->node_name = $element->getName();
		$node->attrs = new self;
		$node->body = trim($element->__toString());

		// Add attributes.
		$attrs = $element->attributes();
		foreach ($attrs as $key => $val)
		{
			$node->attrs->{$key} = trim($val);
		}
		$attrs = $element->attributes('xml', true);
		foreach ($attrs as $key => $val)
		{
			$node->attrs->{"xml:$key"} = trim($val);
		}

		// Recursively process child elements.
		foreach ($element->children() as $child)
		{
			// Skip children that do not match the language.
			$attrs = $child->attributes('xml', true);
			if (isset($attrs['lang']) && strval($attrs['lang']) !== $lang)
			{
				continue;
			}

			$child_name = $child->getName();
			$child_node = self::_recursiveConvert($child, $lang);
			if (!isset($node->$child_name))
			{
				$node->$child_name = $child_node;
			}
			elseif (is_array($node->$child_name))
			{
				$node->$child_name[] = $child_node;
			}
			else
			{
				$node->$child_name = [$node->$child_name, $child_node];
			}
		}

		return $node;
	}

	/**
	 * Hack to prevent undefined property errors.
	 *
	 * @param string $name
	 */
	public function __get($name)
	{
		return isset($this->$name) ? $this->$name : null;
	}
}
