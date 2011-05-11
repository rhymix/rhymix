<?php
    /**
     * @class  moduleAdminController
     * @author NHN (developers@xpressengine.com)
     * @brief admin controller class of the module module 
     **/

    class moduleAdminController extends module {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief Add the module category
         **/
        function procModuleAdminInsertCategory() {
            $args->title = Context::get('title');
            $output = executeQuery('module.insertModuleCategory', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage("success_registed");
        }

        /**
         * @brief Update category
         **/
        function procModuleAdminUpdateCategory() {
            $mode = Context::get('mode');

            switch($mode) {
                case 'delete' :
                        $output = $this->doDeleteModuleCategory();
                        $msg_code = 'success_deleted';
                    break;
                case 'update' :
                        $output = $this->doUpdateModuleCategory();
                        $msg_code = 'success_updated';
                    break;
            }
            if(!$output->toBool()) return $output;

            $this->setMessage($msg_code);
        }

        /**
         * @brief Change the title of the module category
         **/
        function doUpdateModuleCategory() {
            $args->title = Context::get('title');
            $args->module_category_srl = Context::get('module_category_srl');
            return executeQuery('module.updateModuleCategory', $args);
        }

        /**
         * @brief Delete the module category
         **/
        function doDeleteModuleCategory() {
            $args->module_category_srl = Context::get('module_category_srl');
            return executeQuery('module.deleteModuleCategory', $args);
        }

        /**
         * @brief Copy Module
         **/
        function procModuleAdminCopyModule() {
            // Get information of the target module to copy
            $module_srl = Context::get('module_srl');
            if(!$module_srl) return;
            // Get module name to create and browser title
            $clones = array();
            $args = Context::getAll();
            for($i=1;$i<=10;$i++) {
                $mid = trim($args->{"mid_".$i});
                if(!$mid) continue;
                if(!preg_match("/^[a-zA-Z]([a-zA-Z0-9_]*)$/i", $mid)) return new Object(-1, 'msg_limit_mid');
                $browser_title = $args->{"browser_title_".$i};
                if(!$mid) continue;
                if($mid && !$browser_title) $browser_title = $mid;
                $clones[$mid] = $browser_title;
            }
            if(!count($clones)) return;

            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');
            // Get module information
			$columnList = array('module', 'module_category_srl', 'layout_srl', 'use_mobile', 'mlayout_srl', 'menu_srl', 'site_srl', 'skin', 'mskin', 'description', 'mcontent', 'open_rss', 'header_text', 'footer_text', 'regdate');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl, $columnList);
            // Get permission information
            $module_args->module_srl = $module_srl;
            $output = executeQueryArray('module.getModuleGrants', $module_args);
            $grant = array();
            if($output->data) {
                foreach($output->data as $key => $val) $grant[$val->name][] = $val->group_srl;
            }


            $oDB = &DB::getInstance();
            $oDB->begin();
            // Copy a module
            foreach($clones as $mid => $browser_title) {
                $clone_args = null;
                $clone_args = clone($module_info);
                $clone_args->module_srl = null;
                $clone_args->content = null;
                $clone_args->mid = $mid;
                $clone_args->browser_title = $browser_title;
                $clone_args->is_default = 'N';
                // Create a module
                $output = $oModuleController->insertModule($clone_args);
                $module_srl = $output->get('module_srl');
                // Grant module permissions
                if(count($grant)) $oModuleController->insertModuleGrants($module_srl, $grant);
            }

            $oDB->commit();
            $this->setMessage('success_registed');
        }

        /**
         * @brief Save the module permissions
         **/
        function procModuleAdminInsertGrant() {
            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');
            // Get module_srl
            $module_srl = Context::get('module_srl');
            // Get information of the module
			$columnList = array('module_srl', 'module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl, $columnList);
            if(!$module_info) return new Object(-1,'msg_invalid_request');
            // Register Admin ID
            $oModuleController->deleteAdminId($module_srl);
            $admin_member = Context::get('admin_member');
            if($admin_member) {
                $admin_members = explode(',',$admin_member);
                for($i=0;$i<count($admin_members);$i++) {
                    $admin_id = trim($admin_members[$i]);
                    if(!$admin_id) continue;
                    $oModuleController->insertAdminId($module_srl, $admin_id);

                }
            }
            // List permissions
            $xml_info = $oModuleModel->getModuleActionXML($module_info->module);

            $grant_list = $xml_info->grant;

            $grant_list->access->default = 'guest';
            $grant_list->manager->default = 'manager';

            foreach($grant_list as $grant_name => $grant_info) {
                // Get the default value
                $default = Context::get($grant_name.'_default');
                // -1 = Log-in user only, -2 = site members only, 0 = all users
                if(strlen($default)){
                    $grant->{$grant_name}[] = $default;
                    continue;
                // users in a particular group
                } else {
                    $group_srls = Context::get($grant_name);
                    if($group_srls) {
                        if(strpos($group_srls,'|@|')!==false) $group_srls = explode('|@|',$group_srls);
                        elseif(strpos($group_srls,',')!==false) $group_srls = explode(',',$group_srls);
                        else $group_srls = array($group_srls);
                        $grant->{$grant_name} = $group_srls;
                    }
                    continue;
                }
                $grant->{$group_srls} = array();
            }
            
            // Stored in the DB
            $args->module_srl = $module_srl;
            $output = executeQuery('module.deleteModuleGrants', $args);
            if(!$output->toBool()) return $output;
            // Permissions stored in the DB
            foreach($grant as $grant_name => $group_srls) {
                foreach($group_srls as $key => $val) {
                    $args = null;
                    $args->module_srl = $module_srl;
                    $args->name = $grant_name;
                    $args->group_srl = $val;
                    $output = executeQuery('module.insertModuleGrant', $args);
                    if(!$output->toBool()) return $output;
                }
            }
            $this->setMessage('success_registed');
        }

        /**
         * @brief Updating Skins
         **/
        function procModuleAdminUpdateSkinInfo() {
            // Get information of the module_srl
            $module_srl = Context::get('module_srl');

            $oModuleModel = &getModel('module');
			$columnList = array('module_srl', 'module', 'skin');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl, $columnList);
            if($module_info->module_srl) {
                $skin = $module_info->skin;
                // Get skin information (to check extra_vars)
                $module_path = './modules/'.$module_info->module;
                $skin_info = $oModuleModel->loadSkinInfo($module_path, $skin);
                $skin_vars = $oModuleModel->getModuleSkinVars($module_srl);
                // Check received variables (unset such variables as act, module_srl, page, mid, module)
                $obj = Context::getRequestVars();
                unset($obj->act);
                unset($obj->module_srl);
                unset($obj->page);
                unset($obj->mid);
                unset($obj->module);
                // Separately handle if a type of extra_vars is an image in the original skin_info
                if($skin_info->extra_vars) {
                    foreach($skin_info->extra_vars as $vars) {
                        if($vars->type!='image') continue;

                        $image_obj = $obj->{$vars->name};
                        // Get a variable to delete
                        $del_var = $obj->{"del_".$vars->name};
                        unset($obj->{"del_".$vars->name});
                        if($del_var == 'Y') {
                            FileHandler::removeFile($skin_vars[$vars->name]->value);
                            continue;
                        }
                        // Use the previous data if not uploaded
                        if(!$image_obj['tmp_name']) {
                            $obj->{$vars->name} = $skin_vars[$vars->name]->value;
                            continue;
                        }
                        // Ignore if the file is not successfully uploaded
                        if(!is_uploaded_file($image_obj['tmp_name'])) {
                            unset($obj->{$vars->name});
                            continue;
                        }
                        // Ignore if the file is not an image
                        if(!preg_match("/\.(jpg|jpeg|gif|png)$/i", $image_obj['name'])) {
                            unset($obj->{$vars->name});
                            continue;
                        }
                        // Upload the file to a path
                        $path = sprintf("./files/attach/images/%s/", $module_srl);
                        // Create a directory
                        if(!FileHandler::makeDir($path)) return false;

                        $filename = $path.$image_obj['name'];
                        // Move the file
                        if(!move_uploaded_file($image_obj['tmp_name'], $filename)) {
                            unset($obj->{$vars->name});
                            continue;
                        }
                        // Upload the file
                        FileHandler::removeFile($skin_vars[$vars->name]->value);
                        // Change a variable
                        unset($obj->{$vars->name});
                        $obj->{$vars->name} = $filename;
                    }
                }
                // Load the entire skin of the module and then remove the image
                /*
                if($skin_info->extra_vars) {
                    foreach($skin_info->extra_vars as $vars) {
                        if($vars->type!='image') continue;
                        $value = $skin_vars[$vars->name];
                        if(file_exists($value)) @unlink($value);
                    }
                }
                */
                $oModuleController = &getController('module');
                $oModuleController->deleteModuleSkinVars($module_srl);
                // Register
                $oModuleController->insertModuleSkinVars($module_srl, $obj);
            }

            $this->setLayoutPath('./common/tpl');
            $this->setLayoutFile('default_layout.html');
            $this->setTemplatePath('./modules/module/tpl');
            $this->setTemplateFile("top_refresh.html");
        }

        /**
         * @brief List module information
         **/
        function procModuleAdminModuleSetup() {
            $vars = Context::getRequestVars();

            if(!$vars->module_srls) return new Object(-1,'msg_invalid_request');

            $module_srls = explode(',',$vars->module_srls);
            if(!count($module_srls)) return new Object(-1,'msg_invalid_request');

            $oModuleModel = &getModel('module');
            $oModuleController= &getController('module');
			$columnList = array('module_srl', 'module', 'use_mobile', 'mlayout_srl', 'menu_srl', 'site_srl', 'mid', 'mskin', 'browser_title', 'is_default', 'content', 'mcontent', 'open_rss', 'regdate');
            foreach($module_srls as $module_srl) {
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl, $columnList);
                $module_info->module_category_srl = $vars->module_category_srl;
                $module_info->layout_srl = $vars->layout_srl;
                $module_info->skin = $vars->skin;
                $module_info->description = $vars->description;
                $module_info->header_text = $vars->header_text;
                $module_info->footer_text = $vars->footer_text;
                $oModuleController->updateModule($module_info);
            }

            $this->setMessage('success_registed');
        }

        /**
         * @brief List permissions of the module
         **/
        function procModuleAdminModuleGrantSetup() {
            $module_srls = Context::get('module_srls');
            if(!$module_srls) return new Object(-1,'msg_invalid_request');

            $modules = explode(',',$module_srls);
            if(!count($modules)) return new Object(-1,'msg_invalid_request');

            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');

			$columnList = array('module_srl', 'module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($modules[0], $columnList);
            $xml_info = $oModuleModel->getModuleActionXml($module_info->module);
            $grant_list = $xml_info->grant;

            $grant_list->access->default = 'guest';
            $grant_list->manager->default = 'manager';

            foreach($grant_list as $grant_name => $grant_info) {
                // Get the default value
                $default = Context::get($grant_name.'_default');
                // -1 = Sign only, 0 = all users
                if(strlen($default)){
                    $grant->{$grant_name}[] = $default;
                    continue;
                // Users in a particular group
                } else {
                    $group_srls = Context::get($grant_name);
                    if($group_srls) {
                        if(strpos($group_srls,'|@|')!==false) $group_srls = explode('|@|',$group_srls);
                        elseif(strpos($group_srls,',')!==false) $group_srls = explode(',',$group_srls);
                        else $group_srls = array($group_srls);
                        $grant->{$grant_name} = $group_srls;
                    }
                    continue;
                }
                $grant->{$group_srls} = array();
            }

            
            // Stored in the DB
            foreach($modules as $module_srl) {
                $args = null;
                $args->module_srl = $module_srl;
                $output = executeQuery('module.deleteModuleGrants', $args);
                if(!$output->toBool()) continue;
                // Permissions stored in the DB
                foreach($grant as $grant_name => $group_srls) {
                    foreach($group_srls as $key => $val) {
                        $args = null;
                        $args->module_srl = $module_srl;
                        $args->name = $grant_name;
                        $args->group_srl = $val;
                        $output = executeQuery('module.insertModuleGrant', $args);
                        if(!$output->toBool()) return $output;
                    }
                }
            }
            $this->setMessage('success_registed');
        }

        /**
         * @brief Add/Update language 
         **/
        function procModuleAdminInsertLang() {
            // Get language code
            $site_module_info = Context::get('site_module_info');
            $args->site_srl = (int)$site_module_info->site_srl;
            $args->name = str_replace(' ','_',Context::get('lang_code'));
            if(!$args->name) return new Object(-1,'msg_invalid_request');
            // Check whether a language code exists
            $output = executeQueryArray('module.getLang', $args);
            if(!$output->toBool()) return $output;
            // If exists, clear the old values for updating
            if($output->data) $output = executeQuery('module.deleteLang', $args);
            if(!$output->toBool()) return $output;
            // Enter
            $lang_supported = Context::get('lang_supported');
            foreach($lang_supported as $key => $val) {
                $args->lang_code = $key;
                $args->value = trim(Context::get($key));
                if(!$args->value) {
                    $args->value = Context::get(strtolower($key));
                    if(!$args->value) $args->value = $args->name;
                }
                $output = executeQuery('module.insertLang', $args);
                if(!$output->toBool()) return $output;
            }
            $this->makeCacheDefinedLangCode($args->site_srl);

            $this->add('name', $args->name);
        }

        /**
         * @brief Remove language
         **/
        function procModuleAdminDeleteLang() {
            // Get language code
            $site_module_info = Context::get('site_module_info');
            $args->site_srl = (int)$site_module_info->site_srl;
            $args->name = str_replace(' ','_',Context::get('name'));
            if(!$args->name) return new Object(-1,'msg_invalid_request');

            $output = executeQuery('module.deleteLang', $args);
            if(!$output->toBool()) return $output;
            $this->makeCacheDefinedLangCode($args->site_srl);
        }

        /**
         * @brief Save the file of user-defined language code
         **/
        function makeCacheDefinedLangCode($site_srl = 0) {
            // Get the language file of the current site
            if(!$site_srl) {
                $site_module_info = Context::get('site_module_info');
                $args->site_srl = (int)$site_module_info->site_srl;
            } else {
                $args->site_srl = $site_srl;
            }
            $output = executeQueryArray('module.getLang', $args);
            if(!$output->toBool() || !$output->data) return;
            // Set the cache directory
            $cache_path = _XE_PATH_.'files/cache/lang_defined/';
            if(!is_dir($cache_path)) FileHandler::makeDir($cache_path);

            $lang_supported = Context::get('lang_supported');
            foreach($lang_supported as $key => $val) {
                $fp[$key] = fopen( sprintf('%s/%d.%s.php', $cache_path, $args->site_srl, $key), 'w' );
                if(!$fp[$key]) return;
                fwrite($fp[$key],"<?php if(!defined('__ZBXE__')) exit(); \r\n");
            }

            foreach($output->data as $key => $val) {
                if($fp[$val->lang_code]) fwrite($fp[$val->lang_code], sprintf('$lang["%s"] = "%s";'."\r\n", $val->name, str_replace('"','\\"',$val->value)));
            }

            foreach($lang_supported as $key => $val) {
                if(!$fp[$key]) continue;
                fwrite($fp[$key],"?>");
                fclose($fp[$key]);
            }
        }

    }
?>
