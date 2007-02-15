<?php
    /**
    * @class AddOnHandler
    * @author zero (zero@nzeo.com)
    * @brief addon을 호출하여 실행
    * @todo 미구현
    **/

    class AddOnHandler extends Handler {

        var $addon_name; ///< 실행하고자 하는 addon의 이름

        /**
         * @brief 모듈에 등록된 애드온을 act값에 맞추어 정리
         **/
        function callAddOns(&$oModule, $oModuleInfo, $status) {
            if(!$oModuleInfo->isAddOnExists($status, ContextHandler::get('act'))) return;

            $addon_list = $oModuleInfo->getAddOnList($status, ContextHandler::get('act'));
            $addon_cnt = count($addon_list);
            if(!$addon_cnt) return;

            for($i=0; $i<$addon_cnt; $i++) {
                $addon_name = $addon_list[$i];

                $oAddOn = new AddOnHandler($addon_name);
                $oAddOn->proc($oModule, $oModuleInfo);
            }
        }

        /**
         * @brief constructor
         **/
        function AddOnHandler($addon_name) {
            $this->addon_name = $addon_name;
        }

        /**
         * @brief 애드온의 실행
         **/
        function proc(&$oModule, $oModuleInfo) {
            // 해당 모듈을 읽어서 객체를 만듬
            $addon_file = sprintf('./addons/%s/%s.addon.php', $this->addon_name, $this->addon_name);

            // 모듈 파일이 없으면 에러
            if(!file_exists($addon_file)) return;

            // 모듈 파일을 include
            require_once($addon_file);

            // 선택된 모듈의 instance는 eval string으로 처리
            $eval_str = sprintf('$oAddOn = new %s();', $this->addon_name);
            eval($eval_str); 

            // 애드온 실행
            $oAddOn->proc($oModule, $oModuleInfo);
        }

    }

?>
