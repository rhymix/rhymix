<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * @class ModuleObject
 * @author NAVER (developers@xpressengine.com)
 * base class of ModuleHandler
 * */
class ModuleObject extends Object
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
	 * set message
	 * @param string $message a message string
	 * @param string $type type of message (error, info, update)
	 * @return void
	 * */
	function setMessage($message = 'success', $type = NULL)
	{
		parent::setMessage($message);
		$this->setMessageType($type);
	}

	/**
	 * set type of message
	 * @param string $type type of message (error, info, update)
	 * @return void
	 * */
	function setMessageType($type)
	{
		$this->add('message_type', $type);
	}

	/**
	 * get type of message
	 * @return string $type
	 * */
	function getMessageType()
	{
		$type = $this->get('message_type');
		$typeList = array('error' => 1, 'info' => 1, 'update' => 1);
		if(!isset($typeList[$type]))
		{
			$type = $this->getError() ? 'error' : 'info';
		}
		return $type;
	}

	/**
	 * sett to set the template path for refresh.html
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
	 * sett to set the action name
	 * @param string $act
	 * @return void
	 * */
	function setAct($act)
	{
		$this->act = $act;
	}

	/**
	 * sett to set module information
	 * @param object $module_info object containing module information
	 * @param object $xml_info object containing module description
	 * @return void
	 * */
	function setModuleInfo($module_info, $xml_info)
	{
		// The default variable settings
		$this->mid = $module_info->mid;
		$this->module_srl = $module_info->module_srl;
		$this->module_info = $module_info;
		$this->origin_module_info = $module_info;
		$this->xml_info = $xml_info;
		$this->skin_vars = $module_info->skin_vars;
		
		$oModuleModel = getModel('module');
		
		// variable module config
		$this->module_config = $oModuleModel->getModuleConfig($this->module, $module_info->site_srl);
		
		// Proceeding <permission check> of module.xml
		$permission_check = $xml_info->permission_check->{$this->act};
		
		// If permission check target is not the current module
		if($permission_check->key && $check_module_srl = Context::get($permission_check->key))
		{
			// If value is array
			if(is_array($check_module_srl) || preg_match('/,|\|@\|/', $check_module_srl, $delimiter))
			{
				// Convert string to array. delimiter is ,(comma) or |@|
				if(!is_array($check_module_srl))
				{
					if($delimiter[0])
					{
						$check_module_srl = explode($delimiter[0], $check_module_srl);
					}
					else
					{
						$check_module_srl = array($check_module_srl);
					}
				}
				
				// Check and Stop
				foreach($check_module_srl as $target_srl)
				{
					if($this->checkPermissionBySrl($target_srl, $permission_check->type, $xml_info) === false)
					{
						return;
					}
				}
				
				$checked = true;
			}
			// If value is string
			else
			{
				// only check, and return grant information
				if(($grant = $this->checkPermissionBySrl($check_module_srl, $permission_check->type)) === false)
				{
					return;
				}
			}
		}
		
		// Get grant information of user
		if(!isset($grant))
		{
			$grant = $oModuleModel->getGrant($module_info, Context::get('logged_info'), $xml_info);
		}
		
		// Check permissions
		if(!isset($checked) && !$this->checkPermission($xml_info, $grant))
		{
			return;
		}
		
		// permission variable settings
		$this->grant = $grant;
		Context::set('grant', $grant);
		
		// execute init
		if(method_exists($this, 'init'))
		{
			$this->init();
		}
	}
	
	/**
	 * Check permission by target_srl
	 * @param string $target_srl as module_srl. It may be a reference serial number
	 * @param string $type module name. get module_srl from module
	 * @param object $xml_info object containing module description. and if used, check permission
	 * @return mixed fail : false, success : true or object
	 * */
	function checkPermissionBySrl($target_srl, $type = null, $xml_info = null)
	{
		if(!preg_match('/^([0-9]+)$/', $target_srl))
		{
			$this->stop('msg_invalid_request');
			return false;
		}
		
		if($type)
		{
			if($type == 'document')
			{
				$target_srl = getModel('document')->getDocument($target_srl, false, false)->get('module_srl');
			}
			if($type == 'comment')
			{
				$target_srl = getModel('comment')->getComment($target_srl)->get('module_srl');
			}
		}
		
		$module_info = getModel('module')->getModuleInfoByModuleSrl($target_srl);
		
		if(!$module_info->module_srl)
		{
			$this->stop('msg_invalid_request');
			return false;
		}
		
		$grant = getModel('module')->getGrant($module_info, Context::get('logged_info'));
		
		if($xml_info)
		{
			// Check permissions
			if(!$this->checkPermission($xml_info, $grant))
			{
				return false;
			}
			
			return true;
		}
		
		return $grant;
	}
	
	/**
	 * Check permissions
	 * @param object $xml_info object containing module description
	 * @param object $grant grant information of user
	 * @return boolean true : success, false : fail
	 * */
	function checkPermission($xml_info, $grant = null)
	{
		// Get grant information
		if(!$grant)
		{
			$grant = getModel('module')->getGrant($this->module_info, Context::get('logged_info'), $xml_info);
		}
		
		// If manager, Pass
		if($grant->manager)
		{
			return true;
		}
		
		// get permission types(guest, member, manager, root) of the currently requested action
		$permission = $xml_info->permission->{$this->act};
		
		// check manager if a permission in module.xml otherwise action if no permission
		if(!$permission && substr_count($this->act, 'Admin'))
		{
			$permission = 'manager';
		}
		
		// Check permissions
		if($permission)
		{
			if($permission == 'member' && !Context::get('is_logged'))
			{
				$this->stop('msg_not_permitted_act');
				return false;
			}
			else if(in_array($permission, array('root', 'manager')))
			{
				$this->stop('admin.msg_is_not_administrator');
				return false;
			}
		}
		
		return true;
	}
	
	/**
	 * set the stop_proc and approprate message for msg_code
	 * @param string $msg_code an error code
	 * @return ModuleObject $this
	 * */
	function stop($msg_code)
	{
		// flag setting to stop the proc processing
		$this->stop_proc = TRUE;
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
		$addon_file = $oAddonController->getCacheFilePath(Mobile::isFromMobilePhone() ? "mobile" : "pc");
		if(FileHandler::exists($addon_file)) include($addon_file);

		if(isset($this->xml_info->action->{$this->act}) && method_exists($this, $this->act))
		{
			// Check permissions
			if($this->module_srl && !$this->grant->access)
			{
				$this->stop("msg_not_permitted_act");
				return FALSE;
			}

			// integrate skin information of the module(change to sync skin info with the target module only by seperating its table)
			$is_default_skin = ((!Mobile::isFromMobilePhone() && $this->module_info->is_skin_fix == 'N') || (Mobile::isFromMobilePhone() && $this->module_info->is_mskin_fix == 'N'));
			$usedSkinModule = !($this->module == 'page' && ($this->module_info->page_type == 'OUTSIDE' || $this->module_info->page_type == 'WIDGET'));
			if($usedSkinModule && $is_default_skin && $this->module != 'admin' && strpos($this->act, 'Admin') === false && $this->module == $this->module_info->module)
			{
				$dir = (Mobile::isFromMobilePhone()) ? 'm.skins' : 'skins';
				$valueName = (Mobile::isFromMobilePhone()) ? 'mskin' : 'skin';
				$oModuleModel = getModel('module');
				$skinType = (Mobile::isFromMobilePhone()) ? 'M' : 'P';
				$skinName = $oModuleModel->getModuleDefaultSkin($this->module, $skinType);
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

			$oModuleModel = getModel('module');
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
		if($output instanceof Object)
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
		$addon_file = $oAddonController->getCacheFilePath(Mobile::isFromMobilePhone() ? "mobile" : "pc");
		if(FileHandler::exists($addon_file)) include($addon_file);

		if($original_output instanceof Object && !$original_output->toBool())
		{
			return FALSE;
		}
		elseif($output instanceof Object && $output->getError())
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
