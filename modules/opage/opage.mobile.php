<?php
require_once(_XE_PATH_.'modules/opage/opage.view.php');
class opageMobile extends opageView {

    function init() {
        // 템플릿 경로 구함 (opage의 경우 tpl에 관리자용 템플릿 모아놓음)
        $this->setTemplatePath($this->module_path.'tpl');

        $oOpageModel = &getModel('opage');
        $module_info = $oOpageModel->getOpage($this->module_srl);
        Context::set('module_info', $module_info);

        // 외부 페이지에서 명시된 외부 페이지 경로/ 캐싱 간격을 를 구함
        if($module_info->mpath) $this->path = $module_info->mpath;
        else $this->path = $module_info->path;
        $this->caching_interval = $module_info->caching_interval;

        // 캐시 파일 지정
        $this->cache_file = sprintf("./files/cache/opage/%d.m.cache.php", $module_info->module_srl);
    }

}
