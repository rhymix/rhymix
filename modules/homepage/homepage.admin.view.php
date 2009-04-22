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
            $oLayoutModel = &getModel('layout');
            $oHomepageAdminModel = &getAdminModel('homepage');
            $oHomepageModel = &getModel('homepage');
            $oModuleModel = &getModel('module');

            // cafe 전체 설정을 구함
            $homepage_config = $oHomepageModel->getConfig();
            Context::set('homepage_config', $homepage_config);

            // 레이아웃 목록을 구함
            $layout_list = $oLayoutModel->getDownloadedLayoutList();
            Context::set('layout_list', $layout_list);

            // 서비스 모듈을 구함
            $installed_module_list = $oModuleModel->getModulesXmlInfo();
            foreach($installed_module_list as $key => $val) {
                if($val->category != 'service') continue;
                $service_modules[] = $val;
            }
            Context::set('service_modules', $service_modules);

            // 생성된 카페 목록을 구함
            $page = Context::get('page');
            $output = $oHomepageAdminModel->getHomepageList($page);

            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('homepage_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            $this->setTemplateFile('index');
        }

        function dispHomepageAdminSetup() {
            $oLayoutModel = &getModel('layout');
            $oHomepageAdminModel = &getAdminModel('homepage');
            $oModuleModel = &getModel('module');
            $oHomepageModel = &getModel('homepage');

            $site_srl = Context::get('site_srl');
            $homepage_info = $oHomepageModel->getHomepageInfo($site_srl);
            Context::set('homepage_info', $homepage_info);

            // cafe 전체 설정을 구함
            $homepage_config = $oHomepageModel->getConfig($site_srl);
            Context::set('homepage_config', $homepage_config);

            // 레이아웃 목록을 구함
            $layout_list = $oLayoutModel->getDownloadedLayoutList();
            Context::set('layout_list', $layout_list);

            // 서비스 모듈을 구함
            $installed_module_list = $oModuleModel->getModulesXmlInfo();
            foreach($installed_module_list as $key => $val) {
                if($val->category != 'service') continue;
                $service_modules[] = $val;
            }
            Context::set('service_modules', $service_modules);

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
