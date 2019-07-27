<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class ModuleHandler
 * @author NAVER (developers@xpressengine.com)
 * Handling modules
 *
 * @remarks This class is to excute actions of modules.
 *          Constructing an instance without any parameterconstructor, it finds the target module based on Context.
 *          If there is no act on the found module, excute an action referencing action_forward.
 * */
class ModuleHandler extends Handler
{

	var $module = NULL; ///< Module
	var $act = NULL; ///< action
	var $mid = NULL; ///< Module ID
	var $document_srl = NULL; ///< Document Number
	var $module_srl = NULL; ///< Module Number
	var $module_info = NULL; ///< Module Info
	var $error = NULL; ///< an error code.
	var $httpStatusCode = NULL; ///< http status code.

	/**
	 * prepares variables to use in moduleHandler
	 * @param string $module name of module
	 * @param string $act name of action
	 * @param int $mid
	 * @param int $document_srl
	 * @param int $module_srl
	 * @return void
	 * */

	public function __construct($module = '', $act = '', $mid = '', $document_srl = '', $module_srl = '')
	{
		// If XE has not installed yet, set module as install
		if(!Context::isInstalled())
		{
			$this->module = 'install';
			$this->act = Context::get('act');
			return;
		}

		// Check security check status
		$oContext = Context::getInstance();
		switch($oContext->security_check)
		{
			case 'OK':
				break;
			case 'ALLOW ADMIN ONLY':
				if(!Context::get('logged_info')->isAdmin())
				{
					$this->error = 'msg_security_violation';
					return;
				}
				break;
			case 'DENY ALL':
			default:
				$this->error = 'msg_security_violation';
				return;
		}

		// Set variables from request arguments
		$this->module = $module ? $module : Context::get('module');
		$this->act = $act ? $act : Context::get('act');
		$this->mid = $mid ? $mid : Context::get('mid');
		$this->document_srl = $document_srl ? (int) $document_srl : (int) Context::get('document_srl');
		$this->module_srl = $module_srl ? (int) $module_srl : (int) Context::get('module_srl');
        if($entry = Context::get('entry'))
        {
            $this->entry = Context::convertEncodingStr($entry);
        }
        if(!$this->module && $this->mid === 'admin')
        {
        	Context::set('module', $this->module = 'admin');
        	Context::set('mid', $this->mid = null);
        }

		// Validate variables to prevent XSS
		$isInvalid = false;
		if($this->module && !preg_match('/^[a-zA-Z0-9_-]+$/', $this->module))
		{
			$isInvalid = true;
		}
		if($this->mid && !preg_match('/^[a-zA-Z0-9_-]+$/', $this->mid))
		{
			$isInvalid = true;
		}
		if($this->act && !preg_match('/^[a-zA-Z0-9_-]+$/', $this->act))
		{
			$isInvalid = true;
		}
		if($isInvalid)
		{
			$this->error = 'msg_security_violation';
			return;
		}

		if(isset($this->act) && (strlen($this->act) >= 4 && substr_compare($this->act, 'disp', 0, 4) === 0))
		{
			if(Context::get('_use_ssl') == 'optional' && Context::isExistsSSLAction($this->act) && !RX_SSL)
			{
				if(Context::get('_https_port') != null)
				{
					header('location: https://' . $_SERVER['HTTP_HOST'] . ':' . Context::get('_https_port') . $_SERVER['REQUEST_URI']);
				}
				else
				{
					header('location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
				}
				return;
			}
		}

		// call a trigger before moduleHandler init
		self::triggerCall('moduleHandler.init', 'before', $this);

		// execute addon (before module initialization)
		$called_position = 'before_module_init';
		$oAddonController = getController('addon');
		$addon_file = $oAddonController->getCacheFilePath(Mobile::isFromMobilePhone() ? 'mobile' : 'pc');
		if(file_exists($addon_file)) include($addon_file);
	}

	/**
	 * Initialization. It finds the target module based on module, mid, document_srl, and prepares to execute an action
	 * @return boolean true: OK, false: redirected
	 * */
	public function init()
	{
		$oModuleModel = getModel('module');
		$site_module_info = Context::get('site_module_info');
		
		// Check unregistered domain action.
		if (!$site_module_info || !isset($site_module_info->domain_srl) || $site_module_info->is_default_replaced)
		{
			$site_module_info = getModel('module')->getDefaultDomainInfo();
			if ($site_module_info)
			{
				$domain_action = config('url.unregistered_domain_action') ?: 'redirect_301';
				switch ($domain_action)
				{
					case 'redirect_301':
						header('Location: ' . Context::getDefaultUrl($site_module_info) . RX_REQUEST_URL, true, 301);
						return false;
						
					case 'redirect_302':
						header('Location: ' . Context::getDefaultUrl($site_module_info) . RX_REQUEST_URL, true, 302);
						return false;
					
					case 'block':
						$this->error = 'The site does not exist';
						$this->httpStatusCode = 404;
						return true;
						
					case 'display':
						// pass
				}
			}
		}

		// if success_return_url and error_return_url is incorrect
		$urls = array(Context::get('success_return_url'), Context::get('error_return_url'));
		foreach($urls as $url)
		{
			if(empty($url))
			{
				continue;
			}
			
			if($host = parse_url($url, PHP_URL_HOST))
			{
				$defaultHost = parse_url(Context::getDefaultUrl(), PHP_URL_HOST);
				if($host !== $defaultHost)
				{
					$siteModuleHost = $site_module_info->domain;
					if(strpos($siteModuleHost, '/') !== false)
					{
						$siteModuleHost = parse_url($siteModuleHost, PHP_URL_HOST);
					}
					if($host !== $siteModuleHost)
					{
						Context::set('success_return_url', null);
						Context::set('error_return_url', null);
					}
				}
			}
		}
		
		// Convert document alias (entry) to document_srl
		if(!$this->document_srl && $this->mid && $this->entry)
		{
			$oDocumentModel = getModel('document');
			$this->document_srl = $oDocumentModel->getDocumentSrlByAlias($this->mid, $this->entry);
			if($this->document_srl)
			{
				Context::set('document_srl', $this->document_srl);
			}
		}

		// Get module's information based on document_srl, if it's specified
		if($this->document_srl)
		{
			$module_info = $oModuleModel->getModuleInfoByDocumentSrl($this->document_srl);
			if($module_info)
			{
				// If it exists, compare mid based on the module information
				// if mids are not matching, set it as the document's mid
				if(!$this->mid || ($this->mid != $module_info->mid))
				{
					if(Context::getRequestMethod() == 'GET')
					{
						Context::setCacheControl(0);
						header('location: ' . getNotEncodedSiteUrl($site_module_info->domain, 'mid', $module_info->mid, 'document_srl', $this->document_srl), true, 301);
						return false;
					}
					else
					{
						$this->mid = $module_info->mid;
						Context::set('mid', $this->mid);
					}
				}
				// if requested module is different from one of the document, remove the module information retrieved based on the document number
				if($this->module && $module_info->module != $this->module)
				{
					unset($module_info);
				}
			}
			
			// Block access to secret or temporary documents.
			if(Context::getRequestMethod() == 'GET')
			{
				$oDocumentModel = getModel('document');
				$oDocument = $oDocumentModel->getDocument($this->document_srl);
				if($oDocument->isExists() && !$oDocument->isAccessible())
				{
					$this->httpStatusCode = '403';
				}
			}
		}

		// If module_info is not set yet, and there exists mid information, get module information based on the mid
		if(!$module_info && $this->mid)
		{
			$module_info = $oModuleModel->getModuleInfoByMid($this->mid, $site_module_info->site_srl);
			//if($this->module && $module_info->module != $this->module) unset($module_info);
		}

		// If module_info is not set still, and $module does not exist, find the default module
		if(!$module_info && !$this->module && !$this->mid)
		{
			$module_info = $site_module_info;
		}

		if(!$module_info && !$this->module && $site_module_info->module_site_srl)
		{
			$module_info = $site_module_info;
		}

		// Set index document
		if($site_module_info->index_document_srl && !$this->module && !$this->mid && !$this->document_srl && Context::getRequestMethod() === 'GET' && !count($_GET))
		{
			Context::set('document_srl', $this->document_srl = $site_module_info->index_document_srl, true);
		}

		// redirect, if site start module
		if(!$site_module_info->index_document_srl && Context::getRequestMethod() === 'GET' && isset($_GET['mid']) && $_GET['mid'] === $site_module_info->mid && count($_GET) === 1)
		{
			Context::setCacheControl(0);
			header('location: ' . getNotEncodedSiteUrl($site_module_info->domain), true, 301);
			return false;
		}
		
		// If module info was set, retrieve variables from the module information
		if($module_info)
		{
			$this->module = $module_info->module;
			$this->mid = $module_info->mid;
			$this->module_info = $module_info;
			if ($module_info->mid == $site_module_info->mid)
			{
				$seo_title = config('seo.main_title') ?: '$SITE_TITLE - $SITE_SUBTITLE';
			}
			else
			{
				$seo_title = config('seo.subpage_title') ?: '$SITE_TITLE - $SUBPAGE_TITLE';
			}
			
			getController('module')->replaceDefinedLangCode($seo_title);
			Context::setBrowserTitle($seo_title, array(
				'site_title' => Context::getSiteTitle(),
				'site_subtitle' => Context::getSiteSubtitle(),
				'subpage_title' => $module_info->browser_title,
				'page' => Context::get('page') ?: 1,
			));
			
			$module_config = $oModuleModel->getModuleConfig('module');
			if ($module_info->meta_keywords)
			{
				Context::addMetaTag('keywords', $module_info->meta_keywords);
			}
			elseif($module_config->meta_keywords)
			{
				Context::addMetaTag('keywords', $module_config->meta_keywords);
			}
			
			if ($module_info->meta_description)
			{
				Context::addMetaTag('description', $module_info->meta_description);
			}
			elseif($module_config->meta_description)
			{
				Context::addMetaTag('description', $module_config->meta_description);
			}

			$viewType = (Mobile::isFromMobilePhone()) ? 'M' : 'P';
			$targetSrl = $viewType === 'M' ? 'mlayout_srl' : 'layout_srl';

			// use the site default layout.
			if($module_info->{$targetSrl} == -1)
			{
				$oLayoutAdminModel = getAdminModel('layout');
				$layoutSrl = $oLayoutAdminModel->getSiteDefaultLayout($viewType, $module_info->site_srl);
			}
			elseif($module_info->{$targetSrl} == -2 && $viewType === 'M')
			{
				$layoutSrl = $module_info->layout_srl;
				if($layoutSrl == -1)
				{
					$viewType = 'P';
					$oLayoutAdminModel = getAdminModel('layout');
					$layoutSrl = $oLayoutAdminModel->getSiteDefaultLayout($viewType, $module_info->site_srl);
				}
			}
			else
			{
				$layoutSrl = $module_info->{$targetSrl};
			}

			// reset a layout_srl in module_info.
			$module_info->{$targetSrl} = $layoutSrl;

			$part_config = $oModuleModel->getModulePartConfig('layout', $layoutSrl);
			Context::addHtmlHeader($part_config->header_script);
		}

		// Set module and mid into module_info
		if(!isset($this->module_info))
		{
			$this->module_info = new stdClass();
		}
		$this->module_info->module = $this->module;
		$this->module_info->mid = $this->mid;

		// Set site_srl add 2011 08 09
		$this->module_info->site_srl = $site_module_info->site_srl;

		// Still no module? it's an error
		if(!$this->module)
		{
			$this->error = 'msg_module_is_not_exists';
			$this->httpStatusCode = '404';
			return true;
		}

		// If mid exists, set mid into context
		if($this->mid)
		{
			Context::set('mid', $this->mid, TRUE);
		}
		
		// Call a trigger after moduleHandler init
		$output = self::triggerCall('moduleHandler.init', 'after', $this->module_info);
		if(!$output->toBool())
		{
			$this->error = $output->getMessage();
			return true;
		}

		// Set current module info into context
		Context::set('current_module_info', $this->module_info);

		return true;
	}

	/**
	 * get a module instance and execute an action
	 * @return ModuleObject executed module instance
	 * */
	public function procModule()
	{
		$oModuleModel = getModel('module');
		$display_mode = Mobile::isFromMobilePhone() ? 'mobile' : 'view';

		// If error occurred while preparation, return a message instance
		if($this->error)
		{
			self::_setInputErrorToContext();
			$oMessageObject = self::getModuleInstance('message', $display_mode);
			$oMessageObject->setError(-1);
			$oMessageObject->setMessage($this->error);
			$oMessageObject->dispMessage();
			if($this->httpStatusCode)
			{
				$oMessageObject->setHttpStatusCode($this->httpStatusCode);
			}
			return $oMessageObject;
		}

		// Get action information with conf/module.xml
		$xml_info = $oModuleModel->getModuleActionXml($this->module);

		// If not installed yet, modify act
		if($this->module == "install")
		{
			if(!$this->act || !$xml_info->action->{$this->act})
			{
				$this->act = $xml_info->default_index_act;
			}
		}

		// if act exists, find type of the action, if not use default index act
		if(!$this->act)
		{
			$this->act = $xml_info->default_index_act;
		}

		// still no act means error
		if(!$this->act)
		{
			$this->error = 'msg_module_is_not_exists';
			$this->httpStatusCode = '404';

			self::_setInputErrorToContext();
			$oMessageObject = self::getModuleInstance('message', $display_mode);
			$oMessageObject->setError(-1);
			$oMessageObject->setMessage($this->error);
			$oMessageObject->dispMessage();
			if($this->httpStatusCode)
			{
				$oMessageObject->setHttpStatusCode($this->httpStatusCode);
			}
			return $oMessageObject;
		}

		// get type, kind
		$type = $xml_info->action->{$this->act}->type;
		$ruleset = $xml_info->action->{$this->act}->ruleset;
		$meta_noindex = $xml_info->action->{$this->act}->meta_noindex;
		$kind = stripos($this->act, 'admin') !== FALSE ? 'admin' : '';
		if ($meta_noindex === 'true')
		{
			Context::addMetaTag('robots', 'noindex');
		}

		if(!$kind && $this->module == 'admin')
		{
			$kind = 'admin';
		}

		// check REQUEST_METHOD in controller
		if($type == 'controller')
		{
			$allowedMethod = $xml_info->action->{$this->act}->method;

			if(!$allowedMethod)
			{
				$allowedMethodList[0] = 'POST';
			}
			else
			{
				$allowedMethodList = explode('|', strtoupper($allowedMethod));
			}

			if(!in_array(strtoupper($_SERVER['REQUEST_METHOD']), $allowedMethodList))
			{
				$this->error = 'msg_invalid_request';
				$oMessageObject = self::getModuleInstance('message', $display_mode);
				$oMessageObject->setError(-1);
				$oMessageObject->setMessage($this->error);
				$oMessageObject->dispMessage();
				return $oMessageObject;
			}
		}
		
		// check CSRF for non-GET (POST, PUT, etc.) actions
		if(Context::getRequestMethod() !== 'GET' && Context::isInstalled())
		{
			if($xml_info->action->{$this->act} && $xml_info->action->{$this->act}->check_csrf !== 'false' && !checkCSRF())
			{
				$this->_setInputErrorToContext();
				$this->error = 'msg_invalid_request';
				$oMessageObject = ModuleHandler::getModuleInstance('message', $display_mode);
				$oMessageObject->setError(-1);
				$oMessageObject->setMessage($this->error);
				$oMessageObject->dispMessage();
				return $oMessageObject;
			}
		}
		
		if($this->module_info->use_mobile != "Y")
		{
			Mobile::setMobile(FALSE);
		}

		$logged_info = Context::get('logged_info');

		// if(type == view, and case for using mobilephone)
		if($type == "view" && Mobile::isFromMobilePhone() && Context::isInstalled())
		{
			$orig_type = "view";
			$type = "mobile";
			// create a module instance
			$oModule = self::getModuleInstance($this->module, $type, $kind);
			if(!is_object($oModule) || !method_exists($oModule, $this->act))
			{
				$type = $orig_type;
				Mobile::setMobile(FALSE);
				$oModule = self::getModuleInstance($this->module, $type, $kind);
			}
		}
		else
		{
			// create a module instance
			$oModule = self::getModuleInstance($this->module, $type, $kind);
		}

		if(!is_object($oModule))
		{
			self::_setInputErrorToContext();
			$oMessageObject = self::getModuleInstance('message', $display_mode);
			$oMessageObject->setError(-1);
			$oMessageObject->setMessage($this->error);
			$oMessageObject->dispMessage();
			if($this->httpStatusCode)
			{
				$oMessageObject->setHttpStatusCode($this->httpStatusCode);
			}
			return $oMessageObject;
		}

		// If there is no such action in the module object
		if(!isset($xml_info->action->{$this->act}) || !method_exists($oModule, $this->act))
		{
			if(!Context::isInstalled())
			{
				self::_setInputErrorToContext();
				$this->error = 'msg_invalid_request';
				$oMessageObject = self::getModuleInstance('message', $display_mode);
				$oMessageObject->setError(-1);
				$oMessageObject->setMessage($this->error);
				$oMessageObject->dispMessage();
				if($this->httpStatusCode)
				{
					$oMessageObject->setHttpStatusCode($this->httpStatusCode);
				}
				return $oMessageObject;
			}
			
			// 1. Look for the module with action name
			if(preg_match('/^([a-z]+)([A-Z])([a-z0-9\_]+)(.*)$/', $this->act, $matches))
			{
				$module = strtolower($matches[2] . $matches[3]);
				$xml_info = $oModuleModel->getModuleActionXml($module);

				if($xml_info->action->{$this->act} && ($this->module == 'admin' || $xml_info->action->{$this->act}->standalone != 'false'))
				{
					$forward = new stdClass();
					$forward->module = $module;
					$forward->type = $xml_info->action->{$this->act}->type;
					$forward->ruleset = $xml_info->action->{$this->act}->ruleset;
					$forward->meta_noindex = $xml_info->action->{$this->act}->meta_noindex;
					$forward->act = $this->act;
				}
				else
				{
					$this->error = 'msg_invalid_request';
					$oMessageObject = self::getModuleInstance('message', $display_mode);
					$oMessageObject->setError(-1);
					$oMessageObject->setMessage($this->error);
					$oMessageObject->dispMessage();

					return $oMessageObject;
				}
			}
			
			if(empty($forward->module))
			{
				$forward = $oModuleModel->getActionForward($this->act);
			}
			
			if(!empty($forward->module))
			{
				$kind = stripos($forward->act, 'admin') !== FALSE ? 'admin' : '';
				$type = $forward->type;
				$ruleset = $forward->ruleset;
				$tpl_path = $oModule->getTemplatePath();
				$orig_module = $oModule;
				if($forward->meta_noindex === 'true')
				{
					Context::addMetaTag('robots', 'noindex');
				}
				
				$xml_info = $oModuleModel->getModuleActionXml($forward->module);
				
				// Protect admin action
				if(($this->module == 'admin' || $kind == 'admin') && !$oModuleModel->getGrant($forward, $logged_info)->root)
				{
					if($this->module == 'admin' || empty($xml_info->permission->{$this->act}))
					{
						self::_setInputErrorToContext();
						$this->error = 'admin.msg_is_not_administrator';
						$oMessageObject = self::getModuleInstance('message', $display_mode);
						$oMessageObject->setError(-1);
						$oMessageObject->setMessage($this->error);
						$oMessageObject->dispMessage();
						return $oMessageObject;
					}
				}
				
				// SECISSUE also check foward act method
				// check REQUEST_METHOD in controller
				if($type == 'controller')
				{
					$allowedMethod = $xml_info->action->{$forward->act}->method;

					if(!$allowedMethod)
					{
						$allowedMethodList[0] = 'POST';
					}
					else
					{
						$allowedMethodList = explode('|', strtoupper($allowedMethod));
					}

					if(!in_array(strtoupper($_SERVER['REQUEST_METHOD']), $allowedMethodList))
					{
						$this->error = 'msg_security_violation';
						$oMessageObject = self::getModuleInstance('message', $display_mode);
						$oMessageObject->setError(-1);
						$oMessageObject->setMessage($this->error);
						$oMessageObject->dispMessage();
						return $oMessageObject;
					}
				}
				
				// check CSRF for non-GET (POST, PUT, etc.) actions
				if(Context::getRequestMethod() !== 'GET' && Context::isInstalled())
				{
					if($xml_info->action->{$this->act} && $xml_info->action->{$this->act}->check_csrf !== 'false' && !checkCSRF())
					{
						$this->_setInputErrorToContext();
						$this->error = 'msg_security_violation';
						$oMessageObject = ModuleHandler::getModuleInstance('message', $display_mode);
						$oMessageObject->setError(-1);
						$oMessageObject->setMessage($this->error);
						$oMessageObject->dispMessage();
						return $oMessageObject;
					}
				}
				
				if($type == "view" && Mobile::isFromMobilePhone())
				{
					$orig_type = "view";
					$type = "mobile";
					// create a module instance
					$oModule = self::getModuleInstance($forward->module, $type, $kind);
					if(!is_object($oModule) || !method_exists($oModule, $this->act))
					{
						$type = $orig_type;
						Mobile::setMobile(FALSE);
						$oModule = self::getModuleInstance($forward->module, $type, $kind);
					}
				}
				else
				{
					$oModule = self::getModuleInstance($forward->module, $type, $kind);
				}
				
				if(!is_object($oModule))
				{
					self::_setInputErrorToContext();
					$oMessageObject = self::getModuleInstance('message', $display_mode);
					$oMessageObject->setError(-1);
					$oMessageObject->setMessage('msg_module_is_not_exists');
					$oMessageObject->dispMessage();
					if($this->httpStatusCode)
					{
						$oMessageObject->setHttpStatusCode($this->httpStatusCode);
					}
					return $oMessageObject;
				}
				
				// Admin page layout
				if($this->module == 'admin' && $type == 'view' && $this->act != 'dispLayoutAdminLayoutModify')
				{
					$oAdminView = getAdminView('admin');
					$oAdminView->makeGnbUrl($forward->module);
					$oModule->setLayoutPath("./modules/admin/tpl");
					$oModule->setLayoutFile("layout.html");
				}
			}
			else if($xml_info->default_index_act && method_exists($oModule, $xml_info->default_index_act))
			{
				$this->act = $xml_info->default_index_act;
			}
			else
			{
				$this->error = 'msg_invalid_request';
				$oModule->setError(-1);
				$oModule->setMessage($this->error);
				return $oModule;
			}
		}
		
		// ruleset check...
		if(!empty($ruleset))
		{
			$rulesetModule = !empty($forward->module) ? $forward->module : $this->module;
			$rulesetFile = $oModuleModel->getValidatorFilePath($rulesetModule, $ruleset, $this->mid);
			if(!empty($rulesetFile))
			{
				if($_SESSION['XE_VALIDATOR_ERROR_LANG'])
				{
					$errorLang = $_SESSION['XE_VALIDATOR_ERROR_LANG'];
					foreach($errorLang as $key => $val)
					{
						Context::setLang($key, $val);
					}
					unset($_SESSION['XE_VALIDATOR_ERROR_LANG']);
				}

				$Validator = new Validator($rulesetFile);
				$result = $Validator->validate();
				if(!$result)
				{
					$lastError = $Validator->getLastError();
					$returnUrl = Context::get('error_return_url');
					$errorMsg = $lastError['msg'] ? $lastError['msg'] : 'validation error';

					//for xml response
					$oModule->setError(-1);
					$oModule->setMessage($errorMsg);
					//for html redirect
					$this->error = $errorMsg;
					$_SESSION['XE_VALIDATOR_ERROR'] = -1;
					$_SESSION['XE_VALIDATOR_MESSAGE'] = $this->error;
					$_SESSION['XE_VALIDATOR_MESSAGE_TYPE'] = 'error';
					$_SESSION['XE_VALIDATOR_RETURN_URL'] = $returnUrl;
					$_SESSION['XE_VALIDATOR_ID'] = Context::get('xe_validator_id');
					self::_setInputValueToSession();
					return $oModule;
				}
			}
		}

		$oModule->setAct($this->act);

		$this->module_info->module_type = $type;
		$oModule->setModuleInfo($this->module_info, $xml_info);

		$skipAct = array(
				'dispEditorConfigPreview' => 1,
				'dispLayoutPreviewWithModule' => 1
		);
		$db_use_mobile = Mobile::isMobileEnabled();

		$tablet_use = Rhymix\Framework\UA::isTablet();
		$config_tablet_use = config('mobile.tablets');
		if($type == "view" && $this->module_info->use_mobile == "Y" && Mobile::isMobileCheckByAgent() && !isset($skipAct[Context::get('act')]) && $db_use_mobile === true && ($tablet_use === true && $config_tablet_use === false) === false)
		{
			global $lang;
			$header = '<style>div.xe_mobile{opacity:0.7;margin:1em 0;padding:.5em;background:#333;border:1px solid #666;border-left:0;border-right:0}p.xe_mobile{text-align:center;margin:1em 0}a.xe_mobile{color:#ff0;font-weight:bold;font-size:24px}@media only screen and (min-width:500px){a.xe_mobile{font-size:15px}}</style>';
			$footer = '<div class="xe_mobile"><p class="xe_mobile"><a class="xe_mobile" href="' . getUrl('m', '1') . '">' . $lang->msg_pc_to_mobile . '</a></p></div>';
			Context::addHtmlHeader($header);
			Context::addHtmlFooter($footer);
		}

		if($type == "view" && $kind != 'admin')
		{
			$domain_info = Context::get('site_module_info');
			if ($domain_info && $domain_info->settings && $domain_info->settings->html_header)
			{
				Context::addHtmlHeader($domain_info->settings->html_header);				
			}
			if ($domain_info && $domain_info->settings && $domain_info->settings->html_footer)
			{
				Context::addHtmlFooter($domain_info->settings->html_footer);				
			}
			if ($domain_info && $domain_info->settings && $domain_info->settings->title)
			{
				if(!Context::getBrowserTitle())
				{
					Context::setBrowserTitle($domain_info->settings->title);
				}
			}
		}

		if ($kind === 'admin') {
			Context::addMetaTag('robots', 'noindex');
		}

		// if failed message exists in session, set context
		self::_setInputErrorToContext();

		$procResult = $oModule->proc();

		$methodList = array('XMLRPC' => 1, 'JSON' => 1, 'JS_CALLBACK' => 1);
		if(!$oModule->stop_proc && !isset($methodList[Context::getRequestMethod()]))
		{
			$error = $oModule->getError();
			$message = $oModule->getMessage();
			$messageType = $oModule->getMessageType();
			$redirectUrl = $oModule->getRedirectUrl();

			if(!$procResult)
			{
				$this->error = $message;
				if(!$redirectUrl && Context::get('error_return_url'))
				{
					$redirectUrl = Context::get('error_return_url');
				}
				self::_setInputValueToSession();
			}

			if($error != 0)
			{
				$_SESSION['XE_VALIDATOR_ERROR'] = $error;
			}
			if($validator_id = Context::get('xe_validator_id'))
			{
				$_SESSION['XE_VALIDATOR_ID'] = $validator_id;
			}
			if($message != 'success')
			{
				$_SESSION['XE_VALIDATOR_MESSAGE'] = $message;
				$_SESSION['XE_VALIDATOR_MESSAGE_TYPE'] = $messageType;
			}
			if(Context::get('xeVirtualRequestMethod') != 'xml' && $redirectUrl)
			{
				$_SESSION['XE_VALIDATOR_RETURN_URL'] = $redirectUrl;
			}
		}

		unset($logged_info);
		return $oModule;
	}

	/**
	 * set error message to Session.
	 * @return void
	 * */
	public static function _setInputErrorToContext()
	{
		if($_SESSION['XE_VALIDATOR_ERROR'] && !Context::get('XE_VALIDATOR_ERROR'))
		{
			Context::set('XE_VALIDATOR_ERROR', $_SESSION['XE_VALIDATOR_ERROR']);
		}
		if($_SESSION['XE_VALIDATOR_MESSAGE'] && !Context::get('XE_VALIDATOR_MESSAGE'))
		{
			Context::set('XE_VALIDATOR_MESSAGE', $_SESSION['XE_VALIDATOR_MESSAGE']);
		}
		if($_SESSION['XE_VALIDATOR_MESSAGE_TYPE'] && !Context::get('XE_VALIDATOR_MESSAGE_TYPE'))
		{
			Context::set('XE_VALIDATOR_MESSAGE_TYPE', $_SESSION['XE_VALIDATOR_MESSAGE_TYPE']);
		}
		if($_SESSION['XE_VALIDATOR_RETURN_URL'] && !Context::get('XE_VALIDATOR_RETURN_URL'))
		{
			Context::set('XE_VALIDATOR_RETURN_URL', $_SESSION['XE_VALIDATOR_RETURN_URL']);
		}
		if($_SESSION['XE_VALIDATOR_ID'] && !Context::get('XE_VALIDATOR_ID'))
		{
			Context::set('XE_VALIDATOR_ID', $_SESSION['XE_VALIDATOR_ID']);
		}
		if(countobj($_SESSION['INPUT_ERROR']))
		{
			Context::set('INPUT_ERROR', $_SESSION['INPUT_ERROR']);
		}

		self::_clearErrorSession();
	}

	/**
	 * clear error message to Session.
	 * @return void
	 * */
	public static function _clearErrorSession()
	{
		unset($_SESSION['XE_VALIDATOR_ERROR']);
		unset($_SESSION['XE_VALIDATOR_MESSAGE']);
		unset($_SESSION['XE_VALIDATOR_MESSAGE_TYPE']);
		unset($_SESSION['XE_VALIDATOR_RETURN_URL']);
		unset($_SESSION['XE_VALIDATOR_ID']);
		unset($_SESSION['INPUT_ERROR']);
	}

	/**
	 * occured error when, set input values to session.
	 * @return void
	 * */
	public static function _setInputValueToSession()
	{
		$requestVars = Context::getRequestVars();
		unset($requestVars->act, $requestVars->mid, $requestVars->vid, $requestVars->success_return_url, $requestVars->error_return_url, $requestVars->xe_validator_id);
		foreach($requestVars AS $key => $value)
		{
			$_SESSION['INPUT_ERROR'][$key] = $value;
		}
	}

	/**
	 * display contents from executed module
	 * @param ModuleObject $oModule module instance
	 * @return void
	 * */
	public function displayContent($oModule = NULL)
	{
		// If the module is not set or not an object, set error
		if(!$oModule || !is_object($oModule))
		{
			$this->error = 'msg_module_is_not_exists';
			$this->httpStatusCode = '404';
		}

		// If connection to DB has a problem even though it's not install module, set error
		if($this->module != 'install' && !DB::getInstance()->isConnected())
		{
			$this->error = 'msg_dbconnect_failed';
		}

		// Call trigger after moduleHandler proc
		$output = self::triggerCall('moduleHandler.proc', 'after', $oModule);
		if(!$output->toBool())
		{
			$this->error = $output->getMessage();
		}

		// Use message view object, if HTML call
		$methodList = array('XMLRPC' => 1, 'JSON' => 1, 'JS_CALLBACK' => 1);
		if(!isset($methodList[Context::getRequestMethod()]))
		{
			if($_SESSION['XE_VALIDATOR_RETURN_URL'])
			{
				if ($_SESSION['is_new_session'])
				{
					ob_end_clean();
					echo sprintf('<html><head><meta charset="UTF-8" /><meta http-equiv="refresh" content="0; url=%s" /></head><body></body></html>', escape($_SESSION['XE_VALIDATOR_RETURN_URL']));
					return;
				}
				else
				{
					ob_end_clean();
					header('location: ' . $_SESSION['XE_VALIDATOR_RETURN_URL']);
					return;
				}
			}

			// If error occurred, handle it
			if($this->error)
			{
				// display content with message module instance
				$type = Mobile::isFromMobilePhone() ? 'mobile' : 'view';
				$oMessageObject = self::getModuleInstance('message', $type);
				$oMessageObject->setError(-1);
				$oMessageObject->setMessage($this->error);
				$oMessageObject->dispMessage();

				// display Error Page
				if(!in_array($oMessageObject->getHttpStatusCode(), array(200, 403)))
				{
					$oMessageObject->setTemplateFile('http_status_code');
				}
				
				// If module was called normally, change the templates of the module into ones of the message view module
				if($oModule)
				{
					$oModule->setTemplatePath($oMessageObject->getTemplatePath());
					$oModule->setTemplateFile($oMessageObject->getTemplateFile());
					$oModule->setHttpStatusCode($oMessageObject->getHttpStatusCode());
					// Otherwise, set message instance as the target module
				}
				else
				{
					$oModule = $oMessageObject;
				}
				
				self::_clearErrorSession();
			}

			// Check if layout_srl exists for the module
			$viewType = (Mobile::isFromMobilePhone()) ? 'M' : 'P';
			if($viewType === 'M')
			{
				$layout_srl = $oModule->module_info->mlayout_srl;
				if($layout_srl == -2)
				{
					$layout_srl = $oModule->module_info->layout_srl;
					$viewType = 'P';
				}
			}
			else
			{
				$layout_srl = $oModule->module_info->layout_srl;
			}

			// if layout_srl is rollback by module, set default layout
			if($layout_srl == -1)
			{
				$oLayoutAdminModel = getAdminModel('layout');
				$layout_srl = $oLayoutAdminModel->getSiteDefaultLayout($viewType, $oModule->module_info->site_srl);
			}

			if($layout_srl && !$oModule->getLayoutFile())
			{
				// If layout_srl exists, get information of the layout, and set the location of layout_path/ layout_file
				$oLayoutModel = getModel('layout');
				$layout_info = $oLayoutModel->getLayout($layout_srl);
				if($layout_info)
				{
					// Input extra_vars into $layout_info
					if($layout_info->extra_var_count)
					{

						foreach($layout_info->extra_var as $var_id => $val)
						{
							if($val->type == 'image')
							{
								if(strncmp('./files/attach/images/', $val->value, 22) === 0)
								{
									$val->value = Context::getRequestUri() . substr($val->value, 2);
								}
							}
							$layout_info->{$var_id} = $val->value;
						}
					}
					// Set menus into context
					if($layout_info->menu_count)
					{
						$oMenuAdminController = getAdminController('menu');
						$homeMenuCacheFile = null;
						
						foreach($layout_info->menu as $menu_id => $menu)
						{							// No menu selected
							if($menu->menu_srl == 0)
							{
								$menu->list = array();
							}
							else
							{
								if($menu->menu_srl == -1)
								{
									if ($homeMenuCacheFile === null)
									{
										$homeMenuCacheFile = $oMenuAdminController->getHomeMenuCacheFile();
									}

									$homeMenuSrl = 0;
									if(FileHandler::exists($homeMenuCacheFile))
									{
										include($homeMenuCacheFile);
									}
									
									$menu->xml_file = './files/cache/menu/' . $homeMenuSrl . '.xml.php';
									$menu->php_file = './files/cache/menu/' . $homeMenuSrl . '.php';
									$menu->menu_srl = $homeMenuSrl;
								}
								
								$php_file = FileHandler::exists($menu->php_file);
								if(!$php_file)
								{
									$oMenuAdminController->makeXmlFile($menu->menu_srl);
									$php_file = FileHandler::exists($menu->php_file);
								}
								if($php_file)
								{
									include($php_file);
								}
							}
							
							Context::set($menu_id, $menu);
						}
					}

					// Set layout information into context
					Context::set('layout_info', $layout_info);

					$oModule->setLayoutPath($layout_info->path);
					$oModule->setLayoutFile('layout');

					// If layout was modified, use the modified version
					$edited_layout = $oLayoutModel->getUserLayoutHtml($layout_info->layout_srl);
					if(file_exists($edited_layout))
					{
						$oModule->setEditedLayoutFile($edited_layout);
					}
				}
			}
			$isLayoutDrop = Context::get('isLayoutDrop');
			if($isLayoutDrop)
			{
				$kind = stripos($this->act, 'admin') !== FALSE ? 'admin' : '';
				if($kind == 'admin')
				{
					$oModule->setLayoutFile('popup_layout');
				}
				else
				{
					$oModule->setLayoutPath('common/tpl');
					$oModule->setLayoutFile('default_layout');
				}
			}
		}
		
		// Set http status code
		if($this->httpStatusCode && $oModule->getHttpStatusCode() === 200)
		{
			$oModule->setHttpStatusCode($this->httpStatusCode);
		}
		
		// Set http status message
		self::_setHttpStatusMessage($oModule->getHttpStatusCode());
		
		// Display contents
		$oDisplayHandler = new DisplayHandler();
		$oDisplayHandler->printContent($oModule);
	}

	/**
	 * returns module's path
	 * @param string $module module name
	 * @return string path of the module
	 * */
	public static function getModulePath($module)
	{
		return sprintf('./modules/%s/', $module);
	}

	/**
	 * It creates a module instance
	 * @param string $module module name
	 * @param string $type instance type, (e.g., view, controller, model)
	 * @param string $kind admin or svc
	 * @return ModuleObject module instance (if failed it returns null)
	 * @remarks if there exists a module instance created before, returns it.
	 * */
	public static function getModuleInstance($module, $type = 'view', $kind = '')
	{
		$parent_module = $module;
		$kind = strtolower($kind);
		$type = strtolower($type);

		$kinds = array('svc' => 1, 'admin' => 1);
		if(!isset($kinds[$kind]))
		{
			$kind = 'svc';
		}

		$key = $module . '.' . ($kind != 'admin' ? '' : 'admin') . '.' . $type;

		if(is_array($GLOBALS['__MODULE_EXTEND__']) && array_key_exists($key, $GLOBALS['__MODULE_EXTEND__']))
		{
			$module = $extend_module = $GLOBALS['__MODULE_EXTEND__'][$key];
		}

		// if there is no instance of the module in global variable, create a new one
		if(!isset($GLOBALS['_loaded_module'][$module][$type][$kind]))
		{
			self::_getModuleFilePath($module, $type, $kind, $class_path, $high_class_file, $class_file, $instance_name);

			if($extend_module && (!is_readable($high_class_file) || !is_readable($class_file)))
			{
				$module = $parent_module;
				self::_getModuleFilePath($module, $type, $kind, $class_path, $high_class_file, $class_file, $instance_name);
			}

			// Check if the base class and instance class exist
			if(!class_exists($module, true))
			{
				return NULL;
			}
			if(!class_exists($instance_name, true))
			{
				return NULL;
			}

			// Create an instance
			$oModule = new $instance_name();
			if(!is_object($oModule))
			{
				return NULL;
			}
			
			// Populate default properties
			if($oModule->user === false)
			{
				$oModule->user = Context::get('logged_info') ?: new Rhymix\Framework\Helpers\SessionHelper;
			}

			// Load language files for the class
			if($module !== 'module')
			{
				Context::loadLang($class_path . 'lang');
			}
			if($extend_module)
			{
				Context::loadLang(ModuleHandler::getModulePath($parent_module) . 'lang');
			}

			// Set variables to the instance
			$oModule->setModule($module);
			$oModule->setModulePath($class_path);

			// Store the created instance into GLOBALS variable
			$GLOBALS['_loaded_module'][$module][$type][$kind] = $oModule;
		}

		// return the instance
		return $GLOBALS['_loaded_module'][$module][$type][$kind];
	}

	public static function _getModuleFilePath($module, $type, $kind, &$classPath, &$highClassFile, &$classFile, &$instanceName)
	{
		$classPath = self::getModulePath($module);

		$highClassFile = sprintf('%s%s%s.class.php', _XE_PATH_, $classPath, $module);
		$highClassFile = FileHandler::getRealPath($highClassFile);

		$types = array('view','controller','model','api','wap','mobile','class');
		if(!in_array($type, $types))
		{
			$type = $types[0];
		}
		if($type == 'class')
		{
			$instanceName = '%s';
			$classFile = '%s%s.%s.php';
		}
		elseif($kind == 'admin' && array_search($type, $types) < 3)
		{
			$instanceName = '%sAdmin%s';
			$classFile = '%s%s.admin.%s.php';
		}
		else
		{
			$instanceName = '%s%s';
			$classFile = '%s%s.%s.php';
		}

		$instanceName = sprintf($instanceName, $module, ucfirst($type));
		$classFile = FileHandler::getRealPath(sprintf($classFile, $classPath, $module, $type));
	}

	/**
	 * call a trigger
	 * @param string $trigger_name trigger's name to call
	 * @param string $called_position called position
	 * @param object $obj an object as a parameter to trigger
	 * @return BaseObject
	 * */
	public static function triggerCall($trigger_name, $called_position, &$obj)
	{
		// skip if not installed
		if(!Context::isInstalled())
		{
			return new BaseObject();
		}

		$oModuleModel = getModel('module');
		$triggers = $oModuleModel->getTriggers($trigger_name, $called_position);
		if(!$triggers)
		{
			$triggers = array();
		}
		
		//store before trigger call time
		$before_trigger_time = microtime(true);

		foreach($triggers as $item)
		{
			$module = $item->module;
			$type = $item->type;
			$called_method = $item->called_method;

			// todo why don't we call a normal class object ?
			$oModule = getModule($module, $type);
			if(!$oModule || !method_exists($oModule, $called_method))
			{
				continue;
			}
			
			// do not call if module is blacklisted
			if (Context::isBlacklistedPlugin($oModule->module))
			{
				continue;
			}
			
			try
			{
				$before_each_trigger_time = microtime(true);
				$output = $oModule->{$called_method}($obj);
				$after_each_trigger_time = microtime(true);
			}
			catch (Rhymix\Framework\Exception $e)
			{
				$output = new BaseObject(-2, $e->getMessage());
			}

			if ($trigger_name !== 'common.flushDebugInfo')
			{
				$trigger_target = $module . ($type === 'class' ? '' : $type) . '.' . $called_method;
				
				Rhymix\Framework\Debug::addTrigger(array(
					'name' => $trigger_name . '.' . $called_position,
					'target' => $trigger_target,
					'target_plugin' => $module,
					'elapsed_time' => $after_each_trigger_time - $before_each_trigger_time,
				));
			}

			if($output instanceof BaseObject && !$output->toBool())
			{
				return $output;
			}
			unset($oModule);
		}

		$trigger_functions = $oModuleModel->getTriggerFunctions($trigger_name, $called_position);
		foreach($trigger_functions as $item)
		{
			try
			{
				$before_each_trigger_time = microtime(true);
				$output = $item($obj);
				$after_each_trigger_time = microtime(true);
			}
			catch (Rhymix\Framework\Exception $e)
			{
				$output = new BaseObject(-2, $e->getMessage());
			}

			if ($trigger_name !== 'common.writeSlowlog')
			{
				if (is_string($item))
				{
					$trigger_target = $item;
				}
				elseif (is_array($item) && count($item))
				{
					if (is_object($item[0]))
					{
						$trigger_target = get_class($item[0]) . '.' . strval($item[1]);
					}
					else
					{
						$trigger_target = implode('.', $item);
					}
				}
				else
				{
					$trigger_target = 'closure';
				}
				
				Rhymix\Framework\Debug::addTrigger(array(
					'name' => $trigger_name . '.' . $called_position,
					'target' => $trigger_target,
					'target_plugin' => null,
					'elapsed_time' => $after_each_trigger_time - $before_each_trigger_time,
				));
			}

			if(is_object($output) && method_exists($output, 'toBool') && !$output->toBool())
			{
				return $output;
			}
		}

		return new BaseObject();
	}

	/**
	 * get http status message by http status code
	 * @param string $code
	 * @return string
	 * */
	public static function _setHttpStatusMessage($code)
	{
		$statusMessageList = array(
			'100' => 'Continue',
			'101' => 'Switching Protocols',
			'102' => 'Processing',
			'103' => 'Checkpoint',
			'200' => 'OK',
			'201' => 'Created',
			'202' => 'Accepted',
			'203' => 'Non-Authoritative Information',
			'204' => 'No Content',
			'205' => 'Reset Content',
			'206' => 'Partial Content',
			'207' => 'Multi-Status',
			'208' => 'Already Reported',
			'226' => 'IM Used',
			'300' => 'Multiple Choices',
			'301' => 'Moved Permanently',
			'302' => 'Found',
			'303' => 'See Other',
			'304' => 'Not Modified',
			'305' => 'Use Proxy',
			'306' => 'Switch Proxy',
			'307' => 'Temporary Redirect',
			'308' => 'Permanent Redirect',
			'400' => 'Bad Request',
			'401' => 'Unauthorized',
			'402' => 'Payment Required',
			'403' => 'Forbidden',
			'404' => 'Not Found',
			'405' => 'Method Not Allowed',
			'406' => 'Not Acceptable',
			'407' => 'Proxy Authentication Required',
			'408' => 'Request Timeout',
			'409' => 'Conflict',
			'410' => 'Gone',
			'411' => 'Length Required',
			'412' => 'Precondition Failed',
			'413' => 'Payload Too Large',
			'414' => 'URI Too Long',
			'415' => 'Unsupported Media Type',
			'416' => 'Range Not Satisfiable',
			'417' => 'Expectation Failed',
			'418' => 'I\'m a teapot',
			'420' => 'Enhance Your Calm',
			'421' => 'Misdirected Request',
			'422' => 'Unprocessable Entity',
			'423' => 'Locked',
			'424' => 'Failed Dependency',
			'425' => 'Unordered Collection',
			'426' => 'Upgrade Required',
			'428' => 'Precondition Required',
			'429' => 'Too Many Requests',
			'431' => 'Request Header Fields Too Large',
			'444' => 'No Response',
			'449' => 'Retry With',
			'451' => 'Unavailable For Legal Reasons',
			'500' => 'Internal Server Error',
			'501' => 'Not Implemented',
			'502' => 'Bad Gateway',
			'503' => 'Service Unavailable',
			'504' => 'Gateway Timeout',
			'505' => 'HTTP Version Not Supported',
			'506' => 'Variant Also Negotiates',
			'507' => 'Insufficient Storage',
			'508' => 'Loop Detected',
			'509' => 'Bandwidth Limit Exceeded',
			'510' => 'Not Extended',
			'511' => 'Network Authentication Required',
		);
		$statusMessage = $statusMessageList[strval($code)];
		if(!$statusMessage)
		{
			$statusMessage = 'OK';
		}

		Context::set('http_status_code', $code);
		Context::set('http_status_message', $statusMessage);
	}

}
/* End of file ModuleHandler.class.php */
/* Location: ./classes/module/ModuleHandler.class.php */
