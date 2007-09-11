<?php
    /**
     * @class  fileModel
     * @author zero (zero@nzeo.com)
     * @brief  file 모듈의 model 클래스
     **/

    class fileModel extends file {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 특정 문서에 속한 첨부파일의 개수를 return
         **/
        function getFilesCount($upload_target_srl) {
            $args->upload_target_srl = $upload_target_srl;
            $output = executeQuery('file.getFilesCount', $args);
            return (int)$output->data->count;
        }

        /**
         * @brief 다운로드 경로를 구함
         **/
        function getDownloadUrl($file_srl, $sid) {
            return getUrl('','module','file','act','procFileDownload','file_srl',$file_srl,'sid',$sid);
        }

        /**
         * @brief 파일 설정 정보를 구함
         **/
        function getFileConfig() {
            // 설정 정보를 받아옴 (module model 객체를 이용)
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('file');

            if(!$config->allowed_filesize) $config->allowed_filesize = '2';
            if(!$config->allowed_attach_size) $config->allowed_attach_size = '3';
            if(!$config->allowed_filetypes) $config->allowed_filetypes = '*.*';
            return $config;
        }

        /**
         * @brief 파일 정보를 구함
         **/
        function getFile($file_srl) {
            $args->file_srl = $file_srl;
            $output = executeQuery('file.getFile', $args);
            if(!$output->toBool()) return $output;

            $file = $output->data;
            $file->download_url = $this->getDownloadUrl($file->file_srl, $file->sid);

            return $file;
        }

        /**
         * @brief 특정 문서에 속한 파일을 모두 return
         **/
        function getFiles($upload_target_srl) {
            $args->upload_target_srl = $upload_target_srl;
            $args->sort_index = 'file_srl';
            $output = executeQuery('file.getFiles', $args);
            if(!$output->data) return;

            $file_list = $output->data;

            if($file_list && !is_array($file_list)) $file_list = array($file_list);

            $file_count = count($file_list);
            for($i=0;$i<$file_count;$i++) {
                $file = $file_list[$i];
                $file->download_url = $this->getDownloadUrl($file->file_srl, $file->sid);
                $file_list[$i] = $file;
            }

            return $file_list;
        }

        /**
         * @brief 첨부파일에 대한 설정을 return (관리자/비관리자 자동 구분)
         **/
        function getUploadConfig() {
            $logged_info = Context::get('logged_info');
            if($logged_info->is_admin == 'Y') {
                //$file_config->allowed_filesize = 1024;
                //$file_config->allowed_attach_size = 1024;
                $file_config->allowed_filesize = ini_get('upload_max_filesize');
                $file_config->allowed_attach_size = ini_get('upload_max_filesize');
                $file_config->allowed_filetypes = '*.*';
            } else {
                $file_config = $this->getFileConfig();
            }
            return $file_config;
        }

        /**
         * @brief 파일 업로드를 위한 관리자/비관리자에 따른 안내문구 return
         **/
        function getUploadStatus($attached_size = 0) {
            $file_config = $this->getUploadConfig();

            // 업로드 상태 표시 작성
            $upload_status = sprintf(
                    '%s : %s/ %s<br /> %s : %s (%s : %s)',
                    Context::getLang('allowed_attach_size'),
                    FileHandler::filesize($attached_size),
                    FileHandler::filesize($file_config->allowed_attach_size*1024*1024),
                    Context::getLang('allowed_filesize'),
                    FileHandler::filesize($file_config->allowed_filesize*1024*1024),
                    Context::getLang('allowed_filetypes'),
                    $file_config->allowed_filetypes
                );
            return $upload_status;
        }
    }
?>
