<?php
    /**
     * @class  poll
     * @author zero (zero@nzeo.com)
     * @brief  poll모듈의 high class
     **/

    class poll extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('poll', 'view', 'dispPollAdminList');
            $oModuleController->insertActionForward('poll', 'view', 'dispPollAdminConfig');

            // 기본 스킨 설정
            $oModuleController = &getController('module');
            $config->skin = 'default';
            $config->colorset = 'normal';
            $oModuleController->insertModuleConfig('poll', $config);

            return new Object();
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
            return new Object();
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
        }
    }
?>
