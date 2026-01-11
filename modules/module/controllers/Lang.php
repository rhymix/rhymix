<?php

namespace Rhymix\Modules\Module\Controllers;

use Rhymix\Framework\DB;
use Rhymix\Framework\Exceptions\InvalidRequest;
use Rhymix\Framework\Template;
use Rhymix\Modules\Module\Models\Lang as LangModel;
use Context;
use Security;

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
		$output = LangModel::search($args);

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
	 * Get lang values associated with the given name.
	 */
	public function getModuleAdminLangCode()
	{
		$name = strval(Context::get('name'));
		if ($name === '')
		{
			return $this->setError('msg_invalid_request');
		}

		$output = LangModel::getUserLang('$user_lang->' . $name);
		$this->add('name', $name);
		$this->add('langs', $output);
	}

	/**
	 * Get lang list by lang name.
	 */
	public function getModuleAdminLangListByName()
	{
		$args = Context::getRequestVars();
		$columnList = ['lang_code', 'name', 'value'];

		$args->langName = preg_replace('/^\$user_lang->/', '', $args->lang_name);
		$output = executeQueryArray('module.getLangListByName', $args, $columnList);
		if ($output->toBool())
		{
			$langList = $output->data;
		}
		else
		{
			$langList = [];
		}

		$this->add('lang_list', $langList);
		$this->add('lang_name', $args->langName);
	}

	/**
	 * Get lang list by lang value.
	 */
	public function getModuleAdminLangListByValue()
	{
		$args = Context::getRequestVars();
		$langList = [];

		// search value
		$output = executeQueryArray('module.getLangNameByValue', $args);
		if (!$output->toBool() || !$output->data)
		{
			$this->add('lang_list', $langList);
			return;
		}

		unset($args->value);

		foreach($output->data as $data)
		{
			$args->langName = $data->name;
			$columnList = ['lang_code', 'name', 'value'];
			$outputByName = executeQueryArray('module.getLangListByName', $args, $columnList);
			foreach ($outputByName->data as $val)
			{
				$langList[] = $val;
			}
		}

		$this->add('lang_list', $langList);
	}

	/**
	 * return multilingual html
	 */
	public function getModuleAdminMultilingualHtml()
	{
		Context::set('use_in_page', false);

		$oTemplate = new Template;
		$html = $oTemplate->compile(RX_BASEDIR . 'modules/module/tpl', 'multilingual_v17.html');
		$this->add('html', $html);
	}

	/**
	 * return multilingual list html
	 */
	public function getModuleAdminLangListHtml()
	{
		$args = new \stdClass;
		$args->name = Context::get('name');
		$args->langCode = Context::get('lang_code') ?: Context::get('lang_type');
		$args->search_keyword = Context::get('search_keyword');
		$args->sort_index = 'name';
		$args->order_type = 'asc';
		$args->list_count = Context::get('list_count');
		$args->page_count = 5;
		$args->page = intval(Context::get('page') ?: 1);
		$output = LangModel::search($args);

		Context::set('lang_code_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);
		Context::set('total_count', $output->total_count);
		Context::set('total_page', $output->total_page);
		Context::set('page', $output->page);

		$oSecurity = new Security();
		$oSecurity->encodeHTML('lang_code_list..');

		$oTemplate = new Template;
		$html = $oTemplate->compile(RX_BASEDIR . 'modules/module/tpl', 'multilingual_v17_list.html');
		$this->add('html', $html);
	}

	/**
	 * Add or update a lang code.
	 */
	public function procModuleAdminInsertLang()
	{
		// Prepare arguments.
		$args = new \stdClass;
		$args->name = str_replace(' ', '_', Context::get('lang_code'));
		$args->lang_name = str_replace(' ', '_', Context::get('lang_name'));
		if (!empty($args->lang_name))
		{
			$args->name = $args->lang_name;
		}

		// Generate a name if not given.
		if (empty($args->name))
		{
			$args->name = 'userLang'.date('YmdHis').''.sprintf('%03d', mt_rand(0, 100));
		}

		$oDB = DB::getInstance();
		$oDB->begin();

		// If the same name exists, delete it first.
		$output = executeQueryArray('module.getLang', $args);
		if (!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}
		if ($output->data)
		{
			$output = LangModel::deleteLang($args->name);
			if (!$output->toBool())
			{
				$oDB->rollback();
				return $output;
			}
		}

		// Save to DB.
		$values = [];
		foreach (Context::get('lang_supported') as $key => $val)
		{
			$values[$key] = trim(Context::get($key));
		}

		$output = LangModel::insertLang($args->name, $values);
		if (!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		$oDB->commit();

		LangModel::generateCache();

		$this->setMessage('success_saved', 'info');
		$this->add('name', $args->name);

		$module = Context::get('module');
		$target = Context::get('target');
		$returnUrl = Context::get('success_return_url') ?: getNotEncodedUrl(['module' => $module, 'target' => $target, 'act' => 'dispModuleAdminLangcode']);
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * Delete a lang code.
	 */
	public function procModuleAdminDeleteLang()
	{
		// Prepare args.
		$args = new \stdClass;
		$args->name = str_replace(' ', '_', Context::get('name'));
		$args->lang_name = str_replace(' ', '_', Context::get('lang_name'));
		if (!empty($args->lang_name))
		{
			$args->name = $args->lang_name;
		}
		if(!$args->name)
		{
			throw new InvalidRequest;
		}

		$output = LangModel::deleteLang($args->name);
		if (!$output->toBool())
		{
			return $output;
		}

		LangModel::generateCache();

		$this->setMessage('success_deleted', 'info');

		$returnUrl = Context::get('success_return_url') ?: getNotEncodedUrl(['module' => 'admin', 'act' => 'dispModuleAdminLangcode']);
		$this->setRedirectUrl($returnUrl);
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
