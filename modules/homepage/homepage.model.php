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

        function getConfig($site_srl = 0) {
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('homepage');
            if(!$config) {
                $config->default_layout = 'cafeXE';
                $config->enable_change_layout = 'N';
                $config->allow_service = array('board'=>10,'page'=>2);
                $config->creation_group = array();
                $config->cafe_main_mid = 'cafe';
                $config->skin = 'xe_default';
                $config->access_type = 'vid';
                $config->default_domain = '';
            } else {
                $config->creation_group = explode(',',$config->creation_group);
                if(!isset($config->cafe_main_mid)) $config->cafe_main_mid = 'cafe';
                if(!isset($config->skin)) $config->skin = 'xe_default';
                if(!isset($config->access_type)) $config->access_type = 'vid';
                if($config->default_domain) {
                    if(strpos($config->default_domain,':')===false) $config->default_domain = 'http://'.$config->default_domain;
                    if(substr($config->default_domain,-1)!='/') $config->default_domain .= '/';
                }
            }
            if($site_srl) {
                $part_config = $oModuleModel->getModulePartConfig('homepage', $site_srl);
                if(!$part_config) $part_config = $config;
                else $config = $part_config;
            }

            return $config;
        }

        function isCreationGranted($member_info = null) {
            if(!$member_info) $member_info = Context::get('logged_info');
            if(!$member_info->member_srl) return false;
            if($member_info->is_admin == 'Y') return true;

            $config = $this->getConfig(0);

            if(!is_array($member_info->group_list) || !count($member_info->group_list) || !count($config->creation_group)) return;

            $keys = array_keys($member_info->group_list);
            for($i=0,$c=count($keys);$i<$c;$i++) {
                if(in_array($keys[$i],$config->creation_group)) return true;
            }
            return false;
        }

        function getHomepageInfo($site_srl) {
            $args->site_srl = $site_srl;
            $output = executeQuery('homepage.getHomepageInfo', $args);
            if(!$output->toBool() || !$output->data) return;

            $banner_src = 'files/attach/cafe_banner/'.$site_srl.'.jpg';
            if(file_exists(_XE_PATH_.$banner_src)) $output->data->cafe_banner = $banner_src.'?rnd='.filemtime(_XE_PATH_.$banner_src);
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
