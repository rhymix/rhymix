<?php

namespace Rhymix\Modules\Module\Controllers;

use Rhymix\Framework\Cache;
use Rhymix\Framework\DB;
use Rhymix\Framework\Exceptions\InvalidRequest;
use Rhymix\Framework\Filters\FilenameFilter;
use Rhymix\Framework\Storage;
use Rhymix\Framework\Template;
use Rhymix\Modules\Module\Models\ModuleCache as ModuleCacheModel;
use Rhymix\Modules\Module\Models\ModuleCategory as ModuleCategoryModel;
use Rhymix\Modules\Module\Models\ModuleConfig as ModuleConfigModel;
use Rhymix\Modules\Module\Models\ModuleDefinition as ModuleDefinitionModel;
use Rhymix\Modules\Module\Models\ModuleInfo as ModuleInfoModel;
use BaseObject;
use Context;
use FileController;
use FileHandler;
use LayoutModel;
use MemberModel;
use ModuleHandler;
use ModuleModel;
use Security;

class ModuleConfig extends Base
{
	/**
	 * Legacy default setting pop-up page.
	 *
	 * @deprecated
	 */
	public function dispModuleAdminModuleSetup()
	{
		$module_srls = Context::get('module_srls');
		if (!is_array($module_srls))
		{
			$module_srls = array_map('intval', explode(',', $module_srls));
		}
		if (!$module_srls)
		{
			throw new InvalidRequest;
		}

		$module_info = ModuleInfoModel::getModuleInfo(array_first($module_srls));
		if (!$module_info)
		{
			throw new InvalidRequest;
		}

		$skin_path = RX_BASEDIR . 'modules/' . $module_info->module . '/skins';
		$skin_list = ModuleDefinitionModel::getSkins($skin_path);
		Context::set('skin_list',$skin_list);

		$layout_list = LayoutModel::getLayoutList();
		Context::set('layout_list', $layout_list);

		$module_category = ModuleCategoryModel::getModuleCategories();
		Context::set('module_category', $module_category);

		$security = new Security();
		$security->encodeHTML('layout_list..title','layout_list..layout');
		$security->encodeHTML('skin_list....');
		$security->encodeHTML('module_category...');

		// Set the layout to be pop-up
		$this->setLayoutPath('./common/tpl');
		$this->setLayoutFile('popup_layout');

		// Set a template file
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('module_setup');
	}

	/**
	 * Legacy additional setting pop-up page.
	 *
	 * @deprecated
	 */
	public function dispModuleAdminModuleAdditionSetup()
	{
		$module_srls = Context::get('module_srls');
		if (!is_array($module_srls))
		{
			$module_srls = array_map('intval', explode(',', $module_srls));
		}
		if (!$module_srls)
		{
			throw new InvalidRequest;
		}

		$content = '';

		// Call a trigger for additional settings
		ModuleHandler::triggerCall('module.dispAdditionSetup', 'before', $content);
		ModuleHandler::triggerCall('module.dispAdditionSetup', 'after', $content);
		Context::set('setup_content', $content);

		// Set the layout to be pop-up
		$this->setLayoutPath('./common/tpl');
		$this->setLayoutFile('popup_layout');

		// Set a template file
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('module_addition_setup');
	}

	/**
	 * Legacy permission setting pop-up page.
	 *
	 * @deprecated
	 */
	public function dispModuleAdminModuleGrantSetup()
	{
		$module_srls = Context::get('module_srls');
		if (!is_array($module_srls))
		{
			$module_srls = array_map('intval', explode(',', $module_srls));
		}
		if (!$module_srls)
		{
			throw new InvalidRequest;
		}

		$module_info = ModuleInfoModel::getModuleInfo(array_first($module_srls));
		if (!$module_info)
		{
			throw new InvalidRequest;
		}

		$xml_info = ModuleDefinitionModel::getModuleActionXml($module_info->module);
		$source_grant_list = $xml_info->grant;

		// Grant virtual permissions for access and manager
		$grant_list = new \stdClass;
		$grant_list->manager = new \stdClass;
		$grant_list->access = new \stdClass;
		$grant_list->access->title = lang('grant_access');
		$grant_list->access->default = 'guest';
		if (count($source_grant_list))
		{
			foreach ($source_grant_list as $key => $val)
			{
				if (!$val->default)
				{
					$val->default = 'guest';
				}
				if ($val->default === 'root')
				{
					$val->default = 'manager';
				}
				$grant_list->{$key} = $val;
			}
		}
		$grant_list->manager->title = lang('grant_manager');
		$grant_list->manager->default = 'manager';
		Context::set('grant_list', $grant_list);

		// Get a list of groups
		$group_list = MemberModel::getGroups();
		Context::set('group_list', $group_list);
		$security = new Security();
		$security->encodeHTML('group_list..title');

		// Set the layout to be pop-up
		$this->setLayoutPath('./common/tpl');
		$this->setLayoutFile('popup_layout');

		// Set a template file
		$this->setTemplatePath($this->module_path . 'tpl');
		$this->setTemplateFile('module_grant_setup');
	}

	/**
	 * Current management page for additional settings.
	 *
	 * This action is not called directly, but used to get HTML content
	 * that can be inserted into each module's configuration page.
	 *
	 * @param ?object $xml_grant
	 * @param ?array $tabChoice
	 * @param ?string $modulePath
	 * @return string
	 */
	public function getSelectedManageHTML(?object $xml_grant, ?array $tabChoice = null, ?string $modulePath = null): string
	{
		// Get layout list.
		$layout_list = LayoutModel::getLayoutList(0, 'P');
		$mlayout_list = LayoutModel::getLayoutList(0, 'M');
		Context::set('layout_list', $layout_list);
		Context::set('mlayout_list', $mlayout_list);

		// Get skin list.
		if ($modulePath)
		{
			$skin_list = ModuleDefinitionModel::getSkins(rtrim($modulePath, '/') . '/skins');
			$mskin_list = ModuleDefinitionModel::getSkins(rtrim($modulePath, '/') . '/m.skins');
			Context::set('skin_list', $skin_list);
			Context::set('mskin_list', $mskin_list);
		}

		$security = new Security();
		$security->encodeHTML('layout_list..layout', 'layout_list..title');
		$security->encodeHTML('mlayout_list..layout', 'mlayout_list..title');
		$security->encodeHTML('skin_list..title');
		$security->encodeHTML('mskin_list..title');

		// Set default permissions for each item defined in XML.
		$grant_list = new \stdClass;
		$xml_grant = $xml_grant ?? new \stdClass;
		$xml_grant->access = new \stdClass;
		$xml_grant->access->title = lang('grant_access');
		$xml_grant->access->default = 'guest';
		foreach($xml_grant as $key => $val)
		{
			if (!$val->default)
			{
				$val->default = 'guest';
			}
			if ($val->default == 'root')
			{
				$val->default = 'manager';
			}
			$grant_list->{$key} = $val;
		}
		$grant_list->manager = new \stdClass;
		$grant_list->manager->title = lang('grant_manager');
		$grant_list->manager->default = 'manager';
		Context::set('grant_list', $grant_list);

		// Get list of member groups, to use in grant setting.
		$group_list = MemberModel::getGroups();
		Context::set('group_list', $group_list);

		// Call other modules for additional settings.
		Context::set('module_srls', 'dummy');
		$content = '';
		ModuleHandler::triggerCall('module.dispAdditionSetup', 'before', $content);
		ModuleHandler::triggerCall('module.dispAdditionSetup', 'after', $content);
		Context::set('setup_content', $content);

		$tabChoice = $tabChoice ?: ['tab1' => 1, 'tab2' => 1, 'tab3' => 1];
		Context::set('tabChoice', $tabChoice);

		// Get information of module_grants
		$oTemplate = new Template;
		return $oTemplate->compile($this->module_path . 'tpl', 'include.manage_selected.html');
	}

	/**
	 * Current management page for permission settings.
	 *
	 * This action is not called directly, but used to get HTML content
	 * that can be inserted into each module's configuration page.
	 *
	 * @param int $module_srl
	 * @param ?object $xml_grant
	 * @return string
	 */
	public function getModuleGrantHTML(int $module_srl, ?object $xml_grant): string
	{
		if (!$module_srl)
		{
			return '';
		}

		// Assign default permissions. (Step 1)
		$grant_list = new \stdClass;
		$grant_list->access = new \stdClass;
		$grant_list->access->title = lang('grant_access');
		$grant_list->access->default = 'guest';
		if ($xml_grant)
		{
			foreach ($xml_grant as $key => $val)
			{
				if (!$val->default)
				{
					$val->default = 'guest';
				}
				if ($val->default == 'root')
				{
					$val->default = 'manager';
				}
				$grant_list->{$key} = $val;
			}
		}
		$grant_list->manager = new \stdClass;
		$grant_list->manager->title = lang('grant_manager');
		$grant_list->manager->default = 'manager';
		Context::set('grant_list', $grant_list);

		// Assign default permissions. (Step 2)
		$selected_group = array();
		$default_xml_grant = array();
		$default_grant = array();
		foreach ($grant_list as $key => $val)
		{
			if (!empty($val->default))
			{
				$default_xml_grant[$key] = $val->default;
				$default_grant[$key] = $val->default;
			}
		}

		// Load current configuration.
		$output = executeQueryArray('module.getModuleGrants', ['module_srl' => $module_srl]);
		foreach ($output->data as $val)
		{
			switch ($val->group_srl)
			{
				case 0: $default_grant[$val->name] = 'all'; break;
				case -1: $default_grant[$val->name] = 'member'; break;
				case -2: $default_grant[$val->name] = 'member'; break;
				case -3: $default_grant[$val->name] = 'manager'; break;
				case -4: $default_grant[$val->name] = 'not_member'; break;
				default:
					$selected_group[$val->name][] = $val->group_srl;
					$default_grant[$val->name] = 'group';

			}
		}
		Context::set('selected_group', $selected_group);
		Context::set('default_xml_grant', $default_xml_grant);
		Context::set('default_grant', $default_grant);
		Context::set('module_srl', $module_srl);

		// Get module managers and the scope of their authority.
		Context::set('admin_member', ModuleInfoModel::getManagers($module_srl));
		Context::set('manager_scopes', ModuleInfoModel::getManagerScopes());

		// Get member module configuration and list of groups.
		Context::set('member_config', MemberModel::getMemberConfig());
		Context::set('group_list', MemberModel::getGroups());

		// Security
		$security = new Security();
		$security->encodeHTML('group_list..title');
		$security->encodeHTML('group_list..description');
		$security->encodeHTML('admin_member..nick_name');

		// Get information of module_grants
		$oTemplate = new Template;
		return $oTemplate->compile($this->module_path . 'tpl', 'module_grants');
	}

	/**
	 * Current management page for skin settings.
	 *
	 * This action is not called directly, but used to get HTML content
	 * that can be inserted into each module's configuration page.
	 *
	 * @param int $module_srl
	 * @param string $mode
	 * @return string
	 */
	public function getModuleSkinHTML(int $module_srl, string $mode = 'P'): string
	{
		$mode = $mode === 'P' ? 'P' : 'M';
		if (!$module_srl)
		{
			return '';
		}

		$module_info = ModuleInfoModel::getModuleInfo($module_srl);
		if (!$module_info)
		{
			return '';
		}

		$module_path = './modules/' . $module_info->module;
		if ($mode === 'P')
		{
			if ($module_info->is_skin_fix == 'N')
			{
				$skin = ModuleConfigModel::getModuleDefaultSkin($module_info->module, 'P');
			}
			else
			{
				$skin = $module_info->skin;
			}
			$skin_info = ModuleDefinitionModel::getSkinInfo($module_path . '/skins/' . $skin);
			$skin_vars = ModuleInfoModel::getSkinVars($module_srl, 'P');
		}
		else
		{
			if ($module_info->is_mskin_fix == 'N')
			{
				$skin_type = $module_info->mskin === '/USE_RESPONSIVE/' ? 'P' : 'M';
				$skin = ModuleConfigModel::getModuleDefaultSkin($module_info->module, $skin_type);
			}
			else
			{
				$skin = $module_info->mskin;
			}
			$skin_info = ModuleDefinitionModel::getSkinInfo($module_path . '/m.skins/' . $skin);
			$skin_vars = ModuleInfoModel::getSkinVars($module_srl, 'M');
		}

		if ($skin_info && $skin_info->extra_vars)
		{
			foreach ($skin_info->extra_vars as $key => $val)
			{
				$name = $val->name;
				$type = $val->type;
				if (isset($skin_vars->{$name}) && $skin_vars->{$name})
				{
					$value = $skin_vars->{$name}->value;
				}
				else
				{
					$value = '';
				}

				if ($type === 'checkbox')
				{
					$value = $value ? unserialize($value) : [];
				}

				$value = empty($value) ? $val->default : $value;
				$skin_info->extra_vars[$key]->value = $value;
			}
		}

		Context::set('module_info', $module_info);
		Context::set('mid', $module_info->mid);
		Context::set('skin_info', $skin_info);
		Context::set('skin_vars', $skin_vars);
		Context::set('mode', $mode);

		//Security
		$security = new Security();
		$security->encodeHTML('mid');
		$security->encodeHTML('module_info.browser_title');
		$security->encodeHTML('skin_info...');

		$oTemplate = new Template;
		return $oTemplate->compile($this->module_path . 'tpl', 'skin_config');
	}

	/**
	 * Update common settings of multiple modules.
	 */
	public function procModuleAdminModuleSetup()
	{
		$vars = Context::getRequestVars();
		if (empty($vars->module_srls))
		{
			throw new InvalidRequest;
		}
		$module_srls = is_array($vars->module_srls) ? array_values($vars->module_srls) : explode(',', $vars->module_srls);
		if (count($module_srls) < 1)
		{
			throw new InvalidRequest;
		}

		$updateList = [
			'module_category_srl',
			'layout_srl',
			'skin',
			'mlayout_srl',
			'mskin',
			'description',
			'header_text',
			'footer_text',
			'use_mobile',
		];

		foreach ($updateList as $key => $val)
		{
			if (isset($vars->{$val . '_delete'}) && $vars->{$val . '_delete'} === 'Y')
			{
				$vars->{$val} = '';
			}
			elseif (!strlen($vars->{$val}))
			{
				unset($updateList[$key]);
			}
		}

		// Update all modules.
		foreach ($module_srls as $module_srl)
		{
			$module_info = ModuleInfoModel::getModuleInfo($module_srl);
			foreach ($updateList as $val)
			{
				$module_info->{$val} = $vars->{$val};
			}
			$output = ModuleInfoModel::updateModule($module_info);
			if (!$output->toBool())
			{
				return $output;
			}
		}

		$this->setMessage('success_registed');

		if (!in_array(Context::getRequestMethod(), ['XMLRPC','JSON']))
		{
			if (Context::get('success_return_url'))
			{
				$this->setRedirectUrl(Context::get('success_return_url'));
			}
			else
			{
				htmlHeader();
				alertScript(lang('success_registed'));
				closePopupScript();
				htmlFooter();
				Context::close();
				exit;
			}
		}
	}

	/**
	 * Update permission settings of multiple modules.
	 */
	public function procModuleAdminModuleGrantSetup()
	{
		$vars = Context::getRequestVars();
		if (empty($vars->module_srls))
		{
			throw new InvalidRequest;
		}
		$module_srls = is_array($vars->module_srls) ? array_values($vars->module_srls) : explode(',', $vars->module_srls);
		if (count($module_srls) < 1)
		{
			throw new InvalidRequest;
		}

		$module_info = ModuleInfoModel::getModuleInfo($module_srls[0]);
		$xml_info = ModuleDefinitionModel::getModuleActionXml($module_info->module);

		$grant_list = $xml_info->grant;
		$grant_list->access = new \stdClass;
		$grant_list->access->default = 'guest';
		$grant_list->manager = new \stdClass;
		$grant_list->manager->default = 'manager';

		$grant = new \stdClass;
		foreach ($grant_list as $grant_name => $grant_info)
		{
			// Get the default value
			$default = Context::get($grant_name.'_default') ?? '';
			$grant->{$grant_name} = array();
			if (strlen($default))
			{
				$grant->{$grant_name}[] = $default;
			}
			else
			{
				$group_srls = Context::get($grant_name);
				if ($group_srls)
				{
					if (!is_array($group_srls))
					{
						if (strpos($group_srls,'|@|') !== false)
						{
							$group_srls = explode('|@|', $group_srls);
						}
						elseif (strpos($group_srls,',') !== false)
						{
							$group_srls = explode(',', $group_srls);
						}
						else
						{
							$group_srls = array($group_srls);
						}
					}
					$grant->{$grant_name} = $group_srls;
				}
			}
		}

		// Update all modules.
		$oDB = DB::getInstance();
		$oDB->begin();
		foreach ($module_srls as $module_srl)
		{
			$output = executeQuery('module.deleteModuleGrants', ['module_srl' => $module_srl]);
			if (!$output->toBool())
			{
				continue;
			}

			foreach ($grant as $grant_name => $group_srls)
			{
				foreach ($group_srls as $val)
				{
					$args = new \stdClass;
					$args->module_srl = $module_srl;
					$args->name = $grant_name;
					$args->group_srl = $val;
					$output = executeQuery('module.insertModuleGrant', $args);
					if (!$output->toBool())
					{
						$oDB->rollback();
						return $output;
					}
				}
			}
		}
		$oDB->commit();

		Cache::clearGroup('site_and_module');
		ModuleCacheModel::clearAll();

		$this->setMessage('success_registed');

		if (!in_array(Context::getRequestMethod(), ['XMLRPC','JSON']))
		{
			if (Context::get('success_return_url'))
			{
				$this->setRedirectUrl(Context::get('success_return_url'));
			}
			else
			{
				htmlHeader();
				alertScript(lang('success_registed'));
				closePopupScript();
				htmlFooter();
				Context::close();
				exit;
			}
		}
	}

	/**
	 * Save module permissions.
	 */
	public function procModuleAdminInsertGrant()
	{
		$module_srl = intval(Context::get('module_srl'));
		if (!$module_srl)
		{
			throw new InvalidRequest;
		}
		$module_info = ModuleInfoModel::getModuleInfo($module_srl);
		if (!$module_info)
		{
			throw new InvalidRequest;
		}

		$oDB = DB::getInstance();
		$oDB->begin();

		// Save module managers.
		$managers = Context::get('admin_member');
		$scopes = Context::get('admin_scopes') ?: null;
		if (is_string($scopes) && $scopes !== '')
		{
			$scopes = explode('|@|', $scopes);
		}

		ModuleInfoModel::deleteManager($module_srl);
		if ($managers)
		{
			$managers = explode(',', $managers);
			foreach($managers as $user_id)
			{
				$user_id = trim($user_id);
				if ($user_id)
				{
					$output = ModuleInfoModel::insertManager($module_srl, $user_id, $scopes);
					if (!$output->toBool())
					{
						$oDB->rollback();
						return $output;
					}
				}
			}
		}

		// List permissions
		$xml_info = ModuleDefinitionModel::getModuleActionXml($module_info->module);
		$grant_list = $xml_info->grant;
		$grant_list->access = new \stdClass;
		$grant_list->access->default = 'guest';
		$grant_list->manager = new \stdClass;
		$grant_list->manager->default = 'manager';

		$grant = new \stdClass;
		foreach ($grant_list as $grant_name => $grant_info)
		{
			// Get the default value
			$default = Context::get($grant_name.'_default');
			$grant->{$grant_name} = array();
			if (strlen($default))
			{
				$grant->{$grant_name}[] = $default;
			}
			else
			{
				$group_srls = Context::get($grant_name);
				if ($group_srls)
				{
					if (strpos($group_srls,'|@|') !== false)
					{
						$group_srls = explode('|@|', $group_srls);
					}
					elseif (strpos($group_srls,',') !== false)
					{
						$group_srls = explode(',', $group_srls);
					}
					else
					{
						$group_srls = array($group_srls);
					}
					$grant->{$grant_name} = $group_srls;
				}
			}
		}

		$output = executeQuery('module.deleteModuleGrants', ['module_srl' => $module_srl]);
		if (!$output->toBool())
		{
			$oDB->rollback();
			return $output;
		}

		foreach ($grant as $grant_name => $group_srls)
		{
			foreach ($group_srls as $val)
			{
				$args = new \stdClass;
				$args->module_srl = $module_srl;
				$args->name = $grant_name;
				$args->group_srl = $val;
				$output = executeQuery('module.insertModuleGrant', $args);
				if (!$output->toBool())
				{
					$oDB->rollback();
					return $output;
				}
			}
		}

		$oDB->commit();

		Cache::clearGroup('site_and_module');
		ModuleCacheModel::clearAll();

		$this->setMessage('success_registed');
	}

	/**
	 * Save module skin configuration.
	 */
	public function procModuleAdminUpdateSkinInfo()
	{
		$module_srl = intval(Context::get('module_srl'));
		$mode = Context::get('_mode') === 'M' ? 'M' : 'P';

		$module_info = ModuleInfoModel::getModuleInfo($module_srl);
		if (!$module_info)
		{
			return;
		}

		$module_path = './modules/' . $module_info->module;
		if ($mode === 'P')
		{
			if ($module_info->is_skin_fix == 'N')
			{
				$skin = ModuleConfigModel::getModuleDefaultSkin($module_info->module, 'P');
			}
			else
			{
				$skin = $module_info->skin;
			}
			$skin_info = ModuleDefinitionModel::getSkinInfo($module_path . '/skins/' . $skin);
			$skin_vars = ModuleInfoModel::getSkinVars($module_srl, 'P');
		}
		else
		{
			if ($module_info->is_mskin_fix == 'N')
			{
				$skin_type = $module_info->mskin === '/USE_RESPONSIVE/' ? 'P' : 'M';
				$skin = ModuleConfigModel::getModuleDefaultSkin($module_info->module, $skin_type);
			}
			else
			{
				$skin = $module_info->mskin;
			}
			$skin_info = ModuleDefinitionModel::getSkinInfo($module_path . '/m.skins/' . $skin);
			$skin_vars = ModuleInfoModel::getSkinVars($module_srl, 'M');
		}

		// Remove unnecessary variables.
		$obj = clone Context::getRequestVars();
		unset($obj->module);
		unset($obj->module_srl);
		unset($obj->mid);
		unset($obj->_mode);
		foreach (ModuleInfoModel::DELETE_VARS as $key => $val)
		{
			unset($obj->{$val});
		}

		// Handle image uploads.
		if ($skin_info->extra_vars)
		{
			foreach ($skin_info->extra_vars as $vars)
			{
				if ($vars->type !== 'image')
				{
					continue;
				}

				$image_obj = $obj->{$vars->name};
				$del_var = $obj->{'del_'.$vars->name};
				unset($obj->{'del_'.$vars->name});

				// If delete is checked, remove the file.
				if ($del_var === 'Y')
				{
					Storage::delete($skin_vars->{$vars->name}->value);
					continue;
				}

				// Ignore if not properly uploaded.
				if (empty($image_obj['tmp_name']) || !is_uploaded_file($image_obj['tmp_name']))
				{
					$obj->{$vars->name} = $skin_vars->{$vars->name}->value;
					continue;
				}

				// Ignore if the file is not an image
				if (!preg_match('/\.(gif|jpe?g|png|svg|webp)$/i', $image_obj['name']))
				{
					unset($obj->{$vars->name});
					continue;
				}

				// Save the file.
				$path = FileController::getStoragePath('images', getNextSequence(), $module_srl, 0, '', false);
				if (!Storage::isDirectory($path) && !Storage::createDirectory($path))
				{
					unset($obj->{$vars->name});
					continue;
				}

				$filename = $path . FilenameFilter::clean($image_obj['name']);
				if (!Storage::moveUploadedFile($image_obj['tmp_name'], $filename))
				{
					unset($obj->{$vars->name});
					continue;
				}

				// Delete the previous file.
				if (isset($skin_vars->{$vars->name}->value) && Storage::isFile($skin_vars->{$vars->name}->value))
				{
					Storage::delete($skin_vars->{$vars->name}->value);
				}

				// Update the extra var with the new filename.
				$obj->{$vars->name} = $filename;
			}
		}

		$output = ModuleInfoModel::insertSkinVars($module_srl, $obj, $mode);
		if(!$output->toBool())
		{
			return $output;
		}

		$this->setMessage('success_saved');
		$this->setRedirectUrl(Context::get('error_return_url'));
	}

	/**
	 * Save module skin and layout information.
	 *
	 * @deprecated
	 */
	public function procModuleAdminSetDesignInfo()
	{
		$module_srl = Context::get('target_module_srl');
		$mid = Context::get('target_mid');
		$mode = Context::get('skin_type') === 'M' ? 'M' : 'P';
		$layout_srl = Context::get('layout_srl');
		$is_skin_fix = Context::get('is_skin_fix') === 'N' ? 'N' : 'Y';
		$skin_name = Context::get('skin_name');
		$skin_vars = Context::get('skin_vars');

		return $this->setDesignInfo(
			(int)$module_srl,
			(string)$mid,
			$mode,
			(int)$layout_srl,
			$is_skin_fix,
			(string)$skin_name,
			$skin_vars
		);
	}

	/**
	 * Save module skin and layout information (subroutine).
	 *
	 * @deprecated
	 */
	public function setDesignInfo(
		int $module_srl = 0,
		string $mid = '',
		string $mode = 'P',
		int $layout_srl = 0,
		string $is_skin_fix = 'Y',
		string $skin_name = '',
		$skin_vars = null
	)
	{
		// Identify the target module.
		if ($mid)
		{
			$module_info = ModuleInfoModel::getModuleInfoByPrefix($mid);
		}
		elseif ($module_srl)
		{
			$module_info = ModuleInfoModel::getModuleInfo($module_srl);
		}
		else
		{
			throw new InvalidRequest;
		}

		if (!$module_info)
		{
			throw new InvalidRequest;
		}

		// Update values.
		$layoutTargetValue = ($mode == 'M') ? 'mlayout_srl' : 'layout_srl';
		$skinFixTargetValue = ($mode == 'M') ? 'is_mskin_fix' : 'is_skin_fix';
		$skinTargetValue = ($mode == 'M') ? 'mskin' : 'skin';
		if ($layout_srl)
		{
			$module_info->{$layoutTargetValue} = $layout_srl;
		}
		if ($is_skin_fix)
		{
			$module_info->{$skinFixTargetValue} = $is_skin_fix;
		}
		if ($is_skin_fix == 'Y')
		{
			$module_info->{$skinTargetValue} = $skin_name;
			$skin_vars = $skin_vars ? json_decode($skin_vars) : [];

			// This doesn't look right, json_decode() will return an object.
			// But it's probably better that this code doesn't work,
			// because skin vars shouldn't be saved with updateModule() anyway.
			if (is_array($skin_vars))
			{
				foreach ($skin_vars as $key => $val)
				{
					if (!empty($val))
					{
						$module_info->{$key} = $val;
					}
				}
			}
		}

		return ModuleInfoModel::updateModule($module_info);
	}

	/**
	 * Update mobile usage setting of a module.
	 *
	 * @deprecated
	 */
	public function procModuleAdminUpdateUseMobile()
	{
		$menu_item_srl = Context::get('menu_item_srl');
		$use_mobile = Context::get('use_mobile') === 'N' ? 'N' : 'Y';

		if (!$menu_item_srl)
		{
			throw new InvalidRequest;
		}

		$oModuleModel = General::getInstance();
		$module_info = $oModuleModel->getModuleInfoByMenuItemSrl($menu_item_srl);

		// designSettings is not original module info, so unset
		unset($module_info->designSettings);

		$module_info->use_mobile = $use_mobile;

		return ModuleInfoModel::updateModule($module_info);
	}
}
