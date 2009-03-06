<?php
    /**
     * @class  fileAdminController
     * @author zero (zero@nzeo.com)
     * @brief  file 모듈의 admin controller 클래스
     **/

    class fileAdminController extends file {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 특정 모두의 첨부파일 모두 삭제
         **/
        function deleteModuleFiles($module_srl) {
            // 전체 첨부파일 목록을 구함
            $args->module_srl = $module_srl;
            $output = executeQueryArray('file.getModuleFiles',$args);
            if(!$output) return $output;
            $files = $output->data;

            // DB에서 삭제
            $args->module_srl = $module_srl;
            $output = executeQuery('file.deleteModuleFiles', $args);
            if(!$output->toBool()) return $output;

            // 실제 파일 삭제 (일단 약속에 따라서 한번에 삭제)
            FileHandler::removeDir( sprintf("./files/attach/images/%s/", $module_srl) ) ;
            FileHandler::removeDir( sprintf("./files/attach/binaries/%s/", $module_srl) );

            // DB에서 구한 파일 목록을 삭제
            $path = array();
            $cnt = count($files);
            for($i=0;$i<$cnt;$i++) {
                $uploaded_filename = $files[$i]->uploaded_filename;
                FileHandler::removeFile($uploaded_filename);

                $path_info = pathinfo($uploaded_filename);
                if(!in_array($path_info['dirname'], $path)) $path[] = $path_info['dirname'];
            }

            // 해당 글의 첨부파일 디렉토리 삭제
            for($i=0;$i<count($path);$i++) FileHandler::removeBlankDir($path[$i]);

            return $output;
        }

        /**
         * @brief 관리자 페이지에서 선택된 파일들을 삭제
         **/
        function procFileAdminDeleteChecked() {
            // 선택된 글이 없으면 오류 표시
            $cart = Context::get('cart');
            if(!$cart) return $this->stop('msg_cart_is_null');
            $file_srl_list= explode('|@|', $cart);
            $file_count = count($file_srl_list);
            if(!$file_count) return $this->stop('msg_cart_is_null');

            $oFileController = &getController('file');

            // 글삭제
            for($i=0;$i<$file_count;$i++) {
                $file_srl = trim($file_srl_list[$i]);
                if(!$file_srl) continue;

                $oFileController->deleteFile($file_srl);
            }

            $this->setMessage( sprintf(Context::getLang('msg_checked_file_is_deleted'), $file_count) );
        }

        /**
         * @brief 파일 기본 정보의 추가
         **/
        function procFileAdminInsertConfig() {
            // 설정 정보를 받아옴 (module model 객체를 이용)
            $config->allowed_filesize = Context::get('allowed_filesize');
            $config->allowed_attach_size = Context::get('allowed_attach_size');
            $config->allowed_filetypes = Context::get('allowed_filetypes');
            $config->allow_outlink = Context::get('allow_outlink');
            $config->allow_outlink_format = Context::get('allow_outlink_format');
            $config->allow_outlink_site = Context::get('allow_outlink_site');

            // module Controller 객체 생성하여 입력
            $oModuleController = &getController('module');
            $output = $oModuleController->insertModuleConfig('file',$config);
            return $output;
        }

        /**
         * @brief 모듈별 파일 기본 정보의 추가
         **/
        function procFileAdminInsertModuleConfig() {
            // 필요한 변수를 받아옴
            $module_srl = Context::get('target_module_srl');

            // 여러개의 모듈 일괄 설정일 경우
            if(preg_match('/^([0-9,]+)$/',$module_srl)) $module_srl = explode(',',$module_srl);
            else $module_srl = array($module_srl);

            $download_grant = trim(Context::get('download_grant'));

            $file_config->allow_outlink = Context::get('allow_outlink');
            $file_config->allow_outlink_format = Context::get('allow_outlink_format');
            $file_config->allow_outlink_site = Context::get('allow_outlink_site');
            $file_config->allowed_filesize = Context::get('allowed_filesize');
            $file_config->allowed_attach_size = Context::get('allowed_attach_size');
            $file_config->allowed_filetypes = Context::get('allowed_filetypes');
            if($download_grant) $file_config->download_grant = explode('|@|',$download_grant);
            else $file_config->download_grant = array();

            $oModuleController = &getController('module');
            for($i=0;$i<count($module_srl);$i++) {
                $srl = trim($module_srl[$i]);
                if(!$srl) continue;
                $oModuleController->insertModulePartConfig('file',$srl,$file_config);
            }

            $this->setError(-1);
            $this->setMessage('success_updated');
        }
    }
?>
