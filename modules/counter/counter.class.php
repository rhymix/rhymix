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
            $oCounterController = &getController('counter');

            // 0 일자로 기록될 전체 방문 기록 row 추가
            //$oCounterController->insertTotalStatus();

            // 오늘자 row입력
            //$oCounterController->insertTodayStatus();

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            // 카운터에 site_srl추가
            $oDB = &DB::getInstance();
            if(!$oDB->isColumnExists('counter_log', 'site_srl')) return true;
            if(!$oDB->isIndexExists('counter_log','idx_site_counter_log')) return true;
            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            // 카운터에 site_srl추가
            $oDB = &DB::getInstance();
            if(!$oDB->isColumnExists('counter_log', 'site_srl')) 
                $oDB->addColumn('counter_log','site_srl','number',11,0,true);
            if(!$oDB->isIndexExists('counter_log','idx_site_counter_log')) 
                $oDB->addIndex('counter_log','idx_site_counter_log',array('site_srl','ipaddress'),false);

            return new Object(0, 'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
        }
    }
?>
