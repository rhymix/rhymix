<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class ModuleObject
 * @author NAVER (developers@xpressengine.com)
 * base class of ModuleHandler
 * */
class ModuleObject extends BaseObject
{

	var $mid = NULL; ///< string to represent run-time instance of Module (XE Module)
	var $module = NULL; ///< Class name of Xe Module that is identified by mid
	var $module_srl = NULL; ///< integer value to represent a run-time instance of Module (XE Module)
	var $module_info = NULL; ///< an object containing the module information
	var $origin_module_info = NULL;
	var $xml_info = NULL; ///< an object containing the module description extracted from XML file
	var $module_path = NULL; ///< a path to directory where module source code resides
	var $act = NULL; ///< a string value to contain the action name
	var $template_path = NULL; ///< a path of directory where template files reside
	var $template_file = NULL; ///< name of template file
	var $layout_path = ''; ///< a path of directory where layout files reside
	var $layout_file = ''; ///< name of layout file
	var $edited_layout_file = ''; ///< name of temporary layout files that is modified in an admin mode
	var $stop_proc = FALSE; ///< a flag to indicating whether to stop the execution of code.
	var $module_config = NULL;
	var $ajaxRequestMethod = array('XMLRPC', 'JSON');
	var $gzhandler_enable = TRUE;
	var $user = FALSE;

	/**
	 * Constructor
	 *
	 * @param int $error Error code
	 * @param string $message Error message
	 * @return void
	 */
	function __construct($error = 0, $message = 'success')
	{
		$this->user = Context::get('logged_info') ?: new Rhymix\Framework\Helpers\SessionHelper;
		if(!($this->user instanceof Rhymix\Framework\Helpers\SessionHelper))
		{
			$this->user = Rhymix\Framework\Session::getMemberInfo();
		}
		parent::__construct($error, $message);
	}

	/**
	 * setter to set the name of module
	 * @param string $module name of module
	 * @return void
	 * */
	function setModule($module)
	{
		$this->module = $module;
	}

	/**
	 * setter to set the name of module path
	 * @param string $path the directory path to a module directory
	 * @return void
	 * */
	function setModulePath($path)
	{
		if(substr_compare($path, '/', -1) !== 0)
		{
			$path.='/';
		}
		$this->module_path = $path;
	}

	/**
	 * setter to set an url for redirection
	 * @param string $url url for redirection
	 * @remark redirect_url is used only for ajax requests
	 * @return void
	 * */
	function setRedirectUrl($url = './', $output = NULL)
	{
		$this->add('redirect_url', $url);

		if($output !== NULL && is_object($output))
		{
			return $output;
		}
	}

	/**
	 * get url for redirection
	 * @return string redirect_url
	 * */
	function getRedirectUrl()
	{
		return $this->get('redirect_url');
	}

	/**
	 * Set the template path for refresh.html
	 * refresh.html is executed as a result of method execution
	 * Tpl as the common run of the refresh.html ..
	 * @return void
	 * */
	function setRefreshPage()
	{
		$this->setTemplatePath('./common/tpl');
		$this->setTemplateFile('refresh');
	}

	/**
	 * Set the action name
	 * @param string $act
	 * @return void
	 * */
	function setAct($act)
	{
		$this->act = $act;
	}
	
	/**
	 * Set module information
	 * @param object $module_info object containing module information
	 * @param object $xml_info object containing module description
	 * @return void
	 * */
	function setModuleInfo($module_info, $xml_info)
	{
		// Set default variables
		$this->mid = $module_info->mid;
		$this->module_srl = $module_info->module_srl;
		$this->module_info = $module_info;
		$this->origin_module_info = $module_info;
		$this->xml_info = $xml_info;
		$this->skin_vars = $module_info->skin_vars;
		$this->module_config = getModel('module')->getModuleConfig($this->module, $module_info->site_srl);
		
		// Set privileges(granted) information
		if($this->setPrivileges() !== true)
		{
			$this->stop('msg_invalid_request');
			return;
		}
		
		// Set admin layout
		if(preg_match('/^disp[A-Z][a-z0-9\_]+Admin/', $this->act))
		{
			/*
			$this->setLayoutPath('modules/admin/tpl');
			$this->setLayoutFile('layout');
			*/
			$oTemplate = TemplateHandler::getInstance();
			$oTemplate->compile('modules/admin/tpl', '_admin_common.html');
		}
		
		// Execute init
		if(method_exists($this, 'init'))
		{
			$this->init();
		}
	}
	
	/**
	 * Set privileges(granted) information of current user and check permission of current module
	 * @return boolean success : true, fail : false
	 * */
	function setPrivileges()
	{
		if(Context::get('logged_info')->is_admin !== 'Y')
		{
			// Get privileges(granted) information for target module by <permission check> of module.xml
			if(($permission_check = $this->xml_info->permission_check->{$this->act}) && $permission_check->key)
			{
				// Check parameter
				if(empty($check_module_srl = trim(Context::get($permission_check->key))))
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
					if(($grant = getModel('module')->getPrivilegesBySrl($target_srl, $permission_check->type)) === false)
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
			$grant = getModel('module')->getGrant($this->module_info, Context::get('logged_info'), $this->xml_info);
			
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
	 * @param object $grant privileges(granted) information of user
	 * @param object $member_info member information
	 * @return boolean success : true, fail : false
	 * */
	function checkPermission($grant = null, $member_info = null)
	{
		// Get logged-in member information
		if(!$member_info)
		{
			$member_info = Context::get('logged_info');
		}
		
		// Get privileges(granted) information of the member for current module
		if(!$grant)
		{
			$grant = getModel('module')->getGrant($this->module_info, $member_info, $this->xml_info);
		}
		
		// If an administrator, Pass
		if($grant->root)
		{
			return true;
		}
		
		// Get permission types(guest, member, manager, root) of the currently requested action
		$permission = $this->xml_info->permission->{$this->act};
		
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
				if($type[2] == 'all' && getModel('module')->findManagerPrivilege($member_info) !== false)
				{
					return true;
				}
				// Manager privilege of the member is found by search same module as this module, Pass
				else if($type[2] == 'same' && getModel('module')->findManagerPrivilege($member_info, $this->module) !== false)
				{
					return true;
				}
				// Manager privilege of the member is found by search same module as the module, Pass
				else if(getModel('module')->findManagerPrivilege($member_info, $type[2]) !== false)
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
	 * set the stop_proc and approprate message for msg_code
	 * @param string $msg_code an error code
	 * @return ModuleObject $this
	 * */
	function stop($msg_code)
	{
		if($this->stop_proc !== true)
		{
			// flag setting to stop the proc processing
			$this->stop_proc = true;
			
			// Error handling
			$this->setError(-1);
			$this->setMessage($msg_code);
			
			// Error message display by message module
			$type = Mobile::isFromMobilePhone() ? 'mobile' : 'view';
			$oMessageObject = ModuleHandler::getModuleInstance('message', $type);
			$oMessageObject->setError(-1);
			$oMessageObject->setMessage($msg_code);
			$oMessageObject->dispMessage();
			
			$this->setTemplatePath($oMessageObject->getTemplatePath());
			$this->setTemplateFile($oMessageObject->getTemplateFile());
			$this->setHttpStatusCode($oMessageObject->getHttpStatusCode());
		}
		
		return $this;
	}

	/**
	 * set the file name of the template file
	 * @param string name of file
	 * @return void
	 * */
	function setTemplateFile($filename)
	{
		if(isset($filename) && substr_compare($filename, '.html', -5) !== 0)
		{
			$filename .= '.html';
		}
		$this->template_file = $filename;
	}

	/**
	 * retrieve the directory path of the template directory
	 * @return string
	 * */
	function getTemplateFile()
	{
		return $this->template_file;
	}

	/**
	 * set the directory path of the template directory
	 * @param string path of template directory.
	 * @return void
	 * */
	function setTemplatePath($path)
	{
		if(!$path) return;

		if((strlen($path) >= 1 && substr_compare($path, '/', 0, 1) !== 0) && (strlen($path) >= 2 && substr_compare($path, './', 0, 2) !== 0))
		{
			$path = './' . $path;
		}

		if(substr_compare($path, '/', -1) !== 0)
		{
			$path .= '/';
		}
		$this->template_path = $path;
	}

	/**
	 * retrieve the directory path of the template directory
	 * @return string
	 * */
	function getTemplatePath()
	{
		return $this->template_path;
	}

	/**
	 * set the file name of the temporarily modified by admin
	 * @param string name of file
	 * @return void
	 * */
	function setEditedLayoutFile($filename)
	{
		if(!$filename) return;

		if(substr_compare($filename, '.html', -5) !== 0)
		{
			$filename .= '.html';
		}
		$this->edited_layout_file = $filename;
	}

	/**
	 * retreived the file name of edited_layout_file
	 * @return string
	 * */
	function getEditedLayoutFile()
	{
		return $this->edited_layout_file;
	}

	/**
	 * set the file name of the layout file
	 * @param string name of file
	 * @return void
	 * */
	function setLayoutFile($filename)
	{
		if(!$filename) return;

		if(substr_compare($filename, '.html', -5) !== 0)
		{
			$filename .= '.html';
		}
		$this->layout_file = $filename;
	}

	/**
	 * get the file name of the layout file
	 * @return string
	 * */
	function getLayoutFile()
	{
		return $this->layout_file;
	}

	/**
	 * set the directory path of the layout directory
	 * @param string path of layout directory.
	 * */
	function setLayoutPath($path)
	{
		if(!$path) return;

		if((strlen($path) >= 1 && substr_compare($path, '/', 0, 1) !== 0) && (strlen($path) >= 2 && substr_compare($path, './', 0, 2) !== 0))
		{
			$path = './' . $path;
		}
		if(substr_compare($path, '/', -1) !== 0)
		{
			$path .= '/';
		}
		$this->layout_path = $path;
	}

	/**
	 * set the directory path of the layout directory
	 * @return string
	 * */
	function getLayoutPath($layout_name = "", $layout_type = "P")
	{
		return $this->layout_path;
	}

	/**
	 * excute the member method specified by $act variable
	 * @return boolean true : success false : fail
	 * */
	function proc()
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
		$oAddonController = getController('addon');
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
			
			// integrate skin information of the module(change to sync skin info with the target module only by seperating its table)
			$oModuleModel = getModel('module');
			$default_skin = ((!$is_mobile && $this->module_info->is_skin_fix == 'N') || ($is_mobile && $this->module_info->is_mskin_fix == 'N'));
			$disable_skin = ($this->module == 'page' && ($this->module_info->page_type == 'OUTSIDE' || $this->module_info->page_type == 'WIDGET'));
			if(!$disable_skin && $default_skin && $this->module != 'admin' && strpos($this->act, 'Admin') === false && $this->module == $this->module_info->module)
			{
				$skinType = ($is_mobile && $this->module_info->mskin !== '/USE_RESPONSIVE/') ? 'M' : 'P';
				$dir = $skinType === 'M' ? 'm.skins' : 'skins';
				$valueName = $skinType === 'M' ? 'mskin' : 'skin';
				$skinName = $this->module_info->{$valueName} === '/USE_DEFAULT/' ? $oModuleModel->getModuleDefaultSkin($this->module, $skinType) : $this->module_info->{$valueName};
				if($this->module == 'page')
				{
					$this->module_info->{$valueName} = $skinName;
				}
				else
				{
					$isTemplatPath = (strpos($this->getTemplatePath(), '/tpl/') !== FALSE);
					if(!$isTemplatPath)
					{
						$this->setTemplatePath(sprintf('%s%s/%s/', $this->module_path, $dir, $skinName));
					}
				}
			}

			$oModuleModel->syncSkinInfoToModuleInfo($this->module_info);
			Context::set('module_info', $this->module_info);
			// Run
			$output = $this->{$this->act}();
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
			return FALSE;
		}

		// execute an addon(call called_position as after_module_proc)
		$called_position = 'after_module_proc';
		$oAddonController = getController('addon');
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
			return FALSE;
		}

		// execute api methods of the module if view action is and result is XMLRPC or JSON
		if($this->module_info->module_type == 'view' || $this->module_info->module_type == 'mobile')
		{
			if(Context::getResponseMethod() == 'XMLRPC' || Context::getResponseMethod() == 'JSON')
			{
				$oAPI = getAPI($this->module_info->module);
				if(method_exists($oAPI, $this->act))
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
