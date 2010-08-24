<?php
    /**
     * @class  syndicationAdminView
     * @author zero (skklove@gmail.com)
     * @brief  syndication admin view class
     **/

    class syndicationAdminView extends syndication {

        function init() {
        }

        function dispSyndicationAdminConfig() {
            $oModuleModel = &getModel('module');

            $module_config = $oModuleModel->getModuleConfig('syndication');
            if(!$module_config->target_services) $module_config->target_services = array();

            foreach($this->services as $key => $val) {
                unset($obj);
                $obj->service = $key;
                $obj->ping = $val;
                $obj->selected = in_array($key, $module_config->target_services)?true:false;
                $services[] = $obj;
            }
            Context::set('services', $services);

            if(!$module_config->site_url) {
                $module_config->site_url = Context::getDefaultUrl()?Context::getDefaultUrl():getFullUrl();
            }
            Context::set('site_url', preg_replace('/^(http|https):\/\//i','',$module_config->site_url));

            if(!$module_config->year) {
                $module_config->year = date("Y");
            }
            Context::set('year', $module_config->year);

            $output = executeQueryArray('syndication.getExceptModules');
            $except_module_list = array();
            for($i=0,$c=count($output->data);$i<$c;$i++) {
                $except_module_list[] = $output->data[$i];
            }
            Context::set('except_module', $except_module_list);

            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('config');
        }

    }
?>
