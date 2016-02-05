<?php

namespace Rhymix\Framework;

/**
 * The config class.
 */
class Config
{
	/**
	 * System configuration is stored here.
	 */
	protected static $_config = array();
	
	/**
	 * Load system configuration.
	 * 
	 * @return void
	 */
	public static function init()
	{
		if (file_exists(RX_BASEDIR . 'files/config/config.php'))
		{
			self::$_config = (include RX_BASEDIR . 'files/config/config.php');
		}
		else
		{
			if (self::$_config = self::convert())
			{
				self::save();
			}
		}
		return self::$_config;
	}
	
	/**
	 * Get all system configuration.
	 * 
	 * @return array
	 */
	public static function getAll()
	{
		return self::$_config;
	}
	
	/**
	 * Get default system configuration.
	 * 
	 * @return array
	 */
	public static function getDefaults()
	{
		return (include RX_BASEDIR . 'common/defaults/config.php');
	}
	
	/**
	 * Get a system configuration value.
	 * 
	 * @param string $key
	 * @return mixed
	 */
	public static function get($key)
	{
		if (!count(self::$_config))
		{
			self::init();
		}
		$data = self::$_config;
		$key = explode('.', $key);
		foreach ($key as $step)
		{
			if ($key === '' || !isset($data[$step]))
			{
				return null;
			}
			$data = $data[$step];
		}
		return $data;
	}
	
	/**
	 * Set a system configuration value.
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	public static function set($key, $value)
	{
		if (!count(self::$_config))
		{
			self::init();
		}
		$data = &self::$_config;
		$key = explode('.', $key);
		foreach ($key as $step)
		{
			$data = &$data[$step];
		}
		$data = $value;
	}
	
	/**
	 * Set all system configuration.
	 * 
	 * @param array $config
	 * @return void
	 */
	public static function setAll($config)
	{
		self::$_config = $config;
	}
	
	/**
	 * Convert previous configuration files to the current format and return it.
	 * 
	 * @return array
	 */
	public static function convert()
	{
		// Load DB info file.
		if (file_exists(RX_BASEDIR . 'files/config/db.config.php'))
		{
			include RX_BASEDIR . 'files/config/db.config.php';
		}
		else
		{
			return array();
		}
		
		// Load FTP info file.
		if (file_exists(RX_BASEDIR . 'files/config/ftp.config.php'))
		{
			include RX_BASEDIR . 'files/config/ftp.config.php';
		}
		
		// Load selected language file.
		if (file_exists(RX_BASEDIR . 'files/config/lang_selected.info'))
		{
			$lang_selected = array();
			$lang_selected_raw = file_get_contents(RX_BASEDIR . 'files/config/lang_selected.info');
			$lang_selected_raw = array_map('trim', explode("\n", $lang_selected_raw));
			foreach ($lang_selected_raw as $lang_selected_item)
			{
				$lang_selected_item = array_map('trim', explode(',', $lang_selected_item));
				if (count($lang_selected_item) && $lang_selected_item[0] !== '')
				{
					$lang_selected_item[0] = ($lang_selected_item[0] === 'jp' ? 'ja' : $lang_selected_item[0]);
					$lang_selected[] = $lang_selected_item[0];
				}
			}
			$lang_selected = array_unique($lang_selected);
			unset($lang_selected_raw, $lang_selected_item);
		}
		else
		{
			$lang_selected = \Context::getLangType() === 'jp' ? 'ja' : \Context::getLangType();
			$lang_selected = array($lang_selected);
		}
		
		// Load defaults for the new configuration.
		$config = (include RX_BASEDIR . 'common/defaults/config.php');
		
		// Convert database configuration.
		if (!isset($db_info->master_db))
		{
			$db_info->master_db = array();
			$db_info->master_db['db_type'] = $db_info->db_type;
			$db_info->master_db['db_hostname'] = $db_info->db_hostname;
			$db_info->master_db['db_port'] = $db_info->db_port;
			$db_info->master_db['db_userid'] = $db_info->db_userid;
			$db_info->master_db['db_password'] = $db_info->db_password;
			$db_info->master_db['db_database'] = $db_info->db_database;
			$db_info->master_db['db_table_prefix'] = $db_info->db_table_prefix;
		}
		
		$config['db']['master']['type'] = strtolower($db_info->master_db['db_type']);
		$config['db']['master']['host'] = $db_info->master_db['db_hostname'];
		$config['db']['master']['port'] = $db_info->master_db['db_port'];
		$config['db']['master']['user'] = $db_info->master_db['db_userid'];
		$config['db']['master']['pass'] = $db_info->master_db['db_password'];
		$config['db']['master']['database'] = $db_info->master_db['db_database'];
		$config['db']['master']['prefix'] = $db_info->master_db['db_table_prefix'];
		
		if (substr($config['db']['master']['prefix'], -1) !== '_')
		{
			$config['db']['master']['prefix'] .= '_';
		}
		
		$config['db']['master']['charset'] = $db_info->master_db['db_charset'] ?: 'utf8';
		
		if (strpos($config['db']['master']['type'], 'innodb') !== false)
		{
			$config['db']['master']['type'] = str_replace('_innodb', '', $config['db']['master']['type']);
			$config['db']['master']['engine'] = 'innodb';
		}
		elseif (strpos($config['db']['master']['type'], 'mysql') !== false)
		{
			$config['db']['master']['engine'] = 'myisam';
		}
		
		if (isset($db_info->slave_db) && count($db_info->slave_db))
		{
			foreach ($db_info->slave_db as $slave_id => $slave_db)
			{
				if ($slave_db !== $db_info->master_db)
				{
					$slave_id = 'slave' . $slave_id;
					$config['db'][$slave_id]['type'] = strtolower($slave_db['db_type']);
					$config['db'][$slave_id]['host'] = $slave_db['db_hostname'];
					$config['db'][$slave_id]['port'] = $slave_db['db_type'];
					$config['db'][$slave_id]['user'] = $slave_db['db_userid'];
					$config['db'][$slave_id]['pass'] = $slave_db['db_password'];
					$config['db'][$slave_id]['database'] = $slave_db['db_database'];
					$config['db'][$slave_id]['prefix'] = $slave_db['db_table_prefix'];
					
					if (substr($config['db'][$slave_id]['prefix'], -1) !== '_')
					{
						$config['db'][$slave_id]['prefix'] .= '_';
					}
					
					$config['db'][$slave_id]['charset'] = $slave_db['db_charset'] ?: 'utf8';
					
					if (strpos($config['db'][$slave_id]['type'], 'innodb') !== false)
					{
						$config['db'][$slave_id]['type'] = str_replace('_innodb', '', $config['db'][$slave_id]['type']);
						$config['db'][$slave_id]['engine'] = 'innodb';
					}
					elseif (strpos($config['db'][$slave_id]['type'], 'mysql') !== false)
					{
						$config['db'][$slave_id]['engine'] = 'myisam';
					}
				}
			}
		}
		
		// Convert cache configuration.
		if (isset($db_info->use_object_cache))
		{
			$config['cache'][] = $db_info->use_object_cache;
		}
		
		// Convert FTP configuration.
		if (isset($ftp_info))
		{
			$config['ftp']['host'] = $ftp_info->ftp_host;
			$config['ftp']['port'] = $ftp_info->ftp_port;
			$config['ftp']['path'] = $ftp_info->ftp_root_path;
			$config['ftp']['user'] = $ftp_info->ftp_user;
			$config['ftp']['pasv'] = $ftp_info->ftp_pasv;
			$config['ftp']['sftp'] = $ftp_info->sftp === 'Y' ? true : false;
		}
		
		// Create new crypto keys.
		$config['crypto']['encryption_key'] = \Password::createSecureSalt(64, 'alnum');
		$config['crypto']['authentication_key'] = \Password::createSecureSalt(64, 'alnum');
		$config['crypto']['session_key'] = \Password::createSecureSalt(64, 'alnum');
		
		// Convert language configuration.
		if (isset($db_info->lang_type))
		{
			$config['locale']['default_lang'] = str_replace('jp', 'ja', strtolower($db_info->lang_type));
		}
		elseif (count($lang_selected))
		{
			$config['locale']['default_lang'] = array_first($lang_selected);
		}
		$config['locale']['enabled_lang'] = array_values($lang_selected);
		
		// Convert timezone configuration.
		$old_timezone = DateTime::getTimezoneOffsetByLegacyFormat($db_info->time_zone ?: '+0900');
		switch ($old_timezone)
		{
			case 32400:
				$config['locale']['default_timezone'] = 'Asia/Seoul'; break;
			default:
				$config['locale']['default_timezone'] = DateTime::getTimezoneNameByOffset($old_timezone);
		}
		$config['locale']['internal_timezone'] = intval(date('Z'));
		
		// Convert URL configuration.
		$default_url = $db_info->default_url;
		if (strpos($default_url, 'xn--') !== false)
		{
			$default_url = \Context::decodeIdna($default_url);
		}
		$config['url']['default'] = $default_url ?: \RX_BASEURL;
		$config['url']['http_port'] = $db_info->http_port ?: null;
		$config['url']['https_port'] = $db_info->https_port ?: null;
		$config['url']['ssl'] = $db_info->use_ssl ?: 'none';
		
		// Convert session configuration.
		$config['session']['delay'] = $db_info->delay_session === 'Y' ? true : false;
		$config['session']['use_db'] = $db_info->use_db_session === 'Y' ? true : false;
		
		// Convert view configuration.
		$config['view']['minify_scripts'] = $db_info->minify_scripts ?: 'common';
		$config['view']['use_gzip'] = (defined('__OB_GZHANDLER_ENABLE__') && constant('__OB_GZHANDLER_ENABLE__'));
		
		// Convert admin IP whitelist.
		if (isset($db_info->admin_ip_list) && is_array($db_info->admin_ip_list) && count($db_info->admin_ip_list))
		{
			$config['admin']['allow'] = array_values($db_info->admin_ip_list);
		}
		
		// Convert sitelock configuration.
		$config['lock']['locked'] = $db_info->use_sitelock === 'Y' ? true : false;
		$config['lock']['title'] = strval($db_info->sitelock_title);
		$config['lock']['message'] = strval($db_info->sitelock_message);
		if (!is_array($db_info->sitelock_whitelist))
		{
			$db_info->sitelock_whitelist = array_map('trim', explode(',', trim($db_info->sitelock_whitelist)));
		}
		if (!in_array('127.0.0.1', $db_info->sitelock_whitelist))
		{
			$db_info->sitelock_whitelist[] = '127.0.0.1';
		}
		$config['lock']['allow'] = array_values($db_info->sitelock_whitelist);
		
		// Convert debug configuration.
		$config['debug']['enabled'] = true;
		$config['debug']['log_errors'] = true;
		$config['debug']['log_queries'] = (\__DEBUG__ & 4) ? true : false;
		$config['debug']['log_slow_queries'] = floatval(\__LOG_SLOW_QUERY__);
		$config['debug']['log_slow_triggers'] = floatval(\__LOG_SLOW_TRIGGER__ * 1000);
		$config['debug']['log_slow_widgets'] = floatval(\__LOG_SLOW_WIDGET__ * 1000);
		
		// Convert embed filter configuration.
		if (is_array($db_info->embed_white_iframe))
		{
			$whitelist = array_unique(array_map(function($item) {
				return preg_match('@^https?://(.*)$@i', $item, $matches) ? $matches[1] : $item;
			}, $db_info->embed_white_iframe));
			natcasesort($whitelist);
			$config['embedfilter']['iframe'] = $whitelist;
		}
		if (is_array($db_info->embed_white_object))
		{
			$whitelist = array_unique(array_map(function($item) {
				return preg_match('@^https?://(.*)$@i', $item, $matches) ? $matches[1] : $item;
			}, $db_info->embed_white_object));
			natcasesort($whitelist);
			$config['embedfilter']['object'] = $whitelist;
		}
		
		// Convert miscellaneous configuration.
		$config['use_mobile_view'] = $db_info->use_mobile_view === 'Y' ? true : false;
		$config['use_prepared_statements'] = $db_info->use_prepared_statements === 'Y' ? true : false;
		$config['use_rewrite'] = $db_info->use_rewrite === 'Y' ? true : false;
		$config['use_sso'] = $db_info->use_sso === 'Y' ? true : false;
		
		// Return the new configuration.
		return $config;
	}
	
	/**
	 * Save the current system configuration.
	 * 
	 * @param array $config (optional)
	 * @return bool
	 */
	public static function save($config = null)
	{
		if ($config)
		{
			self::setAll($config);
		}
		$buff = '<?php' . "\n" . '// Rhymix System Configuration' . "\n" . 'return ' . self::serialize(self::$_config) . ';' . "\n";
		return \FileHandler::writeFile(RX_BASEDIR . 'files/config/config.php', $buff) ? true : false;
	}
	
	/**
	 * Serialize a value for insertion into a PHP-based configuration file.
	 * 
	 * @param mixed $value
	 * @return string
	 */
	public static function serialize($value)
	{
		if (is_object($value))
		{
			return '(object)' . self::serializeValue((array)$value);
		}
		elseif (is_array($value))
		{
			$value = var_export($value, true);
			$value = preg_replace('/array \(\n/', "array(\n", $value);
			$value = preg_replace('/=>\s+array\(\n/', "=> array(\n", $value);
			$value = preg_replace('/array\(\s*\n\s*\)/', 'array()', $value);
			$value = preg_replace_callback('/\n(\x20+)/', function($m) {
				return "\n" . str_repeat("\t", intval(strlen($m[1]) / 2));
			}, $value);
			$value = preg_replace('/\n(\t+)[0-9]+ => /', "\n\$1", $value);
			return $value;
		}
		else
		{
			return var_export($value, true);
		}
	}
}
