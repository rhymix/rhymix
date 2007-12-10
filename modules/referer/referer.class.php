<?php
    /**
     * @class  referer 
     * @author haneul (haneul0318@gmail.com)
     * @brief  referer module's class 
     **/

    class referer extends ModuleObject {

        /**
         * @brief Install referer module 
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('referer', 'view', 'dispRefererAdminIndex');
	
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
