<?php

namespace Rhymix\Modules\Module\Controllers;

use Context;
use ModuleAdminModel;

class Lang extends \Module
{
	/**
	 * Language code management page.
	 */
	public function dispModuleAdminLangcode()
	{
		$args = new \stdClass;
		$args->langCode = Context::get('lang_type');
		$args->search_target = Context::get('search_target');
		$args->search_keyword = Context::get('search_keyword');
		$args->sort_index = 'name';
		$args->order_type = 'asc';
		$args->list_count = 30;
		$args->page_count = 5;
		$args->page = intval(Context::get('page') ?: 1);

		$oModuleAdminModel = ModuleAdminModel::getInstance();
		$output = $oModuleAdminModel->getLangListByLangcode($args);

		Context::set('lang_code_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);

		if (Context::get('module') !== 'admin')
		{
			$this->setLayoutPath('./common/tpl');
			$this->setLayoutFile('popup_layout');
		}

		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('module_langcode');
	}

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
