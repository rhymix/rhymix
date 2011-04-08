<?php
require_once(_XE_PATH_.'modules/opage/opage.view.php');
class opageMobile extends opageView {

    function init() {
        // Get a template path (admin templates are collected on the tpl for opage)
        $this->setTemplatePath($this->module_path.'tpl');

        $oOpageModel = &getModel('opage');
        $module_info = $oOpageModel->getOpage($this->module_srl);
        Context::set('module_info', $module_info);
        // Get a path/caching interval on the external page
        if($module_info->mpath) $this->path = $module_info->mpath;
        else $this->path = $module_info->path;
        $this->caching_interval = $module_info->caching_interval;
        // Specify the cache file
        $this->cache_file = sprintf("./files/cache/opage/%d.m.cache.php", $module_info->module_srl);
    }

}
