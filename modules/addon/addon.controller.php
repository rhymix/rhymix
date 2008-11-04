<?php
    /**
     * @class  addonController
     * @author sol ngleader.com
     * @brief  addon 모듈의 controller class
     **/


    class addonController extends addon {

        /**
         * @brief 초기화
         **/
        function init() {
        }


        /**
         * @brief 애드온 mid 추가 설정 
         **/
        function _getMidList($selected_addon) {

            $oAddonAdminModel = &getAdminModel('addon');
            $addon_info = $oAddonAdminModel->getAddonInfoXml($selected_addon);
            return $addon_info->mid_list;
        }



        /**
         * @brief 애드온 mid 추가 설정 
         **/
        function _setAddMid($selected_addon,$mid) {

            // 요청된 애드온의 정보를 구함
            $mid_list = $this->_getMidList($selected_addon);

            $mid_list[] = $mid;
            $new_mid_list = array_unique($mid_list);
            $this->_setMid($selected_addon,$new_mid_list);
        }


        /**
         * @brief 애드온 mid 추가 설정 
         **/
        function _setDelMid($selected_addon,$mid) {

            // 요청된 애드온의 정보를 구함
            $mid_list = $this->_getMidList($selected_addon);

            $new_mid_list = array();
            if(is_array($mid_list)){
                for($i=0,$c=count($mid_list);$i<$c;$i++){
                    if($mid_list[$i] != $mid) $new_mid_list[] = $mid_list[$i];
                }
            }else{
                $new_mid_list[] = $mid;
            }


            $this->_setMid($selected_addon,$new_mid_list);
        }

        /**
         * @brief 애드온 mid 추가 설정 
         **/
        function _setMid($selected_addon,$mid_list) {
            $args->mid_list =  join('|@|',$mid_list);
            $this->doSetup($selected_addon, $args);
            $this->makeCacheFile();
        }


        /**
         * @brief 애드온 mid 추가
         **/
        function procAddonSetupAddonAddMid() {

            $args = Context::getRequestVars();
            $addon_name = $args->addon_name;
            $mid = $args->mid;
            $this->_setAddMid($addon_name,$mid);
        }


        /**
         * @brief 애드온 mid 삭제
         **/
        function procAddonSetupAddonDelMid() {

            $args = Context::getRequestVars();
            $addon_name = $args->addon_name;
            $mid = $args->mid;

            $this->_setDelMid($addon_name,$mid);
        }




        /**
         * @brief 캐시 파일 생성
         **/
        function makeCacheFile() {
            // 모듈에서 애드온을 사용하기 위한 캐시 파일 생성
            $buff = "";
            $oAddonModel = &getAdminModel('addon');
            $addon_list = $oAddonModel->getInsertedAddons();
            foreach($addon_list as $addon => $val) {
                if($val->is_used != 'Y' || !is_dir(_XE_PATH_.'addons/'.$addon) ) continue;

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

            FileHandler::writeFile($this->cache_file, $buff);
        }



        /**
         * @brief 애드온 설정
         **/
        function doSetup($addon, $extra_vars) {
            if($extra_vars->mid_list) $extra_vars->mid_list = explode('|@|', $extra_vars->mid_list);
            $args->addon = $addon;
            $args->extra_vars = serialize($extra_vars);
            return executeQuery('addon.updateAddon', $args);
        }


    }
?>
