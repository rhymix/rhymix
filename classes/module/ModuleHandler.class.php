<?php
    /**
    * @class ModuleHandler
    * @author NHN (developers@xpressengine.com)
    * @brief Handling modules
    *
    * @remarks This class is to excute actions of modules.
    *          Constructing an instance without any parameterconstructor, it finds the target module based on Context.
    *          If there is no act on the found module, excute an action referencing action_forward.
    **/

    class ModuleHandler extends Handler {

        var $module = NULL; ///< Module
        var $act = NULL; ///< action
        var $mid = NULL; ///< Module ID
        var $document_srl = NULL; ///< Document Number
        var $module_srl = NULL; ///< Module Number

        var $module_info = NULL; ///< Module Info. Object

        var $error = NULL; ///< an error code.

        /**
         * @brief constructor
         * @remarks it prepares variables to use in moduleHandler
         **/
        function ModuleHandler($module = '', $act = '', $mid = '', $document_srl = '', $module_srl = '') {
            // If XE has not installed yet, set module as install
            if(!Context::isInstalled()) {
                $this->module = 'install';
                $this->act = Context::get('act');
                return;
            }

            // Set variables from request arguments
            $this->module = $module?$module:Context::get('module');
            $this->act    = $act?$act:Context::get('act');
            $this->mid    = $mid?$mid:Context::get('mid');
            $this->document_srl = $document_srl?(int)$document_srl:(int)Context::get('document_srl');
            $this->module_srl   = $module_srl?(int)$module_srl:(int)Context::get('module_srl');
            $this->entry  = Context::convertEncodingStr(Context::get('entry'));

            // Validate variables to prevent XSS
			$isInvalid = null;
            if($this->module && !preg_match("/^([a-z0-9\_\-]+)$/i",$this->module)) $isInvalid = true;
            if($this->mid && !preg_match("/^([a-z0-9\_\-]+)$/i",$this->mid)) $isInvalid = true;
            if($this->act && !preg_match("/^([a-z0-9\_\-]+)$/i",$this->act)) $isInvalid = true;
			if ($isInvalid)
			{
				htmlHeader();
				echo Context::getLang("msg_invalid_request");
				htmlFooter();
				Context::close();
				exit;
			}

            // execute addon (before module initialization)
            $called_position = 'before_module_init';
            $oAddonController = &getController('addon');
            $addon_file = $oAddonController->getCacheFilePath(Mobile::isFromMobilePhone()?'mobile':'pc');
            @include($addon_file);
        }

        /**
         * @brief Initialization. It finds the target module based on module, mid, document_srl, and prepares to execute an action
         * @return true: OK, false: redirected
         **/
        function init() {
			$oModuleModel = &getModel('module');
            $site_module_info = Context::get('site_module_info');

            if(!$this->document_srl && $this->mid && $this->entry) {
                $oDocumentModel = &getModel('document');
                $this->document_srl = $oDocumentModel->getDocumentSrlByAlias($this->mid, $this->entry);
                if($this->document_srl) Context::set('document_srl', $this->document_srl);
            }

            // Get module's information based on document_srl, if it's specified
            if($this->document_srl && !$this->module) {
                $module_info = $oModuleModel->getModuleInfoByDocumentSrl($this->document_srl);

                // If the document does not exist, remove document_srl
                if(!$module_info) {
                    unset($this->document_srl);
                } else {
                    // If it exists, compare mid based on the module information
                    // if mids are not matching, set it as the document's mid
                    if($this->mid != $module_info->mid) {
                        $this->mid = $module_info->mid;
                        Context::set('mid', $module_info->mid, true);
                    }
                }
                // if requested module is different from one of the document, remove the module information retrieved based on the document number
                if($this->module && $module_info->module != $this->module) unset($module_info);
            }

            // If module_info is not set yet, and there exists mid information, get module information based on the mid
            if(!$module_info && $this->mid) {
                $module_info = $oModuleModel->getModuleInfoByMid($this->mid, $site_module_info->site_srl);
                //if($this->module && $module_info->module != $this->module) unset($module_info);
            }

            // redirect, if module_site_srl and site_srl are different
            if(!$this->module && !$module_info && $site_module_info->site_srl == 0 && $site_module_info->module_site_srl > 0) {
                $site_info = $oModuleModel->getSiteInfo($site_module_info->module_site_srl);
                header("location:".getNotEncodedSiteUrl($site_info->domain,'mid',$site_module_info->mid));
                return false;
            }

            // If module_info is not set still, and $module does not exist, find the default module
            if(!$module_info && !$this->module) $module_info = $site_module_info;

            if(!$module_info && !$this->module && $site_module_info->module_site_srl) $module_info = $site_module_info;

            // redirect, if site_srl of module_info is different from one of site's module_info
            if($module_info && $module_info->site_srl != $site_module_info->site_srl && !isCrawler()) {
                // If the module is of virtual site
                if($module_info->site_srl) {
                    $site_info = $oModuleModel->getSiteInfo($module_info->site_srl);
                    $redirect_url = getNotEncodedSiteUrl($site_info->domain, 'mid',Context::get('mid'),'document_srl',Context::get('document_srl'),'module_srl',Context::get('module_srl'),'entry',Context::get('entry'));
                // If it's called from a virtual site, though it's not a module of the virtual site
                } else {
                    $db_info = Context::getDBInfo();
                    if(!$db_info->default_url) return Context::getLang('msg_default_url_is_not_defined');
                    else $redirect_url = getNotEncodedSiteUrl($db_info->default_url, 'mid',Context::get('mid'),'document_srl',Context::get('document_srl'),'module_srl',Context::get('module_srl'),'entry',Context::get('entry'));
                }
                header("location:".$redirect_url);
                return false;
            }

            // If module info was set, retrieve variables from the module information
            if($module_info) {
                $this->module = $module_info->module;
                $this->mid = $module_info->mid;
                $this->module_info = $module_info;
                Context::setBrowserTitle($module_info->browser_title);
                $part_config= $oModuleModel->getModulePartConfig('layout',$module_info->layout_srl);
                Context::addHtmlHeader($part_config->header_script);
            }

            // Set module and mid into module_info
            $this->module_info->module = $this->module;
            $this->module_info->mid = $this->mid;

			// Set site_srl add 2011 08 09
			$this->module_info->site_srl = $site_module_info->site_srl;

            // Still no module? it's an error
            if(!$this->module) $this->error = 'msg_module_does_not_exist';

            // If mid exists, set mid into context
            if($this->mid) Context::set('mid', $this->mid, true);

            // Call a trigger after moduleHandler init
            $output = ModuleHandler::triggerCall('moduleHandler.init', 'after', $this->module_info);
            if(!$output->toBool()) {
                $this->error = $output->getMessage();
                return false;
            }

            // Set current module info into context
            Context::set('current_module_info', $this->module_info);

            return true;
        }

        /**
         * @brief get a module instance and execute an action
         * @return executed module instance
         **/
        function procModule() {
            $oModuleModel = &getModel('module');

            // If error occurred while preparation, return a message instance
            if($this->error) {
				$type = Mobile::isFromMobilePhone() ? 'mobile' : 'view';
                $oMessageObject = &ModuleHandler::getModuleInstance('message',$type);
                $oMessageObject->setError(-1);
                $oMessageObject->setMessage($this->error);
                $oMessageObject->dispMessage();
                return $oMessageObject;
            }

            // Get action information with conf/module.xml
            $xml_info = $oModuleModel->getModuleActionXml($this->module);

            // If not installed yet, modify act
            if($this->module=="install") {
                if(!$this->act || !$xml_info->action->{$this->act}) $this->act = $xml_info->default_index_act;
            }

            // if act exists, find type of the action, if not use default index act
            if(!$this->act) $this->act = $xml_info->default_index_act;

            // still no act means error
            if(!$this->act) {
                $this->error = 'msg_module_does_not_exist';
                return;
            }

            // get type, kind
            $type = $xml_info->action->{$this->act}->type;
            $ruleset = $xml_info->action->{$this->act}->ruleset;
            $kind = strpos(strtolower($this->act),'admin')!==false?'admin':'';
            if(!$kind && $this->module == 'admin') $kind = 'admin';
			if($this->module_info->use_mobile != "Y") Mobile::setMobile(false);

			$logged_info = Context::get('logged_info');

			if($kind == 'admin' && $logged_info->is_admin == 'Y'){
				$oModuleAdminModel = &getAdminModel('module');
				if(!$oModuleAdminModel->getModuleAdminIPCheck()) {
					$this->error = "msg_not_permitted_act";
					$oMessageObject = &ModuleHandler::getModuleInstance('message',$type);
					$oMessageObject->setError(-1);
					$oMessageObject->setMessage($this->error);
					$oMessageObject->dispMessage();
					return $oMessageObject;
				}

			}
			unset($logged_info);
			
			// if(type == view, and case for using mobilephone)
			if($type == "view" && Mobile::isFromMobilePhone() && Context::isInstalled())
			{
				$orig_type = "view";
				$type = "mobile";
				// create a module instance
				$oModule = &$this->getModuleInstance($this->module, $type, $kind);
				if(!is_object($oModule) || !method_exists($oModule, $this->act)) {
					$type = $orig_type;
					Mobile::setMobile(false);
					$oModule = &$this->getModuleInstance($this->module, $type, $kind);
				}
			}
			else
			{
				// create a module instance
				$oModule = &$this->getModuleInstance($this->module, $type, $kind);
			}

			if(!is_object($oModule)) {
				$this->error = 'msg_module_does_not_exist';
				return;
			}

			// If there is no such action in the module object
			if(!isset($xml_info->action->{$this->act}) || !method_exists($oModule, $this->act))
			{
				if(!Context::isInstalled())
				{
					$this->error = 'msg_invalid_request';
					return;
				}

                $forward = null;
				// 1. Look for the module with action name
                if(preg_match('/^([a-z]+)([A-Z])([a-z0-9\_]+)(.*)$/', $this->act, $matches)) {
                    $module = strtolower($matches[2].$matches[3]);
                    $xml_info = $oModuleModel->getModuleActionXml($module);
                    if($xml_info->action->{$this->act}) {
                        $forward->module = $module;
                        $forward->type = $xml_info->action->{$this->act}->type;
            			$forward->ruleset = $xml_info->action->{$this->act}->ruleset;
                        $forward->act = $this->act;
                    }
                }

				if(!$forward)
				{
					$forward = $oModuleModel->getActionForward($this->act);
				}

                if($forward->module && $forward->type && $forward->act && $forward->act == $this->act) {
                    $kind = strpos(strtolower($forward->act),'admin')!==false?'admin':'';
					$type = $forward->type;
					$ruleset = $forward->ruleset;
					$tpl_path = $oModule->getTemplatePath();
					$orig_module = $oModule;

					if($type == "view" && Mobile::isFromMobilePhone())
					{
						$orig_type = "view";
						$type = "mobile";
						// create a module instance
						$oModule = &$this->getModuleInstance($forward->module, $type, $kind);
						if(!is_object($oModule) || !method_exists($oModule, $this->act)) {
							$type = $orig_type;
							Mobile::setMobile(false);
							$oModule = &$this->getModuleInstance($forward->module, $type, $kind);
						}
					}
					else
					{
						$oModule = &$this->getModuleInstance($forward->module, $type, $kind);
					}
                    $xml_info = $oModuleModel->getModuleActionXml($forward->module);
					if($kind == "admin" && $type == "view")
					{
						$logged_info = Context::get('logged_info');
						if($logged_info->is_admin=='Y'){
							if ($this->act != 'dispLayoutAdminLayoutModify')
							{
								$oAdminView = &getAdminView('admin');
								$oAdminView->makeGnbUrl($forward->module);
								$oModule->setLayoutPath("./modules/admin/tpl");
								$oModule->setLayoutFile("layout.html");
							}
						}else{
							$this->error = 'msg_is_not_administrator';
							$oMessageObject = &ModuleHandler::getModuleInstance('message',$type);
							$oMessageObject->setError(-1);
							$oMessageObject->setMessage($this->error);
							$oMessageObject->dispMessage();
							return $oMessageObject;
						}
					}
				}
				else if($xml_info->default_index_act && method_exists($oModule, $xml_info->default_index_act))
				{
					$this->act = $xml_info->default_index_act;
				}
				else
				{
					$this->error = 'msg_invalid_request';
					return;
				}
			}

			// ruleset check...
			if(!empty($ruleset))
			{
				$rulesetModule = $forward->module ? $forward->module : $this->module;
				$rulesetFile = $oModuleModel->getValidatorFilePath($rulesetModule, $ruleset);
				if(!empty($rulesetFile))
				{
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
						$this->_setInputValueToSession();
						return $oModule;
					}
				}
			}

            $oModule->setAct($this->act);

            $this->module_info->module_type = $type;
            $oModule->setModuleInfo($this->module_info, $xml_info);

			if($type == "view" && $this->module_info->use_mobile == "Y" && Mobile::isMobileCheckByAgent())
			{
				global $lang;
				$footer = '<div style="margin:1em 0;padding:.5em;background:#333;border:1px solid #666;border-left:0;border-right:0"><p style="text-align:center;margin:1em 0"><a href="'.getUrl('m', '1').'" style="color:#ff0; font-weight:bold">'.$lang->msg_pc_to_mobile.'</a></p></div>';
				Context::addHtmlFooter($footer);
			}

			if($type == "view" && $kind != 'admin'){
        			$module_config= $oModuleModel->getModuleConfig('module');
		                if($module_config->htmlFooter){
                		        Context::addHtmlFooter($module_config->htmlFooter);
	                	}
        		}


            // if failed message exists in session, set context
			$this->_setInputErrorToContext();

            $procResult = $oModule->proc();

			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON')))
			{
				$error = $oModule->getError();
				$message = $oModule->getMessage();
				$messageType = $oModule->getMessageType();
				$redirectUrl = $oModule->getRedirectUrl();

				if (!$procResult)
				{
					$this->error = $message;
					if (!$redirectUrl && Context::get('error_return_url')) $redirectUrl = Context::get('error_return_url');
					$this->_setInputValueToSession();
				}
				else
				{
					if(count($_SESSION['INPUT_ERROR']))
					{
						Context::set('INPUT_ERROR', $_SESSION['INPUT_ERROR']);
						$_SESSION['INPUT_ERROR'] = '';
					}
				}

				$_SESSION['XE_VALIDATOR_ERROR'] = $error;
				if ($message != 'success') $_SESSION['XE_VALIDATOR_MESSAGE'] = $message;
				$_SESSION['XE_VALIDATOR_MESSAGE_TYPE'] = $messageType;
				$_SESSION['XE_VALIDATOR_RETURN_URL'] = $redirectUrl;
			}	
			
            return $oModule;
        }

		function _setInputErrorToContext()
		{
			if($_SESSION['XE_VALIDATOR_ERROR'] && !Context::get('XE_VALIDATOR_ERROR')) Context::set('XE_VALIDATOR_ERROR', $_SESSION['XE_VALIDATOR_ERROR']);
			if($_SESSION['XE_VALIDATOR_MESSAGE'] && !Context::get('XE_VALIDATOR_MESSAGE')) Context::set('XE_VALIDATOR_MESSAGE', $_SESSION['XE_VALIDATOR_MESSAGE']);
			if($_SESSION['XE_VALIDATOR_MESSAGE_TYPE'] && !Context::get('XE_VALIDATOR_MESSAGE_TYPE')) Context::set('XE_VALIDATOR_MESSAGE_TYPE', $_SESSION['XE_VALIDATOR_MESSAGE_TYPE']);
			if($_SESSION['XE_VALIDATOR_RETURN_URL'] && !Context::get('XE_VALIDATOR_RETURN_URL')) Context::set('XE_VALIDATOR_RETURN_URL', $_SESSION['XE_VALIDATOR_RETURN_URL']);

			$this->_clearErrorSession();
		}

		function _clearErrorSession()
		{
			$_SESSION['XE_VALIDATOR_ERROR'] = '';
			$_SESSION['XE_VALIDATOR_MESSAGE'] = '';
			$_SESSION['XE_VALIDATOR_MESSAGE_TYPE'] = '';
			$_SESSION['XE_VALIDATOR_RETURN_URL'] = '';
		}

		function _setInputValueToSession()
		{
			$requestVars = Context::getRequestVars();
			foreach($requestVars AS $key=>$value) $_SESSION['INPUT_ERROR'][$key] = $value;
		}

        /**
         * @brief display contents from executed module
         * @param[in] $oModule module instance
         * @return none
         **/
        function displayContent($oModule = NULL) {
            // If the module is not set or not an object, set error
            if(!$oModule || !is_object($oModule)) {
                $this->error = 'msg_module_does_not_exists';
            }

            // If connection to DB has a problem even though it's not install module, set error
            if($this->module != 'install' && $GLOBALS['__DB__'][Context::getDBType()]->isConnected() == false) {
                $this->error = 'msg_dbconnect_failed';
            }

            // Call trigger after moduleHandler proc
            $output = ModuleHandler::triggerCall('moduleHandler.proc', 'after', $oModule);
            if(!$output->toBool()) $this->error = $output->getMessage();

            // Use message view object, if HTML call
            if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {

				if($_SESSION['XE_VALIDATOR_RETURN_URL'])
				{
					header('location:'.$_SESSION['XE_VALIDATOR_RETURN_URL']);
					return;
				}

                // If error occurred, handle it
                if($this->error) {
                    // display content with message module instance
					$type = Mobile::isFromMobilePhone() ? 'mobile' : 'view';
					$oMessageObject = &ModuleHandler::getModuleInstance('message',$type);
					$oMessageObject->setError(-1);
					$oMessageObject->setMessage($this->error);
					$oMessageObject->dispMessage();

                    // If module was called normally, change the templates of the module into ones of the message view module
                    if($oModule) {
						$oModule->setTemplatePath($oMessageObject->getTemplatePath());
						$oModule->setTemplateFile($oMessageObject->getTemplateFile());
                    // Otherwise, set message instance as the target module
                    } else {
                        $oModule = $oMessageObject;
                    }

					$this->_clearErrorSession();
                }

                // Check if layout_srl exists for the module
				if(Mobile::isFromMobilePhone())
				{
					$layout_srl = $oModule->module_info->mlayout_srl;
				}
				else
				{
					$layout_srl = $oModule->module_info->layout_srl;
				}

                if($layout_srl && !$oModule->getLayoutFile()) {

                    // If layout_srl exists, get information of the layout, and set the location of layout_path/ layout_file
                    $oLayoutModel = &getModel('layout');
                    $layout_info = $oLayoutModel->getLayout($layout_srl);
                    if($layout_info) {

                        // Input extra_vars into $layout_info
                        if($layout_info->extra_var_count) {

                            foreach($layout_info->extra_var as $var_id => $val) {
                                if($val->type == 'image') {
                                    if(preg_match('/^\.\/files\/attach\/images\/(.+)/i',$val->value)) $val->value = Context::getRequestUri().substr($val->value,2);
                                }
                                $layout_info->{$var_id} = $val->value;
                            }
                        }
                        // Set menus into context
                        if($layout_info->menu_count) {
                            foreach($layout_info->menu as $menu_id => $menu) {
                                if(file_exists($menu->php_file)) @include($menu->php_file);
                                Context::set($menu_id, $menu);
                            }
                        }

                        // Set layout information into context
                        Context::set('layout_info', $layout_info);

                        $oModule->setLayoutPath($layout_info->path);
                        $oModule->setLayoutFile('layout');

                        // If layout was modified, use the modified version
                        $edited_layout = $oLayoutModel->getUserLayoutHtml($layout_info->layout_srl);
                        if(file_exists($edited_layout)) $oModule->setEditedLayoutFile($edited_layout);
                    }
                }
            }

            // Display contents
            $oDisplayHandler = new DisplayHandler();
            $oDisplayHandler->printContent($oModule);
        }

        /**
         * @brief returns module's path
         * @param[in] $module module name
         * @return path of the module
         **/
        function getModulePath($module) {
            return sprintf('./modules/%s/', $module);
        }

        /**
         * @brief It creates a module instance
         * @param[in] $module module name
         * @param[in] $type instance type, (e.g., view, controller, model)
         * @param[in] $kind admin or svc
         * @return module instance (if failed it returns null)
         * @remarks if there exists a module instance created before, returns it.
         **/
        function &getModuleInstance($module, $type = 'view', $kind = '') {

            if(__DEBUG__==3) $start_time = getMicroTime();

			$kind = strtolower($kind);
			$type = strtolower($type);

			$kinds = explode(' ', 'svc admin');
			if(!in_array($kind, $kinds)) $kind = $kinds[0];

			$key = $module.'.'.($kind!='admin'?'':'admin').'.'.$type;

			if(is_array($GLOBALS['__MODULE_EXTEND__']) && array_key_exists($key, $GLOBALS['__MODULE_EXTEND__'])) {
				$module = $extend_module = $GLOBALS['__MODULE_EXTEND__'][$key];
			}else{
				unset($parent_module);
			}

            // if there is no instance of the module in global variable, create a new one
            if(!$GLOBALS['_loaded_module'][$module][$type][$kind]) {
				$parent_module = $module;

				$class_path = ModuleHandler::getModulePath($module);
				if(!is_dir(FileHandler::getRealPath($class_path))) return NULL;

                // Get base class name and load the file contains it
                if(!class_exists($module)) {
                    $high_class_file = sprintf('%s%s%s.class.php', _XE_PATH_,$class_path, $module);
                    if(!file_exists($high_class_file)) return NULL;
                    require_once($high_class_file);
                }

                // Get the object's name
				$types = explode(' ', 'view controller model api wap mobile class');
				if(!in_array($type, $types)) $type = $types[0];
				if($type == 'class') {
					$instance_name = '%s';
					$class_file    = '%s%s.%s.php';
				} elseif($kind == 'admin' && array_search($type, $types) < 3) {
					$instance_name = '%sAdmin%s';
					$class_file    = '%s%s.admin.%s.php';
				} else{
					$instance_name = '%s%s';
					$class_file    = '%s%s.%s.php';
				}
				$instance_name = sprintf($instance_name, $module, ucfirst($type));
				$class_file    = sprintf($class_file, $class_path, $module, $type);
				$class_file    = FileHandler::getRealPath($class_file);

                // Get the name of the class file
                if(!is_readable($class_file)) return NULL;

                // Create an instance with eval function
                require_once($class_file);
                if(!class_exists($instance_name)) return NULL;
				$tmp_fn  = create_function('', "return new {$instance_name}();");
				$oModule = $tmp_fn();
                if(!is_object($oModule)) return NULL;

                // Load language files for the class
                Context::loadLang($class_path.'lang');
				if($extend_module) {
					Context::loadLang(ModuleHandler::getModulePath($parent_module).'lang');
				}

                // Set variables to the instance
                $oModule->setModule($module);
                $oModule->setModulePath($class_path);

                // If the module has a constructor, run it.
                if(!isset($GLOBALS['_called_constructor'][$instance_name])) {
                    $GLOBALS['_called_constructor'][$instance_name] = true;
                    if(@method_exists($oModule, $instance_name)) $oModule->{$instance_name}();
                }

                // Store the created instance into GLOBALS variable
                $GLOBALS['_loaded_module'][$module][$type][$kind] = $oModule;
            }

            if(__DEBUG__==3) $GLOBALS['__elapsed_class_load__'] += getMicroTime() - $start_time;

            // return the instance
            return $GLOBALS['_loaded_module'][$module][$type][$kind];
        }

        /**
         * @brief call a trigger
         * @param[in] $trigger_name trigger's name to call
         * @param[in] $called_position called position
         * @param[in] $obj an object as a parameter to trigger
         * @return Object
         **/
        function triggerCall($trigger_name, $called_position, &$obj) {
            // skip if not installed
            if(!Context::isInstalled()) return new Object();

            $oModuleModel = &getModel('module');
            $triggers = $oModuleModel->getTriggers($trigger_name, $called_position);
            if(!$triggers || !count($triggers)) return new Object();

            foreach($triggers as $item) {
                $module = $item->module;
                $type = $item->type;
                $called_method = $item->called_method;

                $oModule = null;
                $oModule = &getModule($module, $type);
                if(!$oModule || !method_exists($oModule, $called_method)) continue;

                $output = $oModule->{$called_method}($obj);
                if(is_object($output) && method_exists($output, 'toBool') && !$output->toBool()) return $output;
                unset($oModule);
            }

            return new Object();
        }
    }
?>
