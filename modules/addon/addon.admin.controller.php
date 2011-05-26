<?php
    /**
     * @class  addonAdminController
     * @author NHN (developers@xpressengine.com)
     * @brief admin controller class of addon modules
     **/
    require_once(_XE_PATH_.'modules/addon/addon.controller.php');

    class addonAdminController extends addonController {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief Add active/inactive change
         **/
        function procAddonAdminToggleActivate() {
            $oAddonModel = &getAdminModel('addon');

            $site_module_info = Context::get('site_module_info');
            // batahom addon values
            $addon = Context::get('addon');
			$type = Context::get('type');
			if(!$type) $type = "pc";
            if($addon) {
                // If enabled Disables
                if($oAddonModel->isActivatedAddon($addon, $site_module_info->site_srl, $type)) $this->doDeactivate($addon, $site_module_info->site_srl, $type);
                // If it is disabled Activate
                else $this->doActivate($addon, $site_module_info->site_srl, $type);
            }

            $this->makeCacheFile($site_module_info->site_srl, $type);
        }

        /**
         * @brief Add the configuration information input
         **/
        function procAddonAdminSetupAddon() {
            $args = Context::getRequestVars();
            $addon_name = $args->addon_name;
            unset($args->module);
            unset($args->act);
            unset($args->addon_name);
            unset($args->body);

            $site_module_info = Context::get('site_module_info');

            $this->doSetup($addon_name, $args, $site_module_info->site_srl);

            $this->makeCacheFile($site_module_info->site_srl, "pc");
            $this->makeCacheFile($site_module_info->site_srl, "mobile");
        }



        /**
         * @brief Add-on
         * Adds Add to DB
         **/
        function doInsert($addon, $site_srl = 0) {
            $args->addon = $addon;
            $args->is_used = 'N';
            if(!$site_srl) return executeQuery('addon.insertAddon', $args);
            $args->site_srl = $site_srl;
            return executeQuery('addon.insertSiteAddon', $args);
        }

        /**
         * @brief Add-activated
         * addons add-ons to the table on the activation state sikyeojum
         **/
        function doActivate($addon, $site_srl = 0, $type = "pc") {
            $args->addon = $addon;
			if($type == "pc") $args->is_used = 'Y';
			else $args->is_used_m = "Y";
            if(!$site_srl) return executeQuery('addon.updateAddon', $args);
            $args->site_srl = $site_srl;
            return executeQuery('addon.updateSiteAddon', $args);
        }

        /**
         * @brief Disable Add-ons
         *
         * addons add a table to remove the name of the deactivation is sikige
         **/
        function doDeactivate($addon, $site_srl = 0, $type = "pc") {
            $args->addon = $addon;
			if($type == "pc") $args->is_used = 'N';
			else $args->is_used_m = 'N';
            if(!$site_srl) return executeQuery('addon.updateAddon', $args);
            $args->site_srl = $site_srl;
            return executeQuery('addon.updateSiteAddon', $args);
        }
    }
?>