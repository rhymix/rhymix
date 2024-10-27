<?php

namespace Rhymix\Modules\Admin\Controllers\SystemConfig;

use Context;
use HTMLDisplayHandler;
use Rhymix\Framework\Cache;
use Rhymix\Framework\Config;
use Rhymix\Framework\DateTime;
use Rhymix\Framework\Exception;
use Rhymix\Framework\Lang;
use Rhymix\Framework\Router;
use Rhymix\Modules\Admin\Controllers\Base;

class Advanced extends Base
{
	/**
	 * Display Advanced Settings page
	 */
	public function dispAdminConfigAdvanced()
	{
		// Object cache
		$object_cache_types = Cache::getSupportedDrivers();
		$object_cache_type = Config::get('cache.type');
		if ($object_cache_type)
		{
			$cache_default_ttl = Config::get('cache.ttl');
			$cache_servers = Config::get('cache.servers');
		}
		else
		{
			$cache_config = array_first(Config::get('cache'));
			if ($cache_config)
			{
				$object_cache_type = preg_replace('/^memcache$/', 'memcached', preg_replace('/:.+$/', '', $cache_config));
			}
			else
			{
				$object_cache_type = 'dummy';
			}
			$cache_default_ttl = 86400;
			$cache_servers = Config::get('cache');
		}

		Context::set('object_cache_types', $object_cache_types);
		Context::set('object_cache_type', $object_cache_type);
		Context::set('cache_default_ttl', $cache_default_ttl);

		if ($cache_servers)
		{
			if (preg_match('!^(/.+)(#[0-9]+)?$!', array_first($cache_servers), $matches))
			{
				Context::set('object_cache_host', $matches[1]);
				Context::set('object_cache_port', 0);
				Context::set('object_cache_dbnum', $matches[2] ? substr($matches[2], 1) : 0);
			}
			else
			{
				Context::set('object_cache_host', parse_url(array_first($cache_servers), PHP_URL_HOST) ?: null);
				Context::set('object_cache_port', parse_url(array_first($cache_servers), PHP_URL_PORT) ?: null);
				Context::set('object_cache_user', parse_url(array_first($cache_servers), PHP_URL_USER) ?? '');
				Context::set('object_cache_pass', parse_url(array_first($cache_servers), PHP_URL_PASS) ?? '');
				$cache_dbnum = preg_replace('/[^\d]/', '', parse_url(array_first($cache_servers), PHP_URL_FRAGMENT) ?: parse_url(array_first($cache_servers), PHP_URL_PATH));
				Context::set('object_cache_dbnum', $cache_dbnum === '' ? 1 : intval($cache_dbnum));
			}
		}
		else
		{
			Context::set('object_cache_host', null);
			Context::set('object_cache_port', null);
			Context::set('object_cache_dbnum', 1);
		}
		Context::set('cache_truncate_method', Config::get('cache.truncate_method'));
		Context::set('cache_control_header', array_map('trim', explode(',', Config::get('cache.cache_control') ?? 'must-revalidate, no-store, no-cache')));

		// Thumbnail settings
		$oDocumentModel = getModel('document');
		$config = $oDocumentModel->getDocumentConfig();
		Context::set('thumbnail_target', $config->thumbnail_target ?: 'all');
		Context::set('thumbnail_type', $config->thumbnail_type ?: 'fill');
		Context::set('thumbnail_quality', $config->thumbnail_quality ?: 75);
		if ($config->thumbnail_type === 'none')
		{
			Context::set('thumbnail_target', 'none');
			Context::set('thumbnail_type', 'fill');
		}

		// Default and enabled languages
		Context::set('supported_lang', Lang::getSupportedList());
		Context::set('default_lang', Config::get('locale.default_lang'));
		Context::set('enabled_lang', Config::get('locale.enabled_lang'));
		Context::set('auto_select_lang', Config::get('locale.auto_select_lang'));

		// Default time zone
		Context::set('timezones', DateTime::getTimezoneList());
		Context::set('selected_timezone', Config::get('locale.default_timezone'));

		// Other settings
		Context::set('use_rewrite', Router::getRewriteLevel());
		Context::set('use_mobile_view', (config('mobile.enabled') !== null ? config('mobile.enabled') : config('use_mobile_view')) ? true : false);
		Context::set('tablets_as_mobile', config('mobile.tablets') ? true : false);
		Context::set('mobile_viewport', config('mobile.viewport') ?? HTMLDisplayHandler::DEFAULT_VIEWPORT);
		Context::set('use_ssl', Config::get('url.ssl'));
		Context::set('delay_session', Config::get('session.delay'));
		Context::set('delay_template_compile', Config::get('view.delay_compile'));
		Context::set('use_db_session', Config::get('session.use_db'));
		Context::set('partial_page_rendering', Config::get('view.partial_page_rendering') ?? 'internal_only');
		Context::set('manager_layout', Config::get('view.manager_layout'));
		Context::set('minify_scripts', Config::get('view.minify_scripts'));
		Context::set('concat_scripts', Config::get('view.concat_scripts'));
		Context::set('jquery_version', Config::get('view.jquery_version'));
		Context::set('outgoing_proxy', Config::get('other.proxy'));

		$this->setTemplateFile('config_advanced');
	}

	/**
	 * Update advanced configuration.
	 */
	public function procAdminUpdateAdvanced()
	{
		$vars = Context::getRequestVars();

		// Object cache
		if ($vars->object_cache_type)
		{
			if ($vars->object_cache_type === 'memcached' || $vars->object_cache_type === 'redis')
			{
				if (starts_with('unix:/', $vars->object_cache_host))
				{
					$cache_servers = array(substr($vars->object_cache_host, 5));
				}
				elseif (starts_with('/', $vars->object_cache_host))
				{
					$cache_servers = array($vars->object_cache_host);
				}
				else
				{
					if (trim($vars->object_cache_user) !== '' || trim($vars->object_cache_pass) !== '')
					{
						$auth = sprintf('%s:%s@', urlencode(trim($vars->object_cache_user)), urlencode(trim($vars->object_cache_pass)));
					}
					else
					{
						$auth = '';
					}
					$cache_servers = array($vars->object_cache_type . '://' . $auth . $vars->object_cache_host . ':' . intval($vars->object_cache_port));
				}

				if ($vars->object_cache_type === 'redis')
				{
					$cache_servers[0] .= '#' . intval($vars->object_cache_dbnum);
				}
			}
			else
			{
				$cache_servers = array();
			}
			if (!Cache::getDriverInstance($vars->object_cache_type, $cache_servers))
			{
				throw new Exception('msg_cache_handler_not_supported');
			}
			Config::set('cache', array(
				'type' => $vars->object_cache_type,
				'ttl' => intval($vars->cache_default_ttl ?: 86400),
				'servers' => $cache_servers,
			));
		}
		else
		{
			Config::set('cache', array());
		}

		// Cache truncate method
		if (in_array($vars->cache_truncate_method, array('delete', 'empty')))
		{
			Config::set('cache.truncate_method', $vars->cache_truncate_method);
		}

		$cache_control = ['no-cache'];
		foreach (['no-cache', 'no-store', 'must-revalidate'] as $val)
		{
			if (isset($vars->cache_control_header) && in_array($val, $vars->cache_control_header))
			{
				$cache_control[] = $val;
			}
		}
		Config::set('cache.cache_control', implode(', ', array_reverse($cache_control)));

		// Thumbnail settings
		$oDocumentModel = getModel('document');
		$document_config = $oDocumentModel->getDocumentConfig();
		$document_config->thumbnail_target = $vars->thumbnail_target ?: 'all';
		$document_config->thumbnail_type = $vars->thumbnail_type ?: 'fill';
		$document_config->thumbnail_quality = intval($vars->thumbnail_quality) ?: 75;
		$oModuleController = getController('module');
		$oModuleController->insertModuleConfig('document', $document_config);

		// Mobile view
		Config::set('mobile.enabled', $vars->use_mobile_view === 'Y');
		Config::set('mobile.tablets', $vars->tablets_as_mobile === 'Y');
		Config::set('mobile.viewport', utf8_trim($vars->mobile_viewport));
		if (Config::get('use_mobile_view') !== null)
		{
			Config::set('use_mobile_view', $vars->use_mobile_view === 'Y');
		}

		// Languages and time zone
		$enabled_lang = $vars->enabled_lang;
		if (!in_array($vars->default_lang, $enabled_lang ?: []))
		{
			$enabled_lang[] = $vars->default_lang;
		}
		Config::set('locale.default_lang', $vars->default_lang);
		Config::set('locale.enabled_lang', array_values($enabled_lang));
		Config::set('locale.auto_select_lang', $vars->auto_select_lang === 'Y');
		Config::set('locale.default_timezone', $vars->default_timezone);

		// Proxy
		$proxy = trim($vars->outgoing_proxy ?? '');
		if ($proxy !== '' && !preg_match('!^(https?|socks)://.+!', $proxy))
		{
			throw new Exception('msg_invalid_outgoing_proxy');
		}

		// Other settings
		Config::set('url.rewrite', intval($vars->use_rewrite));
		Config::set('use_rewrite', $vars->use_rewrite > 0);
		Config::set('session.delay', $vars->delay_session === 'Y');
		Config::set('session.use_db', $vars->use_db_session === 'Y');
		Config::set('view.partial_page_rendering', $vars->partial_page_rendering);
		Config::set('view.manager_layout', $vars->manager_layout ?: 'module');
		Config::set('view.minify_scripts', $vars->minify_scripts ?: 'common');
		Config::set('view.concat_scripts', $vars->concat_scripts ?: 'none');
		Config::set('view.delay_compile', intval($vars->delay_template_compile));
		Config::set('view.jquery_version', $vars->jquery_version == 3 ? 3 : 2);
		Config::set('other.proxy', $proxy);

		// Save
		if (!Config::save())
		{
			throw new Exception('msg_failed_to_save_config');
		}

		$this->setMessage('success_updated');
		$this->setRedirectUrl(Context::get('success_return_url') ?: getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdminConfigAdvanced'));
	}
}
