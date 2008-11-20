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

            $config = $oModuleModel->getModuleConfig('planet');
            if($config->mid) {
                $_o = executeQuery('module.getMidInfo', $config);
                if(!$_o->data) unset($config);
            }

            if(!$config->mid) {
                $args->module = 'planet';
                $args->browser_title = 'planetXE';
                $args->skin = 'xe_planet';
                $args->is_default = 'N';
                $args->mid = 'planet';
                $idx = 0;
                while(true) {
                    $_o = executeQuery('module.getMidInfo', $args);
                    if(!$_o->data) break;
                    $idx = $idx + 1;
                }
                $args->module_srl = getNextSequence();
                $output = $oModuleController->insertModule($args);

                $planet_args->mid = $args->mid;
                $oPlanetController->insertPlanetConfig($planet_args);
            }


            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController->insertActionForward('planet', 'view', 'dispPlanetHome');
            $oModuleController->insertActionForward('planet', 'view', 'dispPlanetAdminSetup');
            $oModuleController->insertActionForward('planet', 'view', 'dispPlanetAdminList');
            $oModuleController->insertActionForward('planet', 'view', 'dispPlanetAdminSkinInfo');
            $oModuleController->insertActionForward('planet', 'view', 'dispPlanetAdminDelete');
            $oModuleController->insertActionForward('planet', 'view', 'dispPlanetAdminInsert');
            $oModuleController->insertActionForward('planet', 'view', 'favorite');
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            return new Object(0, 'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
        }
    }
?>
