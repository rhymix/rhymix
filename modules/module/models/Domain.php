<?php

namespace Rhymix\Modules\Module\Models;

use Rhymix\Framework\Cache;
use Rhymix\Framework\DB;
use Rhymix\Framework\URL;
use Rhymix\Framework\Helpers\DBResultHelper;
use Context;

#[\AllowDynamicProperties]
class Domain extends ModuleInfo
{
	/**
	 * Attributes to match database columns.
	 */
	public ?int $domain_srl = 0;
	public string $domain = '';
	public string $is_default_domain = 'N';
	public bool $is_default_replaced = false;
	public int $index_module_srl = 0;
	public int $index_document_srl = 0;
	public int $default_layout_srl = 0;
	public int $default_mlayout_srl = 0;
	public int $default_menu_srl = 0;
	public string $default_language = '';
	public ?int $http_port = null;
	public ?int $https_port = null;
	public ?int $site_srl = 0;
	public string $security = 'none';
	public $description;
	public $settings;
	public $regdate;

	/**
	 * Decode settings when a row is loaded from DB.
	 */
	public function __construct()
	{
		if (isset($this->settings))
		{
			$this->settings = json_decode($this->settings);
		}
		else
		{
			$this->settings = new \stdClass;
		}

		if (!isset($this->settings->color_scheme))
		{
			$this->settings->color_scheme = 'auto';
		}

		if (isset($this->settings->language) && $this->settings->language)
		{
			$this->default_language = $this->settings->language;
		}
		else
		{
			$this->default_language = config('locale.default_lang');
		}

		if (!isset($this->site_srl))
		{
			$this->site_srl = 0;
		}

		parent::__construct();
	}

	/**
	 * Get the full URL prefix for this domain.
	 * Example: https://www.example.com:8443
	 *
	 * @return string
	 */
	public function getUrlPrefix(): string
	{
		$prefix = ($this->security === 'always' ? 'https://' : 'http://') . $this->domain;
		if ($this->security === 'always' && !empty($this->https_port))
		{
			$prefix .= ':' . $this->https_port;
		}
		if ($this->security !== 'always' && !empty($this->http_port))
		{
			$prefix .= ':' . $this->http_port;
		}
		return $prefix;
	}

	/**
	 * Get a domain by its domain_srl.
	 *
	 * @param int $domain_srl
	 * @return ?self
	 */
	public static function getDomain(int $domain_srl): ?self
	{
		$cache_key = 'site_and_module:domain_info:domain_srl:' . $domain_srl;
		$domain_info = Cache::get($cache_key);
		if (!($domain_info instanceof self))
		{
			$output = executeQueryArray('module.getDomainInfo', ['domain_srl' => $domain_srl], [], self::class);
			if ($output->data)
			{
				$domain_info = array_first($output->data);
				Cache::set($cache_key, $domain_info, 0, true);
			}
		}

		return $domain_info;
	}

	/**
	 * Get a domain by domain name.
	 *
	 * @param string $domain_name
	 * @return ?self
	 */
	public static function getDomainByDomainName(string $domain_name): ?self
	{
		if ($domain_name === '')
		{
			return null;
		}
		if (str_contains($domain_name, 'xn--'))
		{
			$domain_name = URL::decodeIdna($domain_name);
		}
		$domain_name = strtolower($domain_name);
		$cache_key = 'site_and_module:domain_info:domain_name:' . $domain_name;
		$domain_info = Cache::get($cache_key);
		if (!($domain_info instanceof self))
		{
			$output = executeQueryArray('module.getDomainInfo', ['domain' => $domain_name], [], self::class);
			if ($output->data)
			{
				$domain_info = array_first($output->data);
				Cache::set($cache_key, $domain_info, 0, true);
			}
		}

		return $domain_info;
	}

	/**
	 * Get the default domain.
	 *
	 * @return ?self
	 */
	public static function getDefaultDomain(): ?self
	{
		$cache_key = 'site_and_module:domain_info:default_domain';
		$domain_info = Cache::get($cache_key);
		if (!($domain_info instanceof self))
		{
			$output = executeQueryArray('module.getDomainInfo', ['is_default_domain' => 'Y'], [], self::class);
			if ($output->data)
			{
				$domain_info = array_first($output->data);
				Cache::set($cache_key, $domain_info, 0, true);
			}
		}

		return $domain_info;
	}

	/**
	 * Get a domain with associated module information.
	 *
	 * This method will create a default domain if it does not exist,
	 * and add module extra variables to the domain object.
	 *
	 * @param ?string $domain_name
	 * @return self
	 */
	public static function getDefaultDomainWithModuleInfo(?string $domain_name = null): self
	{
		// Use the current domain name if not provided.
		if (!$domain_name)
		{
			$domain_name = URL::getCurrentDomain();
		}

		// Try to get domain info by domain name.
		$domain_info = $domain_name ? self::getDomainByDomainName($domain_name) : null;

		// Fall back to the default domain, or create one if it does not exist.
		if (!$domain_info)
		{
			$domain_info = self::getDefaultDomain();
			if (!$domain_info)
			{
				$domain_info = \Module::getInstance()->migrateDomains();
			}
			$domain_info->is_default_replaced = true;
		}

		// Fill in module extra vars and return.
		if ($domain_info->module_srl)
		{
			return array_first(ModuleInfo::addExtraVars([$domain_info]));
		}
		else
		{
			return $domain_info;
		}
	}

	/**
	 * Get the URL prefix of the domain that a module instance belongs to.
	 *
	 * The result is a proper URL including the scheme and port.
	 * If the module instance does not belong to any domain, null is returned.
	 *
	 * @param int $module_srl
	 * @return ?string
	 */
	public static function getDomainPrefixByModuleSrl(int $module_srl): ?string
	{
		$module_srl = intval($module_srl);
		if (isset(ModuleCache::$module_srl2domain[$module_srl]))
		{
			return ModuleCache::$module_srl2domain[$module_srl];
		}

		$prefix = Cache::get('site_and_module:module_srl_prefix:' . $module_srl);
		if (isset($prefix))
		{
			ModuleCache::$module_srl2domain[$module_srl] = $prefix;
			return $prefix;
		}

		$output = executeQuery('module.getModuleInfoByModuleSrl', [
			'module_srl' => $module_srl,
			'include_domain_info' => true,
		]);
		if (is_object($output->data))
		{
			$info = $output->data;
			if (!$info->domain_srl || $info->domain_srl == -1 || !isset($info->domain))
			{
				$prefix = '';
			}
			else
			{
				$prefix = ($info->security === 'always' ? 'https://' : 'http://') . $info->domain;
				if ($info->security === 'always' && !empty($info->https_port))
				{
					$prefix .= ':' . $info->https_port;
				}
				if ($info->security !== 'always' && !empty($info->http_port))
				{
					$prefix .= ':' . $info->http_port;
				}
			}
			Cache::set('site_and_module:module_srl_prefix:' . $module_srl, $prefix, 0, true);
			ModuleCache::$module_srl2domain[$module_srl] = $prefix;
			return $prefix;
		}
		else
		{
			return null;
		}
	}

	/**
	 * Get the list of domains.
	 *
	 * @param int $count
	 * @param int $page
	 * @return DBResultHelper
	 */
	public static function getDomainList($count = 20, $page = 1): DBResultHelper
	{
		$args = new \stdClass;
		$args->list_count = $count;
		$args->page = $page;
		return executeQueryArray('module.getDomains', $args, [], self::class);
	}
}
