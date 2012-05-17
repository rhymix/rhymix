<?php
    /**
     * @class  pageAdminView
     * @author NHN (developers@xpressengine.com)
     * @brief page admin view of the module class
     **/

    class pageAdminView extends page {

        var $module_srl = 0;
        var $list_count = 20;
        var $page_count = 10;

        /**
         * @brief Initialization
         **/
        function init() {
            // Pre-check if module_srl exists. Set module_info if exists
            $module_srl = Context::get('module_srl');
            // Create module model object
            $oModuleModel = &getModel('module');
            // module_srl two come over to save the module, putting the information in advance
            if($module_srl) {
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
                if(!$module_info) {
                    Context::set('module_srl','');
                    $this->act = 'list';
                } else {
                    ModuleModel::syncModuleToSite($module_info);
                    $this->module_info = $module_info;
                    Context::set('module_info',$module_info);
                }
            }
            // Get a list of module categories
            $module_category = $oModuleModel->getModuleCategories();
            Context::set('module_category', $module_category);
			//Security
			$security = new Security();
			$security->encodeHTML('module_category..title');

			// Get a template path (page in the administrative template tpl putting together)
            $this->setTemplatePath($this->module_path.'tpl');

        }

        /**
         * @brief Manage a list of pages showing
         **/
        function dispPageAdminContent() {
            $args->sort_index = "module_srl";
            $args->page = Context::get('page');
            $args->list_count = 40;
            $args->page_count = 10;
            $args->s_module_category_srl = Context::get('module_category_srl');

			$s_mid = Context::get('s_mid');
			if($s_mid) $args->s_mid = $s_mid;

			$s_browser_title = Context::get('s_browser_title');
			if($s_browser_title) $args->s_browser_title = $s_browser_title;

            $output = executeQuery('page.getPageList', $args);
			$oModuleModel = &getModel('module');
			$page_list = $oModuleModel->addModuleExtraVars($output->data);
            moduleModel::syncModuleToSite($page_list);

            // To write to a template context:: set
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('page_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);
			//Security
			$security = new Security();
			$security->encodeHTML('page_list..browser_title');
			$security->encodeHTML('page_list..mid');
			$security->encodeHTML('module_info.');

			// Set a template file
            $this->setTemplateFile('index');
        }

        /**
         * @brief Information output of the selected page
         **/
        function dispPageAdminInfo() {
            // Get module_srl by GET parameter
            $module_srl = Context::get('module_srl');
            $module_info = Context::get('module_info');
            // If you do not value module_srl just showing the index page
            if(!$module_srl) return $this->dispPageAdminContent();
            // If the layout is destined to add layout information haejum (layout_title, layout)
            if($module_info->layout_srl) {
                $oLayoutModel = &getModel('layout');
                $layout_info = $oLayoutModel->getLayout($module_info->layout_srl);
                $module_info->layout = $layout_info->layout;
                $module_info->layout_title = $layout_info->layout_title;
            }
            // Get a layout list
            $oLayoutModel = &getModel('layout');
            $layout_list = $oLayoutModel->getLayoutList();
            Context::set('layout_list', $layout_list);

			$mobile_layout_list = $oLayoutModel->getLayoutList(0,"M");
			Context::set('mlayout_list', $mobile_layout_list);
            // Set a template file

			if ($this->module_info->page_type == 'ARTICLE'){
				$oModuleModel = &getModel('module');
				$skin_list = $oModuleModel->getSkins($this->module_path);
				Context::set('skin_list',$skin_list);

				$mskin_list = $oModuleModel->getSkins($this->module_path, "m.skins");
				Context::set('mskin_list', $mskin_list);
			}

			//Security
			$security = new Security();
			$security->encodeHTML('layout_list..layout');
			$security->encodeHTML('layout_list..title');
			$security->encodeHTML('mlayout_list..layout');
			$security->encodeHTML('mlayout_list..title');
			$security->encodeHTML('module_info.');

            $this->setTemplateFile('page_info');
        }

        /**
         * @brief Additional settings page showing
         * For additional settings in a service module in order to establish links with other modules peyijiim
         **/
        function dispPageAdminPageAdditionSetup() {
            // call by reference content from other modules to come take a year in advance for putting the variable declaration
            $content = '';

            $oEditorView = &getView('editor');
            $oEditorView->triggerDispEditorAdditionSetup($content);
            Context::set('setup_content', $content);
            // Set a template file
            $this->setTemplateFile('addition_setup');

			$security = new Security();
			$security->encodeHTML('module_info.');
        }

        /**
         * @brief Add Page Form Output
         **/
        function dispPageAdminInsert() {
            // Get module_srl by GET parameter
            $module_srl = Context::get('module_srl');
            // Get and set module information if module_srl exists
            if($module_srl) {
                $oModuleModel = &getModel('module');
				$columnList = array('module_srl', 'mid', 'module_category_srl', 'browser_title', 'layout_srl', 'use_mobile', 'mlayout_srl');
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl, $columnList);
                if($module_info->module_srl == $module_srl) Context::set('module_info',$module_info);
                else {
                    unset($module_info);
                    unset($module_srl);
                }
            }
            // Get a layout list
            $oLayoutModel = &getModel('layout');
            $layout_list = $oLayoutModel->getLayoutList();
            Context::set('layout_list', $layout_list);

			$mobile_layout_list = $oLayoutModel->getLayoutList(0,"M");
			Context::set('mlayout_list', $mobile_layout_list);

            $oModuleModel = &getModel('module');
            $skin_list = $oModuleModel->getSkins($this->module_path);
            Context::set('skin_list',$skin_list);

			$mskin_list = $oModuleModel->getSkins($this->module_path, "m.skins");
			Context::set('mskin_list', $mskin_list);

			//Security
			$security = new Security();
			$security->encodeHTML('layout_list..layout');
			$security->encodeHTML('layout_list..title');
			$security->encodeHTML('mlayout_list..layout');
			$security->encodeHTML('mlayout_list..title');

            // Set a template file
            $this->setTemplateFile('page_insert');
        }

		function dispPageAdminMobileContent() {
            if($this->module_srl) Context::set('module_srl',$this->module_srl);
            // Specifying the cache file
            $cache_file = sprintf("%sfiles/cache/page/%d.%s.m.cache.php", _XE_PATH_, $this->module_info->module_srl, Context::getLangType());
            $interval = (int)($this->module_info->page_caching_interval);
            if($interval>0) {
                if(!file_exists($cache_file)) $mtime = 0;
                else $mtime = filemtime($cache_file);

                if($mtime + $interval*60 > time()) {
                    $page_content = FileHandler::readFile($cache_file);
                } else {
                    $oWidgetController = &getController('widget');
                    $page_content = $oWidgetController->transWidgetCode($this->module_info->mcontent);
                    FileHandler::writeFile($cache_file, $page_content);
                }
            } else {
                if(file_exists($cache_file)) FileHandler::removeFile($cache_file);
                $page_content = $this->module_info->mcontent;
            }

            Context::set('module_info', $this->module_info);
            Context::set('page_content', $page_content);

            $this->setTemplateFile('mcontent');
		}

		function dispPageAdminMobileContentModify() {
            Context::set('module_info', $this->module_info);
            // Setting contents
            $content = Context::get('mcontent');
            if(!$content) $content = $this->module_info->mcontent;
            Context::set('content', $content);
            // Convert them to teach the widget
            $oWidgetController = &getController('widget');
            $content = $oWidgetController->transWidgetCode($content, true);
            Context::set('page_content', $content);
            // Set widget list
            $oWidgetModel = &getModel('widget');
            $widget_list = $oWidgetModel->getDownloadedWidgetList();
            Context::set('widget_list', $widget_list);

            //Security
			$security = new Security();
			$security->encodeHTML('widget_list..title','module_info.mid');

			// Set a template file
            $this->setTemplateFile('page_mobile_content_modify');
		}

        /**
         * @brief Edit Page Content
         **/
        function dispPageAdminContentModify() {
            // Set the module information
            Context::set('module_info', $this->module_info);

			if ($this->module_info->page_type == 'WIDGET') $this->_setWidgetTypeContentModify();
			else if ($this->module_info->page_type == 'ARTICLE') $this->_setArticleTypeContentModify();
        }

		function _setWidgetTypeContentModify() {
            // Setting contents
            $content = Context::get('content');
            if(!$content) $content = $this->module_info->content;
            Context::set('content', $content);
            // Convert them to teach the widget
            $oWidgetController = &getController('widget');
            $content = $oWidgetController->transWidgetCode($content, true);
            Context::set('page_content', $content);
            // Set widget list
            $oWidgetModel = &getModel('widget');
            $widget_list = $oWidgetModel->getDownloadedWidgetList();
            Context::set('widget_list', $widget_list);

			//Security
			$security = new Security();
			$security->encodeHTML('widget_list..title','module_info.mid');

            // Set a template file
            $this->setTemplateFile('page_content_modify');
        }

		function _setArticleTypeContentModify() {
			$oDocumentModel = &getModel('document');
			$oDocument = $oDocumentModel->getDocument(0, true);
			
			if ($this->module_info->document_srl){
				$document_srl = $this->module_info->document_srl;
				$oDocument->setDocument($document_srl);
				Context::set('document_srl', $document_srl);
			}
            Context::addJsFilter($this->module_path.'tpl/filter', 'insert_article.xml');
			Context::set('oDocument', $oDocument);
			Context::set('mid', $this->module_info->mid);
            $this->setTemplateFile('article_content_modify');
		}

        /**
         * @brief Delete page output
         **/
        function dispPageAdminDelete() {
            $module_srl = Context::get('module_srl');
            if(!$module_srl) return $this->dispContent();

            $oModuleModel = &getModel('module');
			$columnList = array('module_srl', 'module', 'mid');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl, $columnList);
            Context::set('module_info',$module_info);
            // Set a template file
            $this->setTemplateFile('page_delete');

			$security = new Security();
			$security->encodeHTML('module_info.');
        }

        /**
         * @brief Rights Listing
         **/
        function dispPageAdminGrantInfo() {
            // Common module settings page, call rights
            $oModuleAdminModel = &getAdminModel('module');
            $grant_content = $oModuleAdminModel->getModuleGrantHTML($this->module_info->module_srl, $this->xml_info->grant);
            Context::set('grant_content', $grant_content);

            $this->setTemplateFile('grant_list');

			$security = new Security();
			$security->encodeHTML('module_info.');
        }
    }
?>
