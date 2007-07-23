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
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('file', 'view', 'dispFileAdminList');
            $oModuleController->insertActionForward('file', 'view', 'dispFileAdminConfig');
            $oModuleController->insertActionForward('file', 'controller', 'procFileUpload');
            $oModuleController->insertActionForward('file', 'controller', 'procFileDelete');
            $oModuleController->insertActionForward('file', 'controller', 'procFileDownload');
            
            // 첨부파일의 기본 설정 저장
            $config->allowed_filesize = '2';
            $config->allowed_attach_size = '2';
            $config->allowed_filetypes = '*.*';
            $oModuleController->insertModuleConfig('file', $config);

            // file 모듈에서 사용할 디렉토리 생성
            FileHandler::makeDir('./files/attach/images');
            FileHandler::makeDir('./files/attach/binaries');

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
    }
?>
