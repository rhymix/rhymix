<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class ModuleObject
 * @author NAVER (developers@xpressengine.com)
 * base class of ModuleHandler
 */
class ModuleObject extends BaseObject
{
	// Variables about the current module
	public $module;
	public $module_info;
	public $origin_module_info;
	public $module_config;
	public $module_path;
	public $xml_info;

	// Variables about the current module instance and the current request
	public $module_srl;
	public $mid;
	public $act;

	// Variables about the layout and/or template
	public $template_path;
	public $template_file;
	public $layout_path;
	public $layout_file;
	public $edited_layout_file;

	// Variables to control processing
	public $stop_proc = false;

	// Variables for convenience
	public $user;

	// Other variables for compatibility
	public $ajaxRequestMethod = array('XMLRPC', 'JSON');
	public $gzhandler_enable = true;

	/**
	 * Constructor
	 *
	 * @param int $error Error code
	 * @param string $message Error message
	 * @return void
	 */
	public function __construct($error = 0, $message = 'success')
	{
		parent::__construct($error, $message);
	}

	/**
	 * Singleton
	 * 
	 * @param string $module_hint (optional)
	 * @return static
	 */
	public static function getInstance($module_hint = null)
	{
		// If an instance already exists, return it.
		$class_name = static::class;
		if (isset($GLOBALS['_module_instances_'][$class_name]))
		{
			return $GLOBALS['_module_instances_'][$class_name];
		}

		// Get some information about the class.
		if ($module_hint)
		{
			$module_path = \RX_BASEDIR . 'modules/' . $module_hint . '/';
			$module = $module_hint;
		}
		else
		{
			$class_filename = (new ReflectionClass($class_name))->getFileName();
			preg_match('!^(.+[/\\\\]modules[/\\\\]([^/\\\\]+)[/\\\\])!', $class_filename, $matches);
			$module_path = $matches[1];
			$module = $matches[2];
		}

		// Create a new instance.
		$obj = new $class_name;

		// Populate default properties.
		$obj->setModulePath($module_path);
		$obj->setModule($module);
		$obj->user = Context::get('logged_info');
		if(!($obj->user instanceof Rhymix\Framework\Helpers\SessionHelper))
		{
			$obj->user = Rhymix\Framework\Session::getMemberInfo();
		}

		// Load language files.
		if($module !== 'module')
		{
			Context::loadLang($module_path . 'lang');
		}

		// Return the instance.
		return $GLOBALS['_module_instances_'][$class_name] = $obj;
	}

	/**
	 * setter to set the name of module
	 * 
	 * @param string $module name of module
	 * @return $this
	 */
	public function setModule($module)
	{
		$this->module = $module;
		return $this;
	}

	/**
	 * setter to set the name of module path
	 * 
	 * @param string $path the directory path to a module directory
	 * @return $this
	 */
	public function setModulePath($path)
	{
		if(substr_compare($path, '/', -1) !== 0)
		{
			$path.='/';
		}
		$this->module_path = $path;
		return $this;
	}

	/**
	 * setter to set an url for redirection
	 * 
	 * @param string $url url for redirection
	 * @return $this
	 */
	public function setRedirectUrl($url = './', $output = NULL)
	{
		$this->add('redirect_url', $url);

		if($output !== NULL && is_object($output))
		{
			return $output;
		}
		else
		{
			return $this;
		}
	}

	/**
	 * get url for redirection
	 * 
	 * @return string
	 */
	public function getRedirectUrl()
	{
		return $this->get('redirect_url');
	}

	/**
	 * Set the template path for refresh.html
	 * refresh.html is executed as a result of method execution
	 * Tpl as the common run of the refresh.html ..
	 * 
	 * @return $this
	 */
	public function setRefreshPage()
	{
		$this->setTemplatePath('./common/tpl');
		$this->setTemplateFile('refresh');
		return $this;
	}

	/**
	 * Set the action name
	 * 
	 * @param string $act
	 * @return $this
	 */
	public function setAct($act)
	{
		$this->act = $act;
		return $this;
	}
	
	/**
	 * Set module information
	 * 
	 * @param object $module_info object containing module information
	 * @param object $xml_info object containing module description
	 * @return $this
	 */
	public function setModuleInfo($module_info, $xml_info)
	{
		// Set default variables
		$this->mid = $module_info->mid;
		$this->module_srl = $module_info->module_srl ?? null;
		$this->module_info = $module_info;
		$this->origin_module_info = $module_info;
		$this->xml_info = $xml_info;
		$this->skin_vars = $module_info->skin_vars ?? null;
		$this->module_config = ModuleModel::getInstance()->getModuleConfig($this->module, $module_info->site_srl);
		
		// Set privileges(granted) information
		if($this->setPrivileges() !== true)
		{
			$this->stop('msg_not_permitted');
			return;
		}
		
		// Set admin layout
		if(preg_match('/^disp[A-Z][a-z0-9\_]+Admin/', $this->act))
		{
			if(config('view.manager_layout') === 'admin')
			{
				$this->setLayoutPath('modules/admin/tpl');
				$this->setLayoutFile('layout');
			}
			else
			{
				$oTemplate = TemplateHandler::getInstance();
				$oTemplate->compile('modules/admin/tpl', '_admin_common.html');
			}
		}
		
		// Execute init
		if(method_exists($this, 'init'))
		{
			try
			{
				$this->init();
			}
			catch (Rhymix\Framework\Exception $e)
			{
				$this->stop($e->getMessage());
			}
		}

		return $this;
	}
	
	/**
	 * Set privileges(granted) information of current user and check permission of current module
	 * 
	 * @return bool
	 */
	public function setPrivileges()
	{
		if(!$this->user->isAdmin())
		{
			// Get privileges(granted) information for target module by <permission check> of module.xml
			if(($permission = $this->xml_info->action->{$this->act}->permission) && $permission->check_var)
			{
				// Check parameter
				if(empty($check_module_srl = trim(Context::get($permission->check_var))))
				{
					return false;
				}
				
				// If value is not array
				if(!is_array($check_module_srl))
				{
					// Convert string to array. delimiter is ,(comma) or |@|
					if(preg_match('/,|\|@\|/', $check_module_srl, $delimiter) && $delimiter[0])
					{
						$check_module_srl = explode($delimiter[0], $check_module_srl);
					}
					else
					{
						$check_module_srl = array($check_module_srl);
					}
				}
				
				// Check permission by privileges(granted) information for target module
				foreach($check_module_srl as $target_srl)
				{
					// Get privileges(granted) information of current user for target module
					if(($grant = ModuleModel::getInstance()->getPrivilegesBySrl($target_srl, $permission->check_type)) === false)
					{
						return false;
					}
					
					// Check permission
					if($this->checkPermission($grant) !== true)
					{
						$this->stop('msg_not_permitted_act');
						return false;
					}
				}
			}
		}
		
		// If no privileges(granted) information, check permission by privileges(granted) information for current module
		if(!isset($grant))
		{
			// Get privileges(granted) information of current user for current module
			$grant = ModuleModel::getInstance()->getGrant($this->module_info, $this->user, $this->xml_info);
			
			// Check permission
			if($this->checkPermission($grant) !== true)
			{
				$this->stop('msg_not_permitted_act');
				return false;
			}
		}
		
		// If member action, grant access for log-in, sign-up, member pages
		if(preg_match('/^(disp|proc)(Member|Communication)[A-Z][a-zA-Z]+$/', $this->act))
		{
			$grant->access = true;
		}
		
		// Set privileges(granted) variables
		$this->grant = $grant;
		Context::set('grant', $grant);
		
		return true;
	}
	
	/**
	 * Check permission
	 * 
	 * @param object $grant privileges(granted) information of user
	 * @param object $member_info member information
	 * @return bool
	 */
	public function checkPermission($grant = null, $member_info = null)
	{
		// Get logged-in member information
		if(!$member_info)
		{
			$member_info = $this->user;
		}
		
		// Get privileges(granted) information of the member for current module
		if(!$grant)
		{
			$grant = ModuleModel::getGrant($this->module_info, $member_info, $this->xml_info);
		}
		
		// If an administrator, Pass
		if($grant->root)
		{
			return true;
		}
		
		// Get permission types(guest, member, manager, root) of the currently requested action
		$permission = $this->xml_info->action->{$this->act}->permission->target ?: ($this->xml_info->permission->{$this->act} ?? null);
		
		// If admin action, set default permission
		if(empty($permission) && stripos($this->act, 'admin') !== false)
		{
			$permission = 'root';
		}
		
		// If permission is not or 'guest', Pass
		if(empty($permission) || $permission == 'guest')
		{
			return true;
		}
		// If permission is 'member', check logged-in
		else if($permission == 'member')
		{
			if(Context::get('is_logged'))
			{
				return true;
			}
		}
		// If permission is 'manager', check 'is user have manager privilege(granted)'
		else if(preg_match('/^(manager|([a-z0-9\_]+)-managers)$/', $permission, $type))
		{
			if($grant->manager)
			{
				return true;
			}
			
			// If permission is '*-managers', search modules to find manager privilege of the member
			if(Context::get('is_logged') && isset($type[2]))
			{
				// Manager privilege of the member is found by search all modules, Pass
				if($type[2] == 'all' && ModuleModel::findManagerPrivilege($member_info) !== false)
				{
					return true;
				}
				// Manager privilege of the member is found by search same module as this module, Pass
				elseif($type[2] == 'same' && ModuleModel::findManagerPrivilege($member_info, $this->module) !== false)
				{
					return true;
				}
				// Manager privilege of the member is found by search same module as the module, Pass
				elseif(ModuleModel::findManagerPrivilege($member_info, $type[2]) !== false)
				{
					return true;
				}
			}
		}
		// If permission is 'root', false
		// Because an administrator who have root privilege(granted) was passed already
		else if($permission == 'root')
		{
			return false;
		}
		// If grant name, check the privilege(granted) of the user
		else if($grant_names = explode(',', $permission))
		{
			$privilege_list = array_keys((array) $this->xml_info->grant);
			
			foreach($grant_names as $name)
			{
				if(!in_array($name, $privilege_list) || !$grant->$name)
				{
					return false;
				}
			}
			
			return true;
		}
		
		return false;
	}
	
	/**
	 * Stop processing this module instance.
	 * 
	 * @param string $msg_code an error code
	 * @return ModuleObject $this
	 */
	public function stop($msg_code)
	{
		if($this->stop_proc !== true)
		{
			// flag setting to stop the proc processing
			$this->stop_proc = true;
			
			// Error handling
			$this->setError(-1);
			$this->setMessage($msg_code);
			
			// Get backtrace
			$backtrace = debug_backtrace(false);
			$caller = array_shift($backtrace);
			$location = $caller['file'] . ':' . $caller['line'];
			
			// Error message display by message module
			$type = Mobile::isFromMobilePhone() ? 'mobile' : 'view';
			$oMessageObject = ModuleHandler::getModuleInstance('message', $type);
			$oMessageObject->setError(-1);
			$oMessageObject->setMessage($msg_code);
			$oMessageObject->dispMessage(null, $location);
			
			$this->setTemplatePath($oMessageObject->getTemplatePath());
			$this->setTemplateFile($oMessageObject->getTemplateFile());
			$this->setHttpStatusCode($oMessageObject->getHttpStatusCode());
		}
		
		return $this;
	}

	/**
	 * set the file name of the template file
	 * 
	 * @param string name of file
	 * @return $this
	 */
	public function setTemplateFile($filename)
	{
		if(isset($filename) && substr_compare($filename, '.html', -5) !== 0)
		{
			$filename .= '.html';
		}
		$this->template_file = $filename;
		return $this;
	}

	/**
	 * retrieve the directory path of the template directory
	 * 
	 * @return string
	 */
	public function getTemplateFile()
	{
		return $this->template_file;
	}

	/**
	 * set the directory path of the template directory
	 * 
	 * @param string path of template directory.
	 * @return $this
	 */
	public function setTemplatePath($path)
	{
		if(!$path) return $this;
		if (!preg_match('!^(?:\\.?/|[A-Z]:[\\\\/]|\\\\\\\\)!i', $path))
		{
			$path = './' . $path;
		}
		if(substr_compare($path, '/', -1) !== 0)
		{
			$path .= '/';
		}
		$this->template_path = $path;
		return $this;
	}

	/**
	 * retrieve the directory path of the template directory
	 * 
	 * @return string
	 */
	public function getTemplatePath()
	{
		return $this->template_path;
	}

	/**
	 * set the file name of the temporarily modified by admin
	 * 
	 * @param string name of file
	 * @return $this
	 */
	public function setEditedLayoutFile($filename)
	{
		if(!$filename) return $this;

		if(substr_compare($filename, '.html', -5) !== 0)
		{
			$filename .= '.html';
		}
		$this->edited_layout_file = $filename;
		return $this;
	}

	/**
	 * retreived the file name of edited_layout_file
	 * 
	 * @return string
	 */
	public function getEditedLayoutFile()
	{
		return $this->edited_layout_file;
	}

	/**
	 * set the file name of the layout file
	 * 
	 * @param string name of file
	 * @return $this
	 */
	public function setLayoutFile($filename)
	{
		if($filename && substr_compare($filename, '.html', -5) !== 0)
		{
			$filename .= '.html';
		}
		$this->layout_file = $filename;
		return $this;
	}

	/**
	 * get the file name of the layout file
	 * 
	 * @return string
	 */
	public function getLayoutFile()
	{
		return $this->layout_file;
	}

	/**
	 * set the directory path of the layout directory
	 * 
	 * @param string path of layout directory.
	 * @return $this
	 */
	public function setLayoutPath($path)
	{
		if(!$path) return;
		if (!preg_match('!^(?:\\.?/|[A-Z]:[\\\\/]|\\\\\\\\)!i', $path))
		{
			$path = './' . $path;
		}
		if(substr_compare($path, '/', -1) !== 0)
		{
			$path .= '/';
		}
		$this->layout_path = $path;
		return $this;
	}

	/**
	 * set the directory path of the layout directory
	 * 
	 * @return string
	 */
	public function getLayoutPath($layout_name = "", $layout_type = "P")
	{
		return $this->layout_path;
	}

	/**
	 * excute the member method specified by $act variable
	 * @return bool
	 */
	public function proc()
	{
		// pass if stop_proc is true
		if($this->stop_proc)
		{
			return FALSE;
		}
		
		// Check mobile status
		$is_mobile = Mobile::isFromMobilePhone();

		// trigger call
		$triggerOutput = ModuleHandler::triggerCall('moduleObject.proc', 'before', $this);
		if(!$triggerOutput->toBool())
		{
			$this->setError($triggerOutput->getError());
			$this->setMessage($triggerOutput->getMessage());
			return FALSE;
		}

		// execute an addon(call called_position as before_module_proc)
		$called_position = 'before_module_proc';
		$oAddonController = AddonController::getInstance();
		$addon_file = $oAddonController->getCacheFilePath($is_mobile ? "mobile" : "pc");
		if(FileHandler::exists($addon_file)) include($addon_file);
		
		// Check mobile status again, in case a trigger changed it
		$is_mobile = Mobile::isFromMobilePhone();

		// Perform action if it exists
		if(isset($this->xml_info->action->{$this->act}) && method_exists($this, $this->act))
		{
			// Check permissions
			if($this->module_srl && !$this->grant->access)
			{
				$this->stop("msg_not_permitted_act");
				return FALSE;
			}
			
			// Set module skin
			if(isset($this->module_info->skin) && $this->module_info->module === $this->module && strpos($this->act, 'Admin') === false)
			{
				$skin_type = $is_mobile ? 'M' : 'P';
				$skin_key = $is_mobile ? 'mskin' : 'skin';
				$skin_dir = $is_mobile ? 'm.skins' : 'skins';
				$module_skin = $this->module_info->{$skin_key} ?: '/USE_DEFAULT/';
				$use_default_skin = $this->module_info->{'is_' . $skin_key . '_fix'} === 'N';
				
				// Set default skin
				if(!$this->getTemplatePath() || $use_default_skin)
				{
					if($module_skin === '/USE_DEFAULT/')
					{
						$module_skin = ModuleModel::getModuleDefaultSkin($this->module, $skin_type);
						$this->module_info->{$skin_key} = $module_skin;
					}
					if($module_skin === '/USE_RESPONSIVE/')
					{
						$skin_dir = 'skins';
						$module_skin = $this->module_info->skin ?: '/USE_DEFAULT/';
						if($module_skin === '/USE_DEFAULT/')
						{
							$module_skin = ModuleModel::getModuleDefaultSkin($this->module, 'P');
						}
					}
					if(!is_dir(sprintf('%s%s/%s', $this->module_path, $skin_dir, $module_skin)))
					{
						$module_skin = 'default';
					}
					$this->setTemplatePath(sprintf('%s%s/%s', $this->module_path, $skin_dir, $module_skin));
				}
				
				// Set skin variable
				ModuleModel::syncSkinInfoToModuleInfo($this->module_info);
				Context::set('module_info', $this->module_info);
			}
			
			// Run
			try
			{
				$output = $this->{$this->act}();
			}
			catch (Rhymix\Framework\Exception $e)
			{
				$output = new BaseObject(-2, $e->getMessage());
				$location = $e->getFile() . ':' . $e->getLine();
				$output->add('rx_error_location', $location);
			}
		}
		else
		{
			return FALSE;
		}

		// check return value of action
		if($output instanceof BaseObject)
		{
			$this->setError($output->getError());
			$this->setMessage($output->getMessage());
			if($output->getError() && $output->get('rx_error_location'))
			{
				$this->add('rx_error_location', $output->get('rx_error_location'));
			}
			$original_output = clone $output;
		}
		else
		{
			$original_output = null;
		}

		// trigger call
		$triggerOutput = ModuleHandler::triggerCall('moduleObject.proc', 'after', $this);
		if(!$triggerOutput->toBool())
		{
			$this->setError($triggerOutput->getError());
			$this->setMessage($triggerOutput->getMessage());
			if($triggerOutput->get('rx_error_location'))
			{
				$this->add('rx_error_location', $triggerOutput->get('rx_error_location'));
			}
			return FALSE;
		}

		// execute an addon(call called_position as after_module_proc)
		$called_position = 'after_module_proc';
		$oAddonController = AddonController::getInstance();
		$addon_file = $oAddonController->getCacheFilePath($is_mobile ? "mobile" : "pc");
		if(FileHandler::exists($addon_file)) include($addon_file);

		if($original_output instanceof BaseObject && !$original_output->toBool())
		{
			return FALSE;
		}
		elseif($output instanceof BaseObject && $output->getError())
		{
			$this->setError($output->getError());
			$this->setMessage($output->getMessage());
			if($output->get('rx_error_location'))
			{
				$this->add('rx_error_location', $output->get('rx_error_location'));
			}
			return FALSE;
		}

		// execute api methods of the module if view action is and result is XMLRPC or JSON
		if($this->module_info->module_type == 'view' || $this->module_info->module_type == 'mobile')
		{
			if(Context::getResponseMethod() == 'XMLRPC' || Context::getResponseMethod() == 'JSON')
			{
				$oAPI = getAPI($this->module_info->module);
				if($oAPI instanceof ModuleObject && method_exists($oAPI, $this->act))
				{
					$oAPI->{$this->act}($this);
				}
			}
		}
		return TRUE;
	}

}
/* End of file ModuleObject.class.php */
/* Location: ./classes/module/ModuleObject.class.php */
