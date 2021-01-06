<?php

namespace Rhymix\Framework\Parsers;

use Rhymix\Framework\Config;
use Rhymix\Framework\DateTime;
use Rhymix\Framework\Security;
use Rhymix\Framework\Storage;

/**
 * Config parser class for XE compatibility.
 */
class ConfigParser
{
	/**
	 * Convert previous configuration files to the current format and return it.
	 * 
	 * @return array
	 */
	public static function convert()
	{
		// Load DB info file.
		if (file_exists(\RX_BASEDIR . Config::$old_db_config_filename))
		{
			ob_start();
			include \RX_BASEDIR . Config::$old_db_config_filename;
			ob_end_clean();
		}
		else
		{
			return array();
		}
		
		// Load FTP info file.
		if (file_exists(\RX_BASEDIR . Config::$old_ftp_config_filename))
		{
			ob_start();
			include \RX_BASEDIR . Config::$old_ftp_config_filename;
			ob_end_clean();
		}
		
		// Load selected language file.
		if (file_exists(\RX_BASEDIR . Config::$old_lang_config_filename))
		{
			$lang_selected = array();
			$lang_selected_raw = file_get_contents(\RX_BASEDIR . Config::$old_lang_config_filename);
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
		$config = (include \RX_BASEDIR . Config::$default_config_filename);
		
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
		
		if (isset($db_info->slave_db) && is_array($db_info->slave_db) && count($db_info->slave_db))
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
			if (!is_array($db_info->use_object_cache))
			{
				$db_info->use_object_cache = array($db_info->use_object_cache);
			}
			$config['cache']['type'] = preg_replace('/^memcache$/', 'memcached', preg_replace('/:.+$/', '', array_first($db_info->use_object_cache)));
			$config['cache']['ttl'] = 86400;
			$config['cache']['servers'] = in_array($config['cache']['type'], array('memcached', 'redis')) ? $db_info->use_object_cache : array();
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
		$config['crypto']['encryption_key'] = Security::getRandom(64, 'alnum');
		$config['crypto']['authentication_key'] = $db_info->secret_key ?: Security::getRandom(64, 'alnum');
		$config['crypto']['session_key'] = Security::getRandom(64, 'alnum');
		
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
		$config['url']['default'] = $default_url ?: (\RX_SSL ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . \RX_BASEURL;
		$config['url']['http_port'] = $db_info->http_port ?: null;
		$config['url']['https_port'] = $db_info->https_port ?: null;
		$config['url']['ssl'] = ($db_info->use_ssl === 'none') ? 'none' : 'always';
		
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
			$db_info->sitelock_whitelist = $db_info->sitelock_whitelist ? array_map('trim', explode(',', trim($db_info->sitelock_whitelist))) : array();
		}
		if (!in_array('127.0.0.1', $db_info->sitelock_whitelist))
		{
			$db_info->sitelock_whitelist[] = '127.0.0.1';
		}
		$config['lock']['allow'] = array_values($db_info->sitelock_whitelist);
		
		// Convert media filter configuration.
		if (is_array($db_info->embed_white_iframe))
		{
			$whitelist = array_unique(array_map(function($item) {
				return preg_match('@^https?://(.*)$@i', $item, $matches) ? $matches[1] : $item;
			}, $db_info->embed_white_iframe));
			natcasesort($whitelist);
			$config['mediafilter']['iframe'] = $whitelist;
		}
		if (is_array($db_info->embed_white_object))
		{
			$whitelist = array_unique(array_map(function($item) {
				return preg_match('@^https?://(.*)$@i', $item, $matches) ? $matches[1] : $item;
			}, $db_info->embed_white_object));
			natcasesort($whitelist);
			$config['mediafilter']['object'] = $whitelist;
		}
		
		// Convert miscellaneous configuration.
		$config['file']['folder_structure'] = 1;
		$config['file']['umask'] = Storage::recommendUmask();
		$config['mobile']['enabled'] = $db_info->use_mobile_view === 'N' ? false : true;
		$config['use_rewrite'] = $db_info->use_rewrite === 'Y' ? true : false;
		$config['use_sso'] = $db_info->use_sso === 'Y' ? true : false;
		
		// Copy other configuration.
		unset($db_info->master_db, $db_info->slave_db);
		unset($db_info->lang_type, $db_info->time_zone);
		unset($db_info->default_url, $db_info->http_port, $db_info->https_port, $db_info->use_ssl);
		unset($db_info->delay_session, $db_info->use_db_session);
		unset($db_info->minify_scripts, $db_info->admin_ip_list);
		unset($db_info->use_sitelock, $db_info->sitelock_title, $db_info->sitelock_message, $db_info->sitelock_whitelist);
		unset($db_info->embed_white_iframe, $db_info->embed_white_object);
		unset($db_info->use_object_cache, $db_info->use_mobile_view, $db_info->use_prepared_statements);
		unset($db_info->use_rewrite, $db_info->use_sso);
		foreach ($db_info as $key => $value)
		{
			$config['other'][$key] = $value;
		}
		
		// Return the new configuration.
		return $config;
	}
}
