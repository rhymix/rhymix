<?php

namespace Rhymix\Modules\Module\Controllers;

use Context;

class Lang extends \Module
{
	/**
	 * Display the language change popup (for legacy support).
	 */
	public function dispModuleChangeLang()
	{
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('lang.html');
	}

	/**
	 * Get lang code list for autocomplete (for legacy support).
	 */
	public function getLangListByLangcodeForAutoComplete()
	{
		$args = new \stdClass;
		$args->sort_index = 'name';
		$args->order_type = 'asc';
		$args->list_count = 100;
		$args->page_count = 5;
		$args->page = 1;
		$args->search_keyword = strval(Context::get('search_keyword'));
		$output = executeQueryArray('module.getLangListByLangcode', $args);

		$list = array();
		foreach($output->data as $code_info)
		{
			$list[] = [
				'name' => '$user_lang->' . $code_info->name,
				'value' => $code_info->value,
			];
		}

		$this->add('results', $list);
	}

	/**
	 * Get translation by lang code.
	 */
	public function getLangByLangcode()
	{
		$langCode = Context::get('langCode');
		if (!$langCode) return;

		$langCode = Context::replaceUserLang($langCode);
		$this->add('lang', $langCode);
	}
}
