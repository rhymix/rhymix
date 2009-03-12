<?php
    /**
     * @class  planet
     * @author sol (sol@ngleader.com)
     * @brief  planet 모듈의 high class
     **/

    require_once(_XE_PATH_.'modules/planet/planet.item.php');
    require_once(_XE_PATH_.'modules/planet/planet.info.php');

    class planet extends ModuleObject {


        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            /**
             * planet 이라는 mid를 미리 입력해 놓음
             * 이 mid는 차후 수정 가능하고 planet 메인 페이지를 사용하기 위한 더미 형식의 mid로 사용됨.
             * 만약 이미 존재하는 경우를 대비해서 뒤에 숫자를 붙이도록 함.
             **/

            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');
            $oPlanetController = &getController('planet');

            $module_info = $oModuleModel->getModuleConfig('planet');
            if($module_info->mid) {
                $_o = executeQuery('module.getMidInfo', $module_info);
                if(!$_o->data) unset($module_info);
            }

            if(!$module_info->mid) {
                $args->module = 'planet';
                $args->browser_title = 'planetXE';
                $args->skin = 'xe_planet';
                $args->is_default = 'N';
                $args->mid = 'planet';
                $args->module_srl = getNextSequence();
                $output = $oModuleController->insertModule($args);

                $planet_args->mid = $args->mid;
                $oPlanetController->insertPlanetConfig($planet_args);
            }

            // 2009. 01. 29 아이디 클릭시 나타나는 팝업메뉴에 플래닛 보기 기능 추가
            $oModuleController->insertTrigger('member.getMemberMenu', 'planet', 'controller', 'triggerMemberMenu', 'after');
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oModuleModel = &getModel('module');

            // 2009. 01. 29 아이디 클릭시 나타나는 팝업메뉴에 플래닛 보기 기능 추가
            if(!$oModuleModel->getTrigger('member.getMemberMenu', 'planet', 'controller', 'triggerMemberMenu', 'after')) return true;

            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');

            // 2009. 01. 29 아이디 클릭시 나타나는 팝업메뉴에 플래닛 보기 기능 추가
            if(!$oModuleModel->getTrigger('member.getMemberMenu', 'planet', 'controller', 'triggerMemberMenu', 'after')) 
                $oModuleController->insertTrigger('member.getMemberMenu', 'planet', 'controller', 'triggerMemberMenu', 'after');

            return new Object(0, 'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
        }
    }
?>
