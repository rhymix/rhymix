<?php
    /**
     * @class  syndicationAdminController
     * @author zero (skklove@gmail.com)
     * @brief  syndication 모듈의 admin Controller class
     **/

    class syndicationAdminController extends syndication {

        function init() {
        }

        function procSyndicationAdminInsertService() {
            $oModuleController = &getController('module');
            $oSyndicationController = &getController('syndication');
            $oSyndicationModel = &getModel('syndication');

            $config->target_services = explode('|@|',Context::get('target_services'));
            $config->site_url = preg_replace('/\/+$/is','',Context::get('site_url'));
            $config->year = Context::get('year');
            if(!$config->site_url) return new Object(-1,'msg_site_url_is_null');

            $oModuleController->insertModuleConfig('syndication',$config);
            $oSyndicationController->ping($oSyndicationModel->getID('site'), 'site');

            $except_module = Context::get('except_module');
            $output = executeQuery('syndication.deleteExceptModules');
            if(!$output->toBool()) return $output;

            $modules = explode(',',$except_module);
            for($i=0,$c=count($modules);$i<$c;$i++) {
                $args->module_srl = $modules[$i];
                $output = executeQuery('syndication.insertExceptModule',$args);
                if(!$output->toBool()) return $output;
            }

            $this->setMessage('success_applied');
        }
    }
?>
