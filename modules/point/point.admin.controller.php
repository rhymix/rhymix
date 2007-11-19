<?php
    /**
     * @class  pointAdminController
     * @author zero (zero@nzeo.com)
     * @brief  point모듈의 admin controller class
     **/

    class pointAdminController extends point {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 기본 설정 저장
         **/
        function procPointAdminInsertConfig() {
            // 설정 정보 가져오기
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');

            // 변수 정리
            $args = Context::getRequestVars();

            $config->point_name = $args->point_name;
            if(!$config->point_name) $config->point_name = 'point';

            $config->signup_point = (int)$args->signup_point;
            $config->login_point = (int)$args->login_point;
            $config->insert_document = (int)$args->insert_document;
            $config->insert_comment = (int)$args->insert_comment;
            $config->upload_file = (int)$args->upload_file;
            $config->download_file = (int)$args->download_file;

            $config->max_level = $args->max_level;
            if($config->max_level>1000) $config->max_level = 1000;
            if($config->max_level<1) $config->max_level = 1;

            $config->level_icon = $args->level_icon;
            if($args->disable_download == 'Y') $config->disable_download = 'Y';
            else $config->disable_download = 'N';

            unset($config->level_step);
            for($i=1;$i<=$config->max_level;$i++) {
                $key = "level_step_".$i;
                $config->level_step[$i] = (int)$args->{$key};
            }

            $config->expression = $args->expression;

            // 저장
            $oModuleController = &getController('module');
            $oModuleController->insertModuleConfig('point', $config);

            $this->cacheActList();

            $this->setMessage('success_updated');
        }

        /**
         * @brief 모듈별 설정 저장
         **/
        function procPointAdminInsertModuleConfig() {
            // 설정 정보 가져오기
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');

            // 변수 정리
            $args = Context::getRequestVars();

            foreach($args as $key => $val) {
                preg_match("/^(insert_document|insert_comment|upload_file|download_file)_([0-9]+)$/", $key, $matches);
                if(!$matches[1]) continue;
                $name = $matches[1];
                $module_srl = $matches[2];
                if(strlen($val)==0) unset($config->module_point[$module_srl][$name]);
                else $config->module_point[$module_srl][$name] = (int)$val;
            }

            // 저장
            $oModuleController = &getController('module');
            $oModuleController->insertModuleConfig('point', $config);

            $this->cacheActList();

            $this->setMessage('success_updated');
        }

        /**
         * @brief 모듈별 개별 포인트 저장
         **/
        function procPointAdminInsertPointModuleConfig() {
            $module_srl = Context::get('target_module_srl');
            if(!$module_srl) return new Object(-1, 'msg_invalid_request');

            // 설정 정보 가져오기
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');

            $config->module_point[$module_srl]['insert_document'] = (int)Context::get('insert_document');
            $config->module_point[$module_srl]['insert_comment'] = (int)Context::get('insert_comment');
            $config->module_point[$module_srl]['upload_file'] = (int)Context::get('upload_file');
            $config->module_point[$module_srl]['download_file'] = (int)Context::get('download_file');

            $oModuleController = &getController('module');
            $oModuleController->insertModuleConfig('point', $config);

            return new Object(0, 'success_registed');
        }

        /**
         * @brief 기능별 act 저장
         **/
        function procPointAdminInsertActConfig() {
            // 설정 정보 가져오기
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');

            // 변수 정리
            $args = Context::getRequestVars();

            $config->insert_document_act = $args->insert_document_act;
            $config->delete_document_act = $args->delete_document_act;
            $config->insert_comment_act = $args->insert_comment_act;
            $config->delete_comment_act = $args->delete_comment_act;
            $config->upload_file_act = $args->upload_file_act;
            $config->delete_file_act = $args->delete_file_act;
            $config->download_file_act = $args->download_file_act;

            // 저장
            $oModuleController = &getController('module');
            $oModuleController->insertModuleConfig('point', $config);

            $this->cacheActList();

            $this->setMessage('success_updated');
        }

        /**
         * @brief 회원 포인트 변경
         **/
        function procPointAdminUpdatePoint() {
            $member_srl = Context::get('member_srl');
            $point = Context::get('point');

            $oPointController = &getController('point');
            return $oPointController->setPoint($member_srl, (int)$point);
        }

        /**
         * @brief 캐시파일 저장
         **/
        function cacheActList() {
            // 설정 정보 가져오기
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');

            // 각 act값을 정리
            $act_list = sprintf("%s,%s,%s,%s,%s,%s,%s",
                    $config->insert_document_act,
                    $config->delete_document_act,
                    $config->insert_comment_act,
                    $config->delete_comment_act,
                    $config->upload_file_act,
                    $config->delete_file_act,
                    $config->download_file_act
            );

            $act_cache_file = "./files/cache/point.act.cache";
            FileHandler::writeFile($act_cache_file, $act_list);
        }

    }
?>
