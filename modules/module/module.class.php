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

		// Insert new domain
		if(!getModel('module')->getDefaultDomainInfo())
		{
			$current_url = Rhymix\Framework\Url::getCurrentUrl();
			$current_port = intval(parse_url($current_url, PHP_URL_PORT)) ?: null;
			$domain = new stdClass();
			$domain->domain_srl = 0;
			$domain->domain = Rhymix\Framework\URL::getDomainFromURL($current_url);
			$domain->is_default_domain = 'Y';
			$domain->index_module_srl = 0;
			$domain->index_document_srl = 0;
			$domain->http_port = RX_SSL ? null : $current_port;
			$domain->https_port = RX_SSL ? $current_port : null;
			$domain->security = config('url.ssl') ?: 'none';
			$domain->description = '';
			$domain->settings = json_encode(array('language' => null, 'timezone' => null));
			$output = executeQuery('module.insertDomain', $domain);
			if (!$output->toBool())
			{
				return $output;
			}
		}
		
		// Create a directory to use in the module module
		FileHandler::makeDir('./files/cache/module_info');
		FileHandler::makeDir('./files/cache/triggers');
		FileHandler::makeDir('./files/ruleset');
	}

	/**
	 * @brief a method to check if successfully installed
	 */
	function checkUpdate()
	{
		$oDB = &DB::getInstance();
		// 2008. 10. 27 Add multi-index in the table, the module_part_config
		if(!$oDB->isIndexExists('module_part_config', 'idx_module_part_config') && !$oDB->isIndexExists('module_part_config', 'unique_module_part_config')) return true;
		// 2008. 11. 13 Delete unique constraint on mid in modules. Add site_srl and then create unique index on site_srl and mid
		if(!$oDB->isIndexExists('modules',"idx_site_mid")) return true;
		// Move permissions/skin information of all modules to the table, grants.
		if($oDB->isColumnExists('modules', 'grants')) return true;
		// Delete extra_vars* column
		for($i=1;$i<=20;$i++)
		{
			if($oDB->isColumnExists("documents","extra_vars".$i)) return true;
		}

		// Check indexes
		if(!$oDB->isColumnExists("modules", "use_mobile")) return true;
		if(!$oDB->isColumnExists("modules", "mlayout_srl")) return true;
		if(!$oDB->isColumnExists("modules", "mcontent")) return true;
		if(!$oDB->isColumnExists("modules", "mskin")) return true;

		// check fix skin
		if(!$oDB->isColumnExists("modules", "is_skin_fix")) return true;

		if(!$oDB->isColumnExists("module_config", "site_srl")) return true;

		if(!is_dir('./files/ruleset')) return true;

		$args = new stdClass;
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

		// Check domains
		if (!$oDB->isTableExists('domains') || !getModel('module')->getDefaultDomainInfo())
		{
			return true;
		}

		// check fix mskin
		if(!$oDB->isColumnExists("modules", "is_mskin_fix")) return true;

		$oModuleModel = getModel('module');
		$moduleConfig = $oModuleModel->getModuleConfig('module');
		if(!$moduleConfig->isUpdateFixedValue) return true;
		
		// check unique index on module_part_config
		if($oDB->isIndexExists('module_part_config', 'idx_module_part_config')) return true;
		if(!$oDB->isIndexExists('module_part_config', 'unique_module_part_config')) return true;

		// check module_part_config data type
		$column_info = $oDB->getColumnInfo('module_part_config', 'config');
		if($column_info->xetype !== 'bigtext')
		{
			return true;
		}
		$column_info = $oDB->getColumnInfo('module_part_config', 'module');
		if($column_info->size > 80)
		{
			return true;
		}

		// check module_config data type
		$column_info = $oDB->getColumnInfo('module_config', 'config');
		if($column_info->xetype !== 'bigtext')
		{
			return true;
		}
		$column_info = $oDB->getColumnInfo('module_config', 'module');
		if($column_info->size > 80)
		{
			return true;
		}
	}

	/**
	 * @brief Execute update
	 */
	function moduleUpdate()
	{
		$oDB = &DB::getInstance();
		// 2008. 10. 27 module_part_config Add a multi-index to the table and check all information of module_configg
		if(!$oDB->isIndexExists('module_part_config', 'idx_module_part_config') && !$oDB->isIndexExists('module_part_config', 'unique_module_part_config'))
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
							$doc_args = new stdClass();
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

					Rhymix\Framework\Cache::clearGroup('site_and_module');
				}
			}
			
			// Various column drop
			$oDB->dropColumn('modules','grants');
			$oDB->dropColumn('modules','admin_id');
			$oDB->dropColumn('modules','skin_vars');
			$oDB->dropColumn('modules','extra_vars');
		}
		
		// extra_vars * Remove Column
		for($i=1;$i<=20;$i++)
		{
			if(!$oDB->isColumnExists("documents","extra_vars".$i)) continue;
			$oDB->dropColumn('documents','extra_vars'.$i);
		}

		// Migrate domains
		if (!getModel('module')->getDefaultDomainInfo())
		{
			$this->migrateDomains();
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
					$args = new stdClass();
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

		// check module_config data type
		$column_info = $oDB->getColumnInfo('module_config', 'config');
		if($column_info->xetype !== 'bigtext')
		{
			$oDB->modifyColumn('module_config', 'config', 'bigtext');
		}
		$column_info = $oDB->getColumnInfo('module_config', 'module');
		if($column_info->size > 80)
		{
			$oDB->modifyColumn('module_config', 'module', 'varchar', 80, '', true);
		}

		// check module_part_config data type
		$column_info = $oDB->getColumnInfo('module_part_config', 'config');
		if($column_info->xetype !== 'bigtext')
		{
			$oDB->modifyColumn('module_part_config', 'config', 'bigtext');
		}
		$column_info = $oDB->getColumnInfo('module_part_config', 'module');
		if($column_info->size > 80)
		{
			$oDB->modifyColumn('module_part_config', 'module', 'varchar', 80, '', true);
		}

		// check unique index on module_part_config
		if($oDB->isIndexExists('module_part_config', 'idx_module_part_config'))
		{
			$oDB->dropIndex('module_part_config', 'idx_module_part_config');
		}
		if(!$oDB->isIndexExists('module_part_config', 'unique_module_part_config'))
		{
			$oDB->addIndex('module_part_config', 'unique_module_part_config', array('module', 'module_srl'), true);
			if(!$oDB->isIndexExists('module_part_config', 'unique_module_part_config'))
			{
				$oDB->addIndex('module_part_config', 'unique_module_part_config', array('module', 'module_srl'), false);
			}
		}
	}
	
	/**
	 * @brief Migrate old sites and multidomain info to new 'domains' table
	 */
	function migrateDomains()
	{
		// Create the domains table.
		$oDB = DB::getInstance();
		if (!$oDB->isTableExists('domains'))
		{
			$oDB->createTableByXmlFile($this->module_path . 'schemas/domains.xml');
		}
		
		// Get current site configuration.
		$oModuleModel = getModel('module');
		$config = $oModuleModel->getModuleConfig('module');
		
		// Initialize domains data.
		$domains = array();
		$default_domain = new stdClass;
		
		// Check XE sites.
		$output = executeQueryArray('module.getSites');
		if ($output->data)
		{
			foreach ($output->data as $site_info)
			{
				$site_domain = $site_info->domain;
				if (!preg_match('@^https?://@', $site_domain))
				{
					$site_domain = 'http://' . $site_domain;
				}
				
				$domain = new stdClass();
				$domain->domain_srl = $site_info->site_srl;
				$domain->domain = Rhymix\Framework\URL::getDomainFromURL(strtolower($site_domain));
				$domain->is_default_domain = $site_info->site_srl == 0 ? 'Y' : 'N';
				$domain->index_module_srl = $site_info->index_module_srl;
				$domain->index_document_srl = 0;
				$domain->http_port = config('url.http_port') ?: null;
				$domain->https_port = config('url.https_port') ?: null;
				$domain->security = config('url.ssl') ?: 'none';
				$domain->description = '';
				$domain->settings = json_encode(array(
					'title' => $config->siteTitle,
					'subtitle' => $config->siteSubtitle,
					'language' => $site_info->default_language,
					'timezone' => config('locale.default_timezone'),
					'html_footer' => $config->htmlFooter,
				));
				$domain->regdate = $site_info->regdate;
				$domains[$domain->domain] = $domain;
				if ($domain->is_default_domain === 'Y')
				{
					$default_domain = $domain;
				}
			}
		}
		else
		{
			$output = executeQuery('module.getDefaultMidInfo', $args);
			$default_hostinfo = parse_url(Rhymix\Framework\URL::getCurrentURL());
			
			$domain = new stdClass();
			$domain->domain_srl = 0;
			$domain->domain = Rhymix\Framework\URL::decodeIdna(strtolower($default_hostinfo['host']));
			$domain->is_default_domain = 'Y';
			$domain->index_module_srl = $output->data ? $output->data->module_srl : 0;
			$domain->index_document_srl = 0;
			$domain->http_port = isset($default_hostinfo['port']) ? intval($default_hostinfo['port']) : null;
			$domain->https_port = null;
			$domain->security = config('url.ssl') ?: 'none';
			$domain->description = '';
			$domain->settings = json_encode(array(
				'title' => $config->siteTitle,
				'subtitle' => $config->siteSubtitle,
				'language' => $site_info->default_language,
				'timezone' => config('locale.default_timezone'),
				'html_footer' => $config->htmlFooter,
			));
			$domains[$domain->domain] = $domain;
			$default_domain = $domain;
		}
		
		// Check multidomain module.
		if (getModel('multidomain'))
		{
			$output = executeQueryArray('multidomain.getMultidomainList', (object)array('order_type' => 'asc', 'list_count' => 100000000));
			if ($output->data)
			{
				foreach ($output->data as $site_info)
				{
					$site_domain = $site_info->domain;
					if (!preg_match('@^https?://@', $site_domain))
					{
						$site_domain = 'http://' . $site_domain;
					}
					
					$domain = new stdClass();
					$domain->domain_srl = $site_info->multidomain_srl;
					$domain->domain = Rhymix\Framework\URL::getDomainFromURL(strtolower($site_domain));
					$domain->is_default_domain = isset($domains[$domain->domain]) ? $domains[$domain->domain]->is_default_domain : 'N';
					$domain->index_module_srl = intval($site_info->module_srl);
					$domain->index_document_srl = intval($site_info->document_srl);
					$domain->http_port = config('url.http_port') ?: null;
					$domain->https_port = config('url.https_port') ?: null;
					$domain->security = config('url.ssl') ?: 'none';
					$domain->description = '';
					$domain->settings = json_encode(array(
						'title' => $config->siteTitle,
						'subtitle' => $config->siteSubtitle,
						'language' => $site_info->default_language,
						'timezone' => config('locale.default_timezone'),
						'html_footer' => $config->htmlFooter,
					));
					$domain->regdate = $site_info->regdate;
					$domains[$domain->domain] = $domain;
					if ($domain->is_default_domain === 'Y')
					{
						$default_domain = $domain;
					}
				}
			}
		}
		
		// Insert into DB.
		foreach ($domains as $domain)
		{
			$output = executeQuery('module.insertDomain', $domain);
			if (!$output->toBool())
			{
				return $output;
			}
		}
		
		// Clear cache.
		Rhymix\Framework\Cache::clearGroup('site_and_module');
		
		// Return the default domain info.
		return $default_domain;
	}

	/**
	 * @brief Re-generate the cache file
	 */
	function recompileCache()
	{
		$oModuleModel = getModel('module');
		$module_list = $oModuleModel->getModuleList();
		$module_names = array_map(function($module_info) {
			return $module_info->module;
		}, $module_list);
		
		$oModuleModel->loadModuleExtends();
		
		// Delete triggers belonging to modules that don't exist
		$args = new stdClass;
		$args->module = $module_names ?: [];
		executeQuery('module.deleteTriggers', $args);
		Rhymix\Framework\Cache::delete('triggers');
	}
}
/* End of file module.class.php */
/* Location: ./modules/module/module.class.php */
