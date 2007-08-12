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
            $args->module_srl = $module_srl;
            $output = executeQuery('file.deleteModuleFiles', $args);
            if(!$output->toBool()) return $output;

            // 실제 파일 삭제
            $path[0] = sprintf("./files/attach/images/%s/", $module_srl);
            $path[1] = sprintf("./files/attach/binaries/%s/", $module_srl);
            FileHandler::removeDir($path[0]);
            FileHandler::removeDir($path[1]);

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
            // 기본 정보를 받음
            $args = Context::gets('allowed_filesize','allowed_attach_size','allowed_filetypes');

            // module Controller 객체 생성하여 입력
            $oModuleController = &getController('module');
            $output = $oModuleController->insertModuleConfig('file',$args);
            return $output;
        }

    }
?>
