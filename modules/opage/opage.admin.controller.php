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
            // module 모듈의 controller 객체 생성
            $oModuleController = &getController('module');

            // 기본 모듈 정보로 등록된 변수 구함
            $module_args = Context::gets('module_srl','module_category_srl','browser_title','is_default','layout_srl');
            $module_args->module = 'opage';
            $module_args->mid = Context::get('opage_name');
            if($module_args->is_default!='Y') $module_args->is_default = 'N';

            // 외부 문서 위치, 캐싱 시간은 extra_vars에 저장
            $config_args = Context::gets('path','caching_interval');
            $module_args->extra_vars = serialize($config_args);

            // module_srl이 넘어오면 원 모듈이 있는지 확인
            if($module_args->module_srl) {
                $oModuleModel = &getModel('module');
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_args->module_srl);
            }

            // is_default=='Y' 이면 기본 모듈을 제거
            if($module_args->is_default=='Y') $oModuleController->clearDefaultModule();

            // module_srl의 값에 따라 insert/update
            if($module_info->module_srl != $module_args->module_srl) {
                $output = $oModuleController->insertModule($module_args);
                $msg_code = 'success_registed';
                $module_info->module_srl = $output->get('module_srl');
            } else {
                $output = $oModuleController->updateModule($module_args);
                $msg_code = 'success_updated';
            }

            // 등록 실패시 에러 반환
            if(!$output->toBool()) return $output;

            /**
             * 권한 저장
             **/
            // 현 모듈의 권한 목록을 가져옴
            $grant_list = $this->xml_info->grant;

            if(count($grant_list)) {
                foreach($grant_list as $key => $val) {
                    $group_srls = Context::get($key);
                    if($group_srls) $arr_grant[$key] = explode('|@|',$group_srls);
                }
                $grants = serialize($arr_grant);
            }

            $oModuleController = &getController('module');
            $oModuleController->updateModuleGrant($module_info->module_srl, $grants);

            // 캐시 파일 삭제
            $cache_file = sprintf("./files/cache/opage/%d.cache.php", $module_info->module_srl);
            if(file_exists($cache_file)) FileHandler::removeFile($cache_file);

            // 등록 성공후 return될 메세지 정리
            $this->add("module_srl", $module_args->module_srl);
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
