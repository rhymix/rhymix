<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * adminAdminController class
 * admin controller class of admin module
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/admin
 * @version 0.1
 */
class adminAdminController extends admin
{

	/**
	 * initialization
	 * @return void
	 */
	function init()
	{
		// forbit access if the user is not an administrator
		$oMemberModel = getModel('member');
		$logged_info = $oMemberModel->getLoggedInfo();
		if($logged_info->is_admin != 'Y')
		{
			return $this->stop("msg_is_not_administrator");
		}
	}

	/**
	 * Admin menu reset
	 * @return void
	 */
	function procAdminMenuReset()
	{
		$menuSrl = Context::get('menu_srl');
		if(!$menuSrl)
		{
			return $this->stop('msg_invalid_request');
		}

		$oMenuAdminController = getAdminController('menu');
		$output = $oMenuAdminController->deleteMenu($menuSrl);
		if(!$output->toBool())
		{
			return $output;
		}

		FileHandler::removeDir('./files/cache/menu/admin_lang/');

		$this->setRedirectUrl(Context::get('error_return_url'));
	}

	/**
	 * Regenerate all cache files
	 * @return void
	 */
	function procAdminRecompileCacheFile()
	{
		// rename cache dir
		$temp_cache_dir = './files/cache_' . $_SERVER['REQUEST_TIME'];
		FileHandler::rename('./files/cache', $temp_cache_dir);
		FileHandler::makeDir('./files/cache');

		// remove module extend cache
		FileHandler::removeFile(_XE_PATH_ . 'files/config/module_extend.php');

		// remove debug files
		FileHandler::removeFile(_XE_PATH_ . 'files/_debug_message.php');
		FileHandler::removeFile(_XE_PATH_ . 'files/_debug_db_query.php');
		FileHandler::removeFile(_XE_PATH_ . 'files/_db_slow_query.php');

		$oModuleModel = getModel('module');
		$module_list = $oModuleModel->getModuleList();

		// call recompileCache for each module
		foreach($module_list as $module)
		{
			$oModule = NULL;
			$oModule = getClass($module->module);
			if(method_exists($oModule, 'recompileCache'))
			{
				$oModule->recompileCache();
			}
		}

		// remove cache
		$truncated = array();
		$oObjectCacheHandler = CacheHandler::getInstance('object');
		$oTemplateCacheHandler = CacheHandler::getInstance('template');

		if($oObjectCacheHandler->isSupport())
		{
			$truncated[] = $oObjectCacheHandler->truncate();
		}

		if($oTemplateCacheHandler->isSupport())
		{
			$truncated[] = $oTemplateCacheHandler->truncate();
		}

		if(count($truncated) && in_array(FALSE, $truncated))
		{
			return new Object(-1, 'msg_self_restart_cache_engine');
		}

		// remove cache dir
		$tmp_cache_list = FileHandler::readDir('./files', '/(^cache_[0-9]+)/');
		if($tmp_cache_list)
		{
			foreach($tmp_cache_list as $tmp_dir)
			{
				if($tmp_dir)
				{
					FileHandler::removeDir('./files/' . $tmp_dir);
				}
			}
		}

		// remove duplicate indexes (only for CUBRID)
		$db_type = Context::getDBType();
		if($db_type == 'cubrid')
		{
			$db = DB::getInstance();
			$db->deleteDuplicateIndexes();
		}

		// check autoinstall packages
		$oAutoinstallAdminController = getAdminController('autoinstall');
		$oAutoinstallAdminController->checkInstalled();

		$this->setMessage('success_updated');
	}

	/**
	 * Logout
	 * @return void
	 */
	function procAdminLogout()
	{
		$oMemberController = getController('member');
		$oMemberController->procMemberLogout();

		header('Location: ' . getNotEncodedUrl('', 'module', 'admin'));
	}

	public function procAdminInsertDefaultDesignInfo()
	{
		$vars = Context::getRequestVars();
		if(!$vars->site_srl)
		{
			$vars->site_srl = 0;
		}

		// create a DesignInfo file
		$output = $this->updateDefaultDesignInfo($vars);
		return $this->setRedirectUrl(Context::get('error_return_url'), $output);
	}

	public function updateDefaultDesignInfo($vars)
	{
		$siteDesignPath = _XE_PATH_ . 'files/site_design/';

		$vars->module_skin = json_decode($vars->module_skin);

		if(!is_dir($siteDesignPath))
		{
			FileHandler::makeDir($siteDesignPath);
		}

		$siteDesignFile = _XE_PATH_ . 'files/site_design/design_' . $vars->site_srl . '.php';

		$layoutTarget = 'layout_srl';
		$skinTarget = 'skin';

		if($vars->target_type == 'M')
		{
			$layoutTarget = 'mlayout_srl';
			$skinTarget = 'mskin';
		}

		if(is_readable($siteDesignFile))
		{
			include($siteDesignFile);
		}
		else
		{
			$designInfo = new stdClass();
		}

		$layoutSrl = (!$vars->layout_srl) ? 0 : $vars->layout_srl;

		$designInfo->{$layoutTarget} = $layoutSrl;

		foreach($vars->module_skin as $moduleName => $skinName)
		{
			if($moduleName == 'ARTICLE')
			{
				$moduleName = 'page';
			}

			if(!isset($designInfo->module->{$moduleName})) $designInfo->module->{$moduleName} = new stdClass();
			$designInfo->module->{$moduleName}->{$skinTarget} = $skinName;
		}

		$this->makeDefaultDesignFile($designInfo, $vars->site_srl);

		return new Object();
	}

	function makeDefaultDesignFile($designInfo, $site_srl = 0)
	{
		$buff = array();
		$buff[] = '<?php if(!defined("__XE__")) exit();';
		$buff[] = '$designInfo = new stdClass;';

		if($designInfo->layout_srl)
		{
			$buff[] = sprintf('$designInfo->layout_srl = %s; ', $designInfo->layout_srl);
		}

		if($designInfo->mlayout_srl)
		{
			$buff[] = sprintf('$designInfo->mlayout_srl = %s;', $designInfo->mlayout_srl);
		}

		$buff[] = '$designInfo->module = new stdClass;';

		foreach($designInfo->module as $moduleName => $skinInfo)
		{
			$buff[] = sprintf('$designInfo->module->%s = new stdClass;', $moduleName);
			foreach($skinInfo as $target => $skinName)
			{
				$buff[] = sprintf('$designInfo->module->%s->%s = \'%s\';', $moduleName, $target, $skinName);
			}
		}

		$siteDesignFile = _XE_PATH_ . 'files/site_design/design_' . $site_srl . '.php';
		FileHandler::writeFile($siteDesignFile, implode(PHP_EOL, $buff));
	}

	/**
	 * Toggle favorite
	 * @return void
	 */
	function procAdminToggleFavorite()
	{
		$siteSrl = Context::get('site_srl');
		$moduleName = Context::get('module_name');

		// check favorite exists
		$oModel = getAdminModel('admin');
		$output = $oModel->isExistsFavorite($siteSrl, $moduleName);
		if(!$output->toBool())
		{
			return $output;
		}

		// if exists, delete favorite
		if($output->get('result'))
		{
			$favoriteSrl = $output->get('favoriteSrl');
			$output = $this->_deleteFavorite($favoriteSrl);
			$result = 'off';
		}
		// if not exists, insert favorite
		else
		{
			$output = $this->_insertFavorite($siteSrl, $moduleName);
			$result = 'on';
		}

		if(!$output->toBool())
		{
			return $output;
		}

		$this->add('result', $result);

		return $this->setRedirectUrl(Context::get('error_return_url'), $output);
	}

	/**
	 * Cleanning favorite
	 * @return Object
	 */
	function cleanFavorite()
	{
		$oModel = getAdminModel('admin');
		$output = $oModel->getFavoriteList();
		if(!$output->toBool())
		{
			return $output;
		}

		$favoriteList = $output->get('favoriteList');
		if(!$favoriteList)
		{
			return new Object();
		}

		$deleteTargets = array();
		foreach($favoriteList as $favorite)
		{
			if($favorite->type == 'module')
			{
				$modulePath = _XE_PATH_ . 'modules/' . $favorite->module;
				if(!is_dir($modulePath))
				{
					$deleteTargets[] = $favorite->admin_favorite_srl;
				}
			}
		}

		if(!count($deleteTargets))
		{
			return new Object();
		}

		$args = new stdClass();
		$args->admin_favorite_srls = $deleteTargets;
		$output = executeQuery('admin.deleteFavorites', $args);
		if(!$output->toBool())
		{
			return $output;
		}

		return new Object();
	}

	/**
	 * Enviroment gathering agreement
	 * @return void
	 */
	function procAdminEnviromentGatheringAgreement()
	{
		$isAgree = Context::get('is_agree');
		if($isAgree == 'true')
		{
			$_SESSION['enviroment_gather'] = 'Y';
		}
		else
		{
			$_SESSION['enviroment_gather'] = 'N';
		}

		$redirectUrl = getNotEncodedUrl('', 'module', 'admin');
		$this->setRedirectUrl($redirectUrl);
	}

	/**
	 * Admin config update
	 * @return void
	 */
	function procAdminUpdateConfig()
	{
		$adminTitle = Context::get('adminTitle');
		$file = $_FILES['adminLogo'];

		$oModuleModel = getModel('module');
		$oAdminConfig = $oModuleModel->getModuleConfig('admin');

		if(!is_object($oAdminConfig))
		{
			$oAdminConfig = new stdClass();
		}

		if($file['tmp_name'])
		{
			$target_path = 'files/attach/images/admin/';
			FileHandler::makeDir($target_path);

			// Get file information
			list($width, $height, $type, $attrs) = @getimagesize($file['tmp_name']);
			if($type == 3)
			{
				$ext = 'png';
			}
			elseif($type == 2)
			{
				$ext = 'jpg';
			}
			else
			{
				$ext = 'gif';
			}

			$target_filename = sprintf('%s%s.%s.%s', $target_path, 'adminLogo', date('YmdHis'), $ext);
			@move_uploaded_file($file['tmp_name'], $target_filename);

			$oAdminConfig->adminLogo = $target_filename;
		}
		if($adminTitle)
		{
			$oAdminConfig->adminTitle = strip_tags($adminTitle);
		}
		else
		{
			unset($oAdminConfig->adminTitle);
		}

		if($oAdminConfig)
		{
			$oModuleController = getController('module');
			$oModuleController->insertModuleConfig('admin', $oAdminConfig);
		}

		$this->setMessage('success_updated', 'info');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdminSetup');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * Admin logo delete
	 * @return void
	 */
	function procAdminDeleteLogo()
	{
		$oModuleModel = getModel('module');
		$oAdminConfig = $oModuleModel->getModuleConfig('admin');

		FileHandler::removeFile(_XE_PATH_ . $oAdminConfig->adminLogo);
		unset($oAdminConfig->adminLogo);

		$oModuleController = getController('module');
		$oModuleController->insertModuleConfig('admin', $oAdminConfig);

		$this->setMessage('success_deleted', 'info');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispAdminSetup');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * Insert favorite
	 * @return object query result
	 */
	function _insertFavorite($siteSrl, $module, $type = 'module')
	{
		$args = new stdClass();
		$args->adminFavoriteSrl = getNextSequence();
		$args->site_srl = $siteSrl;
		$args->module = $module;
		$args->type = $type;
		$output = executeQuery('admin.insertFavorite', $args);
		return $output;
	}

	/**
	 * Delete favorite
	 * @return object query result
	 */
	function _deleteFavorite($favoriteSrl)
	{
		$args = new stdClass();
		$args->admin_favorite_srl = $favoriteSrl;
		$output = executeQuery('admin.deleteFavorite', $args);
		return $output;
	}

	/**
	 * Delete all favorite
	 * @return object query result
	 */
	function _deleteAllFavorite()
	{
		$args = new stdClass;
		$output = executeQuery('admin.deleteAllFavorite', $args);
		return $output;
	}

	/**
	 * Remove admin icon
	 * @return object|void
	 */
	function procAdminRemoveIcons()
	{

		$site_info = Context::get('site_module_info');
		$virtual_site = '';
		if($site_info->site_srl) 
		{
			$virtual_site = $site_info->site_srl . '/';
		}

		$iconname = Context::get('iconname');
		$file_exist = FileHandler::readFile(_XE_PATH_ . 'files/attach/xeicon/' . $virtual_site . $iconname);
		if($file_exist)
		{
			@FileHandler::removeFile(_XE_PATH_ . 'files/attach/xeicon/' . $virtual_site . $iconname);
		}
		else
		{
			return new Object(-1, 'fail_to_delete');
		}
		$this->setMessage('success_deleted');
	}

	function procAdminUpdateSitelock()
	{
		$vars = Context::getRequestVars();
		$oInstallController = getController('install');

		$db_info = Context::getDBInfo();

		$db_info->use_sitelock = ($vars->use_sitelock) ? $vars->use_sitelock : 'N';
		$db_info->sitelock_title = $vars->sitelock_title;
		$db_info->sitelock_message = $vars->sitelock_message;

		$whitelist = $vars->sitelock_whitelist;
		$whitelist = preg_replace("/[\r|\n|\r\n]+/",",",$whitelist);
		$whitelist = preg_replace("/\s+/","",$whitelist);
		if(preg_match('/(<\?|<\?php|\?>)/xsm', $whitelist))
		{
			$whitelist = '';
		}
		$whitelist .= ',127.0.0.1,' . $_SERVER['REMOTE_ADDR'];
		$whitelist = explode(',',trim($whitelist, ','));
		$whitelist = array_unique($whitelist);

		if(!IpFilter::validate($whitelist)) {
			return new Object(-1, 'msg_invalid_ip');
		}

		$db_info->sitelock_whitelist = $whitelist;

		$oInstallController = getController('install');
		if(!$oInstallController->makeConfigFile())
		{
			return new Object(-1, 'msg_invalid_request');
		}

		if(!in_array(Context::getRequestMethod(), array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url');
			if(!$returnUrl) $returnUrl = getNotEncodedUrl('', 'act', 'dispAdminConfigGeneral');
			header('location:' . $returnUrl);
			return;
		}
	}

	function procAdminUpdateEmbedWhitelist()
	{
		$vars = Context::getRequestVars();

		$db_info = Context::getDBInfo();

		$white_object = $vars->embed_white_object;
		$white_object = preg_replace("/[\r\n|\r|\n]+/", '|@|', $white_object);
		$white_object = preg_replace("/[\s\'\"]+/", '', $white_object);
		$white_object = explode('|@|', $white_object);
		$white_object = array_unique($white_object);

		$white_iframe = $vars->embed_white_iframe;
		$white_iframe = preg_replace("/[\r\n|\r|\n]+/", '|@|', $white_iframe);
		$white_iframe = preg_replace("/[\s\'\"]+/", '', $white_iframe);
		$white_iframe = explode('|@|', $white_iframe);
		$white_iframe = array_unique($white_iframe);

		$whitelist = new stdClass;
		$whitelist->object = $white_object;
		$whitelist->iframe = $white_iframe;

		$db_info->embed_white_object = $white_object;
		$db_info->embed_white_iframe = $white_iframe;

		$oInstallController = getController('install');
		if(!$oInstallController->makeConfigFile())
		{
			return new Object(-1, 'msg_invalid_request');
		}

		require_once(_XE_PATH_ . 'classes/security/EmbedFilter.class.php');
		$oEmbedFilter = EmbedFilter::getInstance();
		$oEmbedFilter->_makeWhiteDomainList($whitelist);

		if(!in_array(Context::getRequestMethod(), array('XMLRPC','JSON')))
		{
			$returnUrl = Context::get('success_return_url');
			if(!$returnUrl) $returnUrl = getNotEncodedUrl('', 'act', 'dispAdminConfigGeneral');
			header('location:' . $returnUrl);
			return;
		}
	}

}
/* End of file admin.admin.controller.php */
/* Location: ./modules/admin/admin.admin.controller.php */
