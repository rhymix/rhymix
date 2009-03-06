<?php
    /**
     * @class  opageAdminController
     * @author zero (zero@nzeo.com)
     * @brief  opage 모듈의 admin controller class
     **/

    class opageAdminController extends opage {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 외부페이지 추가
         **/
        function procOpageAdminInsert() {
            // module 모듈의 model/controller 객체 생성
            $oModuleController = &getController('module');
            $oModuleModel = &getModel('module');

            // 게시판 모듈의 정보 설정
            $args = Context::getRequestVars();
            $args->module = 'opage';
            $args->mid = $args->opage_name;
            unset($args->opage_name);

            // module_srl이 넘어오면 원 모듈이 있는지 확인
            if($args->module_srl) {
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);
                if($module_info->module_srl != $args->module_srl) unset($args->module_srl);
            }

            // module_srl의 값에 따라 insert/update
            if(!$args->module_srl) {
                $output = $oModuleController->insertModule($args);
                $msg_code = 'success_registed';
            } else {
                $output = $oModuleController->updateModule($args);
                $msg_code = 'success_updated';

                // 캐시 파일 삭제
                $cache_file = sprintf("./files/cache/opage/%d.cache.php", $module_info->module_srl);
                if(file_exists($cache_file)) FileHandler::removeFile($cache_file);
            }

            if(!$output->toBool()) return $output;

            // 등록 성공후 return될 메세지 정리
            $this->add("module_srl", $output->get('module_srl'));
            $this->add("opage", Context::get('opage'));
            $this->setMessage($msg_code);
        }

        /**
         * @brief 외부페이지 삭제
         **/
        function procOpageAdminDelete() {
            $module_srl = Context::get('module_srl');

            // 원본을 구해온다
            $oModuleController = &getController('module');
            $output = $oModuleController->deleteModule($module_srl);
            if(!$output->toBool()) return $output;

            $this->add('module','opage');
            $this->add('opage',Context::get('opage'));
            $this->setMessage('success_deleted');
        }

        /**
         * @brief 외부페이지 기본 정보의 추가
         **/
        function procOpageAdminInsertConfig() {
            // 기본 정보를 받음
            $args = Context::gets('test');

        }

    }
?>
