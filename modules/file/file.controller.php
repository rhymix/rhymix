<?php
    /**
     * @class  fileController
     * @author zero (zero@nzeo.com)
     * @brief  file 모듈의 controller 클래스
     **/

    class fileController extends Module {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 첨부파일 추가
         **/
        function insertFile($module_srl, $document_srl) {
            $oDB = &DB::getInstance();

            $file_info = Context::get('file');

            // 정상적으로 업로드된 파일이 아니면 오류 출력
            if(!is_uploaded_file($file_info['tmp_name'])) return false;

            // 이미지인지 기타 파일인지 체크하여 upload path 지정
            if(eregi("\.(jpg|jpeg|gif|png|wmv|mpg|mpeg|avi|swf|flv|mp3|asaf|wav|asx|midi)$", $file_info['name'])) {
                $path = sprintf("./files/attach/images/%s/%s/", $module_srl,$document_srl);
                $filename = $path.$file_info['name'];
                $direct_download = 'Y';
            } else {
                $path = sprintf("./files/attach/binaries/%s/%s/", $module_srl, $document_srl);
                $filename = $path.md5(crypt(rand(1000000,900000), rand(0,100)));
                $direct_download = 'N';
            }

            // 디렉토리 생성
            if(!FileHandler::makeDir($path)) return false;

            // 파일 이동
            if(!move_uploaded_file($file_info['tmp_name'], $filename)) return false;

            // 사용자 정보를 구함
            $oMemberModel = getModel('member');
            $member_srl = $oMemberModel->getMemberSrl();

            // 파일 정보를 정리
            $args->file_srl = $oDB->getNextSequence();
            $args->document_srl = $document_srl;
            $args->module_srl = $module_srl;
            $args->direct_download = $direct_download;
            $args->source_filename = $file_info['name'];
            $args->uploaded_filename = $filename;
            $args->file_size = filesize($filename);
            $args->comment = NULL;
            $args->member_srl = $member_srl;
            $args->sid = md5($args->source_filename);

            $output = $oDB->executeQuery('document.insertFile', $args);
            if(!$output->toBool()) return $output;

            $output->add('file_srl', $args->file_srl);
            $output->add('file_size', $args->file_size);
            $output->add('source_filename', $args->source_filename);
            return $output;
        }

        /**
         * @brief 첨부파일 삭제
         **/
        function deleteFile($file_srl) {
            $oDB = &DB::getInstance();

            // 파일 정보를 가져옴
            $args->file_srl = $file_srl;
            $output = $oDB->executeQuery('document.getFile', $args);
            if(!$output->toBool()) return $output;
            $file_info = $output->data;
            if(!$file_info) return new Object(-1, 'file_not_founded');

            $source_filename = $output->data->source_filename;
            $uploaded_filename = $output->data->uploaded_filename;

            // DB에서 삭제
            $output = $oDB->executeQuery('document.deleteFile', $args);
            if(!$output->toBool()) return $output;

            // 삭제 성공하면 파일 삭제
            unlink($uploaded_filename);

            return $output;
        }

        /**
         * @brief 특정 문서의 첨부파일을 모두 삭제
         **/
        function deleteFiles($module_srl, $document_srl) {
            $oDB = &DB::getInstance();

            $args->document_srl = $document_srl;
            $output = $oDB->executeQuery('document.deleteFiles', $args);
            if(!$output->toBool()) return $output;

            // 실제 파일 삭제
            $path[0] = sprintf("./files/attach/images/%s/%s/", $module_srl, $document_srl);
            $path[1] = sprintf("./files/attach/binaries/%s/%s/", $module_srl, $document_srl);

            FileHandler::removeDir($path[0]);
            FileHandler::removeDir($path[1]);

            return $output;
        }

        /**
         * @brief 특정 모두의 첨부파일 모두 삭제
         **/
        function deleteModuleFiles($module_srl) {
            $oDB = &DB::getInstance();

            $args->module_srl = $module_srl;
            $output = $oDB->executeQuery('document.deleteModuleFiles', $args);
            if(!$output->toBool()) return $output;

            // 실제 파일 삭제
            $path[0] = sprintf("./files/attach/images/%s/", $module_srl);
            $path[1] = sprintf("./files/attach/binaries/%s/", $module_srl);
            FileHandler::removeDir($path[0]);
            FileHandler::removeDir($path[1]);

            return $output;
        }

    }
?>
