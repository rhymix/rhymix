<?php
    /**
    * @class ModuleObject
    * @author NHN (developers@xpressengine.com)
    * @brief base class of ModuleHandler
    **/

    class ModuleObject extends Object {

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

        var $stop_proc = false; ///< a flag to indicating whether to stop the execution of code.

		var $module_config = NULL;

        /**
         * @brief setter to set the name of module
         * @param name of module
         **/
        function setModule($module) {
            $this->module = $module;
        }

        /**
         * @brief setter to set the name of module path
         * @param the directory path to a module directory
         **/
        function setModulePath($path) {
            if(substr($path,-1)!='/') $path.='/';
            $this->module_path = $path;
        }

        /**
         * @brief setter to set an url for redirection
         * @param $url url for redirection
         * @remark redirect_url is used only for ajax requests
         **/
        function setRedirectUrl($url='./') {
            $this->add('redirect_url', $url);
        }

		/**
		 * @brief get url for redirection
		 **/
		function getRedirectUrl(){
			return $this->get('redirect_url');
		}

		/**
		 * @brief set message
		 * @param $message a message string
		 * @param $type type of message (error, info, update)
		 **/
		function setMessage($message, $type = null){
			parent::setMessage($message);
			$this->setMessageType($type);
		}

		/**
		 * @brief set type of message
		 * @param $type type of message (error, info, update)
		 **/
		function setMessageType($type){
			$this->add('message_type', $type);
		}

		/**
		 * @brief get type of message
		 **/
		function getMessageType(){
			$type = $this->get('message_type');
			if (!in_array($type, array('error', 'info', 'update'))){
				$type = $this->getError()?'error':'info';
			}
			return $type;
		}

        /**
         * @brief sett to set the template path for refresh.html
         * @remark refresh.html is executed as a result of method execution
         * Tpl as the common run of the refresh.html ..
         **/
        function setRefreshPage() {
            $this->setTemplatePath('./common/tpl');
            $this->setTemplateFile('refresh');
        }


        /**
         * @brief sett to set the action name
         **/
        function setAct($act) {
            $this->act = $act;
        }

        /**
         * @brief sett to set module information
         * @param[in] $module_info object containing module information
         * @param[in] $xml_info object containing module description
        **/
        function setModuleInfo($module_info, $xml_info) {
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
            $oModuleModel = &getModel('module');
            // permission settings. access, manager(== is_admin) are fixed and privilege name in XE
            $module_srl = Context::get('module_srl');
            if(!$module_info->mid && preg_match('/^([0-9]+)$/',$module_srl)) {
                $request_module = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
                if($request_module->module_srl == $module_srl) {
                    $grant = $oModuleModel->getGrant($request_module, $logged_info);
                }
            } else {
                $grant = $oModuleModel->getGrant($module_info, $logged_info, $xml_info);
            }
            // display no permission if the current module doesn't have an access privilege
            //if(!$grant->access) return $this->stop("msg_not_permitted");
            // checks permission and action if you don't have an admin privilege
            if(!$grant->manager) {
                // get permission types(guest, member, manager, root) of the currently requested action
                $permission_target = $xml_info->permission->{$this->act};
                // check manager if a permission in module.xml otherwise action if no permission
                if(!$permission_target && substr_count($this->act, 'Admin')) $permission_target = 'manager';
                // Check permissions
                switch($permission_target) {
                    case 'root' :
                            $this->stop('msg_not_permitted_act');
                        break;
                    case 'manager' :
                            if(!$grant->manager) $this->stop('msg_not_permitted_act');
                        break;
                    case 'member' :
                            if(!$is_logged) $this->stop('msg_not_permitted_act');
                        break;
                }
            }
            // permission variable settings
            $this->grant = $grant;

            Context::set('grant', $grant);

			$this->module_config = $oModuleModel->getModuleConfig($this->module, $module_info->site_srl);

            if(method_exists($this, 'init')) $this->init();
        }

        /**
         * @brief set the stop_proc and approprate message for msg_code
         * @param $msg_code an error code
         **/
        function stop($msg_code) {
            // flag setting to stop the proc processing
            $this->stop_proc = true;
            // Error handling
            $this->setError(-1);
            $this->setMessage($msg_code);
            // Error message display by message module
			$type = Mobile::isFromMobilePhone() ? 'mobile' : 'view';
			$oMessageObject = &ModuleHandler::getModuleInstance('message',$type);
			$oMessageObject->setError(-1);
			$oMessageObject->setMessage($msg_code);
			$oMessageObject->dispMessage();

            $this->setTemplatePath($oMessageObject->getTemplatePath());
            $this->setTemplateFile($oMessageObject->getTemplateFile());

            return $this;
        }

        /**
         * @brief set the file name of the template file
         **/
        function setTemplateFile($filename) {
            if(substr($filename,-5)!='.html') $filename .= '.html';
            $this->template_file = $filename;
        }

        /**
         * @brief retrieve the directory path of the template directory
         **/
        function getTemplateFile() {
            return $this->template_file;
        }

        /**
         * @brief set the directory path of the template directory
         **/
        function setTemplatePath($path) {
            if(substr($path,0,1)!='/' && substr($path,0,2)!='./') $path = './'.$path;
            if(substr($path,-1)!='/') $path .= '/';
            $this->template_path = $path;
        }

        /**

         * @brief retrieve the directory path of the template directory
         **/
        function getTemplatePath() {
            return $this->template_path;
        }

        /**
         * @brief set the file name of the temporarily modified by admin
         **/
        function setEditedLayoutFile($filename) {
            if(substr($filename,-5)!='.html') $filename .= '.html';
            $this->edited_layout_file = $filename;
        }

        /**
         * @brief retreived the file name of edited_layout_file
         **/
        function getEditedLayoutFile() {
            return $this->edited_layout_file;
        }

        /**
         * @brief set the file name of the layout file
         **/
        function setLayoutFile($filename) {
            if(substr($filename,-5)!='.html') $filename .= '.html';
            $this->layout_file = $filename;
        }

        /**
         * @brief get the file name of the layout file
         **/
        function getLayoutFile() {
            return $this->layout_file;
        }

        /**
         * @brief set the directory path of the layout directory
         **/
        function setLayoutPath($path) {
            if(substr($path,0,1)!='/' && substr($path,0,2)!='./') $path = './'.$path;
            if(substr($path,-1)!='/') $path .= '/';
            $this->layout_path = $path;
        }

        /**
         * @brief set the directory path of the layout directory
         **/
        function getLayoutPath() {
            return $this->layout_path;
        }

        /**
         * @brief excute the member method specified by $act variable
         *
         **/
        function proc() {
            // pass if stop_proc is true
            if($this->stop_proc) return false;

            // trigger call
            $triggerOutput = ModuleHandler::triggerCall('moduleObject.proc', 'before', $this);
            if(!$triggerOutput->toBool()) {
                $this->setError($triggerOutput->getError());
                $this->setMessage($triggerOutput->getMessage());
                return false;
            }

            // execute an addon(call called_position as before_module_proc)
            $called_position = 'before_module_proc';
            $oAddonController = &getController('addon');
            $addon_file = $oAddonController->getCacheFilePath(Mobile::isFromMobilePhone()?"mobile":"pc");
            @include($addon_file);

            if(isset($this->xml_info->action->{$this->act}) && method_exists($this, $this->act)) {
                // Check permissions
                if(!$this->grant->access) return $this->stop("msg_not_permitted_act");
                // integrate skin information of the module(change to sync skin info with the target module only by seperating its table)
                $oModuleModel = &getModel('module');
                $oModuleModel->syncSkinInfoToModuleInfo($this->module_info);
                Context::set('module_info', $this->module_info);
                // Run
                $output = $this->{$this->act}();
            }
			else {
				return false;
			}

            // trigger call
            $triggerOutput = ModuleHandler::triggerCall('moduleObject.proc', 'after', $this);
            if(!$triggerOutput->toBool()) {
                $this->setError($triggerOutput->getError());
                $this->setMessage($triggerOutput->getMessage());
                return false;
            }

            // execute an addon(call called_position as after_module_proc)
            $called_position = 'after_module_proc';
            $oAddonController = &getController('addon');
            $addon_file = $oAddonController->getCacheFilePath(Mobile::isFromMobilePhone()?"mobile":"pc");
            @include($addon_file);

            if(is_a($output, 'Object') || is_subclass_of($output, 'Object')) {
                $this->setError($output->getError());
                $this->setMessage($output->getMessage());

				if (!$output->toBool()) return false;
            }
            // execute api methos of the module if view action is and result is XMLRPC or JSON
            if($this->module_info->module_type == 'view'){
                if(Context::getResponseMethod() == 'XMLRPC' || Context::getResponseMethod() == 'JSON') {
                    $oAPI = getAPI($this->module_info->module, 'api');
                    if(method_exists($oAPI, $this->act)) {
                        $oAPI->{$this->act}($this);
                    }
                }
            }
            return true;
        }
    }
?>
