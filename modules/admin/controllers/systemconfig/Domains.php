<?php

namespace Rhymix\Modules\Admin\Controllers\SystemConfig;

use Context;
use ModuleModel;
use Rhymix\Framework\Cache;
use Rhymix\Framework\Config;
use Rhymix\Framework\DateTime;
use Rhymix\Framework\DB;
use Rhymix\Framework\Exception;
use Rhymix\Framework\Lang;
use Rhymix\Framework\Storage;
use Rhymix\Framework\URL;
use Rhymix\Modules\Admin\Controllers\Base;
use Rhymix\Modules\Admin\Models\Icon as IconModel;
use Rhymix\Modules\Admin\Models\Utility as UtilityModel;

class Domains extends Base
{
	/**
	 * Display domain settings page
	 */
	public function dispAdminConfigGeneral()
	{
		// Get domain list.
		$oModuleModel = getModel('module');
		$page = intval(Context::get('page')) ?: 1;
		$domain_list = $oModuleModel->getAllDomains(20, $page);
		Context::set('domain_list', $domain_list);
		Context::set('page_navigation', $domain_list->page_navigation);
		Context::set('page', $page);

		// Get index module info.
		$module_list = array();
		$oModuleModel = getModel('module');
		foreach ($domain_list->data as $domain)
		{
			if ($domain->index_module_srl && !isset($module_list[$domain->index_module_srl]))
			{
				$module_list[$domain->index_module_srl] = $oModuleModel->getModuleInfoByModuleSrl($domain->index_module_srl);
			}
		}
		Context::set('module_list', $module_list);

		// Get language list.
		Context::set('supported_lang', Lang::getSupportedList());

		$this->setTemplateFile('config_domains');
	}

	/**
	 * Display domain edit screen
	 */
	public function dispAdminInsertDomain()
	{
		// Get selected domain.
		$domain_srl = strval(Context::get('domain_srl'));
		$domain_info = null;
		if ($domain_srl !== '')
		{
			$domain_info = ModuleModel::getSiteInfo($domain_srl);
			if ($domain_info->domain_srl != $domain_srl)
			{
				throw new Exception('msg_domain_not_found');
			}
		}
		Context::set('domain_info', $domain_info);
		Context::set('domain_copy', false);

		// Get modules.
		if ($domain_info && $domain_info->index_module_srl)
		{
			$index_module_srl = $domain_info->index_module_srl;
		}
		else
		{
			$index_module_srl = '';
		}
		Context::set('index_module_srl', $index_module_srl);

		// Get language list.
		Context::set('supported_lang', Lang::getSupportedList());
		Context::set('enabled_lang', Config::get('locale.enabled_lang'));
		if ($domain_info && !empty($domain_info->settings->language))
		{
			$domain_lang = $domain_info->settings->language ?? 'default';
			$domain_force_lang = $domain_info->settings->force_language ?? false;
		}
		else
		{
			$domain_lang = 'default';
			$domain_force_lang = false;
		}
		Context::set('domain_lang', $domain_lang);
		Context::set('domain_force_lang', $domain_force_lang);

		// Get timezone list.
		Context::set('timezones', DateTime::getTimezoneList());
		if ($domain_info && !empty($domain_info->settings->timezone))
		{
			$domain_timezone = $domain_info->settings->timezone ?? '';
		}
		else
		{
			$domain_timezone = Config::get('locale.default_timezone');
		}
		Context::set('domain_timezone', $domain_timezone);

		// Get favicon and images.
		if ($domain_info)
		{
			Context::set('favicon_url', IconModel::getFaviconUrl($domain_info->domain_srl));
			Context::set('mobicon_url', IconModel::getMobiconUrl($domain_info->domain_srl));
			Context::set('default_image_url', IconModel::getDefaultImageUrl($domain_info->domain_srl));
			Context::set('color_scheme', $domain_info->settings->color_scheme ?? 'auto');
		}

		$this->setTemplateFile('config_domains_edit');
	}

	/**
	 * Display domain copy screen
	 */
	public function dispAdminCopyDomain()
	{
		// Get selected domain.
		$domain_srl = strval(Context::get('domain_srl'));
		$domain_info = ModuleModel::getSiteInfo($domain_srl);
		if ($domain_info->domain_srl != $domain_srl)
		{
			throw new Exception('msg_domain_not_found');
		}

		// Adjust some properties for copying.
		$domain_info->domain_srl = null;
		$domain_info->is_default_domain = 'N';
		Context::set('domain_info', $domain_info);
		Context::set('domain_copy', true);

		// Get modules.
		if ($domain_info && $domain_info->index_module_srl)
		{
			$index_module_srl = $domain_info->index_module_srl;
		}
		else
		{
			$index_module_srl = '';
		}
		Context::set('index_module_srl', $index_module_srl);

		// Get language list.
		Context::set('supported_lang', Lang::getSupportedList());
		Context::set('enabled_lang', Config::get('locale.enabled_lang'));
		if ($domain_info && !empty($domain_info->settings->language))
		{
			$domain_lang = $domain_info->settings->language ?? 'default';
			$domain_force_lang = $domain_info->settings->force_language ?? false;
		}
		else
		{
			$domain_lang = 'default';
			$domain_force_lang = false;
		}
		Context::set('domain_lang', $domain_lang);
		Context::set('domain_force_lang', $domain_force_lang);

		// Get timezone list.
		Context::set('timezones', DateTime::getTimezoneList());
		if ($domain_info && !empty($domain_info->settings->timezone))
		{
			$domain_timezone = $domain_info->settings->timezone;
		}
		else
		{
			$domain_timezone = Config::get('locale.default_timezone');
		}
		Context::set('domain_timezone', $domain_timezone);

		// Get favicon and images.
		if ($domain_info)
		{
			Context::set('favicon_url', IconModel::getFaviconUrl($domain_srl ?? 0));
			Context::set('mobicon_url', IconModel::getMobiconUrl($domain_srl ?? 0));
			Context::set('default_image_url', IconModel::getDefaultImageUrl($domain_srl ?? 0));
			Context::set('color_scheme', $domain_info->settings->color_scheme ?? 'auto');
		}

		$this->setTemplateFile('config_domains_edit');
	}

	/**
	 * Update domains configuration.
	 */
	public function procAdminUpdateDomainConfig()
	{
		$vars = Context::getRequestVars();

		// Validate the unregistered domain action.
		$valid_actions = array('redirect_301', 'redirect_302', 'display', 'block');
		if (!in_array($vars->unregistered_domain_action, $valid_actions))
		{
			$vars->unregistered_domain_action = 'redirect_301';
		}

		// Save system config.
		Config::set('url.unregistered_domain_action', $vars->unregistered_domain_action);
		Config::set('use_sso', $vars->use_sso === 'Y');
		if (!Config::save())
		{
			throw new Exception('msg_failed_to_save_config');
		}

		$this->setMessage('success_updated');
		$this->setRedirectUrl(Context::get('success_return_url') ?: getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdminConfigGeneral'));
	}

	/**
	 * Insert or update domain info.
	 */
	public function procAdminInsertDomain()
	{
		$vars = Context::getRequestVars();
		$domain_srl = intval($vars->domain_srl);
		$domain_info = null;
		if (strval($vars->domain_srl) !== '')
		{
			$domain_info = ModuleModel::getSiteInfo($domain_srl);
			if (!$domain_info || intval($domain_info->domain_srl) !== $domain_srl)
			{
				throw new Exception('msg_domain_not_found');
			}
		}

		// Copying?
		$copy_domain_srl = intval($vars->copy_domain_srl);
		if (!$domain_info && $copy_domain_srl > -1)
		{
			$copy_domain_info = ModuleModel::getSiteInfo($copy_domain_srl);
			if (!$copy_domain_info || intval($copy_domain_info->domain_srl) !== $copy_domain_srl)
			{
				throw new Exception('msg_domain_not_found');
			}
		}
		else
		{
			$copy_domain_info = null;
		}

		// Validate the title and subtitle.
		$vars->title = utf8_trim($vars->title);
		$vars->subtitle = utf8_trim($vars->subtitle);
		if ($vars->title === '')
		{
			throw new Exception('msg_site_title_is_empty');
		}

		// Validate the domain.
		if (!preg_match('@^https?://@', $vars->domain))
		{
			$vars->domain = 'http://' . $vars->domain;
		}
		try
		{
			$vars->domain = URL::getDomainFromUrl(strtolower($vars->domain));
		}
		catch (Exception $e)
		{
			$vars->domain = '';
		}
		if (!$vars->domain)
		{
			throw new Exception('msg_invalid_domain');
		}
		$existing_domain = getModel('module')->getSiteInfoByDomain($vars->domain);
		if ($existing_domain && $existing_domain->domain == $vars->domain && (!$domain_info || $existing_domain->domain_srl != $domain_info->domain_srl))
		{
			throw new Exception('msg_domain_already_exists');
		}

		// Validate the ports.
		if ($vars->http_port == 80 || !$vars->http_port)
		{
			$vars->http_port = 0;
		}
		if ($vars->https_port == 443 || !$vars->https_port)
		{
			$vars->https_port = 0;
		}
		if ($vars->http_port !== 0 && ($vars->http_port < 1 || $vars->http_port > 65535 || $vars->http_port == 443))
		{
			throw new Exception('msg_invalid_http_port');
		}
		if ($vars->https_port !== 0 && ($vars->https_port < 1 || $vars->https_port > 65535 || $vars->https_port == 80))
		{
			throw new Exception('msg_invalid_https_port');
		}

		// Validate the security setting.
		$valid_security_options = array('none', 'optional', 'always');
		if (!in_array($vars->domain_security, $valid_security_options))
		{
			$vars->domain_security = 'none';
		}

		// Validate the index module setting.
		$module_info = getModel('module')->getModuleInfoByModuleSrl(intval($vars->index_module_srl));
		if (!$module_info || $module_info->module_srl != $vars->index_module_srl)
		{
			throw new Exception('msg_invalid_index_module_srl');
		}

		// Validate the index document setting.
		if ($vars->index_document_srl)
		{
			$oDocument = getModel('document')->getDocument($vars->index_document_srl);
			if (!$oDocument || !$oDocument->isExists())
			{
				throw new Exception('msg_invalid_index_document_srl');
			}
			if (intval($oDocument->get('module_srl')) !== intval($vars->index_module_srl))
			{
				throw new Exception('msg_invalid_index_document_srl_module_srl');
			}
		}
		else
		{
			$vars->index_document_srl = 0;
		}

		// Validate the default language.
		$enabled_lang = Config::get('locale.enabled_lang');
		if ($vars->default_lang !== 'default' && !in_array($vars->default_lang, $enabled_lang))
		{
			throw new Exception('msg_lang_is_not_enabled');
		}
		$vars->force_lang = isset($vars->force_lang) ? toBool($vars->force_lang) : false;

		// Validate the default time zone.
		$timezone_list = DateTime::getTimezoneList();
		if ($vars->default_timezone !== 'default' && !isset($timezone_list[$vars->default_timezone]))
		{
			throw new Exception('msg_invalid_timezone');
		}

		// Clean up the meta keywords and description.
		$vars->meta_keywords = utf8_trim($vars->meta_keywords);
		$vars->meta_description = utf8_trim($vars->meta_description);

		// Clean up the header and footer scripts.
		$vars->html_header = UtilityModel::cleanHeaderAndFooterScripts($vars->html_header ?? '');
		$vars->html_footer = UtilityModel::cleanHeaderAndFooterScripts($vars->html_footer ?? '');

		// Validate the color scheme setting.
		$valid_color_scheme_options = array('auto', 'light', 'dark');
		if (!in_array($vars->color_scheme, $valid_color_scheme_options))
		{
			$vars->color_scheme = 'auto';
		}

		// Merge all settings into an array.
		$settings = array(
			'title' => $vars->title,
			'subtitle' => $vars->subtitle,
			'language' => $vars->default_lang,
			'force_language' => $vars->force_lang,
			'timezone' => $vars->default_timezone,
			'meta_keywords' => $vars->meta_keywords,
			'meta_description' => $vars->meta_description,
			'html_header' => $vars->html_header,
			'html_footer' => $vars->html_footer,
			'color_scheme' => $vars->color_scheme
		);

		// Get the DB object and begin a transaction.
		$oDB = DB::getInstance();
		$oDB->begin();

		// Insert or update the domain.
		if (!$domain_info)
		{
			$args = new \stdClass;
			$args->domain_srl = $domain_srl = getNextSequence();
			$args->domain = $vars->domain;
			$args->is_default_domain = $vars->is_default_domain === 'Y' ? 'Y' : 'N';
			$args->index_module_srl = $vars->index_module_srl;
			$args->index_document_srl = $vars->index_document_srl;
			$args->http_port = $vars->http_port;
			$args->https_port = $vars->https_port;
			$args->security = $vars->domain_security;
			$args->description = '';
			$args->settings = json_encode($settings);
			$output = executeQuery('module.insertDomain', $args);
			if (!$output->toBool())
			{
				return $output;
			}
		}
		else
		{
			$args = new \stdClass;
			$args->domain_srl = $domain_info->domain_srl;
			$args->domain = $vars->domain;
			if (isset($vars->is_default_domain))
			{
				$args->is_default_domain = $vars->is_default_domain === 'Y' ? 'Y' : 'N';
			}
			$args->index_module_srl = $vars->index_module_srl;
			$args->index_document_srl = $vars->index_document_srl;
			$args->http_port = $vars->http_port;
			$args->https_port = $vars->https_port;
			$args->security = $vars->domain_security;
			$args->settings = json_encode(array_merge(get_object_vars($domain_info->settings), $settings));
			$output = executeQuery('module.updateDomain', $args);
			if (!$output->toBool())
			{
				return $output;
			}
		}

		// If changing the default domain, set all other domains as non-default.
		if ($vars->is_default_domain === 'Y')
		{
			$args = new \stdClass;
			$args->not_domain_srl = $domain_srl;
			$output = executeQuery('module.updateDefaultDomain', $args);
			if (!$output->toBool())
			{
				return $output;
			}
		}

		// Save or copy the favicon.
		if ($vars->delete_favicon)
		{
			IconModel::deleteIcon($domain_srl, 'favicon.ico');
		}
		elseif (isset($vars->favicon) && is_array($vars->favicon))
		{
			IconModel::saveIcon($domain_srl, 'favicon.ico', $vars->favicon);
		}
		elseif ($copy_domain_info)
		{
			$source_filename = \RX_BASEDIR . 'files/attach/xeicon/' . ($copy_domain_info->domain_srl ? ($copy_domain_info->domain_srl . '/') : '') . 'favicon.ico';
			$target_filename = \RX_BASEDIR . 'files/attach/xeicon/' . $domain_srl . '/' . 'favicon.ico';
			Storage::copy($source_filename, $target_filename);
		}

		// Save or copy the mobile icon.
		if ($vars->delete_mobicon)
		{
			IconModel::deleteIcon($domain_srl, 'mobicon.png');
		}
		elseif (isset($vars->mobicon) && is_array($vars->mobicon))
		{
			IconModel::saveIcon($domain_srl, 'mobicon.png', $vars->mobicon);
		}
		elseif ($copy_domain_info)
		{
			$source_filename = \RX_BASEDIR . 'files/attach/xeicon/' . ($copy_domain_info->domain_srl ? ($copy_domain_info->domain_srl . '/') : '') . 'mobicon.png';
			$target_filename = \RX_BASEDIR . 'files/attach/xeicon/' . $domain_srl . '/' . 'mobicon.png';
			Storage::copy($source_filename, $target_filename);
		}

		// Save or copy the site default image.
		if ($vars->delete_default_image)
		{
			IconModel::deleteDefaultImage($domain_srl);
		}
		elseif (isset($vars->default_image) && is_array($vars->default_image))
		{
			IconModel::saveDefaultImage($domain_srl, $vars->default_image);
		}
		elseif ($copy_domain_info)
		{
			$source_filename = \RX_BASEDIR . 'files/attach/xeicon/' . ($copy_domain_info->domain_srl ? ($copy_domain_info->domain_srl . '/') : '') . 'default_image.php';
			$target_filename = \RX_BASEDIR . 'files/attach/xeicon/' . $domain_srl . '/' . 'default_image.php';
			if (Storage::copy($source_filename, $target_filename))
			{
				$info = Storage::readPHPData($target_filename);
				if ($info && $info['filename'])
				{
					$source_image = \RX_BASEDIR . $info['filename'];
					$target_image = \RX_BASEDIR . 'files/attach/xeicon/' . $domain_srl . '/' . basename($info['filename']);
					if (Storage::copy($source_image, $target_image))
					{
						$info['filename'] = substr($target_image, strlen(\RX_BASEDIR));
						$info = Storage::writePHPData($target_filename, $info);
					}
				}
				else
				{
					Storage::delete($target_filename);
				}
			}
		}

		// Update system configuration to match the default domain.
		if ($domain_info && $domain_info->is_default_domain === 'Y')
		{
			$domain_info->domain = $vars->domain;
			$domain_info->http_port = $vars->http_port;
			$domain_info->https_port = $vars->https_port;
			$domain_info->security = $vars->domain_security;
			Config::set('url.default', Context::getDefaultUrl($domain_info));
			Config::set('url.http_port', $vars->http_port ?: null);
			Config::set('url.https_port', $vars->https_port ?: null);
			Config::set('url.ssl', $vars->domain_security);
			if (!Config::save())
			{
				throw new Exception('msg_failed_to_save_config');
			}
		}

		// Commit.
		$oDB->commit();

		// Clear cache.
		Cache::clearGroup('site_and_module');

		// Redirect to the domain list.
		$this->setRedirectUrl(Context::get('success_return_url') ?: getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdminConfigGeneral'));
	}

	/**
	 * Delete a domain.
	 */
	public function procAdminDeleteDomain()
	{
		// Get selected domain.
		$domain_srl = strval(Context::get('domain_srl'));
		if ($domain_srl === '')
		{
			throw new Exception('msg_domain_not_found');
		}
		$domain_info = getModel('module')->getSiteInfo($domain_srl);
		if ($domain_info->domain_srl != $domain_srl)
		{
			throw new Exception('msg_domain_not_found');
		}
		if ($domain_info->is_default_domain === 'Y')
		{
			throw new Exception('msg_cannot_delete_default_domain');
		}

		// Delete the domain.
		$args = new \stdClass;
		$args->domain_srl = $domain_srl;
		$output = executeQuery('module.deleteDomain', $args);
		if (!$output->toBool())
		{
			return $output;
		}

		// Delete icons and default image for the domain.
		IconModel::deleteIcon($domain_srl, 'favicon.ico');
		IconModel::deleteIcon($domain_srl, 'mobicon.png');
		IconModel::deleteDefaultImage($domain_srl);

		// Clear cache.
		Cache::clearGroup('site_and_module');
	}
}
