<?php

/**
 * Preserved for backward compatibility
 *
 * @deprecated
 */
class AdminAdminModel extends Admin
{
	/**
	 * Return theme module skin list
	 * @return array
	 */
	public function getModulesSkinList()
	{
		if($GLOBALS['__ThemeModuleSkin__']['__IS_PARSE__'])
		{
			return $GLOBALS['__ThemeModuleSkin__'];
		}
		$searched_list = FileHandler::readDir('./modules');
		sort($searched_list);

		$searched_count = count($searched_list);
		if(!$searched_count)
		{
			return;
		}

		$exceptionModule = array('editor', 'poll', 'homepage', 'textyle');

		$oModuleModel = getModel('module');
		foreach($searched_list as $val)
		{
			$skin_list = $oModuleModel->getSkins(RX_BASEDIR . 'modules/' . $val);

			if(is_array($skin_list) && count($skin_list) > 0 && !in_array($val, $exceptionModule))
			{
				if(!$GLOBALS['__ThemeModuleSkin__'][$val])
				{
					$GLOBALS['__ThemeModuleSkin__'][$val] = array();
					$moduleInfo = $oModuleModel->getModuleInfoXml($val);
					$GLOBALS['__ThemeModuleSkin__'][$val]['title'] = $moduleInfo->title;
					$GLOBALS['__ThemeModuleSkin__'][$val]['skins'] = array();
				}
				$GLOBALS['__ThemeModuleSkin__'][$val]['skins'] = array_merge($GLOBALS['__ThemeModuleSkin__'][$val]['skins'], $skin_list);
			}
		}
		$GLOBALS['__ThemeModuleSkin__']['__IS_PARSE__'] = TRUE;

		return $GLOBALS['__ThemeModuleSkin__'];
	}

	/**
	 * Return site list
	 * @return void
	 */
	public function getSiteAllList()
	{
		if(Context::get('domain'))
		{
			$domain = Context::get('domain');
		}
		$siteList = $this->getAllSitesThatHaveModules($domain);
		$this->add('site_list', $siteList);
	}

	/**
	 * Returns a list of all sites that contain modules
	 * For each site domain and site_srl are retrieved
	 *
	 * @return array
	 */
	public function getAllSitesThatHaveModules($domain = NULL)
	{
		$args = new stdClass();
		if($domain)
		{
			$args->domain = $domain;
		}
		$columnList = array('domain', 'site_srl');

		$siteList = array();
		$output = executeQueryArray('admin.getSiteAllList', $args, $columnList);
		if($output->toBool())
		{
			$siteList = $output->data;
		}

		$oModuleModel = getModel('module');
		foreach($siteList as $key => $value)
		{
			$args->site_srl = $value->site_srl;
			$list = $oModuleModel->getModuleSrlList($args);

			if(!is_array($list))
			{
				$list = array($list);
			}

			foreach($list as $k => $v)
			{
				if(!is_dir(RX_BASEDIR . 'modules/' . $v->module))
				{
					unset($list[$k]);
				}
			}

			if(!count($list))
			{
				unset($siteList[$key]);
			}
		}
		return $siteList;
	}

	/**
	 * Return site count
	 * @param string $date
	 * @return int
	 */
	public function getSiteCountByDate($date = '')
	{
		$args = new stdClass;
		if ($date)
		{
			$args->regDate = date('Ymd', strtotime($date));
		}

		$output = executeQuery('admin.getSiteCountByDate', $args);
		if (!$output->toBool())
		{
			return 0;
		}

		return $output->data->count;
	}

	/**
	 * Aliases for backward compatibility.
	 */
	public static function getFavoriteList($site_srl = 0, $add_module_info = false)
	{
		return Rhymix\Modules\Admin\Models\Favorite::getFavorites(!!$add_module_info);
	}

	public static function isExistsFavorite($site_srl, $module)
	{
		return Rhymix\Modules\Admin\Models\Favorite::isFavorite(strval($module));
	}

	public static function getFaviconUrl($domain_srl = 0)
	{
		return Rhymix\Modules\Admin\Models\Icon::getFaviconUrl(intval($domain_srl));
	}

	public static function getMobileIconUrl($domain_srl = 0)
	{
		return Rhymix\Modules\Admin\Models\Icon::getMobiconUrl(intval($domain_srl));
	}

	public static function getSiteDefaultImageUrl($domain_srl = 0, &$width = 0, &$height = 0)
	{
		return Rhymix\Modules\Admin\Models\Icon::getDefaultImageUrl(intval($domain_srl), $width, $height);
	}

	public static function iconUrlCheck($iconname, $default_icon_name, $domain_srl)
	{
		return Rhymix\Modules\Admin\Models\Icon::getIconUrl(intval($domain_srl), $iconname);
	}

	public static function getSFTPPath()
	{
		return new BaseObject(-1, 'msg_ftp_invalid_auth_info');
	}

	public static function getFTPPath()
	{
		return new BaseObject(-1, 'msg_ftp_invalid_auth_info');
	}

	public static function getAdminFTPPath()
	{
		return new BaseObject(-1, 'msg_ftp_invalid_auth_info');
	}

	public static function getSFTPList()
	{
		return new BaseObject(-1, 'msg_ftp_invalid_auth_info');
	}

	public static function getAdminFTPList()
	{
		return new BaseObject(-1, 'msg_ftp_invalid_auth_info');
	}

	public static function getThemeList()
	{
		return [];
	}

	public static function getThemeInfo($theme_name, $layout_list = [])
	{

	}
}
