<?php

namespace Rhymix\Modules\Module\Controllers;

use Rhymix\Framework\Exception;
use Rhymix\Framework\Exceptions\InvalidRequest;
use Rhymix\Framework\Exceptions\NotPermitted;
use Rhymix\Framework\Template;
use Rhymix\Modules\Module\Models\Filebox as FileboxModel;
use BaseObject;
use Context;
use FileHandler;
use ModuleModel;
use Security;
use WidgetModel;

class Filebox extends Base
{
	/**
	 * Filebox management page.
	 */
	public function dispModuleAdminFileBox()
	{
		$output = ModuleModel::getModuleFileBoxList();
		Context::set('filebox_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);
		Context::set('page', intval(Context::get('page') ?: 1));

		$max_filesize = min(
			FileHandler::returnBytes(ini_get('upload_max_filesize')),
			FileHandler::returnBytes(ini_get('post_max_size'))
		);
		Context::set('max_filesize', $max_filesize);

		$oSecurity = new Security();
		$oSecurity->encodeHTML('filebox_list..comment', 'filebox_list..attributes.');

		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('adminFileBox');
	}

	/**
	 * Filebox view popup (for legacy support).
	 */
	public function dispModuleFileBox()
	{
		if (!$this->user->isAdmin())
		{
			throw new NotPermitted;
		}

		$input_name = Context::get('input');
		if(!$input_name || !preg_match('/^[a-z0-9_]+$/i', $input_name))
		{
			throw new InvalidRequest;
		}

		$addscript = sprintf('<script> var selected_filebox_input_name = %s; </script>', json_encode($input_name));
		Context::addHtmlHeader($addscript);

		$output = ModuleModel::getModuleFileBoxList();
		Context::set('filebox_list', $output->data);
		Context::set('page_navigation', $output->page_navigation);

		$filter = Context::get('filter');
		if ($filter)
		{
			Context::set('arrfilter', explode(',', $filter));
		}

		$this->setLayoutFile('popup_layout');
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('filebox_list');
	}

	/**
	 * Filebox add/edit popup (for legacy support).
	 */
	public function dispModuleFileBoxAdd()
	{
		if (!$this->user->isAdmin())
		{
			throw new NotPermitted;
		}

		$filter = Context::get('filter');
		if ($filter)
		{
			Context::set('arrfilter', explode(',', $filter));
		}

		$this->setLayoutFile('popup_layout');
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('filebox_add');
	}

	/**
	 * Get HTML for the filebox list.
	 */
	public function getFileBoxListHtml()
	{
		if (!$this->user->isAdmin())
		{
			return new BaseObject(-1, 'msg_not_permitted');
		}

		$link = parse_url($_SERVER['HTTP_REFERER'] ?? '');
		parse_str($link['query'] ?? '', $link_params);
		if (isset($link_params['selected_widget']))
		{
			$selected_widget = $link_params['selected_widget'];
			$widget_info = WidgetModel::getWidgetInfo($selected_widget);
			Context::set('allow_multiple', $widget_info->extra_var->images->allow_multiple ?? 'N');
		}
		else
		{
			Context::set('allow_multiple', 'N');
		}

		$output = ModuleModel::getModuleFileBoxList();
		Context::set('filebox_list', $output->data);

		$page = Context::get('page');
		if (!$page) $page = 1;
		Context::set('page', $page);
		Context::set('page_navigation', $output->page_navigation);

		$security = new Security();
		$security->encodeHTML('filebox_list..comment', 'filebox_list..attributes.');

		$oTemplate = new Template;
		$html = $oTemplate->compile(RX_BASEDIR . 'modules/module/tpl/', 'filebox_list_html');

		$this->add('html', $html);
	}

	/**
	 * Add a file to the filebox, or update an existing file.
	 */
	public function procModuleFileBoxAdd()
	{
		$ajax = Context::get('ajax');
		if ($ajax)
		{
			Context::setResponseMethod('JSON');
		}

		if (!$this->user->isAdmin())
		{
			throw new NotPermitted;
		}

		$vars = Context::gets('addfile', 'filter');
		$attributeNames = Context::get('attribute_name');
		$attributeValues = Context::get('attribute_value');
		if (is_array($attributeNames) && is_array($attributeValues) && count($attributeNames) == count($attributeValues))
		{
			$attributes = array();
			foreach ($attributeNames as $no => $name)
			{
				if (empty($name))
				{
					continue;
				}
				$attributes[] = sprintf('%s:%s', $name, $attributeValues[$no]);
			}
			$attributes = implode(';', $attributes);
		}

		$vars->comment = $attributes ?? null;
		$module_filebox_srl = Context::get('module_filebox_srl');

		$ext = strtolower(substr(strrchr($vars->addfile['name'],'.'),1));
		$vars->ext = $ext;
		if ($vars->filter)
		{
			$filter = array_map('trim', explode(',',$vars->filter));
			if (!in_array($ext, $filter))
			{
				throw new Exception('msg_error_occured');
			}
		}
		if (in_array($ext, ['php', 'js']))
		{
			throw new Exception(sprintf(lang('msg_filebox_invalid_extension'), $ext));
		}

		$vars->member_srl = $this->user->member_srl;

		// update
		if ($module_filebox_srl > 0)
		{
			$vars->module_filebox_srl = $module_filebox_srl;
			$output = FileboxModel::updateFile($vars);
			if (!$output->toBool())
			{
				return $output;
			}
		}
		// insert
		else
		{
			$addfile = Context::get('addfile');
			if (!$addfile || !isset($addfile['tmp_name']) || !is_uploaded_file($addfile['tmp_name']))
			{
				throw new Exception('msg_error_occured');
			}
			if ($addfile['error'] != 0)
			{
				throw new Exception('msg_error_occured');
			}
			$output = FileboxModel::insertFile($vars);
			if (!$output->toBool())
			{
				return $output;
			}
		}

		$this->setTemplatePath($this->module_path.'tpl');

		if (!$ajax)
		{
			$returnUrl = Context::get('success_return_url') ?: getNotEncodedUrl(['module' => 'admin', 'act' => 'dispModuleAdminFileBox']);
			$this->setRedirectUrl($returnUrl);
		}
		else
		{
			$this->add('save_filename', $output->get('save_filename'));
		}
	}

	/**
	 * Delete a file from the filebox.
	 */
	public function procModuleFileBoxDelete()
	{
		if (!$this->user->isAdmin())
		{
			throw new NotPermitted;
		}

		$module_filebox_srl = intval(Context::get('module_filebox_srl'));
		if (!$module_filebox_srl)
		{
			throw new InvalidRequest;
		}

		return FileboxModel::deleteFile($module_filebox_srl);
	}
}
