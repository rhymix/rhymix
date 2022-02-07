<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  installController
 * @author NAVER (developers@xpressengine.com)
 * @brief install module of the Controller class
 */
class installController extends install
{
	var $flagLicenseAgreement = './files/env/license_agreement';

	/**
	 * @brief Initialization
	 */
	function init()
	{
		// Stop if already installed.
		if (Context::isInstalled())
		{
			throw new Rhymix\Framework\Exception('msg_already_installed');
		}
		
		// Increase time limit.
		@set_time_limit(0);
	}

	/**
	 * @brief division install step... DB Config temp file create
	 */
	function procDBConfig()
	{
		// Get DB config variables.
		$config = Context::gets('db_type', 'db_host', 'db_port', 'db_user', 'db_pass', 'db_database', 'db_prefix');
		
		// Disallow installation using the root account.
		if (trim($config->db_user) === 'root' && !preg_match('/Development Server$/', $_SERVER['SERVER_SOFTWARE']))
		{
			return new BaseObject(-1, 'msg_dbroot_disallowed');
		}
		
		// Create a temporary setting object.
		Rhymix\Framework\Config::set('db.master', array(
			'type' => $config->db_type,
			'host' => $config->db_host,
			'port' => $config->db_port,
			'user' => $config->db_user,
			'pass' => $config->db_pass,
			'database' => $config->db_database,
			'prefix' => $config->db_prefix ? (rtrim($config->db_prefix, '_') . '_') : '',
		));
		
		// Check connection to the DB.
		$oDB = DB::getInstance();
		$output = $oDB->getError();
		if (!$output->toBool() || !$oDB->isConnected())
		{
			return $output;
		}
		
		// Check MySQL server capabilities.
		if(stripos($config->db_type, 'mysql') !== false)
		{
			// Check if InnoDB is supported.
			$show_engines = $oDB->_fetch($oDB->_query('SHOW ENGINES'));
			foreach($show_engines as $engine_info)
			{
				if ($engine_info->Engine === 'InnoDB' && $engine_info->Support !== 'NO')
				{
					$config->db_type .= '_innodb';
					break;
				}
			}
			
			// Check if utf8mb4 is supported.
			$oDB->charset = $oDB->getBestSupportedCharset();
			$config->db_charset = $oDB->charset;
		}
		
		// Check if tables already exist.
		$table_check = array('documents', 'comments', 'modules', 'sites');
		foreach ($table_check as $table_name)
		{
			if ($oDB->isTableExists($table_name))
			{
				throw new Rhymix\Framework\Exception('msg_table_already_exists');
			}
		}
		
		// Save DB config in session.
		$_SESSION['db_config'] = $config;
		
		// Continue the installation.
		if(!in_array(Context::getRequestMethod(), array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'act', 'dispInstallOtherConfig');
			$this->setRedirectUrl($returnUrl);
		}
	}

	/**
	 * @brief Install with received information
	 */
	function procInstall($install_config = null)
	{
		// Check if it is already installed
		if (Context::isInstalled())
		{
			throw new Rhymix\Framework\Exception('msg_already_installed');
		}
		
		// Get install parameters.
		$config = Rhymix\Framework\Config::getDefaults();
		if ($install_config)
		{
			$install_config = (array)$install_config;
			$config['db']['master']['type'] = 'mysql';
			$config['db']['master']['host'] = $install_config['db_hostname'];
			$config['db']['master']['port'] = $install_config['db_port'];
			$config['db']['master']['user'] = $install_config['db_userid'];
			$config['db']['master']['pass'] = $install_config['db_password'];
			$config['db']['master']['database'] = $install_config['db_database'];
			$config['db']['master']['prefix'] = $install_config['db_table_prefix'];
			$config['db']['master']['charset'] = $install_config['db_charset'];
			$config['db']['master']['engine'] = strpos($install_config['db_type'], 'innodb') !== false ? 'innodb' : 'myisam';
			$config['use_rewrite'] = $install_config['use_rewrite'] === 'Y' ? true : false;
			$config['url']['ssl'] = $install_config['use_ssl'] ?: 'none';
			$time_zone = $install_config['time_zone'];
			$user_info = new stdClass;
			$user_info->email_address = $install_config['email_address'];
			$user_info->password = $install_config['password'];
			$user_info->nick_name = $install_config['nick_name'];
			$user_info->user_id = $install_config['user_id'];
		}
		else
		{
			$config['db']['master']['type'] = 'mysql';
			$config['db']['master']['host'] = $_SESSION['db_config']->db_host;
			$config['db']['master']['port'] = $_SESSION['db_config']->db_port;
			$config['db']['master']['user'] = $_SESSION['db_config']->db_user;
			$config['db']['master']['pass'] = $_SESSION['db_config']->db_pass;
			$config['db']['master']['database'] = $_SESSION['db_config']->db_database;
			$config['db']['master']['prefix'] = $_SESSION['db_config']->db_prefix;
			$config['db']['master']['charset'] = $_SESSION['db_config']->db_charset;
			$config['db']['master']['engine'] = strpos($_SESSION['db_config']->db_type, 'innodb') !== false ? 'innodb' : 'myisam';
			$config['use_rewrite'] = $_SESSION['use_rewrite'] === 'Y' ? true : false;
			$config['url']['ssl'] = Context::get('use_ssl') ?: 'none';
			$time_zone = Context::get('time_zone');
			$user_info = Context::gets('email_address', 'password', 'nick_name', 'user_id');
		}
		
		// Fix the database table prefix.
		$config['db']['master']['prefix'] = rtrim($config['db']['master']['prefix'], '_');
		if ($config['db']['master']['prefix'] !== '')
		{
			$config['db']['master']['prefix'] .= '_';
		}
		
		// Create new crypto keys.
		$config['crypto']['encryption_key'] = Rhymix\Framework\Security::getRandom(64, 'alnum');
		$config['crypto']['authentication_key'] = Rhymix\Framework\Security::getRandom(64, 'alnum');
		$config['crypto']['session_key'] = Rhymix\Framework\Security::getRandom(64, 'alnum');
		
		// Set the default language.
		$config['locale']['default_lang'] = Context::getLangType();
		$config['locale']['enabled_lang'] = array($config['locale']['default_lang']);
		
		// Set the default time zone.
		if (strpos($time_zone, '/') !== false)
		{
			$config['locale']['default_timezone'] = $time_zone;
			$user_timezone = null;
		}
		else
		{
			$user_timezone = intval(Rhymix\Framework\DateTime::getTimezoneOffsetByLegacyFormat($time_zone ?: '+0900') / 3600);
			switch ($user_timezone)
			{
				case 9:
					$config['locale']['default_timezone'] = 'Asia/Seoul'; break;
				case 0:
					$config['locale']['default_timezone'] = 'Etc/UTC'; break;
				default:
					$config['locale']['default_timezone'] = 'Etc/GMT' . ($user_timezone > 0 ? '-' : '+') . abs($user_timezone);
			}
		}
		
		// Set the internal time zone.
		if ($config['locale']['default_timezone'] === 'Asia/Seoul')
		{
			$config['locale']['internal_timezone'] = 32400;
		}
		elseif ($user_timezone !== null)
		{
			$config['locale']['internal_timezone'] = $user_timezone * 3600;
		}
		else
		{
			$config['locale']['internal_timezone'] = 0;
		}
		
		// Set the default URL.
		$config['url']['default'] = Context::getRequestUri();
		
		// Set the default umask.
		$config['file']['umask'] = Rhymix\Framework\Storage::recommendUmask();
		
		// Load the new configuration.
		Rhymix\Framework\Config::setAll($config);
		Context::loadDBInfo($config);
		
		// Check DB.
		$oDB = DB::getInstance();
		if (!$oDB->isConnected())
		{
			return $oDB->getError();
		}
		
		// Assign a temporary administrator while installing.
		foreach ($user_info as $key => $val)
		{
			Context::set($key, $val, true);
		}
		$user_info->is_admin = 'Y';
		Context::set('logged_info', $user_info);
		
		// Install all the modules.
		try
		{
			$oDB->begin();
			$this->installDownloadedModule();
			$oDB->commit();
		}
		catch(Exception $e)
		{
			$oDB->rollback();
			throw new Rhymix\Framework\Exception($e->getMessage());
		}
		
		// Execute the install script.
		$scripts = FileHandler::readDir(RX_BASEDIR . 'modules/install/script', '/(\.php)$/');
		if(count($scripts))
		{
			sort($scripts);
			foreach($scripts as $script)
			{
				$script_path = FileHandler::getRealPath('./modules/install/script/');
				$output = include($script_path . $script);
			}
		}
		
		// Apply site lock.
		if (Context::get('use_sitelock') === 'Y')
		{
			$user_ip_range = getView('install')->detectUserIPRange();
			Rhymix\Framework\Config::set('lock.locked', true);
			Rhymix\Framework\Config::set('lock.message', 'This site is locked.');
			Rhymix\Framework\Config::set('lock.allow', array('127.0.0.1', $user_ip_range));
		}
		
		// Use APC cache if available.
		if (function_exists('apcu_exists'))
		{
			Rhymix\Framework\Config::set('cache.type', 'apc');
		}
		
		// Save the new configuration.
		Rhymix\Framework\Config::save();
		
		// Unset temporary session variables.
		unset($_SESSION['use_rewrite']);
		unset($_SESSION['db_config']);
		
		// Redirect to the home page.
		$this->setMessage('msg_install_completed');
		
		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : RX_BASEURL;
		$this->setRedirectUrl($returnUrl);
		return new BaseObject();
	}

	/**
	 * @brief Result returned after checking the installation environment
	 */
	function checkInstallEnv()
	{
		// Check each item
		$checklist = array();

		// Check PHP version
		if(version_compare(PHP_VERSION, __XE_MIN_PHP_VERSION__, '>='))
		{
			$checklist['php_version'] = true;
		}
		else
		{
			$checklist['php_version'] = false;
		}

		// Check DB
		if(extension_loaded('pdo_mysql'))
		{
			$checklist['db_support'] = true;
		}
		else
		{
			$checklist['db_support'] = false;
		}

		// Check permission
		if(is_writable(RX_BASEDIR) || is_writable(RX_BASEDIR . 'files'))
		{
			$checklist['permission'] = true;
		}
		else
		{
			$checklist['permission'] = false;
		}
		
		// Check session availability
		$license_agreement_time = intval(trim(FileHandler::readFile($this->flagLicenseAgreement)));
		if(isset($_SESSION['license_agreement']) && (!$license_agreement_time || ($license_agreement_time == $_SESSION['license_agreement'])))
		{
			$sess_autostart = intval(ini_get('session.auto_start'));
			
			if($sess_autostart === 0)
			{
				$checklist['session'] = true;
			}
			else
			{
				$checklist['session'] = false;
			}
		}
		else
		{
			$checklist['session'] = false;
		}

		// Check curl
		if(function_exists('curl_init'))
		{
			$checklist['curl'] = true;
		}
		else
		{
			$checklist['curl'] = false;
		}

		// Check GD
		if(function_exists('imagecreatefromgif'))
		{
			$checklist['gd'] = true;
		}
		else
		{
			$checklist['gd'] = false;
		}

		// Check iconv or mbstring
		if(function_exists('iconv') || function_exists('mb_convert_encoding'))
		{
			$checklist['iconv'] = true;
		}
		else
		{
			$checklist['iconv'] = false;
		}

		// Check json
		if(function_exists('json_encode'))
		{
			$checklist['json'] = true;
		}
		else
		{
			$checklist['json'] = false;
		}

		// Check mcrypt or openssl
		if(function_exists('mcrypt_encrypt') || function_exists('openssl_encrypt'))
		{
			$checklist['mcrypt'] = true;
		}
		else
		{
			$checklist['mcrypt'] = false;
		}

		// Check xml & simplexml
		if(function_exists('xml_parser_create') && function_exists('simplexml_load_string'))
		{
			$checklist['xml'] = true;
		}
		else
		{
			$checklist['xml'] = false;
		}

		// Enable install if all conditions are met
		$install_enable = true;
		foreach($checklist as $k => $v)
		{
			if (!$v)
			{
				$install_enable = false;
				break;
			}
		}

		// Save the checked result to the Context
		Context::set('checklist', $checklist);
		Context::set('install_enable', $install_enable);
		Context::set('phpversion', PHP_VERSION);

		return $install_enable;
	}

	/**
	 * @brief License agreement
	 */
	function procInstallLicenseAgreement()
	{
		$vars = Context::getRequestVars();
		if($vars->license_agreement !== 'Y')
		{
			throw new Rhymix\Framework\Exception('msg_must_accept_license_agreement');
		}
		
		$license_agreement_time = time();
		$_SESSION['license_agreement'] = $license_agreement_time;
		FileHandler::writeFile($this->flagLicenseAgreement, $license_agreement_time . PHP_EOL);
		$this->setRedirectUrl(getNotEncodedUrl('', 'act', 'dispInstallCheckEnv'));
	}

	/**
	 * @brief Create files and subdirectories
	 * Local evironment setting before installation by using DB information
	 */
	function makeDefaultDirectory()
	{
		$directory_list = array(
			'./files/config',
			'./files/cache/queries',
			'./files/cache/js_filter_compiled',
			'./files/cache/template_compiled',
		);

		foreach($directory_list as $dir)
		{
			FileHandler::makeDir($dir);
		}
	}

	/**
	 * @brief Install all the modules
	 *
	 * Create a table by using schema xml file in the shcema directory of each module
	 */
	function installDownloadedModule()
	{
		$oModuleModel = getModel('module');
		// Create a table ny finding schemas/*.xml file in each module
		$module_list = FileHandler::readDir('./modules/', NULL, false, true);
		$modules = array();
		foreach($module_list as $module_path)
		{
			// Get module name
			$module = basename($module_path);
			$xml_info = $oModuleModel->getModuleInfoXml($module);
			if(!$xml_info) continue;
			$modules[$xml_info->category][] = $module;
		}
		// Install "module" module in advance
		$this->installModule('module','./modules/module');
		$oModule = ModuleModel::getModuleInstallClass($module);
		if($oModule->checkUpdate()) $oModule->moduleUpdate();
		// Determine the order of module installation depending on category
		$install_step = array('system','content','member');
		// Install all the remaining modules
		foreach($install_step as $category)
		{
			if(is_array($modules[$category]) && count($modules[$category]))
			{
				foreach($modules[$category] as $module)
				{
					if($module == 'module') continue;
					$this->installModule($module, sprintf('./modules/%s', $module));

					$oModule = ModuleModel::getModuleInstallClass($module);
					if(is_object($oModule) && method_exists($oModule, 'checkUpdate'))
					{
						if($oModule->checkUpdate()) $oModule->moduleUpdate();
					}
				}
				unset($modules[$category]);
			}
		}
		// Install all the remaining modules
		if(count($modules))
		{
			foreach($modules as $category => $module_list)
			{
				if(is_array($module_list) && count($module_list))
				{
					foreach($module_list as $module)
					{
						if($module == 'module') continue;
						$this->installModule($module, sprintf('./modules/%s', $module));

						$oModule = ModuleModel::getModuleInstallClass($module);
						if($oModule && method_exists($oModule, 'checkUpdate') && method_exists($oModule, 'moduleUpdate'))
						{
							if($oModule->checkUpdate()) $oModule->moduleUpdate();
						}
					}
				}
			}
		}

		return new BaseObject();
	}

	/**
	 * @brief Install an each module
	 */
	function installModule($module, $module_path)
	{
		// create db instance
		$oDB = DB::getInstance();
		// Create a table if the schema xml exists in the "schemas" directory of the module
		$schema_dir = sprintf('%s/schemas/', $module_path);
		$schema_files = FileHandler::readDir($schema_dir, NULL, false, true);
		$schema_sorted = [];
		foreach ($schema_files as $filename)
		{
			if (!preg_match('/\/([a-zA-Z0-9_]+)\.xml$/', $filename, $matches))
			{
				continue;
			}
			if (preg_match('/<table\s[^>]*deleted="true"/i', file_get_contents($filename)))
			{
				continue;
			}
			
			$table_name = $matches[1];
			if(isset($schema_sorted[$table_name]) || $oDB->isTableExists($table_name))
			{
				continue;
			}
			
			$schema_sorted[$table_name] = $filename;
		}
		
		$schema_sorted = Rhymix\Framework\Parsers\DBTableParser::resolveDependency($schema_sorted);
		foreach ($schema_sorted as $table_name => $filename)
		{
			$output = $oDB->createTable($filename);
			if(!$output->toBool())
			{
				throw new Exception(lang('msg_create_table_failed') . ': ' . $table_name . ': ' . $oDB->getError()->getMessage());
			}
		}
		
		// Create a table and module instance and then execute install() method
		unset($oModule);
		$oModule = ModuleModel::getModuleInstallClass($module);
		if(method_exists($oModule, 'moduleInstall')) $oModule->moduleInstall();
		return new BaseObject();
	}
	
	/**
	 * Placeholder for third-party apps that try to manipulate system configuration.
	 * 
	 * @return void
	 */
	public function makeConfigFile()
	{
		return true;
	}
}
/* End of file install.controller.php */
/* Location: ./modules/install/install.controller.php */
