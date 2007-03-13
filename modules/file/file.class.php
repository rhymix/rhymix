<?php
    /**
     * @class  file
     * @author zero (zero@nzeo.com)
     * @brief  file 모듈의 high 클래스
     **/

    class file extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // file 모듈에서 사용할 디렉토리 생성
            $directory_list = array(
                    './files',
                    './files/attach',
                    './files/attach/images',
                    './files/attach/binaries',
                );

            foreach($directory_list as $dir) {
                if(is_dir($dir)) continue;
                @mkdir($dir, 0707);
                @chmod($dir, 0707);
            }
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
