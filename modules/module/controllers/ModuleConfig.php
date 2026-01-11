<?php

namespace Rhymix\Modules\Module\Controllers;

use Rhymix\Framework\Cache;
use Rhymix\Framework\DB;
use Rhymix\Framework\Exceptions\InvalidRequest;
use Rhymix\Framework\Filters\FilenameFilter;
use Rhymix\Framework\Template;
use Rhymix\Modules\Module\Models\ModuleCategory as ModuleCategoryModel;
use Rhymix\Modules\Module\Models\ModuleDefinition as ModuleDefinitionModel;
use Rhymix\Modules\Module\Models\ModuleInfo as ModuleInfoModel;
use BaseObject;
use Context;
use FileHandler;
use MemberModel;
use ModuleHandler;
use ModuleModel;
use Security;

class ModuleConfig extends Base
{
	/**
	 * Applying the default settings to all modules
	 */
	public function dispModuleAdminModuleSetup()
	{
		$module_srls = Context::get('module_srls');

		$modules = explode(',',$module_srls);
		if(!count($modules)) if(!$module_srls) throw new InvalidRequest;

		$columnList = array('module_srl', 'module');
		$module_info = ModuleInfoModel::getModuleInfo($modules[0], $columnList);
		// Get a skin list of the module
		$skin_list = ModuleDefinitionModel::getSkins(RX_BASEDIR . 'modules/' . $module_info->module . '/skins');
		Context::set('skin_list',$skin_list);
		// Get a layout list
		$oLayoutModel = getModel('layout');
		$layout_list = $oLayoutModel->getLayoutList();
		Context::set('layout_list', $layout_list);
		// Get a list of module categories
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
	 * Apply module addition settings to all modules
	 */
	public function dispModuleAdminModuleAdditionSetup()
	{
		$module_srls = Context::get('module_srls');

		$modules = explode(',',$module_srls);
		if(!count($modules)) if(!$module_srls) throw new InvalidRequest;
		// pre-define variables because you can get contents from other module (call by reference)
		$content = '';
		// Call a trigger for additional settings
		// Considering uses in the other modules, trigger name cen be publicly used
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
	 * Applying module permission settings to all modules
	 */
	public function dispModuleAdminModuleGrantSetup()
	{
		$module_srls = Context::get('module_srls');

		$modules = explode(',',$module_srls);
		if(!count($modules)) if(!$module_srls) throw new InvalidRequest;

		$oModuleModel = getModel('module');
		$columnList = array('module_srl', 'module');
		$module_info = ModuleInfoModel::getModuleInfo($modules[0], $columnList);
		$xml_info = $oModuleModel->getModuleActionXml($module_info->module);
		$source_grant_list = $xml_info->grant;
		// Grant virtual permissions for access and manager
		$grant_list = new \stdClass;
		$grant_list->manager = new \stdClass;
		$grant_list->access = new \stdClass;
		$grant_list->access->title = lang('grant_access');
		$grant_list->access->default = 'guest';
		if(count($source_grant_list))
		{
			foreach($source_grant_list as $key => $val)
			{
				if(!$val->default) $val->default = 'guest';
				if($val->default == 'root') $val->default = 'manager';
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

	public function getSelectedManageHTML($grantList, $tabChoice = array(), $modulePath = NULL)
	{
		if($modulePath)
		{
			// get the skins path
			$oModuleModel = getModel('module');
			$skin_list = $oModuleModel->getSkins($modulePath);
			Context::set('skin_list',$skin_list);

			$mskin_list = $oModuleModel->getSkins($modulePath, "m.skins");
			Context::set('mskin_list', $mskin_list);
		}

		// get the layouts path
		$oLayoutModel = getModel('layout');
		$layout_list = $oLayoutModel->getLayoutList();
		Context::set('layout_list', $layout_list);

		$mobile_layout_list = $oLayoutModel->getLayoutList(0,"M");
		Context::set('mlayout_list', $mobile_layout_list);

		$security = new Security();
		$security->encodeHTML('layout_list..layout', 'layout_list..title');
		$security->encodeHTML('mlayout_list..layout', 'mlayout_list..title');
		$security->encodeHTML('skin_list..title');
		$security->encodeHTML('mskin_list..title');

		$grant_list =new \stdClass();
		// Grant virtual permission for access and manager
		if(!$grantList)
		{
			$grantList =new \stdClass();
		}
		$grantList->access = new \stdClass();
		$grantList->access->title = lang('grant_access');
		$grantList->access->default = 'guest';
		if(countobj($grantList))
		{
			foreach($grantList as $key => $val)
			{
				if(!$val->default) $val->default = 'guest';
				if($val->default == 'root') $val->default = 'manager';
				$grant_list->{$key} = $val;
			}
		}
		$grant_list->manager = new \stdClass();
		$grant_list->manager->title = lang('grant_manager');
		$grant_list->manager->default = 'manager';
		Context::set('grant_list', $grant_list);

		// Get a list of groups
		$group_list = MemberModel::getGroups();
		Context::set('group_list', $group_list);

		Context::set('module_srls', 'dummy');
		$content = '';
		// Call a trigger for additional settings
		// Considering uses in the other modules, trigger name cen be publicly used
		ModuleHandler::triggerCall('module.dispAdditionSetup', 'before', $content);
		ModuleHandler::triggerCall('module.dispAdditionSetup', 'after', $content);
		Context::set('setup_content', $content);

		if(count($tabChoice) == 0)
		{
			$tabChoice = array('tab1'=>1, 'tab2'=>1, 'tab3'=>1);
		}
		Context::set('tabChoice', $tabChoice);

		// Get information of module_grants
		$oTemplate = new Template;
		return $oTemplate->compile($this->module_path.'tpl', 'include.manage_selected.html');
	}

	/**
	 * Common:: module's permission displaying page in the module
	 * Available when using module instance in all the modules
	 */
	public function getModuleGrantHTML($module_srl, $source_grant_list)
	{
		if(!$module_srl)
		{
			return;
		}

		// get member module's config
		$member_config = MemberModel::getMemberConfig();
		Context::set('member_config', $member_config);

		$oModuleModel = getModel('module');
		$columnList = array('module_srl');
		$module_info = ModuleInfoModel::getModuleInfo($module_srl, $columnList);
		// Grant virtual permission for access and manager
		$grant_list = new \stdClass();
		$grant_list->access = new \stdClass();
		$grant_list->access->title = lang('grant_access');
		$grant_list->access->default = 'guest';
		if($source_grant_list)
		{
			foreach($source_grant_list as $key => $val)
			{
				if(!$val->default) $val->default = 'guest';
				if($val->default == 'root') $val->default = 'manager';
				$grant_list->{$key} = $val;
			}
		}
		$grant_list->manager = new \stdClass();
		$grant_list->manager->title = lang('grant_manager');
		$grant_list->manager->default = 'manager';
		Context::set('grant_list', $grant_list);

		// Get a permission group granted to the current module
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

		$args = new \stdClass();
		$args->module_srl = $module_srl;
		$output = executeQueryArray('module.getModuleGrants', $args);
		if($output->data)
		{
			foreach($output->data as $val)
			{
				if($val->group_srl == 0) $default_grant[$val->name] = 'all';
				else if($val->group_srl == -1) $default_grant[$val->name] = 'member';
				else if($val->group_srl == -2) $default_grant[$val->name] = 'member';
				else if($val->group_srl == -4) $default_grant[$val->name] = 'not_member';
				else if($val->group_srl == -3) $default_grant[$val->name] = 'manager';
				else
				{
					$selected_group[$val->name][] = $val->group_srl;
					$default_grant[$val->name] = 'group';
				}
			}
		}
		Context::set('selected_group', $selected_group);
		Context::set('default_xml_grant', $default_xml_grant);
		Context::set('default_grant', $default_grant);
		Context::set('module_srl', $module_srl);
		// Extract admin ID set in the current module
		$admin_member = ModuleInfoModel::getManagers($module_srl);
		Context::set('admin_member', $admin_member);
		// Get defined scopes
		Context::set('manager_scopes', ModuleInfoModel::getManagerScopes());
		// Get a list of groups
		$group_list = MemberModel::getGroups();
		Context::set('group_list', $group_list);

		//Security
		$security = new Security();
		$security->encodeHTML('group_list..title');
		$security->encodeHTML('group_list..description');
		$security->encodeHTML('admin_member..nick_name');

		// Get information of module_grants
		$oTemplate = new Template;
		return $oTemplate->compile($this->module_path.'tpl', 'module_grants');
	}

	/**
	 * Skin setting page for the module
	 *
	 * @param $module_srl sequence of module
	 * @param $mode P or M
	 * @return string The HTML code
	 */
	public function getModuleSkinHTML($module_srl, $mode)
	{
		$mode = $mode === 'P' ? 'P' : 'M';

		$oModuleModel = getModel('module');
		$module_info = ModuleModel::getModuleInfoByModuleSrl($module_srl);
		if(!$module_info) return;

		if($mode === 'P')
		{
			if($module_info->is_skin_fix == 'N')
			{
				$skin = ModuleModel::getModuleDefaultSkin($module_info->module, 'P');
			}
			else
			{
				$skin = $module_info->skin;
			}
		}
		else
		{
			if($module_info->is_mskin_fix == 'N')
			{
				$skin_type = $module_info->mskin === '/USE_RESPONSIVE/' ? 'P' : 'M';
				$skin = ModuleModel::getModuleDefaultSkin($module_info->module, $skin_type);
			}
			else
			{
				$skin = $module_info->mskin;
			}
		}

		$module_path = './modules/'.$module_info->module;

		// Get XML information of the skin and skin sinformation set in DB
		if($mode === 'P')
		{
			$skin_info = $oModuleModel->loadSkinInfo($module_path, $skin);
			$skin_vars = $oModuleModel->getModuleSkinVars($module_srl);
		}
		else
		{
			$skin_info = $oModuleModel->loadSkinInfo($module_path, $skin, 'm.skins');
			$skin_vars = $oModuleModel->getModuleMobileSkinVars($module_srl);
		}

		if($skin_info && $skin_info->extra_vars)
		{
			foreach($skin_info->extra_vars as $key => $val)
			{
				$group = $val->group;
				$name = $val->name;
				$type = $val->type;
				if (isset($skin_vars[$name]) && $skin_vars[$name])
				{
					$value = $skin_vars[$name]->value;
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
				$skin_info->extra_vars[$key]->value= $value;
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
		return $oTemplate->compile($this->module_path.'tpl', 'skin_config');
	}

	/**
	 * List module information
	 */
	public function procModuleAdminModuleSetup()
	{
		$vars = Context::getRequestVars();

		if(!$vars->module_srls) throw new InvalidRequest;

		$module_srls = explode(',',$vars->module_srls);
		if(count($module_srls) < 1) throw new InvalidRequest;

		$oModuleModel = getModel('module');
		$oModuleController= getController('module');
		$columnList = array('module_srl', 'module', 'menu_srl', 'site_srl', 'mid', 'browser_title', 'is_default', 'content', 'mcontent', 'open_rss', 'regdate');
		$updateList = array('module_category_srl','layout_srl','skin','mlayout_srl','mskin','description','header_text','footer_text', 'use_mobile');
		foreach($updateList as $key => $val)
		{
			if(isset($vars->{$val . '_delete'}) && $vars->{$val . '_delete'} === 'Y')
			{
				$vars->{$val} = '';
			}
			elseif(!strlen($vars->{$val}))
			{
				unset($updateList[$key]);
				$columnList[] = $val;
			}
		}

		foreach($module_srls as $module_srl)
		{
			$module_info = ModuleInfoModel::getModuleInfo($module_srl, $columnList);

			foreach($updateList as $val)
			{
				$module_info->{$val} = $vars->{$val};
			}
			$output = $oModuleController->updateModule($module_info);
		}

		$this->setMessage('success_registed');
		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			if(Context::get('success_return_url'))
			{
				$this->setRedirectUrl(Context::get('success_return_url'));
			}
			else
			{
				global $lang;
				htmlHeader();
				alertScript($lang->success_registed);
				closePopupScript();
				htmlFooter();
				Context::close();
				exit;
			}
		}
	}

	/**
	 * List permissions of the module
	 */
	public function procModuleAdminModuleGrantSetup()
	{
		$module_srls = Context::get('module_srls');
		if(!$module_srls) throw new InvalidRequest;

		$modules = explode(',',$module_srls);
		if(count($modules) < 1) throw new InvalidRequest;

		$oModuleController = getController('module');
		$oModuleModel = getModel('module');

		$columnList = array('module_srl', 'module');
		$module_info = ModuleInfoModel::getModuleInfo($modules[0], $columnList);
		$xml_info = $oModuleModel->getModuleActionXml($module_info->module);
		$grant_list = $xml_info->grant;

		$grant_list->access = new \stdClass;
		$grant_list->access->default = 'guest';
		$grant_list->manager = new \stdClass;
		$grant_list->manager->default = 'manager';

		$grant = new \stdClass;

		foreach($grant_list as $grant_name => $grant_info)
		{
			// Get the default value
			$default = Context::get($grant_name.'_default');
			// -1 = Sign only, 0 = all users
			$grant->{$grant_name} = array();
			if(strlen($default))
			{
				$grant->{$grant_name}[] = $default;
			}
			else
			{
				$group_srls = Context::get($grant_name);
				if($group_srls)
				{
					if(!is_array($group_srls))
					{
						if(strpos($group_srls,'|@|')!==false) $group_srls = explode('|@|',$group_srls);
						elseif(strpos($group_srls,',')!==false) $group_srls = explode(',',$group_srls);
						else $group_srls = array($group_srls);
					}
					$grant->{$grant_name} = $group_srls;
				}
			}
		}

		// Stored in the DB
		foreach($modules as $module_srl)
		{
			$args = new \stdClass;
			$args->module_srl = $module_srl;
			$output = executeQuery('module.deleteModuleGrants', $args);
			if(!$output->toBool()) continue;
			// Permissions stored in the DB
			foreach($grant as $grant_name => $group_srls)
			{
				foreach($group_srls as $val)
				{
					$args = new \stdClass;
					$args->module_srl = $module_srl;
					$args->name = $grant_name;
					$args->group_srl = $val;
					$output = executeQuery('module.insertModuleGrant', $args);
					if(!$output->toBool()) return $output;
				}
			}
		}

		Cache::delete("site_and_module:module_grants:$module_srl");
		$this->setMessage('success_registed');

		if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
		{
			if(Context::get('success_return_url'))
			{
				$this->setRedirectUrl(Context::get('success_return_url'));
			}
			else
			{
				global $lang;
				htmlHeader();
				alertScript($lang->success_registed);
				closePopupScript();
				htmlFooter();
				Context::close();
				exit;
			}
		}
	}

	/**
	 * Save the module permissions
	 */
	public function procModuleAdminInsertGrant()
	{
		$oModuleController = getController('module');
		$oModuleModel = getModel('module');
		// Get module_srl
		$module_srl = Context::get('module_srl');
		// Get information of the module
		$columnList = array('module_srl', 'module');
		$module_info = ModuleInfoModel::getModuleInfo($module_srl, $columnList);
		if(!$module_info) throw new InvalidRequest;

		$oDB = DB::getInstance();
		$oDB->begin();

		// Register Admin ID
		$oModuleController->deleteAdminId($module_srl);
		$admin_member = Context::get('admin_member');
		$scopes = Context::get('admin_scopes') ?: null;
		if(is_string($scopes) && $scopes !== '')
		{
			$scopes = explode('|@|', $scopes);
		}
		if($admin_member)
		{
			$admin_members = explode(',',$admin_member);
			foreach($admin_members as $admin_id)
			{
				$admin_id = trim($admin_id);
				if(!$admin_id) continue;
				$oModuleController->insertAdminId($module_srl, $admin_id, $scopes);
			}
		}

		// List permissions
		$xml_info = $oModuleModel->getModuleActionXML($module_info->module);
		$grant_list = $xml_info->grant;
		$grant_list->access = new \stdClass;
		$grant_list->access->default = 'guest';
		$grant_list->manager = new \stdClass;
		$grant_list->manager->default = 'manager';

		$grant = new \stdClass;
		foreach($grant_list as $grant_name => $grant_info)
		{
			// Get the default value
			$default = Context::get($grant_name.'_default');
			// -1 = Log-in user only, -2 = site members only, -3 = manager only, 0 = all users
			$grant->{$grant_name} = array();
			if(strlen($default))
			{
				$grant->{$grant_name}[] = $default;
			}
			else
			{
				$group_srls = Context::get($grant_name);
				if($group_srls)
				{
					if(strpos($group_srls,'|@|')!==false) $group_srls = explode('|@|',$group_srls);
					elseif(strpos($group_srls,',')!==false) $group_srls = explode(',',$group_srls);
					else $group_srls = array($group_srls);
					$grant->{$grant_name} = $group_srls;
				}
			}
		}

		// Stored in the DB
		$args = new \stdClass;
		$args->module_srl = $module_srl;
		$output = executeQuery('module.deleteModuleGrants', $args);
		if(!$output->toBool()) return $output;
		// Permissions stored in the DB
		foreach($grant as $grant_name => $group_srls)
		{
			foreach($group_srls as $val)
			{
				$args = new \stdClass;
				$args->module_srl = $module_srl;
				$args->name = $grant_name;
				$args->group_srl = $val;
				$output = executeQuery('module.insertModuleGrant', $args);
				if(!$output->toBool()) return $output;
			}
		}

		$oDB->commit();

		Cache::delete("site_and_module:module_grants:$module_srl");
		$this->setMessage('success_registed');
	}

	/**
	 * Updating Skins
	 */
	public function procModuleAdminUpdateSkinInfo()
	{
		// Get information of the module_srl
		$module_srl = Context::get('module_srl');
		$mode = Context::get('_mode');
		$mode = $mode === 'P' ? 'P' : 'M';

		$oModuleModel = getModel('module');
		$columnList = array('module_srl', 'module', 'skin', 'mskin', 'is_skin_fix', 'is_mskin_fix');
		$module_info = ModuleInfoModel::getModuleInfo($module_srl, $columnList);
		if($module_info->module_srl)
		{
			if($mode === 'M')
			{
				if($module_info->is_mskin_fix == 'Y')
				{
					$skin = $module_info->mskin;
				}
				else
				{
					$skin_type = $module_info->mskin === '/USE_RESPONSIVE/' ? 'P' : 'M';
					$skin = $oModuleModel->getModuleDefaultSkin($module_info->module, $skin_type);
				}
			}
			else
			{
				if($module_info->is_skin_fix == 'Y')
				{
					$skin = $module_info->skin;
				}
				else
				{
					$skin = $oModuleModel->getModuleDefaultSkin($module_info->module, 'P');
				}
			}

			// Get skin information (to check extra_vars)
			$module_path = RX_BASEDIR . 'modules/'.$module_info->module;

			if($mode === 'M')
			{
				$skin_info = $oModuleModel->loadSkinInfo($module_path, $skin, 'm.skins');
				$skin_vars = $oModuleModel->getModuleMobileSkinVars($module_srl);
			}
			else
			{
				$skin_info = $oModuleModel->loadSkinInfo($module_path, $skin);
				$skin_vars = $oModuleModel->getModuleSkinVars($module_srl);
			}

			// Check received variables (unset such variables as act, module_srl, page, mid, module)
			$obj = Context::getRequestVars();
			unset($obj->act);
			unset($obj->error_return_url);
			unset($obj->module_srl);
			unset($obj->page);
			unset($obj->mid);
			unset($obj->module);
			unset($obj->_mode);
			// Separately handle if a type of extra_vars is an image in the original skin_info
			if($skin_info->extra_vars)
			{
				foreach($skin_info->extra_vars as $vars)
				{
					if($vars->type!='image') continue;

					$image_obj = $obj->{$vars->name};
					// Get a variable to delete
					$del_var = $obj->{"del_".$vars->name};
					unset($obj->{"del_".$vars->name});
					if($del_var == 'Y')
					{
						FileHandler::removeFile($skin_vars[$vars->name]->value);
						continue;
					}
					// Use the previous data if not uploaded
					if(!$image_obj['tmp_name'])
					{
						$obj->{$vars->name} = $skin_vars[$vars->name]->value;
						continue;
					}
					// Ignore if the file is not successfully uploaded
					if(!is_uploaded_file($image_obj['tmp_name']))
					{
						unset($obj->{$vars->name});
						continue;
					}
					// Ignore if the file is not an image
					if(!preg_match("/\.(jpg|jpeg|gif|png)$/i", $image_obj['name']))
					{
						unset($obj->{$vars->name});
						continue;
					}
					// Upload the file to a path
					$oFileController = getController('file');
					$path = $oFileController->getStoragePath('images', getNextSequence(), $module_srl, 0, '', false);
					// Create a directory
					if(!FileHandler::makeDir($path)) return false;
					$filename = $path . FilenameFilter::clean($image_obj['name']);
					// Move the file
					if(!move_uploaded_file($image_obj['tmp_name'], $filename))
					{
						unset($obj->{$vars->name});
						continue;
					}
					// Upload the file
					FileHandler::removeFile($skin_vars[$vars->name]->value);
					// Change a variable
					unset($obj->{$vars->name});
					$obj->{$vars->name} = $filename;
				}
			}
			// Load the entire skin of the module and then remove the image
			/*
			if($skin_info->extra_vars) {
			foreach($skin_info->extra_vars as $vars) {
			if($vars->type!='image') continue;
			$value = $skin_vars[$vars->name];
			if(file_exists($value)) @unlink($value);
			}
			}
			*/
			$oModuleController = getController('module');

			if($mode === 'M')
			{
				$output = $oModuleController->insertModuleMobileSkinVars($module_srl, $obj);
			}
			else
			{
				$output = $oModuleController->insertModuleSkinVars($module_srl, $obj);
			}
			if(!$output->toBool())
			{
				return $output;
			}
		}

		$this->setMessage('success_saved');
		$this->setRedirectUrl(Context::get('error_return_url'));
	}

	public function procModuleAdminSetDesignInfo()
	{
		$moduleSrl = Context::get('target_module_srl');
		$mid = Context::get('target_mid');

		$skinType = Context::get('skin_type');
		$skinType = ($skinType == 'M') ? 'M' : 'P';

		$layoutSrl = Context::get('layout_srl');

		$isSkinFix = Context::get('is_skin_fix');

		if($isSkinFix)
		{
			$isSkinFix = ($isSkinFix == 'N') ? 'N' : 'Y';
		}

		$skinName = Context::get('skin_name');
		$skinVars = Context::get('skin_vars');

		$output = $this->setDesignInfo($moduleSrl, $mid, $skinType, $layoutSrl, $isSkinFix, $skinName, $skinVars);

		return $output;

	}

	public function setDesignInfo($moduleSrl = 0, $mid = '', $skinType = 'P', $layoutSrl = 0, $isSkinFix = 'Y', $skinName = '', $skinVars = NULL)
	{
		if(!$moduleSrl && !$mid)
		{
			throw new InvalidRequest;
		}

		$oModuleModel = getModel('module');

		if($mid)
		{
			$moduleInfo = $oModuleModel->getModuleInfoByMid($mid);
		}
		else
		{
			$moduleInfo = ModuleInfoModel::getModuleInfo($moduleSrl);
		}

		if(!$moduleInfo)
		{
			throw new InvalidRequest;
		}

		$skinTargetValue = ($skinType == 'M') ? 'mskin' : 'skin';
		$layoutTargetValue = ($skinType == 'M') ? 'mlayout_srl' : 'layout_srl';
		$skinFixTargetValue = ($skinType == 'M') ? 'is_mskin_fix' : 'is_skin_fix';

		if(strlen($layoutSrl))
		{
			$moduleInfo->{$layoutTargetValue} = $layoutSrl;
		}

		if(strlen($isSkinFix))
		{
			$moduleInfo->{$skinFixTargetValue} = $isSkinFix;
		}

		if($isSkinFix == 'Y')
		{
			$moduleInfo->{$skinTargetValue} = $skinName;
			$skinVars = json_decode($skinVars);

			if(is_array($skinVars))
			{
				foreach($skinVars as $key => $val)
				{
					if(empty($val))
					{
						continue;
					}

					$moduleInfo->{$key} = $val;
				}
			}
		}

		$oModuleController = getController('module');
		$output = $oModuleController->updateModule($moduleInfo);

		return $output;
	}

	public function procModuleAdminUpdateUseMobile()
	{
		$menuItemSrl = Context::get('menu_item_srl');
		$useMobile = Context::get('use_mobile');

		if(!$menuItemSrl)
		{
			throw new InvalidRequest;
		}

		$oModuleModel = getModel('module');
		$moduleInfo = $oModuleModel->getModuleInfoByMenuItemSrl($menuItemSrl);

		// designSettings is not original module info, so unset
		unset($moduleInfo->designSettings);

		$useMobile = $useMobile != 'Y' ? 'N' : 'Y';

		$moduleInfo->use_mobile = $useMobile;

		$oModuleController = getController('module');
		$output = $oModuleController->updateModule($moduleInfo);

		return $output;
	}
}
