<?php
    /**
     * @class  addonController
     * @author NHN (developers@xpressengine.com)
     * @brief addon module's controller class
     **/


    class addonController extends addon {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief Main/Virtual-specific add-on file, the location of the cache Wanted
         **/
        function getCacheFilePath($type = "pc") {
            $site_module_info = Context::get('site_module_info');
            $site_srl = $site_module_info->site_srl;

            $addon_path = _XE_PATH_.'files/cache/addons/';

            $addon_file = $addon_path.$site_srl.$type.'.acivated_addons.cache.php';

            if($this->addon_file_called) return $addon_file;
            $this->addon_file_called = true;

            if(!is_dir($addon_path)) FileHandler::makeDir($addon_path);
            if(!file_exists($addon_file)) $this->makeCacheFile($site_srl, $type);
            return $addon_file;
        }


        /**
         * @brief Add-on mid settings
         **/
        function _getMidList($selected_addon, $site_srl = 0) {

            $oAddonAdminModel = &getAdminModel('addon');
            $addon_info = $oAddonAdminModel->getAddonInfoXml($selected_addon, $site_srl);
            return $addon_info->mid_list;
        }



        /**
         * @brief Add-on mid settings
         **/
        function _setAddMid($selected_addon,$mid, $site_srl=0) {
            // Wanted to add the requested information
            $mid_list = $this->_getMidList($selected_addon, $site_srl);

            $mid_list[] = $mid;
            $new_mid_list = array_unique($mid_list);
            $this->_setMid($selected_addon,$new_mid_list, $site_srl);
        }


        /**
         * @brief Add-on mid settings
         **/
        function _setDelMid($selected_addon,$mid,$site_srl=0) {
            // Wanted to add the requested information
            $mid_list = $this->_getMidList($selected_addon,$site_srl);

            $new_mid_list = array();
            if(is_array($mid_list)){
                for($i=0,$c=count($mid_list);$i<$c;$i++){
                    if($mid_list[$i] != $mid) $new_mid_list[] = $mid_list[$i];
                }
            }else{
                $new_mid_list[] = $mid;
            }


            $this->_setMid($selected_addon,$new_mid_list,$site_srl);
        }

        /**
         * @brief Add-on mid settings
         **/
        function _setMid($selected_addon,$mid_list,$site_srl=0) {
            $args->mid_list =  join('|@|',$mid_list);
            $this->doSetup($selected_addon, $args,$site_srl);
            $this->makeCacheFile($site_srl);
        }


        /**
         * @brief Add mid-on
         **/
        function procAddonSetupAddonAddMid() {
            $site_module_info = Context::get('site_module_info');

            $args = Context::getRequestVars();
            $addon_name = $args->addon_name;
            $mid = $args->mid;
            $this->_setAddMid($addon_name,$mid,$site_module_info->site_srl);
        }

        /**
         * @brief Add mid Delete
         **/
        function procAddonSetupAddonDelMid() {
            $site_module_info = Context::get('site_module_info');

            $args = Context::getRequestVars();
            $addon_name = $args->addon_name;
            $mid = $args->mid;

            $this->_setDelMid($addon_name,$mid,$site_module_info->site_srl);
        }

        /**
         * @brief Re-generate the cache file
         **/
        function makeCacheFile($site_srl = 0, $type = "pc", $gtype = 'site') {
            // Add-on module for use in creating the cache file
            $buff = "";
            $oAddonModel = &getAdminModel('addon');
            $addon_list = $oAddonModel->getInsertedAddons($site_srl, $gtype);
            foreach($addon_list as $addon => $val) {
                if($val->addon == "smartphone") continue;
				if(!is_dir(_XE_PATH_.'addons/'.$addon)) continue;
				if(($type == "pc" && $val->is_used != 'Y') || ($type == "mobile" && $val->is_used_m != 'Y') || ($gtype == 'global' && $val->is_fixed != 'Y')) continue;

                $extra_vars = unserialize($val->extra_vars);
                $mid_list = $extra_vars->mid_list;
                if(!is_array($mid_list)||!count($mid_list)) $mid_list = null;
                $mid_list = base64_encode(serialize($mid_list));

                if($val->extra_vars) {
                    unset($extra_vars);
                    $extra_vars = base64_encode($val->extra_vars);
                }

                $buff .= sprintf(' $_ml = unserialize(base64_decode("%s")); if(file_exists("%saddons/%s/%s.addon.php") && (!is_array($_ml) || in_array($_m, $_ml))) { unset($addon_info); $addon_info = unserialize(base64_decode("%s")); $addon_path = "%saddons/%s/"; @include("%saddons/%s/%s.addon.php"); }', $mid_list, _XE_PATH_, $addon, $addon, $extra_vars, _XE_PATH_, $addon, _XE_PATH_, $addon, $addon);
            }

            $buff = sprintf('<?php if(!defined("__ZBXE__")) exit(); $_m = Context::get(\'mid\'); %s ?>', $buff);

            $addon_path = _XE_PATH_.'files/cache/addons/';
            if(!is_dir($addon_path)) FileHandler::makeDir($addon_path);

            if($gtype == 'site') $addon_file = $addon_path.$site_srl.$type.'.acivated_addons.cache.php';
            else $addon_file = $addon_path.$type.'.acivated_addons.cache.php';

            FileHandler::writeFile($addon_file, $buff);
        }

        /**
         * @brief Add-On Set
         **/
        function doSetup($addon, $extra_vars,$site_srl=0, $gtype = 'site') {
            if(!is_array($extra_vars->mid_list))	unset($extra_vars->mid_list);

            $args->addon = $addon;
            $args->extra_vars = serialize($extra_vars);
            if($gtype == 'global') return executeQuery('addon.updateAddon', $args);
            $args->site_srl = $site_srl;
            return executeQuery('addon.updateSiteAddon', $args);
        }

        /**
         * @brief Remove add-on information in the virtual site
         **/
        function removeAddonConfig($site_srl) {
            $addon_path = _XE_PATH_.'files/cache/addons/';
            $addon_file = $addon_path.$site_srl.'.acivated_addons.cache.php';
            if(file_exists($addon_file)) FileHandler::removeFile($addon_file);

            $args->site_srl = $site_srl;
            executeQuery('addon.deleteSiteAddons', $args);


        }


    }
?>
