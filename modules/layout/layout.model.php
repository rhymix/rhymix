<?php
    /**
     * @class  layoutModel
     * @author NHN (developers@xpressengine.com)
     * @version 0.1
     * Model class of the layout module
     **/

    class layoutModel extends layout {

		/**
		 * Check user layout temp
		 * @var string
		 **/
        var $useUserLayoutTemp = null;

        /**
         * Initialization
		 * @return void
         **/
        function init() {
        }

        /**
         * Get a layout list created in the DB
         * If you found a new list, it means that the layout list is inserted to the DB
		 * @deprecated
		 * @param int $site_srl
		 * @param string $layout_type (P : PC, M : Mobile)
		 * @param array $columnList
		 * @return array layout lists in site
         **/
        function getLayoutList($site_srl = 0, $layout_type="P", $columnList = array()) {
            if(!$site_srl) {
                $site_module_info = Context::get('site_module_info');
                $site_srl = (int)$site_module_info->site_srl;
            }
            $args->site_srl = $site_srl;
			$args->layout_type = $layout_type;
            $output = executeQueryArray('layout.getLayoutList', $args, $columnList);
			return $output->data;
        }

		/**
		 * Get the list layout instance with thumbnail link. for setting design.
		 *
		 * @return void
		 */
		public function getLayoutInstanceListForJSONP()
		{
			$siteSrl = Context::get('site_srl');
			$layoutType = Context::get('layout_type');

			$layoutList = $this->getLayoutInstanceList($siteSrl, $layoutType);
			$thumbs = array();
			foreach($layoutList as $key => $val)
			{
				if($thumbs[$val->layouts])
				{
					$val->thumbnail = $thumbs[$val->layouts];
					continue;
				}

				$token = explode('|@|', $val->layout);
				if(count($token) == 2)
				{
					$thumbnailPath = sprintf('./themes/%s/layouts/%s/thumbnail.png' , $token[0], $token[1]);
				}
				else
				{
					$thumbnailPath = sprintf('./layouts/%s/thumbnail.png' , $val->layout);
				}
				if(is_readable($thumbnailPath))
				{
					$val->thumbnail = $thumbnailPath;
				}
				else
				{
					$val->thumbnail = sprintf('./modules/layout/tpl/images/noThumbnail.jpg');
				}
				$thumbs[$val->layout] = $val->thumbnail;
			}

			$this->add('layout_list', $layoutList);
		}

		/**
		 * Get layout instance list
		 * @param int $siteSrl
		 * @param string $layoutType (P : PC, M : Mobile)
		 * @param string $layout name of layout
		 * @param array $columnList
		 * @return array layout lists in site
		 **/
		function getLayoutInstanceList($siteSrl = 0, $layoutType = 'P', $layout = null, $columnList = array())
		{
			if (!$siteSrl)
			{
				$siteModuleInfo = Context::get('site_module_info');
				$siteSrl = (int)$siteModuleInfo->site_srl;
			}
			$args->site_srl = $siteSrl;
			$args->layout_type = $layoutType;
			$args->layout = $layout;
			$output = executeQueryArray('layout.getLayoutList', $args, $columnList);

			// Create instance name list
			$instanceList = array();
			if(is_array($output->data))
			{
				foreach($output->data as $iInfo)
				{
					$instanceList[] = $iInfo->layout;
				}
			}

			// Create downloaded name list
			$downloadedList = array();
			$titleList = array();
			$_downloadedList = $this->getDownloadedLayoutList($layoutType);
			if(is_array($_downloadedList))
			{
				foreach($_downloadedList as $dLayoutInfo)
				{
					$downloadedList[] = $dLayoutInfo->layout;
					$titleList[$dLayoutInfo->layout] = $dLayoutInfo->title;
				}
			}

			// Get downloaded name list have no instance
			$noInstanceList = array_diff($downloadedList, $instanceList);
			foreach($noInstanceList as $layoutName)
			{
				$insertArgs = new stdClass();
				$insertArgs->site_srl = $siteSrl;
				$insertArgs->layout_srl = getNextSequence();
				$insertArgs->layout = $layoutName;
				$insertArgs->title = $titleList[$layoutName];
				$insertArgs->layout_type = $layoutType;

				$oLayoutAdminController = getAdminController('layout');
				$oLayoutAdminController->insertLayout($insertArgs);
			}

			// If create layout instance, reload instance list
			if(count($noInstanceList))
			{
				$output = executeQueryArray('layout.getLayoutList', $args, $columnList);
			}

			return $output->data;
		}


        /**
         * Get one of layout information created in the DB
         * Return DB info + XML info of the generated layout
		 * @param int $layout_srl
		 * @return object info of layout
         **/
		function getLayout($layout_srl) {
            // cache controll
			$oCacheHandler = &CacheHandler::getInstance('object');
			if($oCacheHandler->isSupport()){
				$cache_key = 'object:'.$layout_srl;
				$layout_info = $oCacheHandler->get($cache_key);
			}
			if(!$layout_info) {
				// Get information from the DB
	            $args->layout_srl = $layout_srl;
	            $output = executeQuery('layout.getLayout', $args);
	            if(!$output->data) return;
	            // Return xml file informaton after listing up the layout and extra_vars
	            $layout_info = $this->getLayoutInfo($layout, $output->data, $output->data->layout_type);

				// If deleted layout files, delete layout instance
				// if (!$layout_info) {
					// $oLayoutController = &getAdminController('layout');
					// $oLayoutController->deleteLayout($layout_srl);
					// return;
				// }
	            
				//insert in cache
	            if($oCacheHandler->isSupport()) $oCacheHandler->put($cache_key,$layout_info);
			}
        	return $layout_info;
        }

		function getLayoutRawData($layout_srl, $columnList = array())
		{
			$args->layout_srl = $layout_srl;
			$output = executeQuery('layout.getLayout', $args, $columnList);
			if(!$output->toBool())
				return;

			return $output->data;
		}

        /**
         * Get a layout path
		 * @param string $layout_name
		 * @param string $layout_type (P : PC, M : Mobile)
		 * @return string path of layout
         **/
        function getLayoutPath($layout_name, $layout_type = "P") {
			$layout_parse = explode('|@|', $layout_name);
			if (count($layout_parse) > 1){
				$class_path = './themes/'.$layout_parse[0].'/layouts/'.$layout_parse[1].'/';
			}else if($layout_name == 'faceoff'){
                $class_path = './modules/layout/faceoff/';
            }else if($layout_type == "M") {
				$class_path = sprintf("./m.layouts/%s/", $layout_name);
			}else {
                $class_path = sprintf('./layouts/%s/', $layout_name);
            }
            if(is_dir($class_path)) return $class_path;
            return "";
        }

        /**
         * Get a type and information of the layout
         * A type of downloaded layout
		 * @param string $layout_type (P : PC, M : Mobile)
		 * @param boolean $withAutoinstallInfo
		 * @return array info of layout
         **/
        function getDownloadedLayoutList($layout_type = "P", $withAutoinstallInfo = false) {
			if ($withAutoinstallInfo) $oAutoinstallModel = &getModel('autoinstall');

            // Get a list of downloaded layout and installed layout
            $searched_list = $this->_getInstalledLayoutDirectories($layout_type);
            $searched_count = count($searched_list);
            if(!$searched_count) return;

            // natcasesort($searched_list);
            // Return information for looping searched list of layouts
			$list = array();
            for($i=0;$i<$searched_count;$i++) {
                // Name of the layout
                $layout = $searched_list[$i];
                // Get information of the layout
                $layout_info = $this->getLayoutInfo($layout, null, $layout_type);

				if(!$layout_info)
				{
					continue;
				}

				if ($withAutoinstallInfo)
				{
					// get easyinstall remove url
					$packageSrl = $oAutoinstallModel->getPackageSrlByPath($layout_info->path);
					$layout_info->remove_url = $oAutoinstallModel->getRemoveUrlByPackageSrl($packageSrl);

					// get easyinstall need update
					$package = $oAutoinstallModel->getInstalledPackages($packageSrl);
					$layout_info->need_update = $package[$packageSrl]->need_update;

					// get easyinstall update url
					if ($layout_info->need_update)
					{
						$layout_info->update_url = $oAutoinstallModel->getUpdateUrlByPackageSrl($packageSrl);
					}
				}
                $list[] = $layout_info;
            }

			usort($list, array($this, 'sortLayoutByTitle'));
            return $list;
        }

		/**
		 * Sort layout by title
		 */
		function sortLayoutByTitle($a, $b)
		{
			if(!$a->title)
			{
				$a->title = $a->layout;
			}

			if(!$b->title)
			{
				$b->title = $b->layout;
			}

			$aTitle = strtolower($a->title);
			$bTitle = strtolower($b->title);

			if($aTitle == $bTitle)
			{
				return 0;
			}

			return ($aTitle < $bTitle) ? -1 : 1;
		}

		/**
		 * Get a count of layout
		 * @param string $layoutType (P : PC, M : Mobile)
		 * @return int
		 **/
		function getInstalledLayoutCount($layoutType = 'P')
		{
			$searchedList = $this->_getInstalledLayoutDirectories($layoutType);
			return  count($searchedList);
		}

		/**
		 * Get list of layouts directory
		 * @param string $layoutType (P : PC, M : Mobile)
		 * @return array
		 **/
		function _getInstalledLayoutDirectories($layoutType = 'P')
		{
			if ($layoutType == 'M')
			{
				$directory = './m.layouts';
				$globalValueKey = 'MOBILE_LAYOUT_DIRECTOIES';
			}
			else
			{
				$directory = './layouts';
				$globalValueKey = 'PC_LAYOUT_DIRECTORIES';
			}

			if ($GLOBALS[$globalValueKey]) return $GLOBALS[$globalValueKey];

			$searchedList = FileHandler::readDir($directory);
			if (!$searchedList) $searchedList = array();
			$GLOBALS[$globalValueKey] = $searchedList;

			return $searchedList;
		}

        /**
         * Get information by reading conf/info.xml in the module
         * It uses caching to reduce time for xml parsing ..
		 * @param string $layout
		 * @param object $info
		 * @param string $layoutType (P : PC, M : Mobile)
		 * @return object info of layout
         **/
        function getLayoutInfo($layout, $info = null, $layout_type = "P") {
            if($info) {
                $layout_title = $info->title;
                $layout = $info->layout;
                $layout_srl = $info->layout_srl;
                $site_srl = $info->site_srl;
                $vars = unserialize($info->extra_vars);

                if($info->module_srl) {
                    $layout_path = preg_replace('/([a-zA-Z0-9\_\.]+)(\.html)$/','',$info->layout_path);
                    $xml_file = sprintf('%sskin.xml', $layout_path);
                }
            }
            // Get a path of the requested module. Return if not exists.
            if(!$layout_path) $layout_path = $this->getLayoutPath($layout, $layout_type);
            if(!is_dir($layout_path)) return;
            // Read the xml file for module skin information
            if(!$xml_file) $xml_file = sprintf("%sconf/info.xml", $layout_path);
            if(!file_exists($xml_file)) {
				$layout_info->title = $layout;
                $layout_info->layout = $layout;
                $layout_info->path = $layout_path;
                $layout_info->layout_title = $layout_title;
				if(!$layout_info->layout_type)
					$layout_info->layout_type =  $layout_type;
                return $layout_info;
            }
            // Include the cache file if it is valid and then return $layout_info variable
            if(!$layout_srl){
                $cache_file = $this->getLayoutCache($layout, Context::getLangType());
            }else{
                $cache_file = $this->getUserLayoutCache($layout_srl, Context::getLangType());
            }
            if(file_exists($cache_file)&&filemtime($cache_file)>filemtime($xml_file)) {
                @include($cache_file);


                if($layout_info->extra_var && $vars) {
                    foreach($vars as $key => $value) {
                        if(!$layout_info->extra_var->{$key} && !$layout_info->{$key}) {
                            $layout_info->{$key} = $value;
                        }
                    }
                }

				if(!$layout_info->title)
				{
					$layout_info->title = $layout;
				}

                return $layout_info;
            }
            // If no cache file exists, parse the xml and then return the variable.
            $oXmlParser = new XmlParser();
            $tmp_xml_obj = $oXmlParser->loadXmlFile($xml_file);
            if($tmp_xml_obj->layout) $xml_obj = $tmp_xml_obj->layout;
            elseif($tmp_xml_obj->skin) $xml_obj = $tmp_xml_obj->skin;

            if(!$xml_obj) return;

            $buff = '';
            $buff .= sprintf('$layout_info->site_srl = "%s";', $site_srl);

            if($xml_obj->version && $xml_obj->attrs->version == '0.2') {
                // Layout title, version and other information
                sscanf($xml_obj->date->body, '%d-%d-%d', $date_obj->y, $date_obj->m, $date_obj->d);
                $date = sprintf('%04d%02d%02d', $date_obj->y, $date_obj->m, $date_obj->d);
                $buff .= sprintf('$layout_info->layout = "%s";', $layout);
                $buff .= sprintf('$layout_info->type = "%s";', $xml_obj->attrs->type);
                $buff .= sprintf('$layout_info->path = "%s";', $layout_path);
                $buff .= sprintf('$layout_info->title = "%s";', $xml_obj->title->body);
                $buff .= sprintf('$layout_info->description = "%s";', $xml_obj->description->body);
                $buff .= sprintf('$layout_info->version = "%s";', $xml_obj->version->body);
                $buff .= sprintf('$layout_info->date = "%s";', $date);
                $buff .= sprintf('$layout_info->homepage = "%s";', $xml_obj->link->body);
                $buff .= sprintf('$layout_info->layout_srl = $layout_srl;');
                $buff .= sprintf('$layout_info->layout_title = $layout_title;');
                $buff .= sprintf('$layout_info->license = "%s";', $xml_obj->license->body);
                $buff .= sprintf('$layout_info->license_link = "%s";', $xml_obj->license->attrs->link);
				$buff .= sprintf('$layout_info->layout_type = "%s";', $layout_type);
                // Author information
                if(!is_array($xml_obj->author)) $author_list[] = $xml_obj->author;
                else $author_list = $xml_obj->author;

                for($i=0; $i < count($author_list); $i++) {
                    $buff .= sprintf('$layout_info->author['.$i.']->name = "%s";', $author_list[$i]->name->body);
                    $buff .= sprintf('$layout_info->author['.$i.']->email_address = "%s";', $author_list[$i]->attrs->email_address);
                    $buff .= sprintf('$layout_info->author['.$i.']->homepage = "%s";', $author_list[$i]->attrs->link);
                }
                // Extra vars (user defined variables to use in a template)
                $extra_var_groups = $xml_obj->extra_vars->group;
                if(!$extra_var_groups) $extra_var_groups = $xml_obj->extra_vars;
                if(!is_array($extra_var_groups)) $extra_var_groups = array($extra_var_groups);
                foreach($extra_var_groups as $group){
                    $extra_vars = $group->var;
                    if($extra_vars) {
                        if(!is_array($extra_vars)) $extra_vars = array($extra_vars);

                        $extra_var_count = count($extra_vars);

                        $buff .= sprintf('$layout_info->extra_var_count = "%s";', $extra_var_count);
                        for($i=0;$i<$extra_var_count;$i++) {
                            unset($var);
                            unset($options);
                            $var = $extra_vars[$i];
                            $name = $var->attrs->name;

                            $buff .= sprintf('$layout_info->extra_var->%s->group = "%s";', $name, $group->title->body);
                            $buff .= sprintf('$layout_info->extra_var->%s->title = "%s";', $name, $var->title->body);
                            $buff .= sprintf('$layout_info->extra_var->%s->type = "%s";', $name, $var->attrs->type);
                            $buff .= sprintf('$layout_info->extra_var->%s->value = $vars->%s;', $name, $name);
                            $buff .= sprintf('$layout_info->extra_var->%s->description = "%s";', $name, str_replace('"','\"',$var->description->body));

                            $options = $var->options;
                            if(!$options) continue;

                            if(!is_array($options)) $options = array($options);
                            $options_count = count($options);
                            $thumbnail_exist = false;
                            for($j=0; $j < $options_count; $j++) {
                                $thumbnail = $options[$j]->attrs->src;
                                if($thumbnail) {
                                    $thumbnail = $layout_path.$thumbnail;
                                    if(file_exists($thumbnail)) {
                                        $buff .= sprintf('$layout_info->extra_var->%s->options["%s"]->thumbnail = "%s";', $var->attrs->name, $options[$j]->attrs->value, $thumbnail);
                                        if(!$thumbnail_exist) {
                                            $buff .= sprintf('$layout_info->extra_var->%s->thumbnail_exist = true;', $var->attrs->name);
                                            $thumbnail_exist = true;
                                        }
                                    }
                                }
                                $buff .= sprintf('$layout_info->extra_var->%s->options["%s"]->val = "%s";', $var->attrs->name, $options[$j]->attrs->value, $options[$j]->title->body);

                            }
                        }
                    }
                }
                // Menu
                if($xml_obj->menus->menu) {
                    $menus = $xml_obj->menus->menu;
                    if(!is_array($menus)) $menus = array($menus);

                    $menu_count = count($menus);
                    $buff .= sprintf('$layout_info->menu_count = "%s";', $menu_count);
                    for($i=0;$i<$menu_count;$i++) {
                        $name = $menus[$i]->attrs->name;
                        if($menus[$i]->attrs->default == "true") $buff .= sprintf('$layout_info->default_menu = "%s";', $name);
                        $buff .= sprintf('$layout_info->menu->%s->name = "%s";',$name, $menus[$i]->attrs->name);
                        $buff .= sprintf('$layout_info->menu->%s->title = "%s";',$name, $menus[$i]->title->body);
                        $buff .= sprintf('$layout_info->menu->%s->maxdepth = "%s";',$name, $menus[$i]->attrs->maxdepth);

                        $buff .= sprintf('$layout_info->menu->%s->menu_srl = $vars->%s;', $name, $name);
                        $buff .= sprintf('$layout_info->menu->%s->xml_file = "./files/cache/menu/".$vars->%s.".xml.php";',$name, $name);
                        $buff .= sprintf('$layout_info->menu->%s->php_file = "./files/cache/menu/".$vars->%s.".php";',$name, $name);
                    }
                }


                // history
                if($xml_obj->history) {
                    if(!is_array($xml_obj->history)) $history_list[] = $xml_obj->history;
                    else $history_list = $xml_obj->history;

                    for($i=0; $i < count($history_list); $i++) {
                        sscanf($history_list[$i]->attrs->date, '%d-%d-%d', $date_obj->y, $date_obj->m, $date_obj->d);
                        $date = sprintf('%04d%02d%02d', $date_obj->y, $date_obj->m, $date_obj->d);
                        $buff .= sprintf('$layout_info->history['.$i.']->description = "%s";', $history_list[$i]->description->body);
                        $buff .= sprintf('$layout_info->history['.$i.']->version = "%s";', $history_list[$i]->attrs->version);
                        $buff .= sprintf('$layout_info->history['.$i.']->date = "%s";', $date);

                        if($history_list[$i]->author) {
                            (!is_array($history_list[$i]->author)) ? $obj->author_list[] = $history_list[$i]->author : $obj->author_list = $history_list[$i]->author;

                            for($j=0; $j < count($obj->author_list); $j++) {
                                $buff .= sprintf('$layout_info->history['.$i.']->author['.$j.']->name = "%s";', $obj->author_list[$j]->name->body);
                                $buff .= sprintf('$layout_info->history['.$i.']->author['.$j.']->email_address = "%s";', $obj->author_list[$j]->attrs->email_address);
                                $buff .= sprintf('$layout_info->history['.$i.']->author['.$j.']->homepage = "%s";', $obj->author_list[$j]->attrs->link);
                            }
                        }

                        if($history_list[$i]->log) {
                            (!is_array($history_list[$i]->log)) ? $obj->log_list[] = $history_list[$i]->log : $obj->log_list = $history_list[$i]->log;

                            for($j=0; $j < count($obj->log_list); $j++) {
                                $buff .= sprintf('$layout_info->history['.$i.']->logs['.$j.']->text = "%s";', $obj->log_list[$j]->body);
                                $buff .= sprintf('$layout_info->history['.$i.']->logs['.$j.']->link = "%s";', $obj->log_list[$j]->attrs->link);
                            }
                        }
                    }
                }



            } else {
                // Layout title, version and other information
                sscanf($xml_obj->author->attrs->date, '%d. %d. %d', $date_obj->y, $date_obj->m, $date_obj->d);
                $date = sprintf('%04d%02d%02d', $date_obj->y, $date_obj->m, $date_obj->d);
                $buff .= sprintf('$layout_info->layout = "%s";', $layout);
                $buff .= sprintf('$layout_info->path = "%s";', $layout_path);
                $buff .= sprintf('$layout_info->title = "%s";', $xml_obj->title->body);
                $buff .= sprintf('$layout_info->description = "%s";', $xml_obj->author->description->body);
                $buff .= sprintf('$layout_info->version = "%s";', $xml_obj->attrs->version);
                $buff .= sprintf('$layout_info->date = "%s";', $date);
                $buff .= sprintf('$layout_info->layout_srl = $layout_srl;');
                $buff .= sprintf('$layout_info->layout_title = $layout_title;');
                // Author information
                $buff .= sprintf('$layout_info->author[0]->name = "%s";', $xml_obj->author->name->body);
                $buff .= sprintf('$layout_info->author[0]->email_address = "%s";', $xml_obj->author->attrs->email_address);
                $buff .= sprintf('$layout_info->author[0]->homepage = "%s";', $xml_obj->author->attrs->link);
                // Extra vars (user defined variables to use in a template)
                $extra_var_groups = $xml_obj->extra_vars->group;
                if(!$extra_var_groups) $extra_var_groups = $xml_obj->extra_vars;
                if(!is_array($extra_var_groups)) $extra_var_groups = array($extra_var_groups);
                foreach($extra_var_groups as $group){
                    $extra_vars = $group->var;
                    if($extra_vars) {
                        if(!is_array($extra_vars)) $extra_vars = array($extra_vars);

                        $extra_var_count = count($extra_vars);

                        $buff .= sprintf('$layout_info->extra_var_count = "%s";', $extra_var_count);
                        for($i=0;$i<$extra_var_count;$i++) {
                            unset($var);
                            unset($options);
                            $var = $extra_vars[$i];
                            $name = $var->attrs->name;

                            $buff .= sprintf('$layout_info->extra_var->%s->group = "%s";', $name, $group->title->body);
                            $buff .= sprintf('$layout_info->extra_var->%s->title = "%s";', $name, $var->title->body);
                            $buff .= sprintf('$layout_info->extra_var->%s->type = "%s";', $name, $var->attrs->type);
                            $buff .= sprintf('$layout_info->extra_var->%s->value = $vars->%s;', $name, $name);
                            $buff .= sprintf('$layout_info->extra_var->%s->description = "%s";', $name, str_replace('"','\"',$var->description->body));

                            $options = $var->options;
                            if(!$options) continue;

                            if(!is_array($options)) $options = array($options);
                            $options_count = count($options);
                            for($j=0;$j<$options_count;$j++) {
                                $buff .= sprintf('$layout_info->extra_var->%s->options["%s"]->val = "%s";', $var->attrs->name, $options[$j]->value->body, $options[$j]->title->body);
                            }
                        }
                    }
                }
                // Menu
                if($xml_obj->menus->menu) {
                    $menus = $xml_obj->menus->menu;
                    if(!is_array($menus)) $menus = array($menus);

                    $menu_count = count($menus);
                    $buff .= sprintf('$layout_info->menu_count = "%s";', $menu_count);
                    for($i=0;$i<$menu_count;$i++) {
                        $name = $menus[$i]->attrs->name;
                        if($menus[$i]->attrs->default == "true") $buff .= sprintf('$layout_info->default_menu = "%s";', $name);
                        $buff .= sprintf('$layout_info->menu->%s->name = "%s";',$name, $menus[$i]->attrs->name);
                        $buff .= sprintf('$layout_info->menu->%s->title = "%s";',$name, $menus[$i]->title->body);
                        $buff .= sprintf('$layout_info->menu->%s->maxdepth = "%s";',$name, $menus[$i]->maxdepth->body);

                        $buff .= sprintf('$layout_info->menu->%s->menu_srl = $vars->%s;', $name, $name);
                        $buff .= sprintf('$layout_info->menu->%s->xml_file = "./files/cache/menu/".$vars->%s.".xml.php";',$name, $name);
                        $buff .= sprintf('$layout_info->menu->%s->php_file = "./files/cache/menu/".$vars->%s.".php";',$name, $name);
                    }
                }

            }

            // header_script
            $oModuleModel = &getModel('module');
            $layout_config = $oModuleModel->getModulePartConfig('layout', $layout_srl);
            $header_script = trim($layout_config->header_script);

            if($header_script)
			{
				$buff .= sprintf(' $layout_info->header_script = "%s"; ', str_replace(array('$','"'),array('\$','\\"'),$header_script));
			}

            $buff = '<?php if(!defined("__ZBXE__")) exit(); '.$buff.' ?>';
            FileHandler::writeFile($cache_file, $buff);
            if(file_exists($cache_file)) @include($cache_file);

			if(!$layout_info->title)
			{
				$layout_info->title = $layout;
			}

            return $layout_info;
        }

        /**
         * Return a list of images which are uploaded on the layout setting page
		 * @param int $layout_srl
		 * @return array image list in layout
         **/
        function getUserLayoutImageList($layout_srl){
            $path = $this->getUserLayoutImagePath($layout_srl);
            $list = FileHandler::readDir($path);
            return $list;
        }

        /**
         * Get ini configurations and make them an array.
		 * @param int $layout_srl
		 * @param string $layout_name
		 * @return array 
         **/
        function getUserLayoutIniConfig($layout_srl, $layout_name=null){
            $file = $this->getUserLayoutIni($layout_srl);
            if($layout_name && !file_exists(FileHandler::getRealPath($file))){
                FileHandler::copyFile($this->getDefaultLayoutIni($layout_name),$this->getUserLayoutIni($layout_srl));
            }

            $output = FileHandler::readIniFile($file);
            return $output;
        }

        /**
         * get user layout path
		 * @param int $layout_srl
		 * @return string
         **/
        function getUserLayoutPath($layout_srl){
            return sprintf("./files/faceOff/%s",getNumberingPath($layout_srl,3));
        }

        /**
         * get user layout image path
		 * @param int $layout_srl
		 * @return string
         **/
        function getUserLayoutImagePath($layout_srl){
            return $this->getUserLayoutPath($layout_srl). 'images/';
        }

        /**
         * css which is set by an administrator on the layout setting page
		 * @param int $layout_srl
		 * @return string
         **/
        function getUserLayoutCss($layout_srl){
            return $this->getUserLayoutPath($layout_srl). 'layout.css';
        }

        /**
         * Import faceoff css from css module handler
		 * @param int $layout_srl
		 * @return string
         **/
        function getUserLayoutFaceOffCss($layout_srl){
            $src = $this->_getUserLayoutFaceOffCss($layout_srl);
            if($this->useUserLayoutTemp == 'temp') return;
            return $src;
        }

        /**
         * Import faceoff css from css module handler
		 * @param int $layout_srl
		 * @return string
         **/
        function _getUserLayoutFaceOffCss($layout_srl){
            return $this->getUserLayoutPath($layout_srl). 'faceoff.css';
        }

        /**
         * get user layout tmp html
		 * @param int $layout_srl
		 * @return string
         **/
        function getUserLayoutTempFaceOffCss($layout_srl){
            return $this->getUserLayoutPath($layout_srl). 'tmp.faceoff.css';
        }

        /**
         * user layout html
		 * @param int $layout_srl
		 * @return string
         **/
        function getUserLayoutHtml($layout_srl){
            $src = $this->getUserLayoutPath($layout_srl). 'layout.html';
            $temp = $this->getUserLayoutTempHtml($layout_srl);
            if($this->useUserLayoutTemp == 'temp'){
                if(!file_exists(FileHandler::getRealPath($temp))) FileHandler::copyFile($src,$temp);
                return $temp;
            }else{
                return $src;
            }
        }

        /**
         * user layout tmp html
		 * @param int $layout_srl
		 * @return string
         **/
        function getUserLayoutTempHtml($layout_srl){
            return $this->getUserLayoutPath($layout_srl). 'tmp.layout.html';
        }

        /**
         * user layout ini
		 * @param int $layout_srl
		 * @return string
         **/
        function getUserLayoutIni($layout_srl){
            $src = $this->getUserLayoutPath($layout_srl). 'layout.ini';
            $temp = $this->getUserLayoutTempIni($layout_srl);
            if($this->useUserLayoutTemp == 'temp'){
                if(!file_exists(FileHandler::getRealPath($temp))) FileHandler::copyFile($src,$temp);
                return $temp;
            }else{
                return $src;
            }
        }

        /**
         * user layout tmp ini
		 * @param int $layout_srl
		 * @return string
         **/
        function getUserLayoutTempIni($layout_srl){
            return $this->getUserLayoutPath($layout_srl). 'tmp.layout.ini';
        }

        /**
         * user layout cache
         * TODO It may need to remove the file itself
		 * @param int $layout_srl
		 * @param string $lang_type
		 * @return string
         **/
        function getUserLayoutCache($layout_srl,$lang_type){
            return $this->getUserLayoutPath($layout_srl). "{$lang_type}.cache.php";
        }

        /**
         * layout cache
		 * @param int $layout_srl
		 * @param string $lang_type
		 * @return string
         **/
        function getLayoutCache($layout_name,$lang_type){
            return sprintf("./files/cache/layout/%s.%s.cache.php",$layout_name,$lang_type);
        }

        /**
         * default layout ini to prevent arbitrary changes by a user
		 * @param string $layout_name
		 * @return string
         **/
        function getDefaultLayoutIni($layout_name){
            return $this->getDefaultLayoutPath($layout_name). 'layout.ini';
        }

        /**
         * default layout html to prevent arbitrary changes by a user
		 * @param string $layout_name
		 * @return string
         **/
        function getDefaultLayoutHtml($layout_name){
            return $this->getDefaultLayoutPath($layout_name). 'layout.html';
        }

        /**
         * default layout css to prevent arbitrary changes by a user
		 * @param string $layout_name
		 * @return string
         **/
        function getDefaultLayoutCss($layout_name){
            return $this->getDefaultLayoutPath($layout_name). 'css/layout.css';
        }

        /**
         * default layout path to prevent arbitrary changes by a user
		 * @deprecated
		 * @return string
         **/
        function getDefaultLayoutPath() {
            return "./modules/layout/faceoff/";
        }

        /**
         * faceoff is
		 * @param string $layout_name
		 * @return boolean (true : faceoff, false : layout)
         **/
        function useDefaultLayout($layout_name){
            $info = $this->getLayoutInfo($layout_name);
            if($info->type == 'faceoff') return true;
            else return false;
        }

        /**
         * Set user layout as temporary save mode
		 * @param string $flag (default 'temp')
		 * @return void
         **/
        function setUseUserLayoutTemp($flag='temp'){
            $this->useUserLayoutTemp = $flag;
        }

        /**
         * Temp file list for User Layout
		 * @param int $layout_srl
		 * @return array temp files info
         **/
        function getUserLayoutTempFileList($layout_srl){
            $file_list = array(
                $this->getUserLayoutTempHtml($layout_srl)
                ,$this->getUserLayoutTempFaceOffCss($layout_srl)
                ,$this->getUserLayoutTempIni($layout_srl)
            );
            return $file_list;
        }

        /**
         * Saved file list for User Layout
		 * @param int $layout_srl
		 * @return array files info
         **/
        function getUserLayoutFileList($layout_srl){
            $file_list = array(
                basename($this->getUserLayoutHtml($layout_srl))
                ,basename($this->getUserLayoutFaceOffCss($layout_srl))
                ,basename($this->getUserLayoutIni($layout_srl))
                ,basename($this->getUserLayoutCss($layout_srl))
            );

            $image_path = $this->getUserLayoutImagePath($layout_srl);
            $image_list = FileHandler::readDir($image_path,'/(.*(?:swf|jpg|jpeg|gif|bmp|png)$)/i');

            for($i=0,$c=count($image_list);$i<$c;$i++) $file_list[] = 'images/' . $image_list[$i];
            return $file_list;
        }

        /**
         * faceOff related services for the operation run out
		 * @deprecated
		 * @param object $layout_info
		 * @return void
         **/
        function doActivateFaceOff(&$layout_info) {
            $layout_info->faceoff_ini_config = $this->getUserLayoutIniConfig($layout_info->layout_srl, $layout_info->layout);
            // faceoff layout CSS
            Context::addCSSFile($this->getDefaultLayoutCss($layout_info->layout));
            // CSS generated in the layout manager
            $faceoff_layout_css = $this->getUserLayoutFaceOffCss($layout_info->layout_srl);
            if($faceoff_layout_css) Context::addCSSFile($faceoff_layout_css);
            // CSS output for the widget
            Context::loadFile($this->module_path.'/tpl/css/widget.css', true);
            if($layout_info->extra_var->colorset->value == 'black') Context::loadFile($this->module_path.'/tpl/css/widget@black.css', true);
            else Context::loadFile($this->module_path.'/tpl/css/widget@white.css', true);
            // Different page displayed upon user's permission
            $logged_info = Context::get('logged_info');
            // Display edit button for faceoff layout
            if(Context::get('module')!='admin' && strpos(Context::get('act'),'Admin')===false && ($logged_info->is_admin == 'Y' || $logged_info->is_site_admin)) {
                Context::addHtmlFooter('<div class="faceOffManager" style="height: 23px; position: fixed; right: 3px; top: 3px;"><a href="'.getUrl('','mid',Context::get('mid'),'act','dispLayoutAdminLayoutModify','delete_tmp','Y').'">'.Context::getLang('cmd_layout_edit').'</a></div>');
            }
            // Display menu when editing the faceOff page
            if(Context::get('act')=='dispLayoutAdminLayoutModify' && ($logged_info->is_admin == 'Y' || $logged_info->is_site_admin)) {
                $oTemplate = &TemplateHandler::getInstance();
                Context::addBodyHeader($oTemplate->compile($this->module_path.'/tpl', 'faceoff_layout_menu'));
            }
        }
    }
?>
