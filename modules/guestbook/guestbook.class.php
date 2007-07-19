<?php
    /**
     * @class  guestbook
     * @author zero (zero@nzeo.com)
     * @brief  guestbook 모듈의 high class
     *
     * 모든 guestbook은 이 클래스의 상속을 받게 된다.
     * 이 클래스는 방명록 모듈의 설치 및 업그레이드를 담당하게 되고 공통 변수등을 선언하여 사용할 수 있다.
     **/

    class guestbook extends ModuleObject {

        var $skin = "default"; ///< 기본 스킨 이름
        var $list_count = 20; ///< 한 페이지에 나타날 글의 수
        var $page_count = 10; ///< 페이지의 수

        var $editor = 'default'; ///< 에디터 종류

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            /**
             * action forward에 등록.
             * 이렇게 등록을 한 것들만이 admin module에서 호출되어 사용될 수 있다.
             * action forward를 통하여 관리자 페이지의 구축이 가능하고 방명록 서비스 부분에서 바로 방명록 설정을 할 수 있게 된다.
             **/
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('guestbook', 'view', 'dispGuestbookAdminContent');
            $oModuleController->insertActionForward('guestbook', 'view', 'dispGuestbookAdminGuestbookInfo');
            $oModuleController->insertActionForward('guestbook', 'view', 'dispGuestbookAdminInsertGuestbook');
            $oModuleController->insertActionForward('guestbook', 'view', 'dispGuestbookAdminDeleteGuestbook');
            $oModuleController->insertActionForward('guestbook', 'view', 'dispGuestbookAdminSkinInfo');
            $oModuleController->insertActionForward('guestbook', 'view', 'dispGuestbookAdminGrantInfo');
            $oModuleController->insertActionForward('guestbook', 'controller', 'procGuestbookAdminUpdateSkinInfo');


            // Object 클래스의 객체는 기본적으로 성공(error=0)으로 되어 있고 이 값을 return함으로써 ModuleHandler등에서 오류 유무를 파악할 수 있다.
            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         * 설치시 필수 체크 부분이 있다면 검토하는 코드를 추가할 수 있다.
         **/
        function moduleIsInstalled() {
            return new Object();
        }

        /**
         * @brief 업데이트 실행
         * 설치시 이상이 있으면 이 moduleUpdate() 메쏘드를 이용하여 업데이트 구문을 실행할수 있다.
         **/
        function moduleUpdate() {
            return new Object();
        }

    }
?>
