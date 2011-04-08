<?php
    /**
     * @class  pageView
     * @author NHN (developers@xpressengine.com)
     * @brief page view class of the module
     **/

    class pageView extends page {

        var $module_srl = 0;
        var $list_count = 20;
        var $page_count = 10;

        /**
         * @brief Initialization
         **/
        function init() {
            // Get a template path (page in the administrative template tpl putting together)
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * @brief General request output
         **/
        function dispPageIndex() {
            // Variables used in the template Context:: set()
            if($this->module_srl) Context::set('module_srl',$this->module_srl);
            // Specifying the cache file
            $cache_file = sprintf("%sfiles/cache/page/%d.%s.cache.php", _XE_PATH_, $this->module_info->module_srl, Context::getLangType());
            $interval = (int)($this->module_info->page_caching_interval);
            if($interval>0) {
                if(!file_exists($cache_file)) $mtime = 0;
                else $mtime = filemtime($cache_file);

                if($mtime + $interval*60 > time()) {
                    $page_content = FileHandler::readFile($cache_file); 
					$page_content = preg_replace('@<\!--#Meta:@', '<!--Meta:', $page_content);
                } else {
                    $oWidgetController = &getController('widget');
                    $page_content = $oWidgetController->transWidgetCode($this->module_info->content);
                    FileHandler::writeFile($cache_file, $page_content);
                }
            } else {
                if(file_exists($cache_file)) FileHandler::removeFile($cache_file);
                $page_content = $this->module_info->content;
            }
            
            Context::set('module_info', $this->module_info);
            Context::set('page_content', $page_content);

            $this->setTemplateFile('content');
        }
    }
?>
