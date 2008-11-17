<?php
    /**
     * @class  homepageModel
     * @author zero (zero@nzeo.com)
     * @brief  homepage 모듈의  model class
     **/

    class homepageModel extends homepage {

        var $site_module_info = null;
        var $site_srl = 0;

        function init() {
            // site_module_info값으로 홈페이지의 정보를 구함
            $this->site_module_info = Context::get('site_module_info');
            $this->site_srl = $this->site_module_info->site_srl;
        }

        function getHomepageInfo($site_srl) {
            $args->site_srl = $site_srl;
            $output = executeQuery('homepage.getHomepageInfo', $args);
            if(!$output->toBool() || !$output->data) return;
            return $output->data;
        }

        function getHomepageMenuItem() {
            $node_srl = Context::get('node_srl');
            if(!$node_srl) return new Object(-1,'msg_invalid_request');

            $oMenuAdminModel = &getAdminModel('menu');
            $menu_info = $oMenuAdminModel->getMenuItemInfo($node_srl);

            if(!preg_match('/^http/i',$menu_info->url)) {
                $oModuleModel = &getModel('module');
                $module_info = $oModuleModel->getModuleInfoByMid($menu_info->url, $this->site_srl);
                if($module_info->mid == $menu_info->url) {
                    $menu_info->module_type = $module_info->module;
                    $menu_info->module_id = $module_info->mid;
                    $menu_info->browser_title = $module_info->browser_title;
                    unset($menu_info->url);
                }
            } else {
                $menu_info->module_type = 'url';
                $menu_info->url = preg_replace('/^(http|https):\/\//i','',$menu_info->url);
            }
            $this->add('menu_info', $menu_info);
        }
    }

?>
