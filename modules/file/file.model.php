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
         * @brief 특정 문서에 속한 첨부파일 목록을 return
         **/
        function getFileList() {

            $mid = Context::get("mid");
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleInfoByMid($mid);
            Context::set("module_srl",$config->module_srl);

            $editor_sequence = Context::get("editor_sequence");
            $upload_target_srl = $_SESSION['upload_info'][$editor_sequence]->upload_target_srl;
            if($upload_target_srl) {
                $tmp_files = $this->getFiles($upload_target_srl);
                $file_count = count($tmp_files);

                for($i=0;$i<$file_count;$i++) {
                    $file_info = $tmp_files[$i];
                    if(!$file_info->file_srl) continue;

                    $obj = null;
                    $obj->file_srl = $file_info->file_srl;
                    $obj->source_filename = $file_info->source_filename;
                    $obj->file_size = $file_info->file_size;
                    $obj->disp_file_size = FileHandler::filesize($file_info->file_size);
                    if($file_info->direct_download=='N') $obj->download_url = $this->getDownloadUrl($file_info->file_srl, $file_info->sid);
                    else $obj->download_url = str_replace('./', '', $file_info->uploaded_filename);
                    $obj->direct_download = $file_info->direct_download;
                    $files[] = $obj;
                    $attached_size += $file_info->file_size;
                }
            } else {
                $upload_target_srl = 0;
                $attached_size = 0;
                $files = array();
            }

            // 업로드 상태 표시 작성
            $upload_status = $this->getUploadStatus($attached_size);

            // 필요한 정보들 세팅
            $this->add("files",$files);
            $this->add("editor_sequence",$editor_sequence);
            $this->add("upload_target_srl",$upload_target_srl);
            $this->add("upload_status",$upload_status);
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
            return preg_replace('/^'.preg_quote(getUrl(),'/').'/','',getUrl('','module','file','act','procFileDownload','file_srl',$file_srl,'sid',$sid));
        }

        /**
         * @brief 파일 설정 정보를 구함
         **/
        function getFileConfig($module_srl = null) {
            // 설정 정보를 받아옴 (module model 객체를 이용)
            $oModuleModel = &getModel('module');

            $file_module_config = $oModuleModel->getModuleConfig('file');

            if($module_srl) $file_config = $oModuleModel->getModulePartConfig('file',$module_srl);
            if(!$file_config) $file_config = $file_module_config;

            if($file_config) {
                $config->allowed_filesize = $file_config->allowed_filesize;
                $config->allowed_attach_size = $file_config->allowed_attach_size;
                $config->allowed_filetypes = $file_config->allowed_filetypes;
                $config->download_grant = $file_config->download_grant;
                $config->allow_outlink = $file_config->allow_outlink;
            }

            // 전체 파일첨부 속성을 먼저 따른다
            if(!$config->allowed_filesize) $config->allowed_filesize = $file_module_config->allowed_filesize;
            if(!$config->allowed_attach_size) $config->allowed_attach_size = $file_module_config->allowed_attach_size;
            if(!$config->allowed_filetypes) $config->allowed_filetypes = $file_module_config->allowed_filetypes;
            if(!$config->allow_outlink) $config->allow_outlink = $file_module_config->allow_outlink;
            if(!$config->download_grant) $config->download_grant = $file_module_config->download_grant;

            // 그래도 없으면 default로 
            if(!$config->allowed_filesize) $config->allowed_filesize = '2';
            if(!$config->allowed_attach_size) $config->allowed_attach_size = '3';
            if(!$config->allowed_filetypes) $config->allowed_filetypes = '*.*';
            if(!$config->allow_outlink) $config->allow_outlink = 'Y';
            if(!$config->download_grant) $config->download_grant = array();

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
                $file->source_filename = stripslashes($file->source_filename);
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
                $file_config->allowed_filesize = preg_replace("/[a-z]/is","",ini_get('upload_max_filesize'));
                $file_config->allowed_attach_size = preg_replace("/[a-z]/is","",ini_get('upload_max_filesize'));
                $file_config->allowed_filetypes = '*.*';
            } else {
                $module_srl = Context::get('module_srl');
                $file_config = $this->getFileConfig($module_srl);
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
        
        /**
         * @brief 특정 모듈의 file 설정을 return
         **/
        function getFileModuleConfig($module_srl) {
            return $this->getFileConfig($module_srl);
        }
    }
?>
