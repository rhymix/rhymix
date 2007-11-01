<?php
    /**
     * @class  rss
     * @author zero (zero@nzeo.com)
     * @brief  rss module의 view class
     **/

    class rss extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 
            $oModuleController = &getController('module');

            $oModuleController->insertActionForward('rss', 'view', 'rss');

            // 2007. 10. 18 서비스형 모듈의 추가 설정에 참여하기 위한 trigger 추가
            $oModuleController->insertTrigger('module.dispAdditionSetup', 'rss', 'view', 'triggerDispRssAdditionSetup', 'before');

            // 2007. 10. 19 출력하기 전에 rss url을 세팅하는 트리거 호출
            $oModuleController->insertTrigger('display', 'rss', 'controller', 'triggerRssUrlInsert', 'before');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oModuleModel = &getModel('module');

            // 2007. 10. 18 서비스형 모듈의 추가 설정에 참여하기 위한 trigger 추가
            if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'rss', 'view', 'triggerDispRssAdditionSetup', 'before')) return true;

            // 2007. 10. 19 출력하기 전에 rss url을 세팅하는 트리거 호출
            if(!$oModuleModel->getTrigger('display', 'rss', 'controller', 'triggerRssUrlInsert', 'before')) return true;

            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');

            // 2007. 10. 18 서비스형 모듈의 추가 설정에 참여하기 위한 trigger 추가
            if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'rss', 'view', 'triggerDispRssAdditionSetup', 'before')) 
                $oModuleController->insertTrigger('module.dispAdditionSetup', 'rss', 'view', 'triggerDispRssAdditionSetup', 'before');

            // 2007. 10. 19 출력하기 전에 rss url을 세팅하는 트리거 호출
            if(!$oModuleModel->getTrigger('display', 'rss', 'controller', 'triggerRssUrlInsert', 'before')) 
                $oModuleController->insertTrigger('display', 'rss', 'controller', 'triggerRssUrlInsert', 'before');

            return new Object(0, 'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
        }
    }
?>
