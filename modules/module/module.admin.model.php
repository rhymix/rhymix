<?php
    /**
     * @class  moduleAdminModel
     * @author NHN (developers@xpressengine.com)
     * @version 0.1
     * @brief AdminModel class of the "module" module
     **/

    class moduleAdminModel extends module {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief Return a list of target modules by using module_srls separated by comma(,)
         * Used in the ModuleSelector
         **/
        function getModuleAdminModuleList() {
			$oModuleController = &getController('module');
			$oModuleModel = &getModel('module');
            $args->module_srls = Context::get('module_srls');
            $output = executeQueryArray('module.getModulesInfo', $args);
            if(!$output->toBool() || !$output->data) return new Object();

            foreach($output->data as $key => $val) {
				$info_xml = $oModuleModel->getModuleInfoXml($val->module);
				$oModuleController->replaceDefinedLangCode($val->browser_title);
                $list[$val->module_srl] = array('module_srl'=>$val->module_srl,'mid'=>$val->mid,'browser_title'=>$val->browser_title, 'module_name' => $info_xml->title);
            }
            $modules = explode(',',$args->module_srls);
            for($i=0;$i<count($modules);$i++) {
                $module_list[$modules[$i]] = $list[$modules[$i]];
            }

            $this->add('id', Context::get('id'));
            $this->add('module_list', $module_list);
        }

        function getModuleMidList($args){
            $args->list_count = 20;
            $args->page_count = 10;
            $output = executeQueryArray('module.getModuleMidList', $args);
            if(!$output->toBool()) return $output;

            ModuleModel::syncModuleToSite($output->data);

            return $output;
        }

        /**
         * @brief Common:: module's permission displaying page in the module
         * Available when using module instance in all the modules
         **/
        function getModuleGrantHTML($module_srl, $source_grant_list) {
			// get member module's config
			$oMemberModel = &getModel('member');
			$member_config = $oMemberModel->getMemberConfig();
			Context::set('member_config', $member_config);

            $oModuleModel = &getModel('module');
			$columnList = array('module_srl', 'site_srl');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl, $columnList);
            // Grant virtual permission for access and manager
            $grant_list->access->title = Context::getLang('grant_access');
            $grant_list->access->default = 'guest';
            if(count($source_grant_list)) {
                foreach($source_grant_list as $key => $val) {
                    if(!$val->default) $val->default = 'guest';
                    if($val->default == 'root') $val->default = 'manager';
                    $grant_list->{$key} = $val;
                }
            }
            $grant_list->manager->title = Context::getLang('grant_manager');
            $grant_list->manager->default = 'manager';
            Context::set('grant_list', $grant_list);
            // Get a permission group granted to the current module
            $default_grant = array();
            $args->module_srl = $module_srl;
            $output = executeQueryArray('module.getModuleGrants', $args);
            if($output->data) {
                foreach($output->data as $val) {
                    if($val->group_srl == 0) $default_grant[$val->name] = 'all';
                    else if($val->group_srl == -1) $default_grant[$val->name] = 'member';
                    else if($val->group_srl == -2) $default_grant[$val->name] = 'site';
                    else {
                        $selected_group[$val->name][] = $val->group_srl;
                        $default_grant[$val->name] = 'group';
                    }
                }
            }
            Context::set('selected_group', $selected_group);
            Context::set('default_grant', $default_grant);
            Context::set('module_srl', $module_srl);
            // Extract admin ID set in the current module
            $admin_member = $oModuleModel->getAdminId($module_srl);
            Context::set('admin_member', $admin_member);
            // Get a list of groups
            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups($module_info->site_srl);
            Context::set('group_list', $group_list);

			//Security			
			$security = new Security();
			$security->encodeHTML('group_list..title');
			$security->encodeHTML('group_list..description');

			// Get information of module_grants
            $oTemplate = &TemplateHandler::getInstance();
            return $oTemplate->compile($this->module_path.'tpl', 'module_grants');
        }

        /**
         * @brief Common:: skin setting page for the module
         **/
        function getModuleSkinHTML($module_srl) {
			return $this->_getModuleSkinHTML($module_srl, 'P');
        }

		/**
		 * Common:: skin setting page for the module (mobile)
		 *
		 * @param $module_srl sequence of module
		 * @return string The html code
		 */
		function getModuleMobileSkinHTML($module_srl)
		{
			return $this->_getModuleSkinHtml($module_srl, 'M');
		}

		/**
		 * Skin setting page for the module
		 *
		 * @param $module_srl sequence of module
		 * @param $mode P or M
		 * @return string The HTML code
		 */
		function _getModuleSkinHTML($module_srl, $mode)
		{
			$mode = $mode === 'P' ? 'P' : 'M';

            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
            if(!$module_info) return;

			if($mode === 'P')
			{
            	$skin = $module_info->skin;
			}
			else
			{
				$skin = $module_info->mskin;
			}

            $module_path = './modules/'.$module_info->module;

            // Get XML information of the skin and skin sinformation set in DB
			if($mode === 'P')
			{
            	$skin_info = $oModuleModel->loadSkinInfo($module_path, $skin);
				$skin_vars = $oModuleModel->getModuleSkinVars($module_srl);
			}
			else
			{
				$skin_info = $oModuleModel->loadSkinInfo($module_path, $skin, 'm.skins');
				$skin_vars = $oModuleModel->getModuleMobileSkinVars($module_srl);
			}

            if(count($skin_info->extra_vars)) {
                foreach($skin_info->extra_vars as $key => $val) {
                    $group = $val->group;
                    $name = $val->name;
                    $type = $val->type;
                    if($skin_vars[$name]) $value = $skin_vars[$name]->value;
                    else $value = '';
                    if($type=="checkbox") $value = $value?unserialize($value):array();

                    $skin_info->extra_vars[$key]->value= $value;
                }
            }

            Context::set('module_info', $module_info);
            Context::set('mid', $module_info->mid);
            Context::set('skin_info', $skin_info);
            Context::set('skin_vars', $skin_vars);
			Context::set('mode', $mode);
			
			//Security
			$security = new Security(); 
			$security->encodeHTML('mid');
			$security->encodeHTML('module_info.browser_title');
			$security->encodeHTML('skin_info...');

            $oTemplate = &TemplateHandler::getInstance();
            return $oTemplate->compile($this->module_path.'tpl', 'skin_config');
		}

        /**
         * @brief Get values for a particular language code
         * Return its corresponding value if lang_code is specified. Otherwise return $name.
         **/
        function getLangCode($site_srl, $name) {
            $lang_supported = Context::get('lang_supported');

            if(substr($name,0,12)=='$user_lang->') {
                $args->site_srl = (int)$site_srl;
                $args->name = substr($name,12);
                $output = executeQueryArray('module.getLang', $args);
                if($output->data) {
                    foreach($output->data as $key => $val) {
                        $selected_lang[$val->lang_code] = $val->value;
                    }
                }
            } else {
                $tmp = unserialize($name);
                if($tmp) {
                    $selected_lang = array();
                    $rand_name = $tmp[Context::getLangType()];
                    if(!$rand_name) $rand_name = array_shift($tmp);
					if(is_array($lang_supported))
					{
						foreach($lang_supported as $key => $val)
							$selected_lang[$key] = $tmp[$key]?$tmp[$key]:$rand_name;
					}
                }
            }

            $output = array();
			if(is_array($lang_supported))
			{
				foreach($lang_supported as $key => $val)
					$output[$key] = $selected_lang[$key]?$selected_lang[$key]:$name;
			}
            return $output;
        }

        /**
         * @brief Return if the module language in ajax is requested
         **/
        function getModuleAdminLangCode() {
            $name = Context::get('name');
            if(!$name) return new Object(-1,'msg_invalid_request');
            $site_module_info = Context::get('site_module_info');
            $this->add('name', $name);
            $output = $this->getLangCode($site_module_info->site_srl, '$user_lang->'.$name);
            $this->add('langs', $output);
        }

        /**
         * @brief Returns lang list by lang name
         **/
		function getModuleAdminLangListByName()
		{
			$args = Context::getRequestVars();
			if(!$args->site_srl) $args->site_srl = 0;

			$columnList = array('lang_code', 'name', 'value');

			$langList = array();

			$args->langName = preg_replace('/^\$user_lang->/', '', $args->lang_name);
            $output = executeQueryArray('module.getLangListByName', $args, $columnList);
			if($output->toBool()) $langList = $output->data;

			$this->add('lang_list', $langList);
			$this->add('lang_name', $args->langName);
		}

        /**
         * @brief Return lang list
         **/
		function getModuleAdminLangListByValue()
		{
			$args = Context::getRequestVars();
			if(!$args->site_srl) $args->site_srl = 0;

			$langList = array();

			// search value
			$output = executeQueryArray('module.getLangNameByValue', $args);
			if ($output->toBool() && is_array($output->data)){
				unset($args->value);

				foreach($output->data as $data) {
					$args->langName = $data->name;
					$columnList = array('lang_code', 'name', 'value');
					$outputByName = executeQueryArray('module.getLangListByName', $args, $columnList);

					if($outputByName->toBool()) {
 						$langList = array_merge($langList, $outputByName->data);
					}
				}
			}

			$this->add('lang_list', $langList);
		}

        /**
         * @brief Return current lang list
         **/
		function getLangListByLangcode($args)
		{
            $output = executeQueryArray('module.getLangListByLangcode', $args);
			if(!$output->toBool()) return array();

			return $output;
		}

    }
?>
