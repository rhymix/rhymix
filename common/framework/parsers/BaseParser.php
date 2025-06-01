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
	 * @param \SimpleXMLElement $element
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
	 * @param \SimpleXMLElement $element
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
	 * @param \SimpleXMLElement $element
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
	 * @param \SimpleXMLElement $parent
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
	 * @param \SimpleXMLElement $extra_vars
	 * @param string $lang
	 * @param string $type
	 * @param array $options
	 * @return object
	 */
	protected static function _getExtraVars(\SimpleXMLElement $extra_vars, string $lang, string $type = '', array $options = []): \stdClass
	{
		$result = new \stdClass;

		// Recurse into groups.
		$group_name = $extra_vars->getName() === 'group' ? self::_getChildrenByLang($extra_vars, 'title', $lang) : null;
		foreach ($extra_vars->group ?: [] as $group)
		{
			$group_result = self::_getExtraVars($group, $lang, $type, $options);
			foreach ($group_result as $key => $val)
			{
				$result->{$key} = $val;
			}
		}

		// Parse each variable in the group.
		foreach ($extra_vars->var ?: [] as $var)
		{
			$item = new \stdClass;
			$item->group = $group_name;

			// id and name
			if ($type === 'widget' || $type === 'widgetstyle')
			{
				$item->id = trim($var['id'] ?? '') ?: trim($var->id);
				if (!$item->id)
				{
					$item->id = trim($var['name'] ?? '');
				}
				$item->name = self::_getChildrenByLang($var, 'name', $lang);
				if (!$item->name)
				{
					$item->name = self::_getChildrenByLang($var, 'title', $lang);
				}
			}
			else
			{
				$item->name = trim($var['name'] ?? '');
			}

			// type
			$item->type = trim($var['type'] ?? '');
			if (!$item->type)
			{
				$item->type = trim($var->type) ?: 'text';
			}
			if ($item->type === 'filebox' && isset($var->type))
			{
				$item->filter = trim($var->type['filter'] ?? '');
				$item->allow_multiple = trim($var->type['allow_multiple'] ?? '');
			}

			// Other common attributes
			$item->title = self::_getChildrenByLang($var, 'title', $lang);
			$item->description = str_replace('\\n', "\n", self::_getChildrenByLang($var, 'description', $lang));
			$item->default = trim($var['default'] ?? '') ?: null;
			if (!isset($item->default))
			{
				$item->default = self::_getChildrenByLang($var, 'default', $lang);
			}
			if ($type === 'skin')
			{
				$item->value = trim($var['value'] ?? '') ?: null;
				if ($item->value && preg_match('/(,|\|@\|)/', $item->value ?? '', $delimiter))
				{
					$item->value = explode($delimiter[1], $item->value);
				}
				if ($item->type === 'mid_list' && !is_array($item->value))
				{
					$item->value = [$item->value];
				}
			}
			else
			{
				$item->value = null;
			}

			// Options
			if ($type === 'skin' && $options['version'] === '0.1')
			{
				$xml_options = $var->default ?? null;
			}
			else
			{
				$xml_options = $var->options ?? null;
			}
			if ($xml_options)
			{
				$item->options = [];
				foreach ($xml_options as $option)
				{
					if ($type === 'widget' || $type === 'widgetstyle')
					{
						$value = trim($option->value ?? '');
						$item->options[$value] = self::_getChildrenByLang($option, 'name', $lang);
						if ($option['default'] === 'true')
						{
							$item->default_options[$value] = true;
						}
						if ($option['init'] === 'true')
						{
							$item->init_options[$value] = true;
						}
					}
					elseif ($type === 'layout')
					{
						$option_item = new \stdClass;
						if (!empty($option['src']))
						{
							$thumbnail_path = $options['layout_path'] . $option['src'];
							if (file_exists($thumbnail_path))
							{
								$option_item->thumbnail = $thumbnail_path;
								$item->thumbnail_exist = true;
							}
						}
						$title = self::_getChildrenByLang($option, 'title', $lang);
						$value = trim($option['value'] ?? '');
						$option_item->val = $title;
						$item->options[$value] = $option_item;
					}
					elseif ($type === 'skin' && $options['version'] === '0.1')
					{
						$option_item = new \stdClass;
						$option_item->title = trim($option);
						$option_item->value = trim($option);
						$item->options[] = $option_item; // Numeric keys only
					}
					elseif ($type === 'skin' && $options['version'] === '0.2')
					{
						$option_item = new \stdClass;
						$option_item->title = self::_getChildrenByLang($option, 'title', $lang);
						$option_item->value = trim($option['value'] ?? '');
						$item->options[] = $option_item; // Numeric keys only
					}
					else
					{
						$option_item = new \stdClass;
						$option_item->title = self::_getChildrenByLang($option, 'title', $lang);
						$option_item->value = trim($option['value'] ?? '') ?: trim($option->value ?? '');
						$item->options[trim($option_item->value ?? '')] = $option_item;
					}
				}
			}

			// Other attributes
			if ($type === 'skin' && $options['version'] === '0.1')
			{
				$item->width = intval($var['width'] ?? 0) ?: null;
				$item->height = intval($var['height'] ?? 0) ?: null;
				if (isset($item->options) && count($item->options))
				{
					$item->default = reset($item->options)->value;
				}
			}

			// Add to list of variables
			if ($type === 'widget' || $type === 'widgetstyle')
			{
				$result->{$item->id} = $item;
			}
			else
			{
				$result->{$item->name} = $item;
			}
		}

		return $result;
	}
}
