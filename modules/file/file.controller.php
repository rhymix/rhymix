<?php
    /**
     * @class  fileController
     * @author zero (zero@nzeo.com)
     * @brief  file 모듈의 controller 클래스
     **/

    class fileController extends file {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 첨부파일 추가
         **/
        function insertFile($module_srl, $upload_target_srl) {
            $file_info = Context::get('file');

            // 정상적으로 업로드된 파일이 아니면 오류 출력
            if(!is_uploaded_file($file_info['tmp_name'])) return false;

            // 이미지인지 기타 파일인지 체크하여 upload path 지정
            if(eregi("\.(jpg|jpeg|gif|png|wmv|mpg|mpeg|avi|swf|flv|mp3|asaf|wav|asx|midi)$", $file_info['name'])) {
                $path = sprintf("./files/attach/images/%s/%s/", $module_srl,$upload_target_srl);
                $filename = $path.$file_info['name'];
                $direct_download = 'Y';
            } else {
                $path = sprintf("./files/attach/binaries/%s/%s/", $module_srl, $upload_target_srl);
                $filename = $path.md5(crypt(rand(1000000,900000), rand(0,100)));
                $direct_download = 'N';
            }

            // 디렉토리 생성
            if(!FileHandler::makeDir($path)) return false;

            // 파일 이동
            if(!move_uploaded_file($file_info['tmp_name'], $filename)) return false;

            // 사용자 정보를 구함
            $oMemberModel = &getModel('member');
            $member_srl = $oMemberModel->getLoggedMemberSrl();

            // DB 객체 생성
            $oDB = &DB::getInstance();

            // 파일 정보를 정리
            $args->file_srl = $oDB->getNextSequence();
            $args->upload_target_srl = $upload_target_srl;
            $args->module_srl = $module_srl;
            $args->direct_download = $direct_download;
            $args->source_filename = $file_info['name'];
            $args->uploaded_filename = $filename;
            $args->file_size = filesize($filename);
            $args->comment = NULL;
            $args->member_srl = $member_srl;
            $args->sid = md5(rand(rand(1111111,4444444),rand(4444445,9999999)));

            $output = $oDB->executeQuery('file.insertFile', $args);
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
            $output = $oDB->executeQuery('file.getFile', $args);
            if(!$output->toBool()) return $output;
            $file_info = $output->data;
            if(!$file_info) return new Object(-1, 'file_not_founded');

            $source_filename = $output->data->source_filename;
            $uploaded_filename = $output->data->uploaded_filename;

            // DB에서 삭제
            $output = $oDB->executeQuery('file.deleteFile', $args);
            if(!$output->toBool()) return $output;

            // 삭제 성공하면 파일 삭제
            unlink($uploaded_filename);

            return $output;
        }

        /**
         * @brief 특정 문서의 첨부파일을 모두 삭제
         **/
        function deleteFiles($module_srl, $upload_target_srl) {
            $oDB = &DB::getInstance();

            $args->upload_target_srl = $upload_target_srl;
            $output = $oDB->executeQuery('file.deleteFiles', $args);
            if(!$output->toBool()) return $output;

            // 실제 파일 삭제
            $path[0] = sprintf("./files/attach/images/%s/%s/", $module_srl, $upload_target_srl);
            $path[1] = sprintf("./files/attach/binaries/%s/%s/", $module_srl, $upload_target_srl);

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
            $output = $oDB->executeQuery('file.deleteModuleFiles', $args);
            if(!$output->toBool()) return $output;

            // 실제 파일 삭제
            $path[0] = sprintf("./files/attach/images/%s/", $module_srl);
            $path[1] = sprintf("./files/attach/binaries/%s/", $module_srl);
            FileHandler::removeDir($path[0]);
            FileHandler::removeDir($path[1]);

            return $output;
        }

        /**
         * @brief upload_target_srl을 키로 하는 첨부파일을 찾아서 java script 코드로 return
         **/
        function printUploadedFileList($upload_target_srl) {
            // file의 Model객체 생성
            $oFileModel = &getModel('file');

            // 첨부파일 목록을 구함
            $file_list = $oFileModel->getFiles($upload_target_srl);
            $file_count = count($file_list);

            // 루프를 돌면서 $buff 변수에 java script 코드를 생성
            $buff = "";
            for($i=0;$i<$file_count;$i++) {
                $file_info = $file_list[$i];
                if(!$file_info->file_srl) continue;

                $buff .= sprintf("parent.editor_insert_uploaded_file(\"%d\", \"%d\",\"%s\", \"%d\", \"%s\", \"%s\", \"%s\");\n", $upload_target_srl, $file_info->file_srl, $file_info->source_filename, $file_info->file_size, FileHandler::filesize($file_info->file_size), $file_info->direct_download=='Y'?$file_info->uploaded_filename:'', $file_info->sid);
            }

            header("Content-Type: text/html; charset=UTF-8");
            header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
            header("Cache-Control: no-store, no-cache, must-revalidate");
            header("Cache-Control: post-check=0, pre-check=0", false);
            header("Pragma: no-cache");

            $buff = sprintf("<script type=\"text/javascript\">\nparent.editor_upload_clear_list(\"%s\");\n%s</script>", $upload_target_srl, $buff);
            print $buff;
            exit();
        }

        /**
         * @brief 관리자 페이지에서 선택된 파일들을 삭제
         **/
        function procDeleteChecked() {
            // 선택된 글이 없으면 오류 표시
            $cart = Context::get('cart');
            if(!$cart) return $this->stop('msg_cart_is_null');
            $file_srl_list= explode('|@|', $cart);
            $file_count = count($file_srl_list);
            if(!$file_count) return $this->stop('msg_cart_is_null');

            // 글삭제
            for($i=0;$i<$file_count;$i++) {
                $file_srl = trim($file_srl_list[$i]);
                if(!$file_srl) continue;

                $this->deleteFile($file_srl, true);
            }

            $this->setMessage( sprintf(Context::getLang('msg_checked_file_is_deleted'), $file_count) );
        }
    }
?>
