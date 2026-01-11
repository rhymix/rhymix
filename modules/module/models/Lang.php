<?php

namespace Rhymix\Modules\Module\Models;

use Rhymix\Framework\Cache;
use Rhymix\Framework\DB;
use Rhymix\Framework\Helpers\DBResultHelper;
use Context;

class Lang
{
	/**
	 * Return values for a $user_lang code.
	 *
	 * @param string $name
	 * @param bool $load_all_languages
	 * @return array
	 */
	public static function getUserLang(string $name, bool $load_all_languages = false): array
	{
		if ($name === '')
		{
			return [];
		}

		$lang_supported = $load_all_languages ? Context::loadLangSupported() : Context::loadLangSelected();
		if (!is_array($lang_supported) || count($lang_supported) == 0)
		{
			$lang_supported = [Context::getLangType() => Context::getLangType()];
		}

		$selected_lang = [];
		if (str_starts_with($name, '$user_lang->'))
		{
			$output = executeQueryArray('module.getLang', ['name' => substr($name, 12)]);
			foreach ($output->data as $val)
			{
				$selected_lang[$val->lang_code] = $val->value;
			}
		}

		$output = [];
		foreach ($lang_supported as $key => $val)
		{
			if (isset($selected_lang[$key]) && $selected_lang[$key])
			{
				$output[$key] = $selected_lang[$key];
			}
			else
			{
				$output[$key] = $name;
			}
		}

		return $output;
	}

	/**
	 * Search the lang list by arbitrary conditions.
	 *
	 * @param object $args
	 * @return DBResultHelper
	 */
	public static function search(object $args): DBResultHelper
	{
		return executeQueryArray('module.getLangListByLangcode', $args);
	}

	/**
	 * Add a new lang code.
	 *
	 * @param string $name
	 * @param array $values
	 * @return DBResultHelper
	 */
	public static function insertLang(string $name, array $values): DBResultHelper
	{
		$oDB = DB::getInstance();
		$oDB->begin();

		foreach ($values as $lang_code => $value)
		{
			$args = new \stdClass;
			$args->name = $name;
			$args->lang_code = $lang_code;
			$args->value = $value;
			$output = executeQuery('module.insertLang', $args);
			if (!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}
		}

		$oDB->commit();
		return $output;
	}

	/**
	 * Delete a lang code.
	 *
	 * @param string $name
	 * @return DBResultHelper
	 */
	public static function deleteLang(string $name): DBResultHelper
	{
		return executeQuery('module.deleteLang', ['name' => $name]);
	}


	/**
	 * Generate a cache of defined lang codes.
	 *
	 * This method returns the partial array for the current language.
	 *
	 * @return array
	 */
	public static function generateCache(): array
	{
		// Load all entries from the DB.
		$langMap = array();
		$output = executeQueryArray('module.getLang', []);
		foreach ($output->data as $lang)
		{
			$langMap[$lang->lang_code][$lang->name] = $lang->value;
		}
		if (!count($langMap))
		{
			return [];
		}

		// Ensure that the array for the default language is always defined.
		$lang_supported = Context::loadLangSelected();
		$default_lang = config('locale.default_lang');
		if (!isset($langMap[$default_lang]))
		{
			$langMap[$default_lang] = [];
		}

		// Fill missing entries from other languages.
		foreach ($lang_supported as $langCode => $langName)
		{
			if (!isset($langMap[$langCode]))
			{
				$langMap[$langCode] = [];
			}

			$langMap[$langCode] += $langMap[$default_lang];

			foreach ($lang_supported as $targetLangCode => $targetLangName)
			{
				if ($langCode == $targetLangCode || $langCode == $default_lang)
				{
					continue;
				}

				if (!isset($langMap[$targetLangCode]))
				{
					$langMap[$targetLangCode] = [];
				}

				$langMap[$langCode] += $langMap[$targetLangCode];
			}

			Cache::set('site_and_module:user_defined_langs:0:' . $langCode, $langMap[$langCode], 0, true);
		}

		$current_lang = Context::getLangType();
		return $langMap[$current_lang] ?? [];
	}
}
