<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * Admin model class of addon module
 * @author NAVER (developers@xpressengine.com)
 */
class addonAdminModel extends addon
{

	/**
	 * Initialization
	 *
	 * @return void
	 */
	function init()
	{

	}

	/**
	 * Returns a path of addon
	 *
	 * @param string $addon_name Name to get path
	 * @return string Returns a path
	 */
	function getAddonPath($addon_name)
	{
		$class_path = sprintf('./addons/%s/', $addon_name);
		if(is_dir($class_path))
		{
			return $class_path;
		}
		return "";
	}

	/**
	 * Get addon list for super admin
	 *
	 * @return Object
	 */
	function getAddonListForSuperAdmin()
	{
		$addonList = $this->getAddonList(0, 'site');

		$oAutoinstallModel = getModel('autoinstall');
		foreach($addonList as $key => $addon)
		{
			// check blacklist
			$addonList[$key]->isBlacklisted = Context::isBlacklistedPlugin($addon->addon);
			
			// get easyinstall remove url
			$packageSrl = $oAutoinstallModel->getPackageSrlByPath($addon->path);
			$addonList[$key]->remove_url = $oAutoinstallModel->getRemoveUrlByPackageSrl($packageSrl);

			// get easyinstall need update
			if($addonList[$key]->isBlacklisted)
			{
				$addonList[$key]->need_update = 'N';
			}
			else
			{
				$package = $oAutoinstallModel->getInstalledPackages($packageSrl);
				$addonList[$key]->need_update = $package[$packageSrl]->need_update;
			}

			// get easyinstall update url
			if($addonList[$key]->need_update == 'Y')
			{
				$addonList[$key]->update_url = $oAutoinstallModel->getUpdateUrlByPackageSrl($packageSrl);
			}
		}

		return $addonList;
	}

	/**
	 * Returns addon list
	 *
	 * @param int $site_srl Site srl
	 * @param string $gtype site or global
	 * @return array Returns addon list
	 */
	function getAddonList($site_srl = 0, $gtype = 'site')
	{
		// Wanted to add a list of activated
		$inserted_addons = $this->getInsertedAddons($site_srl, $gtype);
		// Downloaded and installed add-on to the list of Wanted
		$searched_list = FileHandler::readDir('./addons', '/^([a-zA-Z0-9_]+)$/');
		$searched_count = count($searched_list);
		if(!$searched_count)
		{
			return;
		}

		sort($searched_list);

		$oAddonAdminController = getAdminController('addon');

		for($i = 0; $i < $searched_count; $i++)
		{
			// Add the name of
			$addon_name = $searched_list[$i];
			if($addon_name == "smartphone")
			{
				continue;
			}
			// Add the path (files/addons precedence)
			$path = $this->getAddonPath($addon_name);
			// Wanted information on the add-on
			$info = $this->getAddonInfoXml($addon_name, $site_srl, $gtype);
			
			if(!$info) $info = new stdClass();

			$info->addon = $addon_name;
			$info->path = $path;
			$info->activated = FALSE;
			$info->mactivated = FALSE;
			$info->fixed = FALSE;
			// Check if a permossion is granted entered in DB
			if(!in_array($addon_name, array_keys($inserted_addons)))
			{
				// If not, type in the DB type (model, perhaps because of the hate doing this haneungeo .. ㅡ. ㅜ)
				$oAddonAdminController->doInsert($addon_name, $site_srl, $type, 'N', new stdClass);
				// Is activated
			}
			else
			{
				if($inserted_addons[$addon_name]->is_used == 'Y')
				{
					$info->activated = TRUE;
				}
				if($inserted_addons[$addon_name]->is_used_m == 'Y')
				{
					$info->mactivated = TRUE;
				}
				if($gtype == 'global' && $inserted_addons[$addon_name]->is_fixed == 'Y')
				{
					$info->fixed = TRUE;
				}
			}

			$list[] = $info;
		}
		return $list;
	}

	/**
	 * Returns a information of addon
	 *
	 * @param string $addon Name to get information
	 * @param int $site_srl Site srl
	 * @param string $gtype site or global
	 * @return object Returns a information
	 */
	function getAddonInfoXml($addon, $site_srl = 0, $gtype = 'site')
	{
		// Get a path of the requested module. Return if not exists.
		$addon_path = $this->getAddonPath($addon);
		if(!$addon_path)
		{
			return;
		}

		// Read the xml file for module skin information
		$xml_file = sprintf("%sconf/info.xml", FileHandler::getRealpath($addon_path));
		if(!file_exists($xml_file))
		{
			return;
		}

		$oXmlParser = new XeXmlParser();
		$tmp_xml_obj = $oXmlParser->loadXmlFile($xml_file);
		$xml_obj = $tmp_xml_obj->addon;

		if(!$xml_obj)
		{
			return;
		}

		// DB is set to bring history
		$db_args = new stdClass();
		$db_args->addon = $addon;
		if($gtype == 'global')
		{
			$output = executeQuery('addon.getAddonInfo', $db_args);
		}
		else
		{
			$db_args->site_srl = $site_srl;
			$output = executeQuery('addon.getSiteAddonInfo', $db_args);
		}
		$extra_vals = unserialize($output->data->extra_vars);

		$addon_info = new stdClass();
		if($extra_vals->mid_list)
		{
			$addon_info->mid_list = $extra_vals->mid_list;
		}
		else
		{
			$addon_info->mid_list = array();
		}

		if($extra_vals->xe_run_method)
		{
			$addon_info->xe_run_method = $extra_vals->xe_run_method;
		}

		// Add information
		if($xml_obj->version && $xml_obj->attrs->version == '0.2')
		{
			// addon format v0.2
			$date_obj = new stdClass();
			sscanf($xml_obj->date->body, '%d-%d-%d', $date_obj->y, $date_obj->m, $date_obj->d);
			$addon_info->date = sprintf('%04d%02d%02d', $date_obj->y, $date_obj->m, $date_obj->d);

			$addon_info->addon_name = $addon;
			$addon_info->title = $xml_obj->title->body;
			$addon_info->description = trim($xml_obj->description->body);
			$addon_info->version = $xml_obj->version->body;
			$addon_info->homepage = $xml_obj->link->body;
			$addon_info->license = $xml_obj->license->body;
			$addon_info->license_link = $xml_obj->license->attrs->link;

			if(!is_array($xml_obj->author))
			{
				$author_list = array();
				$author_list[] = $xml_obj->author;
			}
			else
			{
				$author_list = $xml_obj->author;
			}

			$addon_info->author = array();
			foreach($author_list as $author)
			{
				$author_obj = new stdClass();
				$author_obj->name = $author->name->body;
				$author_obj->email_address = $author->attrs->email_address;
				$author_obj->homepage = $author->attrs->link;
				$addon_info->author[] = $author_obj;
			}

			// Expand the variable order
			if($xml_obj->extra_vars)
			{
				$extra_var_groups = $xml_obj->extra_vars->group;
				if(!$extra_var_groups)
				{
					$extra_var_groups = $xml_obj->extra_vars;
				}
				if(!is_array($extra_var_groups))
				{
					$extra_var_groups = array($extra_var_groups);
				}

				foreach($extra_var_groups as $group)
				{
					$extra_vars = $group->var;
					if(!is_array($group->var))
					{
						$extra_vars = array($group->var);
					}

					foreach($extra_vars as $key => $val)
					{
						if(!$val)
						{
							continue;
						}

						$obj = new stdClass();
						if(!$val->attrs)
						{
							$val->attrs = new stdClass();
						}
						if(!$val->attrs->type)
						{
							$val->attrs->type = 'text';
						}

						$obj->group = $group->title->body;
						$obj->name = $val->attrs->name;
						$obj->title = $val->title->body;
						$obj->type = $val->attrs->type;
						$obj->description = $val->description->body;
						if($obj->name)
						{
							$obj->value = $extra_vals->{$obj->name};
						}
						if(strpos($obj->value, '|@|') != FALSE)
						{
							$obj->value = explode('|@|', $obj->value);
						}
						if($obj->type == 'mid_list' && !is_array($obj->value))
						{
							$obj->value = array($obj->value);
						}

						// 'Select'type obtained from the option list.
						if($val->options && !is_array($val->options))
						{
							$val->options = array($val->options);
						}

						for($i = 0, $c = countobj($val->options); $i < $c; $i++)
						{
							$obj->options[$i] = new stdClass();
							$obj->options[$i]->title = $val->options[$i]->title->body;
							$obj->options[$i]->value = $val->options[$i]->attrs->value;
						}

						$addon_info->extra_vars[] = $obj;
					}
				}
			}
		}
		else
		{
			// addon format 0.1
			$addon_info = new stdClass();
			$addon_info->addon_name = $addon;
			$addon_info->title = $xml_obj->title->body;
			$addon_info->description = trim($xml_obj->author->description->body);
			$addon_info->version = $xml_obj->attrs->version;
			
			$date_obj = new stdClass();
			sscanf($xml_obj->author->attrs->date, '%d. %d. %d', $date_obj->y, $date_obj->m, $date_obj->d);
			$addon_info->date = sprintf('%04d%02d%02d', $date_obj->y, $date_obj->m, $date_obj->d);
			
			$author_obj = new stdClass();
			$author_obj->name = $xml_obj->author->name->body;
			$author_obj->email_address = $xml_obj->author->attrs->email_address;
			$author_obj->homepage = $xml_obj->author->attrs->link;
			
			$addon_info->author = array();
			$addon_info->author[] = $author_obj;

			if($xml_obj->extra_vars)
			{
				// Expand the variable order
				$extra_var_groups = $xml_obj->extra_vars->group;
				if(!$extra_var_groups)
				{
					$extra_var_groups = $xml_obj->extra_vars;
				}
				if(!is_array($extra_var_groups))
				{
					$extra_var_groups = array($extra_var_groups);
				}
				foreach($extra_var_groups as $group)
				{
					$extra_vars = $group->var;
					if(!is_array($group->var))
					{
						$extra_vars = array($group->var);
					}

					$addon_info->extra_vars = array();
					foreach($extra_vars as $key => $val)
					{
						if(!$val)
						{
							continue;
						}

						$obj = new stdClass();

						$obj->group = $group->title->body;
						$obj->name = $val->attrs->name;
						$obj->title = $val->title->body;
						$obj->type = $val->type->body ? $val->type->body : 'text';
						$obj->description = $val->description->body;
						if($obj->name)
						{
							$obj->value = $extra_vals->{$obj->name};
						}
						if(strpos($obj->value, '|@|') != false)
						{
							$obj->value = explode('|@|', $obj->value);
						}
						if($obj->type == 'mid_list' && !is_array($obj->value))
						{
							$obj->value = array($obj->value);
						}
						// 'Select'type obtained from the option list.
						if($val->options && !is_array($val->options))
						{
							$val->options = array($val->options);
						}

						$obj->options = array();
						for($i = 0, $c = count($val->options); $i < $c; $i++)
						{
							$obj->options[$i]->title = $val->options[$i]->title->body;
							$obj->options[$i]->value = $val->options[$i]->value->body;
						}
						$addon_info->extra_vars[] = $obj;
					}
				}
			}
		}
		return $addon_info;
	}

	/**
	 * Returns activated addon list
	 *
	 * @param int $site_srl Site srl
	 * @param string $gtype site or global
	 * @return array Returns list
	 */
	function getInsertedAddons($site_srl = 0, $gtype = 'site')
	{
		$args = new stdClass();
		$args->list_order = 'addon';
		if($gtype == 'global')
		{
			$output = executeQueryArray('addon.getAddons', $args);
		}
		else
		{
			$args->site_srl = $site_srl;
			$output = executeQueryArray('addon.getSiteAddons', $args);
		}
		if(!$output->data)
		{
			return array();
		}

		$activated_count = count($output->data);
		$addon_list = array();
		for($i = 0; $i < $activated_count; $i++)
		{
			$addon = $output->data[$i];
			$addon_list[$addon->addon] = $addon;
		}
		return $addon_list;
	}

	/**
	 * Returns whether to activate
	 *
	 * @param string $addon Name to check
	 * @param int $site_srl Site srl
	 * @param string $type pc or mobile
	 * @param string $gtype site or global
	 * @return bool If addon is activated returns true. Otherwise returns false.
	 */
	function isActivatedAddon($addon, $site_srl = 0, $type = "pc", $gtype = 'site')
	{
		$always_return_true_for_compatibility = array(
			'member_communication' => true,
		);
		$always_return_false_for_compatibility = array(
		);
		
		if(isset($always_return_true_for_compatibility[$addon]))
		{
			return true;
		}
		if(isset($always_return_false_for_compatibility[$addon]))
		{
			return false;
		}
		
		$args = new stdClass();
		$args->addon = $addon;
		if($gtype == 'global')
		{
			if($type == "pc")
			{
				$output = executeQuery('addon.getAddonIsActivated', $args);
			}
			else
			{
				$output = executeQuery('addon.getMAddonIsActivated', $args);
			}
		}
		else
		{
			$args->site_srl = $site_srl;
			if($type == "pc")
			{
				$output = executeQuery('addon.getSiteAddonIsActivated', $args);
			}
			else
			{
				$output = executeQuery('addon.getSiteMAddonIsActivated', $args);
			}
		}
		if($output->data->count > 0)
		{
			return TRUE;
		}
		return FALSE;
	}

}
/* End of file addon.admin.model.php */
/* Location: ./modules/addon/addon.admin.model.php */
