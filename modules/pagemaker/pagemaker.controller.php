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
            $this->initAdmin();
        }

        /**
         * @brief 문서 입력
         **/
        function procInsertDocument() {

            // 글작성시 필요한 변수를 세팅
            $obj = Context::getRequestVars();
            $obj->module_srl = $this->module_srl;

            // document module의 model 객체 생성
            $oDocumentModel = &getModel('document');

            // document module의 controller 객체 생성
            $oDocumentController = &getController('document');

            // 이미 존재하는 글인지 체크
            $document = $oDocumentModel->getDocument($obj->document_srl, $this->grant->manager);

            // 이미 존재하는 경우 수정
            if($document->document_srl == $obj->document_srl) {
                $output = $oDocumentController->updateDocument($document, $obj);
                $msg_code = 'success_updated';

            // 그렇지 않으면 신규 등록
            } else {
                $output = $oDocumentController->insertDocument($obj);
                $msg_code = 'success_registed';
                $obj->document_srl = $output->get('document_srl');
            }
            if(!$output->toBool()) return $output;

            // 트랙백 발송
            $trackback_url = Context::get('trackback_url');
            $trackback_charset = Context::get('trackback_charset');
            if($trackback_url) {
                $oTrackbackController = &getController('trackback');
                $oTrackbackController->sendTrackback($obj, $trackback_url, $trackback_charset);
            }

            $this->add('document_srl', $output->get('document_srl'));
            $this->add('page', $output->get('page'));
            $this->setMessage($msg_code);
        }

        /**
         * @brief 문서 삭제
         **/
        function procDeleteDocument() {
            // 문서 번호 확인
            $document_srl = Context::get('document_srl');
            if(!$document_srl) return $this->doError('msg_invalid_document');

            // document module model 객체 생성
            $oDocumentController = &getController('document');

            // 삭제 시도
            $output = $oDocumentController->deleteDocument($document_srl, true);
            if(!$output->toBool()) return $output;

            $this->add('page', $output->get('page'));
            $this->setMessage('success_deleted');
        }

        /**
         * @brief 첨부파일 업로드
         **/
        function procUploadFile() {
            // 업로드 권한이 없거나 정보가 없을시 종료
            if(!Context::isUploaded()) exit();

            // 기본적으로 필요한 변수인 document_srl, module_srl을 설정
            $document_srl = Context::get('document_srl');
            $module_srl = $this->module_srl;

            // file class의 controller 객체 생성
            $oFileController = &getController('file');
            $output = $oFileController->insertFile($module_srl, $document_srl);

            // 첨부파일의 목록을 java script로 출력
            $oFileController->printUploadedFileList($document_srl);
        }

        /**
         * @brief 첨부파일 삭제
         * 에디터에서 개별 파일 삭제시 사용
         **/
        function procDeleteFile() {
            // 기본적으로 필요한 변수인 document_srl, module_srl을 설정
            $document_srl = Context::get('document_srl');
            $module_srl = $this->module_srl;
            $file_srl = Context::get('file_srl');

            // file class의 controller 객체 생성
            $oFileController = &getController('file');
            if($file_srl) $output = $oFileController->deleteFile($file_srl, true);

            // 첨부파일의 목록을 java script로 출력
            $oFileController->printUploadedFileList($document_srl);
        }


    }
?>
