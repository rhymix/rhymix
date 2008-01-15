<?php
    /**
     * @class  integration_searchAdminController
     * @author zero (zero@nzeo.com)
     * @brief  integration_search module의 admin view class
     *
     * 통합검색 관리
     *
     **/

    class integration_searchAdminController extends integration_search {
        /**
         * @brief 초기화
         **/
        function init() {}

        /**
         * @brief 설정 저장
         **/
        function procIntegration_searchAdminInsertConfig() {
            // 설정 정보를 받아옴 (module model 객체를 이용)
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('integration_search');

            $args->skin = Context::get('skin');
            $args->target_mid = explode('|@|',Context::get('target_mid'));
            $args->skin_vars = $config->skin_vars;

            $oModuleController = &getController('module');
            return $oModuleController->insertModuleConfig('integration_search',$args);
        }

        /**
         * @brief 스킨 정보 저장
         **/
        function procIntegration_searchAdminInsertSkin() {
            // 설정 정보를 받아옴 (module model 객체를 이용)
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('integration_search');

            $args->skin = $config->skin;
            $args->target_mid = $config->target_mid;

            // 스킨의 정보를 구해옴 (extra_vars를 체크하기 위해서)
            $skin_info = $oModuleModel->loadSkinInfo($this->module_path, $config->skin);

            // 입력받은 변수들을 체크 (mo, act, module_srl, page등 기본적인 변수들 없앰)
            $obj = Context::getRequestVars();
            unset($obj->act);
            unset($obj->module_srl);
            unset($obj->page);

            // 원 skin_info에서 extra_vars의 type이 image일 경우 별도 처리를 해줌
            if($skin_info->extra_vars) {
                foreach($skin_info->extra_vars as $vars) {
                    if($vars->type!='image') continue;

                    $image_obj = $obj->{$vars->name};

                    // 삭제 요청에 대한 변수를 구함
                    $del_var = $obj->{"del_".$vars->name};
                    unset($obj->{"del_".$vars->name});
                    if($del_var == 'Y') {
                        @unlink($module_info->{$vars->name});
                        continue;
                    }

                    // 업로드 되지 않았다면 이전 데이터를 그대로 사용
                    if(!$image_obj['tmp_name']) {
                        $obj->{$vars->name} = $module_info->{$vars->name};
                        continue;
                    }

                    // 정상적으로 업로드된 파일이 아니면 무시
                    if(!is_uploaded_file($image_obj['tmp_name'])) {
                        unset($obj->{$vars->name});
                        continue;
                    }

                    // 이미지 파일이 아니어도 무시
                    if(!preg_match("/\.(jpg|jpeg|gif|png)$/i", $image_obj['name'])) {
                        unset($obj->{$vars->name});
                        continue;
                    }

                    // 경로를 정해서 업로드
                    $path = sprintf("./files/attach/images/%s/", $module_srl);

                    // 디렉토리 생성
                    if(!FileHandler::makeDir($path)) return false;

                    $filename = $path.$image_obj['name'];

                    // 파일 이동
                    if(!move_uploaded_file($image_obj['tmp_name'], $filename)) {
                        unset($obj->{$vars->name});
                        continue;
                    }

                    // 변수를 바꿈
                    unset($obj->{$vars->name});
                    $obj->{$vars->name} = $filename;
                }
            }

            // serialize하여 저장
            $args->skin_vars = serialize($obj);

            $oModuleController = &getController('module');
            return $oModuleController->insertModuleConfig('integration_search',$args);
        }
    }
?>
