<?php
    /**
     * @class  syndicationController
     * @author zero (skklove@gmail.com)
     * @brief  syndication 모듈의 Controller class
     **/

    class syndicationController extends syndication {

        function triggerInsertDocument(&$obj) {
            if($obj->module_srl < 1) return new Object();

            $oSyndicationModel = &getModel('syndication');
            $oModuleModel = &getModel('module');

            if($oSyndicationModel->isExceptedModules($obj->module_srl)) return new Object();

            $config = $oModuleModel->getModuleConfig('syndication');

            $id = $oSyndicationModel->getID('channel', $obj->module_srl);
            $this->ping($id, 'article');

            return new Object();
        }

        function triggerUpdateDocument(&$obj) {
            if($obj->module_srl < 1) return new Object();

            $oSyndicationModel = &getModel('syndication');
            $oModuleModel = &getModel('module');

            if($oSyndicationModel->isExceptedModules($obj->module_srl)) return new Object();

            $config = $oModuleModel->getModuleConfig('syndication');

            $id = $oSyndicationModel->getID('channel', $obj->module_srl);
            $this->ping($id, 'article');

            return new Object();
        }

        function triggerDeleteDocument(&$obj) {
            if($obj->module_srl < 1) return new Object();

            $oSyndicationModel = &getModel('syndication');
            $oModuleModel = &getModel('module');

            if($oSyndicationModel->isExceptedModules($obj->module_srl)) return new Object();

            $this->insertLog($obj->module_srl, $obj->document_srl, $obj->title, $obj->content);

            $config = $oModuleModel->getModuleConfig('syndication');

            $id = $oSyndicationModel->getID('channel', $obj->module_srl);
            $this->ping($id, 'deleted');

            return new Object();
        }

        function triggerDeleteModule(&$obj) {
            $oSyndicationModel = &getModel('syndication');
            $oModuleModel = &getModel('module');

            if($oSyndicationModel->isExceptedModules($obj->module_srl)) return new Object();

            $this->insertLog($obj->module_srl, $obj->document_srl, $obj->title, $obj->content);

            $output = executeQuery('syndication.getExceptModule', $obj);
            if($output->data->count) return new Object();

            $config = $oModuleModel->getModuleConfig('syndication');

            $id = $oSyndicationModel->getID('site', $obj->module_srl);
            $this->ping($id, 'deleted');

            return new Object();
        }
        
        function insertLog($module_srl, $document_srl, $title = null, $summary = null) {
            $args->module_srl = $module_srl;
            $args->document_srl = $document_srl;
            $args->title = $title;
            $args->summary = $summary;
            $output = executeQuery('syndication.insertLog', $args);
        }

        function ping($id, $type) {
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('syndication');

            if(!count($config->target_services)) return;
            if(substr($config->site_url,-1)!='/') $config->site_url .= '/';
            foreach($config->target_services as $key => $val) {
                $ping_url = trim($this->services[$val]);
                if(!$ping_url) continue;
                $ping_body = sprintf('http://%s?module=syndication&act=getSyndicationList&id=%s&type=%s', $config->site_url, $id, $type);
                FileHandler::getRemoteResource($ping_url, null, 3, 'POST', 'application/x-www-form-urlencoded', array(), array(), array('link'=>$ping_body));
            }
        }
    }
?>
