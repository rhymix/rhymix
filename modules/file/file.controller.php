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
         * @brief 에디터에서 첨부파일 업로드 
         **/
        function procFileUpload() {
            // 기본적으로 필요한 변수 설정
            $upload_target_srl = Context::get('upload_target_srl');
            $module_srl = $this->module_srl;

            // 업로드 권한이 없거나 정보가 없을시 종료
            if(!$_SESSION['upload_enable'][$upload_target_srl]) exit();

            $file_info = Context::get('Filedata');

            // 정상적으로 업로드된 파일이 아니면 오류 출력
            if(!is_uploaded_file($file_info['tmp_name'])) return false;

            $output = $this->insertFile($file_info, $module_srl, $upload_target_srl);
        }


        /**
         * @brief 에디터에서 첨부 파일 삭제
         **/
        function procFileDelete() {
            // 기본적으로 필요한 변수인 upload_target_srl, module_srl을 설정
            $upload_target_srl = Context::get('upload_target_srl');
            $file_srl = Context::get('file_srl');

            // 업로드 권한이 없거나 정보가 없을시 종료
            if(!$_SESSION['upload_enable'][$upload_target_srl]) exit();

            if($file_srl) $output = $this->deleteFile($file_srl);

            // 첨부파일의 목록을 java script로 출력
            $this->printUploadedFileList($upload_target_srl);
        }

        /**
         * @brief 업로드 가능하다고 세팅
         **/
        function setUploadEnable($upload_target_srl) {
            $_SESSION['upload_enable'][$upload_target_srl] = true;
        }

        /**
         * @brief 첨부파일 추가
         **/
        function insertFile($file_info, $module_srl, $upload_target_srl, $download_count = 0, $manual_insert = false) {
            if(!$manual_insert) {
                // 첨부파일 설정 가져옴
                $logged_info = Context::get('logged_info');
                if($logged_info->is_admin != 'Y') {
                    $oFileModel = &getModel('file');
                    $config = $oFileModel->getFileConfig();
                    $allowed_attach_size = $config->allowed_attach_size * 1024 * 1024;

                    // 해당 문서에 첨부된 모든 파일의 용량을 가져옴 (DB에서 가져옴)
                    $size_args->upload_target_srl = $upload_target_srl;
                    $output = executeQuery('file.getAttachedFileSize', $size_args);
                    $attached_size = (int)$output->data->attached_size + filesize($file_info['tmp_name']);
                    if($attached_size > $allowed_attach_size) return new Object(-1, 'msg_exceeds_limit_size');
                }
            }

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
            if(!$manual_insert&&!move_uploaded_file($file_info['tmp_name'], $filename)) return false;
            elseif($manual_insert) @rename($file_info['tmp_name'], $filename);

            // 사용자 정보를 구함
            $oMemberModel = &getModel('member');
            $member_srl = $oMemberModel->getLoggedMemberSrl();

            // 파일 정보를 정리
            $args->file_srl = getNextSequence();
            $args->upload_target_srl = $upload_target_srl;
            $args->module_srl = $module_srl;
            $args->direct_download = $direct_download;
            $args->source_filename = $file_info['name'];
            $args->uploaded_filename = $filename;
            $args->download_count = $download_count;
            $args->file_size = filesize($filename);
            $args->comment = NULL;
            $args->member_srl = $member_srl;
            $args->sid = md5(rand(rand(1111111,4444444),rand(4444445,9999999)));

            $output = executeQuery('file.insertFile', $args);
            if(!$output->toBool()) return $output;

            $output->add('file_srl', $args->file_srl);
            $output->add('file_size', $args->file_size);
            $output->add('source_filename', $args->source_filename);
            return $output;
        }

        /**
         * @brief 특정 upload_target_srl의 첨부파일들의 상태를 유효로 변경
         * 글이 등록될때 글에 첨부된 파일들의 상태를 유효상태로 변경함으로서 관리시 불필요 파일로 인식되지 않도록 함
         **/
        function setFilesValid($upload_target_srl) {
            $args->upload_target_srl = $upload_target_srl;
            return executeQuery('file.updateFileValid', $args);
        }

        /**
         * @brief 첨부파일 삭제
         **/
        function deleteFile($file_srl) {
            // 파일 정보를 가져옴
            $args->file_srl = $file_srl;
            $output = executeQuery('file.getFile', $args);
            if(!$output->toBool()) return $output;
            $file_info = $output->data;
            if(!$file_info) return new Object(-1, 'file_not_founded');

            $source_filename = $output->data->source_filename;
            $uploaded_filename = $output->data->uploaded_filename;

            // DB에서 삭제
            $output = executeQuery('file.deleteFile', $args);
            if(!$output->toBool()) return $output;

            // 삭제 성공하면 파일 삭제
            unlink($uploaded_filename);

            return $output;
        }

        /**
         * @brief 특정 문서의 첨부파일을 모두 삭제
         **/
        function deleteFiles($upload_target_srl) {
            // 첨부파일 목록을 받음
            $oFileModel = &getModel('file');
            $file_list = $oFileModel->getFiles($upload_target_srl);

            // 첨부파일이 없으면 성공 return
            if(!is_array($file_list)||!count($file_list)) return new Object();

            // DB에서 삭제 
            $args->upload_target_srl = $upload_target_srl;
            $output = executeQuery('file.deleteFiles', $args);
            if(!$output->toBool()) return $output;

            // 실제 파일 삭제
            $file_count = count($file_list);
            for($i=0;$i<count($file_count);$i++) {
                $module_srl = $file_list[$i]->module_srl;
                $path[0] = sprintf("./files/attach/images/%s/%s/", $module_srl, $upload_target_srl);
                $path[1] = sprintf("./files/attach/binaries/%s/%s/", $module_srl, $upload_target_srl);

                FileHandler::removeDir($path[0]);
                FileHandler::removeDir($path[1]);
            }

            return $output;
        }

        /**
         * @brief 특정 글의 첨부파일을 다른 글로 이동
         **/
        function moveFile($source_srl, $target_module_srl, $target_srl) {
            if($source_srl == $target_srl) return;

            $oFileModel = &getModel('file');
            $file_list = $oFileModel->getFiles($source_srl);
            if(!$file_list) return;

            $file_count = count($file_list);

            for($i=0;$i<$file_count;$i++) {

                unset($file_info);
                $file_info = $file_list[$i];
                $old_file = $file_info->uploaded_filename;

                // 이미지인지 기타 파일인지 체크하여 이동할 위치 정함
                if(eregi("\.(jpg|jpeg|gif|png|wmv|mpg|mpeg|avi|swf|flv|mp3|asaf|wav|asx|midi)$", $file_info->source_filename)) {
                    $path = sprintf("./files/attach/images/%s/%s/", $target_module_srl,$target_srl);
                    $new_file = $path.$file_info->source_filename;
                } else {
                    $path = sprintf("./files/attach/binaries/%s/%s/", $target_module_srl, $target_srl);
                    $new_file = $path.md5(crypt(rand(1000000,900000), rand(0,100)));
                }

                // 디렉토리 생성
                FileHandler::makeDir($path);

                // 파일 이동
                @rename($old_file, $new_file);

                // DB 정보도 수정
                unset($args);
                $args->file_srl = $file_info->file_srl;
                $args->uploaded_filename = $new_file;
                $args->module_srl = $file_info->module_srl;
                $args->upload_target_srl = $target_srl;
                executeQuery('file.updateFile', $args);
            }
        }

        /**
         * @brief upload_target_srl을 키로 하는 첨부파일을 찾아서 java script 코드로 return
         **/
        function printUploadedFileList($upload_target_srl) {
            // file의 Model객체 생성
            $oFileModel = &getModel('file');

            // 첨부파일 목록을 구함
            $tmp_file_list = $oFileModel->getFiles($upload_target_srl);
            $file_count = count($tmp_file_list);

            $logged_info = Context::get('logged_info');
            if($logged_info->is_admin == 'Y') {
                $file_config->allowed_filesize = 1024;
                $file_config->allowed_attach_size = 1024;
                $file_config->allowed_filetypes = '*.*';
            } else {
                $file_config = $oFileModel->getFileConfig();
            }

            // 루프를 돌면서 $buff 변수에 java script 코드를 생성
            $buff = "";
            for($i=0;$i<$file_count;$i++) {
                $file_info = $tmp_file_list[$i];
                if(!$file_info->file_srl) continue;
                if($file_info->direct_download == 'Y') $file_info->uploaded_filename = sprintf('%s%s', Context::getRequestUri(), str_replace('./', '', $file_info->uploaded_filename));
                $file_list[] = $file_info;
                $attached_size += $file_info->file_size;
            }

            // 업로드 상태 표시 작성
            $upload_status = sprintf(
                    '<span class="title">%s : </span><span class="desc_attached">%s</span><span class="desc">/ %s</span><br /><span class="title">%s : </span><span class="desc">%s</span> (<span class="title">%s : </span><span class="desc">%s</span>)',
                    Context::getLang('allowed_attach_size'),
                    FileHandler::filesize($attached_size),
                    FileHandler::filesize($file_config->allowed_attach_size*1024*1024),
                    Context::getLang('allowed_filesize'),
                    FileHandler::filesize($file_config->allowed_filesize*1024*1024),
                    Context::getLang('allowed_filetypes'),
                    $file_config->allowed_filetypes
                );

            Context::set('upload_target_srl', $upload_target_srl); 
            Context::set('file_list', $file_list);
            Context::set('upload_status', $upload_status);

            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('print_uploaded_file_list');
        }

        /**
         * @brief 첨부파일 다운로드
         * 직접 요청을 받음
         * file_srl : 파일의 sequence
         * sid : db에 저장된 비교 값, 틀리면 다운로드 하지 낳음
         **/
        function procFileDownload() {
            $file_srl = Context::get('file_srl');
            $sid = Context::get('sid');

            // 파일의 정보를 DB에서 받아옴
            $oFileModel = &getModel('file');
            $file_obj = $oFileModel->getFile($file_srl);
            if($file_obj->file_srl!=$file_srl||$file_obj->sid!=$sid) exit();

            // 이상이 없으면 download_count 증가
            $args->file_srl = $file_srl;
            executeQuery('file.updateFileDownloadCount', $args);

            // 파일 출력
            $filename = $file_obj->source_filename;

            if(strstr($_SERVER['HTTP_USER_AGENT'], "MSIE")) {
                $filename = urlencode($filename);
                $filename = preg_replace('/\./', '%2e', $filename, substr_count($filename, '.') - 1);
            }

            $uploaded_filename = $file_obj->uploaded_filename;
            if(!file_exists($uploaded_filename)) exit();

            $fp = fopen($uploaded_filename, 'rb');
            if(!$fp) exit();

            header("Cache-Control: ");
            header("Pragma: ");
            header("Content-Type: application/octet-stream");
            header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

            header("Content-Length: " .(string)($file_obj->file_size));
            header('Content-Disposition: attachment; filename="'.$filename.'"');
            header("Content-Transfer-Encoding: binary\n");

            fpassthru($fp);
        }

    }
?>
