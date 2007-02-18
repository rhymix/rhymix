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
         * @brief document_srl을 키로 하는 첨부파일을 찾아서 java script 코드로 return
         **/
        function getUploadedFileList($document_srl) {
            // file의 Model객체 생성
            $oFileModel = getModel('file');

            // 첨부파일 목록을 구함
            $file_list = $oFileModel->getFiles($document_srl);
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
