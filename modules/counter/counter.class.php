<?php
    /**
     * @class  counter
     * @author zero (zero@nzeo.com)
     * @brief  counter 모듈의 high class
     **/

    class counter extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('counter', 'view', 'dispCounterAdminIndex');

            $oCounterController = &getController('counter');

            // 00000000000000 일자로 기록될 전체 방문 기록 row 추가
            $oCounterController->insertTodayStatus('00000000000000');

            // 오늘자 row입력
            $oCounterController->insertTodayStatus();

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function moduleIsInstalled() {
            $oDB = &DB::getInstance();

            // 테이블 검사
            if(!$oDB->isTableExists('counter_log')) return new Object(-1,'fail');
            if(!$oDB->isTableExists('counter_status ')) return new Object(-1,'fail');

            return new Object();
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            return new Object();
        }

    }
?>
