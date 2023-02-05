<?php

namespace Rhymix\Modules\Admin\Controllers\SystemConfig;

use Context;
use ModuleController;
use ModuleModel;
use Rhymix\Framework\Config;
use Rhymix\Framework\Exception;
use Rhymix\Modules\Admin\Controllers\Base;

class SEO extends Base
{
	/**
	 * Display Debug Settings page
	 */
	public function dispAdminConfigSEO()
	{
		// Meta keywords and description
		$config = ModuleModel::getModuleConfig('module');
		Context::set('site_meta_keywords', escape($config->meta_keywords ?? ''));
		Context::set('site_meta_description', escape($config->meta_description ?? ''));

		// Titles
		Context::set('seo_main_title', escape(Config::get('seo.main_title') ?: '$SITE_TITLE - $SITE_SUBTITLE'));
		Context::set('seo_subpage_title', escape(Config::get('seo.subpage_title') ?: '$SITE_TITLE - $SUBPAGE_TITLE'));
		Context::set('seo_document_title', escape(Config::get('seo.document_title') ?: '$SITE_TITLE - $DOCUMENT_TITLE'));

		// OpenGraph metadata
		Context::set('og_enabled', Config::get('seo.og_enabled'));
		Context::set('og_extract_description', Config::get('seo.og_extract_description'));
		Context::set('og_extract_images', Config::get('seo.og_extract_images'));
		Context::set('og_extract_hashtags', Config::get('seo.og_extract_hashtags'));
		Context::set('og_use_nick_name', Config::get('seo.og_use_nick_name'));
		Context::set('og_use_timestamps', Config::get('seo.og_use_timestamps'));
		Context::set('twitter_enabled', Config::get('seo.twitter_enabled'));

		$this->setTemplateFile('config_seo');
	}

	/**
	 * Update SEO configuration.
	 */
	public function procAdminUpdateSEO()
	{
		$vars = Context::getRequestVars();

		$args = new \stdClass;
		$args->meta_keywords = $vars->site_meta_keywords ? implode(', ', array_map('trim', explode(',', $vars->site_meta_keywords))) : '';
		$args->meta_description = trim(utf8_normalize_spaces($vars->site_meta_description));
		$oModuleController = ModuleController::getInstance();
		$oModuleController->updateModuleConfig('module', $args);

		Config::set('seo.main_title', trim(utf8_normalize_spaces($vars->seo_main_title)));
		Config::set('seo.subpage_title', trim(utf8_normalize_spaces($vars->seo_subpage_title)));
		Config::set('seo.document_title', trim(utf8_normalize_spaces($vars->seo_document_title)));

		Config::set('seo.og_enabled', $vars->og_enabled === 'Y');
		Config::set('seo.og_extract_description', $vars->og_extract_description === 'Y');
		Config::set('seo.og_extract_images', $vars->og_extract_images === 'Y');
		Config::set('seo.og_extract_hashtags', $vars->og_extract_hashtags === 'Y');
		Config::set('seo.og_use_nick_name', $vars->og_use_nick_name === 'Y');
		Config::set('seo.og_use_timestamps', $vars->og_use_timestamps === 'Y');
		Config::set('seo.twitter_enabled', $vars->twitter_enabled === 'Y');

		// Save
		if (!Config::save())
		{
			throw new Exception('msg_failed_to_save_config');
		}

		$this->setMessage('success_updated');
		$this->setRedirectUrl(Context::get('success_return_url') ?: getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdminConfigSEO'));
	}
}
