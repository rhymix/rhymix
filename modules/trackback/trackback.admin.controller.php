<?php
    /**
     * @class  trackbackAdminController
     * @author NHN (developers@xpressengine.com)
     * @brief trackback module admin controller class
     **/

    class trackbackAdminController extends trackback {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief Trackbacks delete selected in admin page
         **/
        function procTrackbackAdminDeleteChecked() {
            // An error appears if no document is selected
            $cart = Context::get('cart');
			if(!is_array($cart)) $trackback_srl_list= explode('|@|', $cart);
			else $trackback_srl_list = $cart;

            $trackback_count = count($trackback_srl_list);
            if(!$trackback_count) return $this->stop('msg_cart_is_null');

            $oTrackbackController = &getController('trackback');
            // Delete the post
            for($i=0;$i<$trackback_count;$i++) {
                $trackback_srl = trim($trackback_srl_list[$i]);
                if(!$trackback_srl) continue;

                $oTrackbackController->deleteTrackback($trackback_srl, true);
            }

            $this->setMessage( sprintf(Context::getLang('msg_checked_trackback_is_deleted'), $trackback_count) );
			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispTrackbackAdminList');
				header('location:'.$returnUrl);
				return;
			}
        }

        /**
         * @brief Save Settings
         **/
        function procTrackbackAdminInsertConfig() {
            $config->enable_trackback = Context::get('enable_trackback');
            if($config->enable_trackback != 'Y') $config->enable_trackback = 'N';

            $oModuleController = &getController('module');
            $output = $oModuleController->insertModuleConfig('trackback',$config);
			if($output->toBool() && !in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispTrackbackAdminList');
				header('location:'.$returnUrl);
				return;
			}
            return $output;
        }

        /**
         * @brief Trackback Module Settings
         **/
        function procTrackbackAdminInsertModuleConfig() {
            // Get variables
            $module_srl = Context::get('target_module_srl');
            if(preg_match('/^([0-9,]+)$/',$module_srl)) $module_srl = explode(',',$module_srl);
            else $module_srl = array($module_srl);

            $enable_trackback = Context::get('enable_trackback');
            if(!in_array($enable_trackback, array('Y','N'))) $enable_trackback = 'N';
            
            if(!$module_srl || !$enable_trackback) return new Object(-1, 'msg_invalid_request');

            for($i=0;$i<count($module_srl);$i++) {
                $srl = trim($module_srl[$i]);
                if(!$srl) continue;
                $output = $this->setTrackbackModuleConfig($srl, $enable_trackback);
            }

            $this->setError(-1);
            $this->setMessage('success_updated', 'info');
			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispBoardAdminContent');
				header('location:'.$returnUrl);
				return;
			}
        }

        /**
         * @brief Trackback Module Settings
         **/
        function procTrackbackAdminAddCart()
		{
			$trackback_srl = (int)Context::get('trackback_srl');

			$oTrackbackAdminModel = &getAdminModel('trackback');
			//$columnList = array('trackback_srl');
			$args->trackbackSrlList = array($trackback_srl);

			$output = $oTrackbackAdminModel->getTotalTrackbackList($args);

			if(is_array($output->data))
			{
				foreach($output->data AS $key=>$value)
				{
					if($_SESSION['trackback_management'][$value->trackback_srl]) unset($_SESSION['trackback_management'][$value->trackback_srl]);
					else $_SESSION['trackback_management'][$value->trackback_srl] = true;
				}
			}
        }

        /**
         * @brief Trackback modular set function
         **/
        function setTrackbackModuleConfig($module_srl, $enable_trackback) {
            $config->enable_trackback = $enable_trackback;

            $oModuleController = &getController('module');
            $oModuleController->insertModulePartConfig('trackback', $module_srl, $config);
            return new Object();
        }

        /**
         * @brief Modules belonging to remove all trackbacks
         **/
        function deleteModuleTrackbacks($module_srl) {
            // Delete
            $args->module_srl = $module_srl;
            $output = executeQuery('trackback.deleteModuleTrackbacks', $args);

            return $output;
        }
    }
?>
