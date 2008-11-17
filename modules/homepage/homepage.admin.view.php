<?php
    /**
     * @class  homepageAdminView
     * @author zero (zero@nzeo.com)
     * @brief  homepage 모듈의 admin view class
     **/

    class homepageAdminView extends homepage {

        function init() {
            $template_path = sprintf("%stpl/",$this->module_path);
            $this->setTemplatePath($template_path);
        }

        function dispHomepageAdminContent() {

            $oHompageAdminModel = &getAdminModel('homepage');
            $page = Context::get('page');
            $output = $oHompageAdminModel->getHomepageList($page);

            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('homepage_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            $this->setTemplateFile('index');
        }

        function dispHomepageAdminSetup() {
            $site_srl = Context::get('site_srl');
            $oHomepageModel = &getModel('homepage');
            $homepage_info = $oHomepageModel->getHomepageInfo($site_srl);
            Context::set('homepage_info', $homepage_info);

            $oModuleModel = &getModel('module');
            $admin_list = $oModuleModel->getSiteAdmin($site_srl);
            Context::set('admin_list', $admin_list);

            $this->setTemplateFile('setup');
        }

        function dispHomepageAdminDelete() {
            $site_srl = Context::get('site_srl');
            $oHomepageModel = &getModel('homepage');
            $homepage_info = $oHomepageModel->getHomepageInfo($site_srl);
            Context::set('homepage_info', $homepage_info);

            $oModuleModel = &getModel('module');
            $admin_list = $oModuleModel->getSiteAdmin($site_srl);
            Context::set('admin_list', $admin_list);

            $this->setTemplateFile('delete');
        }
    }

?>
