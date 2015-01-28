<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  module
 * @author NAVER (developers@xpressengine.com)
 * @brief high class of the module module
 */
class module extends ModuleObject
{
	/**
	 * @brief Implement if additional tasks are necessary when installing
	 */
	function moduleInstall()
	{
		// Register action forward (to use in administrator mode)
		$oModuleController = getController('module');

		$oDB = &DB::getInstance();
		$oDB->addIndex("modules","idx_site_mid", array("site_srl","mid"), true);
		$oDB->addIndex('sites','unique_domain',array('domain'),true);
		// Create a directory to use in the module module
		FileHandler::makeDir('./files/cache/module_info');
		FileHandler::makeDir('./files/cache/triggers');
		FileHandler::makeDir('./files/ruleset');

		// Insert site information into the sites table
		$args = new stdClass;
		$args->site_srl = 0;
		$output = $oDB->executeQuery('module.getSite', $args);
		if(!$output->data || !$output->data->index_module_srl)
		{
			$db_info = Context::getDBInfo();
			$domain = Context::getDefaultUrl();
			$url_info = parse_url($domain);
			$domain = $url_info['host'].( (!empty($url_info['port'])&&$url_info['port']!=80)?':'.$url_info['port']:'').$url_info['path'];

			$site_args = new stdClass;
			$site_args->site_srl = 0;
			$site_args->index_module_srl  = 0;
			$site_args->domain = $domain;
			$site_args->default_language = $db_info->lang_type;

			$output = executeQuery('module.insertSite', $site_args);
			if(!$output->toBool()) return $output;
		}

		return new Object();
	}

	/**
	 * @brief a method to check if successfully installed
	 */
	function checkUpdate()
	{
		$oDB = &DB::getInstance();
		// 2008. 10. 27 Add multi-index in the table, the module_part_config
		if(!$oDB->isIndexExists("module_part_config","idx_module_part_config")) return true;
		// 2008. 11. 13 Delete unique constraint on mid in modules. Add site_srl and then create unique index on site_srl and mid
		if(!$oDB->isIndexExists('modules',"idx_site_mid")) return true;
		// Move permissions/skin information of all modules to the table, grants.
		if($oDB->isColumnExists('modules', 'grants')) return true;
		// Move permissions/skin information of all modules to the table, grants.
		if(!$oDB->isColumnExists('sites', 'default_language')) return true;
		// Delete extra_vars* column
		for($i=1;$i<=20;$i++)
		{
			if($oDB->isColumnExists("documents","extra_vars".$i)) return true;
		}
		// Insert site information to the table, sites
		$args = new stdClass();
		$args->site_srl = 0;
		$output = $oDB->executeQuery('module.getSite', $args);
		if(!$output->data) return true;

		// If domain index is defined on the table, sites
		if($oDB->isIndexExists('sites', 'idx_domain')) return true;
		if(!$oDB->isIndexExists('sites','unique_domain')) return true;

		if(!$oDB->isColumnExists("modules", "use_mobile")) return true;
		if(!$oDB->isColumnExists("modules", "mlayout_srl")) return true;
		if(!$oDB->isColumnExists("modules", "mcontent")) return true;
		if(!$oDB->isColumnExists("modules", "mskin")) return true;

		// check fix skin
		if(!$oDB->isColumnExists("modules", "is_skin_fix")) return true;

		if(!$oDB->isColumnExists("module_config", "site_srl")) return true;

		if(!is_dir('./files/ruleset')) return true;

		$args->skin = '.';
		$output = executeQueryArray('module.getModuleSkinDotList', $args);
		if($output->data && count($output->data) > 0)
		{
			foreach($output->data as $item)
			{
				$skin_path = explode('.', $item->skin);
				if(count($skin_path) != 2) continue;
				if(is_dir(sprintf(_XE_PATH_ . 'themes/%s/modules/%s', $skin_path[0], $skin_path[1]))) return true;
			}
		}

		// XE 1.7

		// check fix mskin
		if(!$oDB->isColumnExists("modules", "is_mskin_fix")) return true;

		$oModuleModel = getModel('module');
		$moduleConfig = $oModuleModel->getModuleConfig('module');
		if(!$moduleConfig->isUpdateFixedValue) return true;
	}

	/**
	 * @brief Execute update
	 */
	function moduleUpdate()
	{
		$oDB = &DB::getInstance();
		// 2008. 10. 27 module_part_config Add a multi-index to the table and check all information of module_configg
		if(!$oDB->isIndexExists("module_part_config","idx_module_part_config"))
		{
			$oModuleModel = getModel('module');
			$oModuleController = getController('module');
			$modules = $oModuleModel->getModuleList();
			foreach($modules as $key => $module_info)
			{
				$module = $module_info->module;
				if(!in_array($module, array('point','trackback','layout','rss','file','comment','editor'))) continue;
				$config = $oModuleModel->getModuleConfig($module);

				$module_config = null;
				switch($module)
				{
					case 'point' :
						$module_config = $config->module_point;
						unset($config->module_point);
						break;
					case 'trackback' :
					case 'rss' :
					case 'file' :
					case 'comment' :
					case 'editor' :
						$module_config = $config->module_config;
						unset($config->module_config);
						if(is_array($module_config) && count($module_config))
						{
							foreach($module_config as $key => $val)
							{
								if(isset($module_config[$key]->module_srl)) unset($module_config[$key]->module_srl);
							}
						}
						break;
					case 'layout' :
						$tmp = $config->header_script;
						if(is_array($tmp) && count($tmp))
						{
							foreach($tmp as $k => $v)
							{
								if(!$v && !trim($v)) continue;
								$module_config[$k]->header_script = $v;
							}
						}
						$config = null;
						break;
				}

				$oModuleController->insertModuleConfig($module, $config);

				if(is_array($module_config) && count($module_config))
				{
					foreach($module_config as $module_srl => $module_part_config)
					{
						$oModuleController->insertModulePartConfig($module,$module_srl,$module_part_config);
					}
				}
			}
			$oDB->addIndex("module_part_config","idx_module_part_config", array("module","module_srl"));
		}
		// 2008. 11. 13 drop index(unique_mid). Add a column and index on site_srl and mid columns
		if(!$oDB->isIndexExists('modules',"idx_site_mid"))
		{
			$oDB->dropIndex("modules","unique_mid",true);
			$oDB->addColumn('modules','site_srl','number',11,0,true);
			$oDB->addIndex("modules","idx_site_mid", array("site_srl","mid"),true);
		}
		// document extra vars
		if(!$oDB->isTableExists('document_extra_vars')) $oDB->createTableByXmlFile('./modules/document/schemas/document_extra_vars.xml');

		if(!$oDB->isTableExists('document_extra_keys')) $oDB->createTableByXmlFile('./modules/document/schemas/document_extra_keys.xml');
		// Move permission, skin info, extection info, admin ID of all modules to the table, grants
		if($oDB->isColumnExists('modules', 'grants'))
		{
			$oModuleController = getController('module');
			$oDocumentController = getController('document');
			// Get a value of the current system language code
			$lang_code = Context::getLangType();
			// Get module_info of all modules
			$output = executeQueryArray('module.getModuleInfos');
			if(count($output->data))
			{
				foreach($output->data as $module_info)
				{
					// Separate information about permission granted to the module, extra vars, skin vars, super-admin's authority
					$module_srl = trim($module_info->module_srl);
					// grant an authority
					$grants = unserialize($module_info->grants);
					if($grants) $oModuleController->insertModuleGrants($module_srl, $grants);
					// Insert skin vars
					$skin_vars = unserialize($module_info->skin_vars);
					if($skin_vars) $oModuleController->insertModuleSkinVars($module_srl, $skin_vars);
					// Insert super admin's ID
					$admin_id = trim($module_info->admin_id);
					if($admin_id && $admin_id != 'Array')
					{
						$admin_ids = explode(',',$admin_id);
						if(count($admin_id))
						{
							foreach($admin_ids as $admin_id)
							{
								$oModuleController->insertAdminId($module_srl, $admin_id);
							}
						}
					}
					// Save extra configurations for each module(column data which doesn't exist in the defaut modules)
					$extra_vars = unserialize($module_info->extra_vars);
					$document_extra_keys = null;
					if($extra_vars->extra_vars && count($extra_vars->extra_vars))
					{
						$document_extra_keys = $extra_vars->extra_vars;
						unset($extra_vars->extra_vars);
					}
					if($extra_vars) $oModuleController->insertModuleExtraVars($module_srl, $extra_vars);

					/**
					 * Move document extra vars(it should have conducted in the documents module however extra vars in modules table should be listed up in this module)
					 */
					// Insert extra vars if planet module is
					if($module_info->module == 'planet')
					{
						if(!$document_extra_keys || !is_array($document_extra_keys)) $document_extra_keys = array();
						$planet_extra_keys->name = 'postscript';
						$planet_extra_keys->type = 'text';
						$planet_extra_keys->is_required = 'N';
						$planet_extra_keys->search = 'N';
						$planet_extra_keys->default = '';
						$planet_extra_keys->desc = '';
						$document_extra_keys[20] = $planet_extra_keys;
					}
					// Register keys for document extra vars
					if(count($document_extra_keys))
					{
						foreach($document_extra_keys as $var_idx => $val)
						{
							$oDocumentController->insertDocumentExtraKey($module_srl, $var_idx, $val->name, $val->type, $val->is_required, $val->search, $val->default, $val->desc, 'extra_vars'.$var_idx);
						}
						// 2009-04-14 Fixed a bug that only 100 extra vars are moved
						$oDocumentModel = getModel('document');
						$total_count = $oDocumentModel->getDocumentCount($module_srl);

						if($total_count > 0)
						{
							$per_page = 100;
							$total_pages = (int) (($total_count - 1) / $per_page) + 1;
							// Get extra vars if exist
							$doc_args = null;
							$doc_args->module_srl = $module_srl;
							$doc_args->list_count = $per_page;
							$doc_args->sort_index = 'list_order';
							$doc_args->order_type = 'asc';

							for($doc_args->page = 1; $doc_args->page <= $total_pages; $doc_args->page++)
							{
								$output = executeQueryArray('document.getDocumentList', $doc_args);

								if($output->toBool() && $output->data && count($output->data))
								{
									foreach ($output->data as $document)
									{
										if(!$document) continue;
										foreach ($document as $key => $var)
										{
											if (strpos($key, 'extra_vars') !== 0 || !trim($var) || $var == 'N;') continue;
											$var_idx = str_replace('extra_vars','',$key);
											$oDocumentController->insertDocumentExtraVar($module_srl, $document->document_srl, $var_idx, $var, 'extra_vars'.$var_idx, $lang_code);
										}
									}
								}
							} // for total_pages
						} // if count
					}
					// Additional variables of the module, remove
					$module_info->grant = null;
					$module_info->extra_vars = null;
					$module_info->skin_vars = null;
					$module_info->admin_id = null;
					executeQuery('module.updateModule', $module_info);

					$oCacheHandler = CacheHandler::getInstance('object', null, true);
					if($oCacheHandler->isSupport())
					{
						$oCacheHandler->invalidateGroupKey('site_and_module');
					}
				}
			}
			// Various column drop
			$oDB->dropColumn('modules','grants');
			$oDB->dropColumn('modules','admin_id');
			$oDB->dropColumn('modules','skin_vars');
			$oDB->dropColumn('modules','extra_vars');
		}
		// Rights of all modules/skins transferring the information into a table Update grants
		if(!$oDB->isColumnExists('sites', 'default_language'))
		{
			$oDB->addColumn('sites','default_language','varchar',255,0,false);
		}
		// extra_vars * Remove Column
		for($i=1;$i<=20;$i++)
		{
			if(!$oDB->isColumnExists("documents","extra_vars".$i)) continue;
			$oDB->dropColumn('documents','extra_vars'.$i);
		}

		// Enter the main site information sites on the table
		$args = new stdClass;
		$args->site_srl = 0;
		$output = $oDB->executeQuery('module.getSite', $args);
		if(!$output->data)
		{
			// Basic mid, language Wanted
			$mid_output = $oDB->executeQuery('module.getDefaultMidInfo', $args);
			$db_info = Context::getDBInfo();
			$domain = Context::getDefaultUrl();
			$url_info = parse_url($domain);
			$domain = $url_info['host'].( (!empty($url_info['port'])&&$url_info['port']!=80)?':'.$url_info['port']:'').$url_info['path'];
			$site_args->site_srl = 0;
			$site_args->index_module_srl  = $mid_output->data->module_srl;
			$site_args->domain = $domain;
			$site_args->default_language = $db_info->lang_type;

			$output = executeQuery('module.insertSite', $site_args);
			if(!$output->toBool()) return $output;
		}

		if($oDB->isIndexExists('sites','idx_domain'))
		{
			$oDB->dropIndex('sites','idx_domain');
		}
		if(!$oDB->isIndexExists('sites','unique_domain'))
		{
			$this->updateForUniqueSiteDomain();
			$oDB->addIndex('sites','unique_domain',array('domain'),true);
		}

		if(!$oDB->isColumnExists("modules", "use_mobile"))
		{
			$oDB->addColumn('modules','use_mobile','char',1,'N');
		}
		if(!$oDB->isColumnExists("modules", "mlayout_srl"))
		{
			$oDB->addColumn('modules','mlayout_srl','number',11, 0);
		}
		if(!$oDB->isColumnExists("modules", "mcontent"))
		{
			$oDB->addColumn('modules','mcontent','bigtext');
		}
		if(!$oDB->isColumnExists("modules", "mskin"))
		{
			$oDB->addColumn('modules','mskin','varchar',250);
		}
		if(!$oDB->isColumnExists("modules", "is_skin_fix"))
		{
			$oDB->addColumn('modules', 'is_skin_fix', 'char', 1, 'N');
			$output = executeQuery('module.updateSkinFixModules');
		}
		if(!$oDB->isColumnExists("module_config", "site_srl"))
		{
			$oDB->addColumn('module_config', 'site_srl', 'number', 11, 0, true);
		}
		FileHandler::makeDir('./files/ruleset');

		$args->skin = '.';
		$output = executeQueryArray('module.getModuleSkinDotList', $args);
		if($output->data && count($output->data) > 0)
		{
			foreach($output->data as $item)
			{
				$skin_path = explode('.', $item->skin);
				if(count($skin_path) != 2) continue;
				if(is_dir(sprintf(_XE_PATH_ . 'themes/%s/modules/%s', $skin_path[0], $skin_path[1])))
				{
					unset($args);
					$args->skin = $item->skin;
					$args->new_skin = implode('|@|', $skin_path);
					$output = executeQuery('module.updateSkinAll', $args);
				}
			}
		}

		// XE 1.7
		if(!$oDB->isColumnExists("modules", "is_mskin_fix"))
		{
			$oDB->addColumn('modules', 'is_mskin_fix', 'char', 1, 'N');
			$output = executeQuery('module.updateMobileSkinFixModules');
		}

		$oModuleModel = getModel('module');
		$moduleConfig = $oModuleModel->getModuleConfig('module');
		if(!$moduleConfig->isUpdateFixedValue)
		{
			$output = executeQuery('module.updateSkinFixModules');
			$output = executeQuery('module.updateMobileSkinFixModules');

			$oModuleController = getController('module');
			if(!$moduleConfig) $moduleConfig = new stdClass;
			$moduleConfig->isUpdateFixedValue = TRUE;
			$output = $oModuleController->updateModuleConfig('module', $moduleConfig);
		}
		
		return new Object(0, 'success_updated');
	}
	
	function updateForUniqueSiteDomain()
	{
		$output = executeQueryArray("module.getNonuniqueDomains");
		if(!$output->data) return;
		foreach($output->data as $data)
		{
			if($data->count == 1) continue;
			$domain = $data->domain;
			$args = new stdClass;
			$args->domain = $domain;
			$output2 = executeQueryArray("module.getSiteByDomain", $args);
			$bFirst = true;
			foreach($output2->data as $site)
			{
				if($bFirst)
				{
					$bFirst = false;
					continue;
				}
				$domain .= "_";
				$args = new stdClass;
				$args->domain = $domain;
				$args->site_srl = $site->site_srl;
				$output3 = executeQuery("module.updateSite", $args);
			}
		}
	}

	/**
	 * @brief Re-generate the cache file
	 */
	function recompileCache()
	{
		$oModuleModel = getModel('module');
		$oModuleModel->getModuleList();
		$oModuleModel->loadModuleExtends();
	}
}
/* End of file module.class.php */
/* Location: ./modules/module/module.class.php */
