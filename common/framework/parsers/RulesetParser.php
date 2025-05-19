<?php

namespace Rhymix\Framework\Parsers;

/**
 * Ruleset XML parser class for XE compatibility.
 */
class RulesetParser extends BaseParser
{
	/**
	 * Load an XML file.
	 *
	 * @param string $filename
	 * @param string $lang
	 * @return ?object
	 */
	public static function loadXML(string $filename, string $lang = ''): ?object
	{
		// Load the XML file.
		$xml = simplexml_load_string(file_get_contents($filename));
		if ($xml === false)
		{
			return null;
		}

		// Get the current language.
		$lang = $lang ?: (\Context::getLangType() ?: 'en');

		// Initialize the result object.
		$info = new \stdClass;
		$info->rules = [];
		$info->messages = [];
		$info->filters = [];
		$info->fieldsNames = [];

		// Parse custom rules.
		if ($xml->customrules && $xml->customrules->rule)
		{
			foreach ($xml->customrules->rule as $rule)
			{
				$def = [];
				foreach ($rule->attributes() as $key => $val)
				{
					$def[trim($key)] = trim($val);
				}
				$def['message'] = self::_getChildrenByLang($rule, 'message', $lang) ?: null;
				unset($def['name']);

				$rule_name = trim($rule['name']);
				$info->rules[$rule_name] = $def;

				if ($def['message'])
				{
					$info->messages['invalid_' . $rule_name] = $def['message'];
				}
			}
		}

		// Parse field filters.
		if ($xml->fields && $xml->fields->field)
		{
			foreach ($xml->fields->field as $field)
			{
				$def = [];
				foreach ($field->attributes() as $key => $val)
				{
					$def[trim($key)] = trim($val);
				}
				$def['title'] = self::_getChildrenByLang($field, 'title', $lang) ?: null;
				unset($def['name']);

				if ($field->if)
				{
					foreach ($field->if as $if)
					{
						$condition = [];
						foreach ($if->attributes() as $key => $val)
						{
							$condition[trim($key)] = trim($val);
						}
						$def['if'][] = $condition;
					}
				}

				$filter_name = trim($field['name']);
				$info->filters[$filter_name] = $def;

				if ($def['title'])
				{
					$info->fieldsNames[$filter_name] = $def['title'];
				}
			}
		}

		return $info;
	}
}
