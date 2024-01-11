<?php

namespace Rhymix\Framework\Parsers;

use SimpleXMLElement;

/**
 * This class provides common methods for other parser classes to use.
 */
abstract class BaseParser
{
	/**
	 * List of supported types of extra_vars
	 *
	 * @var array<string>
	 */
	protected static $extra_vars_allowed_types = [
		'checkbox',
		'color',
		'colorpicker',
		'filebox',
		'image',
		'member_group',
		'menu',
		'mid_list',
		'mid',
		'module_srl_list',
		'radio',
		'select-multi-order',
		'select',
		'text',
		'textarea',
	];

	/**
	 * Get all attributes of an element as an associative array.
	 *
	 * @param SimpleXMLElement $element
	 * @param bool $normalize
	 * @return array
	 */
	protected static function _getAttributes(SimpleXMLElement $element, bool $normalize = true): array
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
	protected static function _getAttributeString(SimpleXMLElement $element, string $name): string
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
	protected static function _getAttributeBool(SimpleXMLElement $element, string $name): bool
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
	protected static function _getChildrenByLang(SimpleXMLElement $parent, string $tag_name, string $lang): string
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
	 * @param ?array $allowed_types
	 * @return object
	 */
	protected static function _getExtraVars(SimpleXMLElement $extra_vars, string $lang, array $allowed_types = null): object
	{
		if (empty($allowed_types)) {
			$allowed_types = self::$extra_vars_allowed_types;
		}

		/** @var object $result */
		$result = new \stdClass;

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
			/**
			 * @var \stdClass&object{
			 *     group: ?string,
			 *     name: ?string,
			 *     type: string,
			 *     title: string,
			 *     description: string,
			 *     default: string|array<string>|'',
			 *     required: bool,
			 *     value: ?string,
			 *     init_options: ?array<string, bool>,
			 *     default_options: ?array<string, bool>,
			 *     attrs: object,
			 * } $item
			 */
			$item = new \stdClass;

			$item->attrs = (object) self::_getAttributes($var);

			// group
			$item->group = $extra_vars->getName() === 'group' ? self::_getChildrenByLang($extra_vars, 'title', $lang) : null;

			$item->group_description = $extra_vars->getName() === 'group' ? self::_getChildrenByLang($extra_vars, 'description', $lang) : null;

			// name (attrs.name -> attrs.id)
			$item->name = $item->attrs->name ?: $item->attrs->id;

			// type (attrs.type -> type -> 'text')
			if ($item->attrs->type ?? null)
			{
				$item->type = trim($item->attrs->type ?: $var['type']);
			}
			$item->type = in_array($item->type, $allowed_types) ? $item->type : 'text';

			// title (title -> name)
			$item->title = self::_getChildrenByLang($var, ($var->title == 'title' ?: 'name'), $lang);

			// description
			$item->description = str_replace('\\n', "\n", self::_getChildrenByLang($var, 'description', $lang));

			// default
			if ($item->attrs->default ?? false)
			{
				$item->default = $item->attrs->default ? $item->attrs->default : $var['default'];
			}
			$item->default = trim($item->default) ?: '';
			if (in_array($item->type, array('checkbox')))
			{
				$item->default = explode(',', $item->default);
				$item->default = ($item->default[0] === '') ? array() : $item->default;
			}

			// required (arrts.required)
			$item->required = !!$item->attrs->required;

			// value
			$item->value = null;

			// options
			if ($var->options)
			{
				$item->options = array();
				foreach ($var->options as $option)
				{
					/**
					 * @var \stdClass&object{
					 *     title: string,
					 *     value: string,
					 * } $option_item
					 */
					$option_item = new \stdClass;
					$option_item->attrs = (object) self::_getAttributes($option);

					// options/title (title -> name)
					$option_item->title = self::_getChildrenByLang($option, ($option->title == 'title' ?: 'name'), $lang);

					// options/value
					$option_item->value = trim($option->value);

					if ($item->type === 'select-multi-order')
					{
						$item->init_options = $item->init_options ?: array();
						$item->default_options = $item->default_options ?: array();

						// default_options
						if (tobool($option_item->attrs->default ?? ''))
						{
							$item->default_options[$option_item->value] = true;
						}

						// init_options
						if (tobool($option_item->attrs->init ?? ''))
						{
							$item->init_options[$option_item->value] = true;
						}
					}

					$item->options[$option_item->value] = $option_item;
				}
			}

			$result->{$item->name} = $item;
		}

		return $result;
	}
}
