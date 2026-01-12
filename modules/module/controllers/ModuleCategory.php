<?php

namespace Rhymix\Modules\Module\Controllers;

use Rhymix\Framework\Exceptions\InvalidRequest;
use Rhymix\Modules\Module\Models\ModuleCategory as ModuleCategoryModel;
use Context;
use Security;

class ModuleCategory extends Base
{
	/**
	 * Module category list.
	 */
	public function dispModuleAdminCategory()
	{
		$module_category_srl = intval(Context::get('module_category_srl'));
		if ($module_category_srl)
		{
			$selected_category = ModuleCategoryModel::getModuleCategory($module_category_srl);
			Context::set('selected_category', $selected_category);

			// Security
			$security = new Security();
			$security->encodeHTML('selected_category.title');

			// Set a template file
			$this->setTemplatePath($this->module_path . 'tpl');
			$this->setTemplateFile('category_update_form');
		}
		else
		{
			$category_list = ModuleCategoryModel::getModuleCategories();
			Context::set('category_list', $category_list);

			// Security
			$security = new Security();
			$security->encodeHTML('category_list..title');

			// Set a template file
			$this->setTemplatePath($this->module_path . 'tpl');
			$this->setTemplateFile('category_list');
		}
	}

	/**
	 * Add a module category.
	 */
	public function procModuleAdminInsertCategory()
	{
		$title = escape(strval(Context::get('title')));
		if ($title === '')
		{
			throw new InvalidRequest;
		}

		$output = ModuleCategoryModel::insertModuleCategory($title);
		if (!$output->toBool())
		{
			return $output;
		}

		$this->setMessage("success_registed");

		$returnUrl = Context::get('success_return_url') ?: getNotEncodedUrl(['module' => 'admin', 'act' => 'dispModuleAdminCategory']);
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * Update (rename) a module category.
	 */
	public function procModuleAdminUpdateCategory()
	{
		$module_category_srl = intval(Context::get('module_category_srl'));
		if ($module_category_srl <= 0)
		{
			throw new InvalidRequest;
		}

		$title = escape(strval(Context::get('title')));
		if ($title === '')
		{
			throw new InvalidRequest;
		}

		$output = ModuleCategoryModel::updateModuleCategory($module_category_srl, $title);
		if (!$output->toBool())
		{
			return $output;
		}

		$this->setMessage('success_updated');

		$returnUrl = Context::get('success_return_url') ?: getNotEncodedUrl(['module' => 'admin', 'act' => 'dispModuleAdminCategory']);
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * Delete a module category.
	 */
	public function procModuleAdminDeleteCategory()
	{
		$module_category_srl = intval(Context::get('module_category_srl'));
		if ($module_category_srl <= 0)
		{
			throw new InvalidRequest;
		}

		$output = ModuleCategoryModel::deleteModuleCategory($module_category_srl);
		if (!$output->toBool())
		{
			return $output;
		}

		$this->setMessage('success_deleted');

		$returnUrl = Context::get('success_return_url') ?: getNotEncodedUrl(['module' => 'admin', 'act' => 'dispModuleAdminCategory']);
		$this->setRedirectUrl($returnUrl);
	}
}
