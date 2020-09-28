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

		$oDB = DB::getInstance();
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
		$oDB = DB::getInstance();
		
		// check ruleset directory
		if(!is_dir(RX_BASEDIR . 'files/ruleset')) return true;

		// Check domains
		if (!$oDB->isTableExists('domains') || !getModel('module')->getDefaultDomainInfo())
		{
			return true;
		}

		// check fix mskin
		if(!$oDB->isColumnExists("modules", "is_mskin_fix")) return true;

		$moduleConfig = ModuleModel::getModuleConfig('module');
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

		// check unique index on module_part_config
		if(!$oDB->isIndexExists('module_part_config', 'unique_module_part_config')) return true;

		// check route columns in action_forward table
		if(!$oDB->isColumnExists('action_forward', 'route_regexp')) return true;
		if(!$oDB->isColumnExists('action_forward', 'route_config')) return true;
		if(!$oDB->isColumnExists('action_forward', 'global_route')) return true;
	}

	/**
	 * @brief Execute update
	 */
	function moduleUpdate()
	{
		$oDB = DB::getInstance();

		// Migrate domains
		if (!getModel('module')->getDefaultDomainInfo())
		{
			$this->migrateDomains();
		}
		
		// check ruleset directory
		FileHandler::makeDir(RX_BASEDIR . 'files/ruleset');

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
		
		// check route columns in action_forward table
		if(!$oDB->isColumnExists('action_forward', 'route_regexp'))
		{
			$oDB->addColumn('action_forward', 'route_regexp', 'text');
		}
		if(!$oDB->isColumnExists('action_forward', 'route_config'))
		{
			$oDB->addColumn('action_forward', 'route_config', 'text');
		}
		if(!$oDB->isColumnExists('action_forward', 'global_route'))
		{
			$oDB->addColumn('action_forward', 'global_route', 'char', 1, 'N', true);
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
			$oDB->createTable($this->module_path . 'schemas/domains.xml');
		}
		
		// Get current site configuration.
		$config = ModuleModel::getModuleConfig('module');
		
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
		$module_list = ModuleModel::getModuleList();
		$module_names = array_map(function($module_info) {
			return $module_info->module;
		}, $module_list);
		
		// Delete triggers belonging to modules that don't exist
		$args = new stdClass;
		$args->module = $module_names ?: [];
		executeQuery('module.deleteTriggers', $args);
		Rhymix\Framework\Cache::delete('triggers');
	}
}
/* End of file module.class.php */
/* Location: ./modules/module/module.class.php */
