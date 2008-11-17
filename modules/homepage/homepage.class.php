<?php
    /**
     * @class homepage 
     * @author zero (zero@nzeo.com)
     * @brief  homepage package
     **/

    class homepage extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('homepage', 'view', 'dispHomepageAdminContent');
            $oModuleController->insertActionForward('homepage', 'view', 'dispHomepageAdminSetup');
            $oModuleController->insertActionForward('homepage', 'view', 'dispHomepageAdminDelete');

            // 신규 홈페이지 추가
            $tmp_url = parse_url(Context::getRequestUri());
            $domain = sprintf('%s%s', $tmp_url['host'], $tmp_url['path']);
            $oHomepageAdminController = &getAdminController('homepage');
            $oHomepageAdminController->insertHomepage('homepage', $domain);

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oModuleModel = &getModel('module');

            if(!$oModuleModel->getActionForward('dispHomepageAdminContent')) return true;
            if(!$oModuleModel->getActionForward('dispHomepageAdminSetup')) return true;
            if(!$oModuleModel->getActionForward('dispHomepageAdminDelete')) return true;

            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');

            if(!$oModuleModel->getActionForward('dispHomepageAdminContent'))
                $oModuleController->insertActionForward('homepage', 'view', 'dispHomepageAdminContent');
            if(!$oModuleModel->getActionForward('dispHomepageAdminSetup'))
                $oModuleController->insertActionForward('homepage', 'view', 'dispHomepageAdminSetup');
            if(!$oModuleModel->getActionForward('dispHomepageAdminDelete'))
                $oModuleController->insertActionForward('homepage', 'view', 'dispHomepageAdminDelete');

            return new Object(0, 'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
        }
    }
?>
