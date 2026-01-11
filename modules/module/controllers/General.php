<?php

namespace Rhymix\Modules\Module\Controllers;

use Rhymix\Framework\Exceptions\InvalidRequest;
use Rhymix\Framework\Exceptions\NotPermitted;
use Rhymix\Framework\Security;
use Rhymix\Framework\Cache;
use Rhymix\Modules\Module\Models\ModuleInstance as ModuleInstanceModel;
use BaseObject;
use Context;
use LayoutAdminModel;
use LayoutModel;
use ModuleHandler;
use ModuleModel;

class General extends Base
{
	/**
	 * Display a module selection list.
	 */
	public function dispModuleSelectList()
	{
		// Get a list of modules at the site
		$output = executeQueryArray(isset($query_id) ? $query_id : 'module.getSiteModules', []);

		$mid_list = array();

		foreach ($output->data as $key => $val)
		{
			if (!ModuleModel::getGrant($val, Context::get('logged_info'))->manager)
			{
				continue;
			}

			if (!isset($mid_list[$val->module]))
			{
				$mid_list[$val->module] = new \stdClass;
				$mid_list[$val->module]->list = [];
			}

			$obj = new \stdClass;
			$obj->module_srl = $val->module_srl;
			$obj->browser_title = $val->browser_title;

			$mid_list[$val->module]->list[$val->category ?: 0][$val->mid] = $obj;
			$mid_list[$val->module]->title = ModuleModel::getModuleInfoXml($val->module)->title;
		}

		Context::set('mid_list', $mid_list);

		if (!empty($mid_list))
		{
			$selected_module = Context::get('selected_module');
			if ($selected_module && isset($mid_list[$selected_module]->list))
			{
				Context::set('selected_mids', $mid_list[$selected_module]->list);
			}
			else
			{
				Context::set('selected_mids', $mid_list['board']->list);
				Context::set('selected_module', 'board');
			}
		}
		else
		{
			Context::set('selected_mids', []);
		}

		$security = new \Security();
		$security->encodeHTML('id', 'type', 'site_keyword');

		$this->setLayoutFile('popup_layout');
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('module_selector');
	}

	/**
	 * Display skin information.
	 */
	public function dispModuleSkinInfo()
	{
		$selected_module = Context::get('selected_module');
		$skin = preg_replace('/[^a-zA-Z0-9-_]/', '', Context::get('skin'));

		// Get modules/skin information
		$module_path = sprintf("./modules/%s/", $selected_module);
		if (!is_dir($module_path))
		{
			throw new InvalidRequest;
		}

		$skin_info_xml = sprintf("%sskins/%s/skin.xml", $module_path, $skin);
		if (!file_exists($skin_info_xml))
		{
			throw new InvalidRequest;
		}

		$skin_info = ModuleModel::loadSkinInfo($module_path, $skin);
		Context::set('skin_info', $skin_info);

		$this->setLayoutFile('popup_layout');
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('skin_info');
	}

	/**
	 * Get a skin list for js API.
	 * return void
	 */
	public function getModuleSkinInfoList()
	{
		$module = Context::get('module_type');
		if (!$module)
		{
			throw new InvalidRequest;
		}
		if ($module === 'ARTICLE')
		{
			$module = 'page';
		}

		$path = ModuleHandler::getModulePath($module);
		$skinType = Context::get('skin_type');
		$dir = ($skinType === 'M') ? 'm.skins' : 'skins';
		$skin_list = ModuleModel::getSkins($path, $dir);

		$this->add('skin_info_list', $skin_list);
	}

	/**
	 * Get module info by menu_item_srl.
	 *
	 * @param int $menu_item_srl
	 * @return Object $moduleInfo
	 */
	public function getModuleInfoByMenuItemSrl($menu_item_srl = 0)
	{
		$menuItemSrl = Context::get('menu_item_srl') ?: $menu_item_srl;
		if (!$menuItemSrl)
		{
			return new BaseObject(-1, 'msg_invalid_request');
		}

		$args = new \stdClass;
		$args->menu_item_srl = $menuItemSrl;
		$output = executeQuery('module.getModuleInfoByMenuItemSrl', $args, [], 'auto', ModuleInstanceModel::class);
		if (!$output->toBool())
		{
			return $output;
		}

		$moduleInfo = $output->data;
		$mid = $moduleInfo->mid;

		$moduleInfo->designSettings = new \stdClass;
		$moduleInfo->designSettings->layout = new \stdClass;
		$moduleInfo->designSettings->skin = new \stdClass;

		$oLayoutAdminModel = LayoutAdminModel::getInstance();
		$layoutSrlPc = ($moduleInfo->layout_srl == -1) ? $oLayoutAdminModel->getSiteDefaultLayout('P') : $moduleInfo->layout_srl;
		$layoutSrlMobile = ($moduleInfo->mlayout_srl == -1) ? $oLayoutAdminModel->getSiteDefaultLayout('M') : $moduleInfo->mlayout_srl;
		$skinNamePc = ($moduleInfo->is_skin_fix == 'N') ? ModuleModel::getModuleDefaultSkin($moduleInfo->module, 'P') : $moduleInfo->skin;
		$skinNameMobile = ($moduleInfo->is_mskin_fix == 'N') ? ModuleModel::getModuleDefaultSkin($moduleInfo->module, $moduleInfo->mskin === '/USE_RESPONSIVE/' ? 'P' : 'M') : $moduleInfo->mskin;

		$oLayoutModel = LayoutModel::getInstance();
		$layoutInfoPc = $layoutSrlPc ? $oLayoutModel->getLayoutRawData($layoutSrlPc, array('title')) : NULL;
		$layoutInfoMobile = $layoutSrlMobile ? $oLayoutModel->getLayoutRawData($layoutSrlMobile, array('title')) : NULL;
		$skinInfoPc = ModuleModel::loadSkinInfo(ModuleHandler::getModulePath($moduleInfo->module), $skinNamePc);
		$skinInfoMobile = ModuleModel::loadSkinInfo(ModuleHandler::getModulePath($moduleInfo->module), $skinNameMobile, 'm.skins');
		if (!$skinInfoPc)
		{
			$skinInfoPc = new \stdClass;
			$skinInfoPc->title = $skinNamePc;
		}
		if (!$skinInfoMobile)
		{
			$skinInfoMobile = new \stdClass;
			$skinInfoMobile->title = $skinNameMobile;
		}

		$moduleInfo->designSettings->layout->pcIsDefault = $moduleInfo->layout_srl == -1 ? 1 : 0;
		$moduleInfo->designSettings->layout->pc = $layoutInfoPc->title ?? null;
		$moduleInfo->designSettings->layout->mobileIsDefault = $moduleInfo->mlayout_srl == -1 ? 1 : 0;
		$moduleInfo->designSettings->layout->mobile = $layoutInfoMobile->title ?? null;
		$moduleInfo->designSettings->skin->pcIsDefault = $moduleInfo->is_skin_fix == 'N' ? 1 : 0;
		$moduleInfo->designSettings->skin->pc = $skinInfoPc->title ?? null;
		$moduleInfo->designSettings->skin->mobileIsDefault = ($moduleInfo->is_mskin_fix == 'N' && $moduleInfo->mskin !== '/USE_RESPONSIVE/') ? 1 : 0;
		$moduleInfo->designSettings->skin->mobile = $skinInfoMobile->title ?? null;

		$module_srl = Cache::get('site_and_module:module_srl:' . $mid);
		if ($module_srl)
		{
			$mid_info = Cache::get('site_and_module:module_info:' . $module_srl);
		}
		else
		{
			$mid_info = null;
		}

		if ($mid_info === null)
		{
			Cache::set('site_and_module:module_srl:' . $mid, $output->data->module_srl, 0, true);
			Cache::set('site_and_module:module_info:' . $output->data->module_srl, $moduleInfo, 0, true);
		}
		else
		{
			$mid_info->designSettings = $moduleInfo->designSettings;
			$moduleInfo = $mid_info;
		}

		ModuleModel::addModuleExtraVars($moduleInfo);
		if ($moduleInfo->module === 'page' && $moduleInfo->page_type !== 'ARTICLE')
		{
			unset($moduleInfo->skin);
			unset($moduleInfo->mskin);
		}

		$this->add('module_info_by_menu_item_srl', $moduleInfo);
		return $moduleInfo;
	}

	/**
	 * API call to clear cache entries.
	 *
	 * This can be used to clear the APC cache from CLI scripts,
	 * such as async tasks run from crontab.
	 */
	public function procModuleClearCache()
	{
		// This is a JSON API.
		Context::setResponseMethod('JSON');
		if (PHP_SAPI === 'cli')
		{
			throw new InvalidRequest;
		}

		// Get cache keys to clear.
		$keys = Context::get('keys');
		if (!$keys)
		{
			throw new InvalidRequest;
		}
		if (!is_array($keys))
		{
			$keys = [$keys];
		}

		// Verify the API signature.
		$keystring = implode('|', $keys);
		$signature = Context::get('signature');
		if (!$signature)
		{
			throw new NotPermitted;
		}
		if (!Security::verifySignature($keystring, $signature))
		{
			throw new NotPermitted;
		}

		// Clear the requested cache keys.
		foreach ($keys as $key)
		{
			if ($key === '*')
			{
				Cache::clearAll();
			}
			elseif (preg_match('/^([^:]+):\*$/', $key, $matches))
			{
				Cache::clearGroup($matches[1]);
			}
			else
			{
				Cache::delete($key);
			}
		}
	}
}
