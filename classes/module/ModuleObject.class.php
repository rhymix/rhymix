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
		$ajaxRequestMethod = array_flip($this->ajaxRequestMethod);
		if(!isset($ajaxRequestMethod[Context::getRequestMethod()]))
		{
			$this->add('redirect_url', $url);
		}

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
		// validate certificate info and permission settings necessary in Web-services
		$is_logged = Context::get('is_logged');
		$logged_info = Context::get('logged_info');
		// module model create an object
		$oModuleModel = getModel('module');
		// permission settings. access, manager(== is_admin) are fixed and privilege name in XE
		$module_srl = Context::get('module_srl');
		if(!$module_info->mid && !is_array($module_srl) && preg_match('/^([0-9]+)$/', $module_srl))
		{
			$request_module = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
			if($request_module->module_srl == $module_srl)
			{
				$grant = $oModuleModel->getGrant($request_module, $logged_info);
			}
		}
		else
		{
			$grant = $oModuleModel->getGrant($module_info, $logged_info, $xml_info);
			// have at least access grant
			if(substr_count($this->act, 'Member') || substr_count($this->act, 'Communication'))
			{
				$grant->access = 1;
			}
		}
		// display no permission if the current module doesn't have an access privilege
		//if(!$grant->access) return $this->stop("msg_not_permitted");
		// checks permission and action if you don't have an admin privilege
		if(!$grant->manager)
		{
			// get permission types(guest, member, manager, root) of the currently requested action
			$permission_target = $xml_info->permission->{$this->act};
			// check manager if a permission in module.xml otherwise action if no permission
			if(!$permission_target && substr_count($this->act, 'Admin'))
			{
				$permission_target = 'manager';
			}
			// Check permissions
			switch($permission_target)
			{
				case 'root' :
				case 'manager' :
					$this->stop('msg_is_not_administrator');
					return;
				case 'member' :
					if(!$is_logged)
					{
						$this->stop('msg_not_permitted_act');
						return;
					}
					break;
			}
		}
		// permission variable settings
		$this->grant = $grant;

		Context::set('grant', $grant);

		$this->module_config = $oModuleModel->getModuleConfig($this->module, $module_info->site_srl);

		if(method_exists($this, 'init'))
		{
			$this->init();
		}
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
			debugPrint($this->message, 'ERROR');
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

		if(is_a($output, 'Object') || is_subclass_of($output, 'Object'))
		{
			$this->setError($output->getError());
			$this->setMessage($output->getMessage());

			if(!$output->toBool())
			{
				return FALSE;
			}
		}
		// execute api methos of the module if view action is and result is XMLRPC or JSON
		if($this->module_info->module_type == 'view')
		{
			if(Context::getResponseMethod() == 'XMLRPC' || Context::getResponseMethod() == 'JSON')
			{
				$oAPI = getAPI($this->module_info->module, 'api');
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
