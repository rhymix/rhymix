<?php
    /**
     * @class  boardModel
     * @author zero (zero@nzeo.com)
     * @brief  board 모듈의 Model class
     **/

    class boardModel extends Module {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 문서와 댓글의 비밀번호를 확인
         **/
        function procVerificationPassword() {
            // 비밀번호와 문서 번호를 받음
            $password = md5(Context::get('password'));
            $document_srl = Context::get('document_srl');
            $comment_srl = Context::get('comment_srl');

            // comment_srl이 있을 경우 댓글이 대상
            if($comment_srl) {
                // 문서번호에 해당하는 글이 있는지 확인
                $oComment = getModule('comment');
                $data = $oComment->getComment($comment_srl);
                // comment_srl이 없으면 문서가 대상
            } else {
                // 문서번호에 해당하는 글이 있는지 확인
                $oDocument = getModule('document');
                $data = $oDocument->getDocument($document_srl);
            }

            // 글이 없을 경우 에러
            if(!$data) return $this->doError('msg_invalid_request');

            // 문서의 비밀번호와 입력한 비밀번호의 비교
            if($data->password != $password) return $this->doError('msg_invalid_password');

            // 해당 글에 대한 권한 부여
            if($comment_srl) $_SESSION['own_comment'][$comment_srl] = true;
            else $_SESSION['own_document'][$document_srl] = true;
        }

        /**
         * @brief document_srl을 키로 하는 첨부파일을 찾아서 java script 코드로 return
         **/
        function getUploadedFileList($document_srl) {
            // document의 Model객체 생성
            $oDocumentModel = getModule('document','model');

            // 첨부파일 목록을 구함
            $file_list = $oDocumentModel->getFiles($document_srl);
            $file_count = count($file_list);

            // 루프를 돌면서 $buff 변수에 java script 코드를 생성
            $buff = "";
            for($i=0;$i<$file_count;$i++) {
                $file_info = $file_list[$i];
                if(!$file_info->file_srl) continue;

                $buff .= sprintf("parent.editor_insert_uploaded_file(\"%d\", \"%d\",\"%s\", \"%d\", \"%s\", \"%s\", \"%s\");\n", $document_srl, $file_info->file_srl, $file_info->source_filename, $file_info->file_size, FileHandler::filesize($file_info->file_size), $file_info->direct_download=='Y'?$file_info->uploaded_filename:'', $file_info->sid);
            }

            $buff = sprintf("<script type=\"text/javascript\">\nparent.editor_upload_clear_list(\"%s\");\n%s</script>", $document_srl, $buff);
            return $buff;
        }
    }
?>
