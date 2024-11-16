<?php

namespace Rhymix\Modules\Admin\Controllers;

use BaseObject;
use Context;
use AddonAdminModel;
use LayoutModel;
use ModuleModel;
use WidgetModel;
use Rhymix\Framework\DB;

class ServerEnv extends Base
{
	/**
	 * Display server environment information.
	 */
	public function dispAdminViewServerEnv()
	{
		$info = array();
		$skip = array(
			'phpext' => array('core', 'session', 'spl', 'standard', 'date', 'ctype', 'tokenizer', 'apache2handler', 'filter', 'reflection'),
			'module' => array('addon', 'admin', 'adminlogging', 'advanced_mailer', 'autoinstall', 'board', 'comment', 'communication', 'counter', 'document', 'editor', 'extravar', 'file', 'importer', 'install', 'integration_search', 'krzip', 'layout', 'member', 'menu', 'message', 'module', 'ncenterlite', 'opage', 'page', 'point', 'poll', 'rss', 'session', 'spamfilter', 'tag', 'trackback', 'trash', 'widget'),
			'addon' => array('adminlogging', 'autolink', 'counter', 'member_extra_info', 'point_level_icon', 'photoswipe', 'resize_image'),
			'layout' => array('default', 'user_layout', 'xedition'),
			'widget' => array('content', 'counter_status', 'language_select', 'login_info', 'mcontent', 'pollWidget'),
			'widgetstyle' => array('simple'),
		);

		// Basic environment
		$info[] = '[Basic Information]';
		$info['rhymix_version'] = RX_VERSION;
		$info['date'] = \Rhymix\Framework\DateTime::formatTimestampForCurrentUser('Y-m-d H:i:s O') . ' (' . gmdate('Y-m-d H:i:s') . ' UTC)';
		$info['php'] = sprintf('%s (%d-bit)', phpversion(), PHP_INT_SIZE * 8);
		$info['server'] = $_SERVER['SERVER_SOFTWARE'];
		$info['os'] = sprintf('%s %s', php_uname('s'), php_uname('r'));
		$info['sapi'] = php_sapi_name();
		$info['baseurl'] = Context::getRequestUri();
		$info['basedir'] = RX_BASEDIR;
		$info['owner'] = sprintf('%s (%d:%d)', get_current_user(), getmyuid(), getmygid());
		if (function_exists('posix_getpwuid') && function_exists('posix_geteuid') && $user = @posix_getpwuid(posix_geteuid()))
		{
			$info['user'] = sprintf('%s (%d:%d)', $user['name'], $user['uid'], $user['gid']);
		}
		else
		{
			$info['user'] = 'unknown';
		}
		$info['ssl'] = Context::get('site_module_info')->security ?: Context::getDbInfo()->use_ssl;
		$info[] = '';

		// System settings
		$info[] = '[System Settings]';
		$info['db.type'] = preg_replace('/^mysql.+/', 'mysql', config('db.master.type'));
		$db_extra_info = array();
		if (config('db.master.engine')) $db_extra_info[] = config('db.master.engine');
		if (config('db.master.charset')) $db_extra_info[] = config('db.master.charset');
		if (count($db_extra_info))
		{
			$info['db.type'] .= ' (' . implode(', ', $db_extra_info) . ')';
		}
		$info['db.version'] = DB::getInstance()->db_version;
		if (preg_match('/\d+\.\d+\.\d+-MariaDB.*$/', $info['db.version'], $matches))
		{
			$info['db.version'] = $matches[0];
		}
		$info['cache.type'] = config('cache.type') ?: 'none';
		$info['file.folder_structure'] = config('file.folder_structure');
		$info['file.umask'] = config('file.umask');
		$info['url.rewrite'] = \Rhymix\Framework\Router::getRewriteLevel();
		$info['locale.default_lang'] = config('locale.default_lang');
		$info['locale.default_timezone'] = config('locale.default_timezone');
		$info['locale.internal_timezone'] = config('locale.internal_timezone');
		$info['mobile.enabled'] = config('mobile.enabled') ? 'true' : 'false';
		$info['mobile.tablets'] = config('mobile.tablets') ? 'true' : 'false';
		$info['session.delay'] = config('session.delay') ? 'true' : 'false';
		$info['session.use_db'] = config('session.use_db') ? 'true' : 'false';
		$info['session.use_ssl'] = config('session.use_ssl') ? 'true' : 'false';
		$info['session.use_ssl_cookies'] = config('session.use_ssl_cookies') ? 'true' : 'false';
		$info['view.concat_scripts'] = config('view.concat_scripts');
		$info['view.minify_scripts'] = config('view.minify_scripts');
		$info['use_sso'] = config('use_sso') ? 'true' : 'false';
		$info[] = '';

		// PHP settings
		$ini_info = ini_get_all();
		$info[] = '[PHP Settings]';
		$info['session.auto_start'] = $ini_info['session.auto_start']['local_value'];
		$info['session.gc_maxlifetime'] = $ini_info['session.gc_maxlifetime']['local_value'];
		$info['session.save_handler'] = $ini_info['session.save_handler']['local_value'];
		$info['max_file_uploads'] = $ini_info['max_file_uploads']['local_value'];
		$info['memory_limit'] = $ini_info['memory_limit']['local_value'];
		$info['post_max_size'] = $ini_info['post_max_size']['local_value'];
		$info['upload_max_filesize'] = $ini_info['upload_max_filesize']['local_value'];
		$info['extensions'] = array();
		foreach(get_loaded_extensions() as $ext)
		{
			$ext = strtolower($ext);
			if (!in_array($ext, $skip['phpext']))
			{
				$info['extensions'][] = $ext;
			}
		}
		natcasesort($info['extensions']);
		$info[] = '';

		// Modules
		$info[] = '[Modules]';
		$info['module'] = array();
		$module_list = ModuleModel::getModuleList() ?: array();
		foreach ($module_list as $module)
		{
			if (!in_array($module->module, $skip['module']))
			{
				$moduleInfo = ModuleModel::getModuleInfoXml($module->module);
				if ($moduleInfo->version === 'RX_VERSION')
				{
					$info['module'][] = $module->module;
				}
				else
				{
					$info['module'][] = sprintf('%s (%s)', $module->module, $moduleInfo->version);
				}
			}
		}
		natcasesort($info['module']);
		$info[] = '';

		// Addons
		$info[] = '[Addons]';
		$info['addon'] = array();
		$oAddonAdminModel = AddonAdminModel::getInstance();
		$addon_list = $oAddonAdminModel->getAddonList() ?: array();
		foreach ($addon_list as $addon)
		{
			if (!in_array($addon->addon, $skip['addon']))
			{
				$addonInfo = $oAddonAdminModel->getAddonInfoXml($addon->addon);
				if ($addonInfo->version === 'RX_VERSION')
				{
					$info['addon'][] = $addon->addon;
				}
				else
				{
					$info['addon'][] = sprintf('%s (%s)', $addon->addon, $addonInfo->version);
				}
			}
		}
		natcasesort($info['addon']);
		$info[] = '';

		// Layouts
		$info[] = '[Layouts]';
		$info['layout'] = array();
		$oLayoutModel = LayoutModel::getInstance();
		$layout_list = $oLayoutModel->getDownloadedLayoutList() ?: array();
		foreach($layout_list as $layout)
		{
			if (!in_array($layout->layout, $skip['layout']))
			{
				$layoutInfo = $oLayoutModel->getLayoutInfo($layout->layout);
				if ($layoutInfo->version === 'RX_VERSION')
				{
					$info['layout'][] = $layout->layout;
				}
				else
				{
					$info['layout'][] = sprintf('%s (%s)', $layout->layout, $layoutInfo->version);
				}
			}
		}
		natcasesort($info['layout']);
		$info[] = '';

		// Widgets
		$info[] = '[Widgets]';
		$info['widget'] = array();
		$oWidgetModel = WidgetModel::getInstance();
		$widget_list = $oWidgetModel->getDownloadedWidgetList() ?: array();
		foreach ($widget_list as $widget)
		{
			if (!in_array($widget->widget, $skip['widget']))
			{
				$widgetInfo = $oWidgetModel->getWidgetInfo($widget->widget);
				if ($widgetInfo->version === 'RX_VERSION')
				{
					$info['widget'][] = $widget->widget;
				}
				else
				{
					$info['widget'][] = sprintf('%s (%s)', $widget->widget, $widgetInfo->version);
				}
			}
		}
		natcasesort($info['widget']);
		$info[] = '';

		// Widgetstyles
		$info[] = '[Widgetstyles]';
		$info['widgetstyle'] = array();
		$oWidgetModel = WidgetModel::getInstance();
		$widgetstyle_list = $oWidgetModel->getDownloadedWidgetStyleList() ?: array();
		foreach ($widgetstyle_list as $widgetstyle)
		{
			if (!in_array($widgetstyle->widgetStyle, $skip['widgetstyle']))
			{
				$widgetstyleInfo = $oWidgetModel->getWidgetStyleInfo($widgetstyle->widgetStyle);
				if ($widgetstyleInfo->version === 'RX_VERSION')
				{
					$info['widgetstyle'][] = $widgetstyle->widgetStyle;
				}
				else
				{
					$info['widgetstyle'][] = sprintf('%s (%s)', $widgetstyle->widgetStyle, $widgetstyleInfo->version);
				}
			}
		}
		natcasesort($info['widgetstyle']);
		$info[] = '';
		$str_info = '';

		// Convert to string.
		foreach ($info as $key => $value)
		{
			if (is_array($value))
			{
				$value = implode(', ', $value) ?: "no additional {$key}s";
			}

			if (is_int($key) || ctype_digit($key))
			{
				$str_info .= "$value\n";
			}
			else
			{
				$str_info .= "$key : $value\n";
			}
		}

		Context::set('str_info', $str_info);
		$this->setTemplateFile('server_env.html');
	}

	/**
	 * Method to test if URL rewriting is properly configured in the web server.
	 */
	public function dispAdminRewriteTest()
	{
		$test = intval(Context::get('test'));
		Context::setResponseMethod('JSON');
		$this->add('result', $test * 42);
	}

	/**
	 * Clear APCU cache.
	 */
	public function procAdminClearApcu()
	{
		if (function_exists('apcu_clear_cache') && apcu_clear_cache())
		{
			return new BaseObject(0, 'success_updated');
		}
		else
		{
			return new BaseObject(-1, 'apcu_clear_cache_function_not_found');
		}
	}

	/**
	 * Clear opcache.
	 */
	public function procAdminClearOpcache()
	{
		if (function_exists('opcache_reset') && opcache_reset())
		{
			return new BaseObject(0, 'success_updated');
		}
		else
		{
			return new BaseObject(-1, 'opcache_reset_function_not_found');
		}
	}
}
