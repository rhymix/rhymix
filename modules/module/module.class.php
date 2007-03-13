<?php
    /**
     * @class  module
     * @author zero (zero@nzeo.com)
     * @brief  module 모듈의 high class
     **/

    class module extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // module 모듈에서 사용할 디렉토리 생성
            $directory_list = array(
                    './files',
                    './files/cache',
                    './files/cache/module_info',
                );

            foreach($directory_list as $dir) {
                if(is_dir($dir)) continue;
                @mkdir($dir, 0707);
                @chmod($dir, 0707);
            }

            // 기본 모듈을 생성
            $oModule = &getController('module');
            $oModule->makeDefaultModule();
            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function moduleIsInstalled() {
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
