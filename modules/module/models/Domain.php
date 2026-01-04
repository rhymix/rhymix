<?php

namespace Rhymix\Modules\Module\Models;

use Rhymix\Framework\Cache;
use Rhymix\Framework\DB;
use Rhymix\Framework\URL;
use Rhymix\Framework\Helpers\DBResultHelper;
use Context;

#[\AllowDynamicProperties]
class Domain
{
	/**
	 * Attributes to match database columns.
	 */
	public int $domain_srl;
	public string $domain;
	public string $is_default_domain = 'N';
	public bool $is_default_replaced = false;
	public int $site_srl = 0;
	public int $index_module_srl = 0;
	public int $index_document_srl = 0;
	public int $default_layout_srl = 0;
	public int $default_mlayout_srl = 0;
	public int $default_menu_srl = 0;
	public string $default_language = '';
	public ?int $http_port = null;
	public ?int $https_port = null;
	public string $security = 'none';
	public string $description = '';
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
