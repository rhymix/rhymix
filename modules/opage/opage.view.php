<?php
    /**
     * @class  opageView
     * @author NHN (developers@xpressengine.com)
     * @brief view class of the opage module
     **/

    class opageView extends opage {

        var $path;
        var $cache_file;
        var $caching_interval;

        /**
         * @brief Initialization
         **/
        function init() {
            // Get a template path (admin templates are collected on the tpl for opage)
            $this->setTemplatePath($this->module_path.'tpl');
            // Get information of the external page module
            $oOpageModel = &getModel('opage');
            $module_info = $oOpageModel->getOpage($this->module_srl);
            Context::set('module_info', $module_info);
            // Get a path/caching interval on the external page
            $this->path = $module_info->path;
            $this->caching_interval = $module_info->caching_interval;
            // Specify the cache file
            $this->cache_file = sprintf("./files/cache/opage/%d.cache.php", $module_info->module_srl);
        }

        /**
         * @brief Display when receiving a request
         **/
        function dispOpageIndex() {
            // check if it is http or internal file
            if($this->path) {
                if(preg_match("/^([a-z]+):\/\//i",$this->path)) $content = $this->getHtmlPage($this->path, $this->caching_interval, $this->cache_file);
                else $content = $this->executeFile($this->path, $this->caching_interval, $this->cache_file);
            }

            Context::set('opage_content', $content);
            // Set a template for result output
            $this->setTemplateFile('content');
        }

        /**
         * @brief Save the file and return if a file is requested by http
         **/
        function getHtmlPage($path, $caching_interval, $cache_file) {
            // Verify cache
            if($caching_interval > 0 && file_exists($cache_file) && filemtime($cache_file) + $caching_interval*60 > time()) {

                $content = FileHandler::readFile($cache_file);

            } else {

                FileHandler::getRemoteFile($path, $cache_file);
                $content = FileHandler::readFile($cache_file);

            }
            // Create opage controller
            $oOpageController = &getController('opage');
            // change url of image, css, javascript and so on if the page is from external server
            $content = $oOpageController->replaceSrc($content, $path);
            // Change the document to utf-8 format
            $buff->content = $content;
            $buff = Context::convertEncoding($buff);
            $content = $buff->content;
            // Extract a title
            $title = $oOpageController->getTitle($content);
            if($title) Context::setBrowserTitle($title);
            // Extract header script
            $head_script = $oOpageController->getHeadScript($content);
            if($head_script) Context::addHtmlHeader($head_script);
            // Extract content from the body
            $body_script = $oOpageController->getBodyScript($content);
            if(!$body_script) $body_script = $content;

            return $content;
        }

        /**
         * @brief Create a cache file in order to include if it is an internal file
         **/
        function executeFile($path, $caching_interval, $cache_file) {
            // Cancel if the file doesn't exist
            if(!file_exists($path)) return;
            // Get a path and filename
            $tmp_path = explode('/',$cache_file);
            $filename = $tmp_path[count($tmp_path)-1];
            $filepath = preg_replace('/'.$filename."$/i","",$cache_file);
            // Verify cache
            if($caching_interval <1 || !file_exists($cache_file) || filemtime($cache_file) + $caching_interval*60 <= time() || filemtime($cache_file)<filemtime($path) ) {
                if(file_exists($cache_file)) FileHandler::removeFile($cache_file);
                // Read a target file and get content
                ob_start();
                @include($path);
                $content = ob_get_clean();
                // Replace relative path to the absolute path 
                $path_info = pathinfo($path);
                $this->path = str_replace('\\', '/', realpath($path_info['dirname'])).'/';
                $content = preg_replace_callback('/(src=|href=|url\()("|\')?([^"\'\)]+)("|\'\))?/is',array($this,'_replacePath'),$content);
                $content = preg_replace_callback('/(<load[^>]+target=)(")([^"]+)(")/is',array($this,'_replacePath'),$content);
                $content = preg_replace_callback('/(<!--%import\()(\")([^"]+)(\")/is',array($this,'_replacePath'),$content);

                FileHandler::writeFile($cache_file, $content);
                // Include and then Return the result
                if(!file_exists($cache_file)) return;
                // Attempt to compile
                $oTemplate = &TemplateHandler::getInstance();
                $script = $oTemplate->compileDirect($filepath, $filename);

                FileHandler::writeFile($cache_file, $script);
            }

            $__Context = &$GLOBALS['__Context__'];
            $__Context->tpl_path = $filepath;

            ob_start();
            @include($cache_file);
            $content = ob_get_clean();

            return $content;
        }

        function _replacePath($matches) {
            $val = trim($matches[3]);
            // Pass if the path is external or starts with /, #, { characters
			// /=absolute path, #=hash in a page, {=Template syntax
            if(preg_match('@^((?:http|https|ftp|telnet|mms)://|(?:mailto|javascript):|[/#{])@i',$val)) {
				return $matches[0];
            // In case of  .. , get a path
            } elseif(preg_match('/^\.\./i',$val)) {
				$p = Context::pathToUrl($this->path);
                return sprintf("%s%s%s%s",$matches[1],$matches[2],$p.$val,$matches[4]);
            }

            if(substr($val,0,2)=='./') $val = substr($val,2);
			$p = Context::pathToUrl($this->path);
            $path = sprintf("%s%s%s%s",$matches[1],$matches[2],$p.$val,$matches[4]);

			return $path;
        }

    }
?>
