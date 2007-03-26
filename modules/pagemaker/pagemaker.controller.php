<?php
    /**
     * @class  pagemakerController
     * @author zero (zero@nzeo.com)
     * @brief  pagemaker 모듈의 controller class
     **/

    class pagemakerController extends pagemaker {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 첨부파일 업로드
         **/
        function procUploadFile() {
            // 기본적으로 필요한 변수 설정
            $upload_target_srl = Context::get('upload_target_srl');
            $module_srl = $this->module_srl;

            // file class의 controller 객체 생성
            $oFileController = &getController('file');
            $output = $oFileController->insertFile($module_srl, $upload_target_srl);

            // 첨부파일의 목록을 java script로 출력
            $oFileController->printUploadedFileList($upload_target_srl);
        }

        /**
         * @brief 첨부파일 삭제
         * 에디터에서 개별 파일 삭제시 사용
         **/
        function procDeleteFile() {
            // 기본적으로 필요한 변수인 upload_target_srl, module_srl을 설정
            $upload_target_srl = Context::get('upload_target_srl');
            $module_srl = $this->module_srl;
            $file_srl = Context::get('file_srl');

            // file class의 controller 객체 생성
            $oFileController = &getController('file');
            if($file_srl) $output = $oFileController->deleteFile($file_srl, $this->grant->manager);

            // 첨부파일의 목록을 java script로 출력
            $oFileController->printUploadedFileList($upload_target_srl);
        }


    }
?>
