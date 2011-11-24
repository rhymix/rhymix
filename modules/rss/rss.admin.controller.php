<?php
    /**
     * @class  rssAdminController
     * @author NHN (developers@xpressengine.com)
     * @brief The admin controller class of the rss module
     *
     * RSS 2.0 format document output
     *
     **/

    class rssAdminController extends rss {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief All RSS feeds configurations
         **/
        function procRssAdminInsertConfig() {
            $oModuleModel = &getModel('module');
            $total_config = $oModuleModel->getModuleConfig('rss');

            $config_vars = Context::getRequestVars();

            $config_vars->feed_document_count = (int)$config_vars->feed_document_count;

            if(!$config_vars->use_total_feed) $alt_message = 'msg_invalid_request';
            if(!in_array($config_vars->use_total_feed, array('Y','N'))) $config_vars->open_rss = 'Y';

            if($config_vars->image || $config_vars->del_image) {
                $image_obj = $config_vars->image;
                $config_vars->image = $total_config->image;
                // Get a variable for the delete request
                if($config_vars->del_image == 'Y' || $image_obj) {
                    FileHandler::removeFile($config_vars->image);
                    $config_vars->image = '';
                    $total_config->image = '';
                }
                // Ignore if the file is not the one which has been successfully uploaded
                if($image_obj['tmp_name'] && is_uploaded_file($image_obj['tmp_name'])) {
                    // Ignore if the file is not an image (swf is accepted ~)
                    $image_obj['name'] = Context::convertEncodingStr($image_obj['name']);

                    if(!preg_match("/\.(jpg|jpeg|gif|png)$/i", $image_obj['name'])) $alt_message = 'msg_rss_invalid_image_format';
                    else {
                        // Upload the file to a path
                        $path = './files/attach/images/rss/';
                        // Create a directory
                        if(!FileHandler::makeDir($path)) $alt_message = 'msg_error_occured';
                        else{
                            $filename = $path.$image_obj['name'];
                            // Move the file
                            if(!move_uploaded_file($image_obj['tmp_name'], $filename)) $alt_message = 'msg_error_occured';
                            else {
                                $config_vars->image = $filename;
                            }
                        }
                    }
                }
            }
            if(!$config_vars->image && $config_vars->del_image != 'Y') $config_vars->image = $total_config->image;

            $output = $this->setFeedConfig($config_vars);

            if(!$alt_message) $alt_message = 'success_updated';

            $alt_message = Context::getLang($alt_message);
            Context::set('msg', $alt_message);

            //$this->setLayoutPath('./common/tpl');
            //$this->setLayoutFile('default_layout.html');
            //$this->setTemplatePath($this->module_path.'tpl');
            //$this->setTemplateFile("top_refresh.html");
			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispRssAdminIndex');
				header('location:'.$returnUrl);
				return;
			}
        }


        /**
         * @brief RSS Module configurations
         **/
        function procRssAdminInsertModuleConfig() {
            // Get the object
            $module_srl = Context::get('target_module_srl');
            // In case of batch configuration of several modules
            if(preg_match('/^([0-9,]+)$/',$module_srl)) $module_srl = explode(',',$module_srl);
            else $module_srl = array($module_srl);
            if(!is_array($module_srl)) $module_srl[0] = $module_srl;

            $config_vars = Context::getRequestVars();

            $open_rss = $config_vars->open_rss;
            $open_total_feed = $config_vars->open_total_feed;
            $feed_description = trim($config_vars->feed_description);
            $feed_copyright = trim($config_vars->feed_copyright);

            if(!$module_srl || !$open_rss) return new Object(-1, 'msg_invalid_request');

            if(!in_array($open_rss, array('Y','H','N'))) $open_rss = 'N';
            // Save configurations
            for($i=0;$i<count($module_srl);$i++) {
                $srl = trim($module_srl[$i]);
                if(!$srl) continue;
                $output = $this->setRssModuleConfig($srl, $open_rss, $open_total_feed, $feed_description, $feed_copyright);
            }

            //$this->setError(0);
            $this->setMessage('success_updated', 'info');
			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispBoardAdminContent');
				header('location:'.$returnUrl);
				return;
			}
        }


        /**
         * @brief All Feeds with or without change
         **/
        function procRssAdminToggleActivate() {
            $oRssModel = &getModel('rss');
            // Get mid value
            $module_srl = Context::get('module_srl');
            if($module_srl) {
                $config = $oRssModel->getRssModuleConfig($module_srl);
                if($config->open_total_feed == 'T_N') {
                    $this->setRssModuleConfig($module_srl, $config->open_rss, 'T_Y', $config->feed_description, $config->feed_copyright);
                    $this->add("open_total_feed", 'T_Y');
                }
                else {
                    $this->setRssModuleConfig($module_srl, $config->open_rss, 'T_N', $config->feed_description, $config->feed_copyright);
                    $this->add("open_total_feed", 'T_N');
                }
            }

            $this->add("module_srl", $module_srl);
        }


        /**
         * @brief A funciton to configure all Feeds of the RSS module
         **/
        function setFeedConfig($config) {
            $oModuleController = &getController('module');
            $oModuleController->insertModuleConfig('rss',$config);
            return new Object();
        }


        /**
         * @brief A function t configure the RSS module
         **/
        function setRssModuleConfig($module_srl, $open_rss, $open_total_feed = 'N', $feed_description = 'N', $feed_copyright = 'N') {
            $oModuleController = &getController('module');
            $config->open_rss = $open_rss;
            $config->open_total_feed = $open_total_feed;
            if($feed_description != 'N') { $config->feed_description = $feed_description; }
            if($feed_copyright != 'N') { $config->feed_copyright = $feed_copyright; }
            $oModuleController->insertModulePartConfig('rss',$module_srl,$config);
            return new Object();
        }
    }
?>
