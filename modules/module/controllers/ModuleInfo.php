<?php

namespace Rhymix\Modules\Module\Controllers;

use Rhymix\Framework\DB;
use Rhymix\Framework\Exceptions\InvalidRequest;
use Rhymix\Framework\Template;
use Rhymix\Modules\Admin\Models\Favorite as AdminFavoriteModel;
use Rhymix\Modules\Module\Models\ModuleCategory as ModuleCategoryModel;
use Rhymix\Modules\Module\Models\ModuleDefinition as ModuleDefinitionModel;
use Rhymix\Modules\Module\Models\ModuleInfo as ModuleInfoModel;
use Rhymix\Modules\Module\Models\Prefix as PrefixModel;
use AutoinstallModel;
use BaseObject;
use Context;
use DocumentAdminController;
use ModuleHandler;
use Security;

class ModuleInfo extends Base
{
	/**
	 * Admin index page for the module, kept for backward compatibility.
	 */
	public function dispModuleAdminContent()
	{
		return $this->dispModuleAdminList();
	}

	/**
	 * Display the list of installed modules.
	 */
	public function dispModuleAdminList()
	{
		$oAutoinstallModel = AutoinstallModel::getInstance();
		$module_list = ModuleDefinitionModel::getInstalledModuleDetails();
		foreach ($module_list as $val)
		{
			$val->delete_url = $oAutoinstallModel->getRemoveUrlByPath($val->path);

			// get easyinstall need update
			$packageSrl = $oAutoinstallModel->getPackageSrlByPath($val->path);
			$package = $oAutoinstallModel->getInstalledPackages($packageSrl);
			if ($packageSrl && $package && isset($package[$packageSrl]))
			{
				$val->need_autoinstall_update = $package[$packageSrl]->need_update ?? 'N';
			}
			else
			{
				$val->need_autoinstall_update = 'N';
			}

			// get easyinstall update url
			if ($val->need_autoinstall_update === 'Y')
			{
				$val->update_url = $oAutoinstallModel->getUpdateUrlByPackageSrl($packageSrl);
			}
		}

		$favorites = AdminFavoriteModel::getFavorites()->get('favoriteList');
		$favoriteModuleList = [];
		foreach ($favorites as $favorite_info)
		{
			$favoriteModuleList[] = $favorite_info->module;
		}

		Context::set('favoriteModuleList', $favoriteModuleList);
		Context::set('module_list', $module_list);

		$security = new Security();
		$security->encodeHTML('module_list....');

		// Set a template file
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('module_list');
	}

	/**
	 * Pop-up details of a module (for legacy support).
	 */
	public function dispModuleAdminInfo()
	{
		// Obtain a list of modules
		$module = strval(Context::get('selected_module'));
		$module_info = ModuleDefinitionModel::getModuleInfoXml($module);
		Context::set('module_info', $module_info);

		$security = new Security();
		$security->encodeHTML('module_info...');

		// Set the layout to be pop-up
		$this->setLayoutPath('./common/tpl');
		$this->setLayoutFile('popup_layout');

		// Set a template file
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('module_info');
	}

	/**
	 * Pop-up to copy a module.
	 */
	public function dispModuleAdminCopyModule()
	{
		// Get a target module to copy
		$module_srl = intval(Context::get('module_srl'));
		if ($module_srl <= 0)
		{
			throw new InvalidRequest;
		}

		// Get information of the module
		$module_info = ModuleInfoModel::getModuleInfo($module_srl);
		Context::set('module_info', $module_info);

		$oSecurity = new Security();
		$oSecurity->encodeHTML('module_info.');

		// Set the layout to be pop-up
		$this->setLayoutPath('./common/tpl');
		$this->setLayoutFile('popup_layout');

		// Set a template file
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('copy_module');
	}

	/**
	 * AJAX action that returns information about a comma-separated list of module instances.
	 */
	public function getModuleAdminModuleList()
	{
		$module_srls = Context::get('module_srls');
		if (!is_array($module_srls))
		{
			$module_srls = explode(',', $module_srls);
		}
		$module_srls = array_map('intval', $module_srls);
		if (!count($module_srls))
		{
			$this->add('id', Context::get('id'));
			$this->add('module_list', []);
			return;
		}

		$module_map = [];
		$module_infos = ModuleInfoModel::getModuleInfos($module_srls);
		foreach ($module_infos as $module_info)
		{
			$xml = ModuleDefinitionModel::getModuleInfoXml($module_info->module);
			$module_map[$module_info->module_srl] = [
				'module_srl' => $module_info->module_srl,
				'mid' => $module_info->mid,
				'browser_title' => Context::replaceUserLang($module_info->browser_title),
				'module_name' => $xml->title,
			];
		}

		$result = [];
		foreach ($module_srls as $module_srl)
		{
			$result[$module_srl] = $module_map[$module_srl] ?? null;
		}

		$this->add('id', Context::get('id'));
		$this->add('module_list', $result);
	}

	/**
	 * AJAX action that returns information about a single module instance.
	 */
	public function getModuleAdminModuleInfo()
	{
		$module_srl = Context::get('search_module_srl') ?: Context::get('module_srl');
		$module_info = ModuleInfoModel::getModuleInfo((int)$module_srl);
		$this->add('module_info', $module_info);
	}

	/**
	 * AJAX action that returns module searcher HTML.
	 */
	public function getModuleAdminModuleSearcherHtml()
	{
		$oTemplate = new Template;
		$html = $oTemplate->compile(\RX_BASEDIR . 'modules/module/tpl', 'module_searcher_v17.html');
		$this->add('html', $html);
	}

	/**
	 * AJAX action that returns module permission info.
	 */
	public function getModuleAdminGrant()
	{
		$module = strval(Context::get('target_module'));
		$module_srl = intval(Context::get('module_srl'));
		if (!$module || !$module_srl)
		{
			throw new InvalidRequest;
		}

		// For backward compatibility?
		if ($module == '_SHORTCUT')
		{
			return new BaseObject();
		}

		$xml_info = ModuleDefinitionModel::getModuleActionXml($module);

		// Grant virtual permission for access and manager
		$grantList = new \stdClass;
		$grantList->access = new \stdClass;
		$grantList->access->title = lang('module.grant_access');
		$grantList->access->default = 'guest';
		if ($xml_info->grant)
		{
			foreach($xml_info->grant as $key => $val)
			{
				if(!$val->default) $val->default = 'guest';
				if($val->default == 'root') $val->default = 'manager';
				$grantList->{$key} = $val;
			}
		}
		$grantList->manager = new \stdClass;
		$grantList->manager->title = lang('grant_manager');
		$grantList->manager->default = 'manager';

		// Get a permission group granted to the current module
		$selectedGroup = new \stdClass;
		$defaultGrant = new \stdClass;
		$args = new \stdClass;
		$args->module_srl = $module_srl;
		$output = executeQueryArray('module.getModuleGrants', $args);
		if ($output->data)
		{
			foreach ($output->data as $val)
			{
				switch ($val->group_srl)
				{
					case 0: $defaultGrant->{$val->name} = 'all'; break;
					case -1: $defaultGrant->{$val->name} = 'member'; break;
					case -2: $defaultGrant->{$val->name} = 'member'; break;
					case -3: $defaultGrant->{$val->name} = 'manager'; break;
					case -4: $defaultGrant->{$val->name} = 'not_member'; break;
					default:
						$selectedGroup->{$val->name}[] = $val->group_srl;
						$defaultGrant->{$val->name} = 'group';
				}
			}
		}

		foreach ($grantList as $key => $value)
		{
			if (isset($defaultGrant->{$key}))
			{
				$grantList->{$key}->grant = $defaultGrant->{$key};
			}
			if (isset($selectedGroup->{$key}))
			{
				$grantList->{$key}->group_srls = $selectedGroup->{$key};
			}
		}

		$this->add('grantList', $grantList);
	}

	/**
	 * AJAX action to search a list of modules (for legacy support).
	 */
	public function procModuleAdminGetList()
	{
		$moduleCategorySrl = array();

		// Get a list of modules at the site
		$output = executeQueryArray('module.getSiteModules', []);
		$mid_list = array();
		if (count($output->data) > 0)
		{
			foreach ($output->data as $val)
			{
				$module = trim($val->module);
				if (!$module)
				{
					continue;
				}

				$obj = new \stdClass();
				$obj->module_srl = $val->module_srl;
				$obj->layout_srl = $val->layout_srl;
				$obj->browser_title = Context::replaceUserLang($val->browser_title);
				$obj->mid = $val->mid;
				$obj->module_category_srl = $val->module_category_srl;
				if ($val->module_category_srl > 0)
				{
					$moduleCategorySrl[] = $val->module_category_srl;
				}
				$mid_list[$module]->list[$val->mid] = $obj;
			}
		}

		// Get module category titles
		$categoryNameList = array();
		$moduleCategorySrl = array_unique($moduleCategorySrl);
		$output = ModuleCategoryModel::getModuleCategories($moduleCategorySrl);
		foreach ($output as $value)
		{
			$categoryNameList[$value->module_category_srl] = $value->title;
		}

		$selected_module = Context::get('selected_module');
		if (count($mid_list) > 0)
		{
			foreach ($mid_list as $module => $val)
			{
				if (!$selected_module)
				{
					$selected_module = $module;
				}

				$xml_info = ModuleDefinitionModel::getModuleInfoXml($module);
				if (!$xml_info)
				{
					unset($mid_list[$module]);
					continue;
				}

				$mid_list[$module]->title = $xml_info->title;

				// change module category srl to title
				if (is_array($val->list))
				{
					foreach ($val->list as $key=>$value)
					{
						if ($value->module_category_srl > 0)
						{
							$categorySrl = $mid_list[$module]->list[$key]->module_category_srl;
							if (isset($categoryNameList[$categorySrl]))
							{
								$mid_list[$module]->list[$key]->module_category_srl = $categoryNameList[$categorySrl];
							}
						}
						else
						{
							$mid_list[$module]->list[$key]->module_category_srl = lang('none_category');
						}
					}
				}
			}
		}

		$security = new Security($mid_list);
		$security->encodeHTML('....browser_title');

		$this->add('module_list', $mid_list);
	}

	/**
	 * Copy a module.
	 *
	 * This method can be called over an HTTP request, or called internally.
	 *
	 * @param ?object $args
	 */
	public function procModuleAdminCopyModule(?object $args = null)
	{
		$call_type = isset($args) ? 'func' : 'http';
		if ($call_type === 'http')
		{
			$module_srl = intval(Context::get('module_srl'));
			$args = Context::getRequestVars();
		}
		else
		{
			$module_srl = intval($args->module_srl ?? 0);
		}

		// Check source module.
		if (!$module_srl)
		{
			throw new InvalidRequest;
		}
		$module_info = ModuleInfoModel::getModuleInfo($module_srl);
		if (!$module_info)
		{
			throw new InvalidRequest;
		}

		// Get prefixes and browser titles of clones.
		$clones = array();
		for ($i = 1; $i <= 10; $i++)
		{
			$mid = trim($args->{"mid_".$i} ?? '');
			if (!$mid)
			{
				continue;
			}
			if (!PrefixModel::isValidPrefix($mid, $module_info->module))
			{
				throw new InvalidRequest('msg_limit_mid');
			}
			$browser_title = escape($args->{"browser_title_".$i} ?? '');
			if (!$browser_title)
			{
				$browser_title = $mid;
			}
			$clones[$mid] = $browser_title;
		}
		if(count($clones) < 1)
		{
			throw new InvalidRequest;
		}

		// Get permission information
		$grants = new \stdClass;
		$output = executeQueryArray('module.getModuleGrants', ['module_srl' => $module_srl]);
		foreach ($output->data as $val)
		{
			$grants->{$val->name}[] = $val->group_srl;
		}

		// Get Extra Vars
		$extra_vars = new \stdClass;
		$output = executeQueryArray('module.getModuleExtraVars', ['module_srl' => $module_srl]);
		foreach ($output->data as $info)
		{
			$extra_vars->{$info->name} = $info->value;
		}

		// Get skin vars
		$tmpModuleSkinVars = ModuleInfoModel::getSkinVars($module_srl, 'P');
		$moduleSkinVars = new \stdClass;
		foreach ($tmpModuleSkinVars as $key => $value)
		{
			$moduleSkinVars->{$key} = $value->value;
		}

		$tmpModuleMobileSkinVars = ModuleInfoModel::getSkinVars($module_srl, 'M');
		$moduleMobileSkinVars = new \stdClass;
		foreach ($tmpModuleMobileSkinVars as $key => $value)
		{
			$moduleMobileSkinVars->{$key} = $value->value;
		}

		// Create trigger object.
		$triggerObj = new \stdClass;
		$triggerObj->originModuleSrl = $module_srl;
		$triggerObj->moduleSrlList = [];

		$oDB = DB::getInstance();
		$oDB->begin();

		$errorLog = array();
		foreach ($clones as $mid => $browser_title)
		{
			// Create a copy.
			$clone = new \stdClass;
			$clone->mid = $mid;
			$clone->browser_title = $browser_title;
			$clone->module = $module_info->module;
			$clone->module_category_srl = $module_info->module_category_srl;
			$clone->layout_srl = $module_info->layout_srl;
			$clone->mlayout_srl = $module_info->mlayout_srl;
			$clone->use_mobile = $module_info->use_mobile;
			$clone->skin = $module_info->skin;
			$clone->mskin = $module_info->mskin;
			$clone->description = $module_info->description;
			$clone->content = null;
			$clone->mcontent = $module_info->mcontent;
			$clone->open_rss = $module_info->open_rss;
			$clone->header_text = $module_info->header_text;
			$clone->footer_text = $module_info->footer_text;
			$clone->isMenuCreate = $args->isMenuCreate ?? true;
			$output = ModuleInfoModel::insertModule($clone);
			if (!$output->toBool())
			{
				$errorLog[] = $mid . ' : '. $output->message;
				continue;
			}

			// Grab the module_srl of the new instance.
			$new_module_srl = $output->get('module_srl');

			// If it's an article page, copy the documents as well.
			if ($module_info->module == 'page' && $extra_vars->page_type == 'ARTICLE')
			{
				$oDocumentAdminController = DocumentAdminController::getInstance();

				// copy document
				if (!empty($extra_vars->document_srl))
				{
					$copyOutput = $oDocumentAdminController->copyDocumentModule(array($extra_vars->document_srl), $new_module_srl, 0);
					$copiedSrls = $copyOutput->get('copied_srls');
					if ($copiedSrls && count($copiedSrls) > 0)
					{
						$extra_vars->document_srl = array_last($copiedSrls);
					}
				}

				if (!empty($extra_vars->mdocument_srl))
				{
					$copyOutput = $oDocumentAdminController->copyDocumentModule(array($extra_vars->mdocument_srl), $new_module_srl, 0);
					$copiedSrls = $copyOutput->get('copied_srls');
					if ($copiedSrls && count($copiedSrls) > 0)
					{
						$extra_vars->mdocument_srl = array_last($copiedSrls);
					}
				}
			}

			// Copy additional data.
			if (count(get_object_vars($grants)) > 0)
			{
				ModuleInfoModel::insertGrants($new_module_srl, $grants);
			}
			if (count(get_object_vars($extra_vars)))
			{
				ModuleInfoModel::insertExtraVars($new_module_srl, $extra_vars);
			}
			if (count(get_object_vars($moduleSkinVars)))
			{
				ModuleInfoModel::insertSkinVars($new_module_srl, $moduleSkinVars, 'P');
			}
			if (count(get_object_vars($moduleMobileSkinVars)))
			{
				ModuleInfoModel::insertSkinVars($new_module_srl, $moduleMobileSkinVars, 'M');
			}

			$triggerObj->moduleSrlList[] = $new_module_srl;
		}

		// Call event handler.
		ModuleHandler::triggerCall('module.procModuleAdminCopyModule', 'after', $triggerObj);

		$oDB->commit();

		if (count($errorLog) > 0)
		{
			$message = implode('\n', $errorLog);
			$this->setMessage($message);
		}
		else
		{
			$message = lang('success_registed');
			$this->setMessage('success_registed');
		}

		return $new_module_srl ?? null;
	}
}
