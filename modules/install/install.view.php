<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * @class  installView
 * @author NAVER (developers@xpressengine.com)
 * @brief View class of install module
 */
class installView extends install
{
	public $install_enable = false;
	
	public static $rewriteCheckFilePath = 'files/cache/tmpRewriteCheck.txt';
	public static $rewriteCheckString = '';

	/**
	 * @brief Initialization
	 */
	function init()
	{
		// Set browser title
		Context::setBrowserTitle(Context::getLang('introduce_title'));
		// Specify the template path
		$this->setTemplatePath($this->module_path.'tpl');
		// Error occurs if already installed
		if(Context::isInstalled()) return $this->stop('msg_already_installed');
		// Install a controller
		$oInstallController = getController('install');
		$this->install_enable = $oInstallController->checkInstallEnv();
		// If the environment is installable, execute installController::makeDefaultDirectory()
		if($this->install_enable) $oInstallController->makeDefaultDirectory();
	}

	/**
	 * @brief Display license messages
	 */
	function dispInstallIntroduce()
	{
		$install_config_file = FileHandler::getRealPath('./config/install.config.php');
		if(file_exists($install_config_file))
		{
			/**
			 * If './config/install.config.php' file created  and write array shown in the example below, XE installed using config file.
			 * ex )
			  $install_config = array(
			  'db_type' =>'mysqli_innodb',
			  'db_port' =>'3306',
			  'db_hostname' =>'localhost',
			  'db_userid' =>'root',
			  'db_password' =>'root',
			  'db_database' =>'rx_database',
			  'db_table_prefix' =>'rx',
			  'user_rewrite' =>'N',
			  'time_zone' =>'0000',
			  'email_address' =>'admin@admin.net',
			  'password' =>'pass',
			  'password2' =>'pass',
			  'nick_name' =>'admin',
			  'user_id' =>'admin',
			  'lang_type' =>'ko',	// en, jp, ...
			  );
			 */
			include $install_config_file;
			if(is_array($install_config))
			{
				foreach($install_config as $k => $v) 
				{
					$v = ($k == 'db_table_prefix') ? $v.'_' : $v;
					Context::set($k,$v,true);
				}
				unset($GLOBALS['__DB__']);
				Context::set('install_config', true, true);
				$oInstallController = getController('install');
				$output = $oInstallController->procInstall();
				if (!$output->toBool()) return $output;
				header("location: ./");
				Context::close();
				exit;
			}
		}

		Context::set('l', Context::getLangType());
		return $this->dispInstallLicenseAgreement();
		//$this->setTemplateFile('introduce');
	}

	/**
	 * @brief License agreement
	 */
	function dispInstallLicenseAgreement()
	{
		$this->setTemplateFile('license_agreement');

		$lang_type = Context::getLangType();
		Context::set('lang_type', $lang_type);
	}

	/**
	 * @brief Display messages about installation environment
	 */
	function dispInstallCheckEnv()
	{
		$oInstallController = getController('install');
		
		self::$rewriteCheckString = Password::createSecureSalt(32);
		FileHandler::writeFile(_XE_PATH_ . self::$rewriteCheckFilePath, self::$rewriteCheckString);;
		Context::set('use_rewrite', $_SESSION['use_rewrite'] = 'N'); 
		Context::set('use_nginx', stripos($_SERVER['SERVER_SOFTWARE'], 'nginx') !== false);
		
		$this->setTemplateFile('check_env');
	}

	/**
	 * @brief Choose a DB
	 */
	function dispInstallSelectDB()
	{
		// Display check_env if it is not installable
		if(!$this->install_enable) return $this->dispInstallCheckEnv();
		
		// Delete mod_rewrite check file
		FileHandler::removeFile(_XE_PATH_ . self::$rewriteCheckFilePath);
		
		// Save mod_rewrite check status
		if(Context::get('rewrite') === 'Y')
		{
			Context::set('use_rewrite', $_SESSION['use_rewrite'] = 'Y');
		}
		
		// Enter ftp information
		if(ini_get('safe_mode') && !Context::isFTPRegisted())
		{
			Context::set('progressMenu', '3');
			Context::set('server_ip_address', $_SERVER['SERVER_ADDR']);
			Context::set('server_ftp_user', get_current_user());
			$this->setTemplateFile('ftp');
		}
		else
		{
			$defaultDatabase = 'mysqli_innodb';
			$disableList = DB::getDisableList();
			if(is_array($disableList))
			{
				foreach($disableList AS $key=>$value)
				{
					if($value->db_type == $defaultDatabase)
					{
						$defaultDatabase = 'mysql';
						break;
					}
				}
			}
			Context::set('defaultDatabase', $defaultDatabase);
			Context::set('progressMenu', '4');

			$error_return_url = getNotEncodedUrl('', 'act', Context::get('act'), 'db_type', Context::get('db_type'));
			if(RX_SSL)
			{
				// Error occured when using https protocol at "ModuleHandler::init() '
				$parsedUrl = parse_url($error_return_url);
				$error_return_url = '';
				if(isset($parsedUrl['path'])) $error_return_url .= $parsedUrl['path'];
				if(isset($parsedUrl['query'])) $error_return_url .= '?' . $parsedUrl['query'];
				if(isset($parsedUrl['fragment'])) $error_return_url .= '?' . $parsedUrl['fragment'];
			}
			Context::set('error_return_url', $error_return_url);

			$this->setTemplateFile('select_db');
		}
	}

	/**
	 * @brief Display a screen to enter DB and administrator's information
	 */
	function dispInstallManagerForm()
	{
		// Display check_env if not installable
		if(!$this->install_enable)
		{
			return $this->dispInstallCheckEnv();
		}

		include _XE_PATH_.'files/config/tmpDB.config.php';

		Context::set('use_rewrite', $_SESSION['use_rewrite']);
		Context::set('use_ssl', RX_SSL ? 'always' : 'none');
		Context::set('time_zone', $GLOBALS['time_zone']);
		Context::set('db_type', $db_info->db_type);
		$this->setTemplateFile('admin_form');
	}

	/**
	 * @brief Check whether this server supports mod_rewrite
	 */
	function useRewriteModule()
	{
		if(function_exists('apache_get_modules') && in_array('mod_rewrite',apache_get_modules()))
		{
			return true;
		}

		require_once(_XE_PATH_.'classes/httprequest/XEHttpRequest.class.php');
		$httpRequest = new XEHttpRequest($_SERVER['HTTP_HOST'], $_SERVER['SERVER_PORT']);
		$xeInstallPath = substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], 'index.php', 1));
		$output = $httpRequest->send($xeInstallPath.'modules/install/conf/info.xml');

		return (strpos($output->body, '<?xml') !== 0);
	}
}
/* End of file install.view.php */
/* Location: ./modules/install/install.view.php */
