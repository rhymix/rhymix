<?php
    /**
     * @class  moduleController
     * @author zero (zero@nzeo.com)
     * @brief  module 모듈의 controller class
     **/

    class moduleController extends module {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief action forward 추가
         * action foward는 등록된 action이 요청된 모듈에 없을 경우 찾아서 포워딩을 하는 구조이다
         * 모듈의 설치시에 사용된다.
         **/
        function insertActionForward($module, $type, $act) {
            $args->module = $module;
            $args->type = $type;
            $args->act = $act;

            $output = executeQuery('module.insertActionFoward', $args);
            return $output;
        }

        /**
         * @brief module trigger 추가
         * module trigger는 trigger 대상이 등록된 대상을 호출하는 방법이다.
         *
         **/
        function insertTrigger($trigger_name, $module, $type, $called_method, $called_position) {
            $args->trigger_name = $trigger_name;
            $args->module = $module;
            $args->type = $type;
            $args->called_method = $called_method;
            $args->called_position = $called_position;

            $output = executeQuery('module.insertTrigger', $args);

            // 트리거 정보가 있는 파일 모두 삭제
            FileHandler::removeFilesInDir("./files/cache/triggers");

            return $output;
        }

        /**
         * @brief 특정 모듈의 설정 입력
         * board, member등 특정 모듈의 global config 관리용
         **/
        function insertModuleConfig($module, $config) {
            $args->module = $module;
            $args->config = serialize($config);

            $output = executeQuery('module.deleteModuleConfig', $args);
            if(!$output->toBool()) return $output;

            $output = executeQuery('module.insertModuleConfig', $args);
            return $output;
        }

        /**
         * @brief 특정 mid의 모듈 설정 정보 저장
         * mid의 모듈 의존적인 설정을 관리
         **/
        function insertModulePartConfig($module, $module_srl, $config) {
            $args->module = $module;
            $args->module_srl = $module_srl;
            $args->config = serialize($config);

            $output = executeQuery('module.deleteModulePartConfig', $args);
            if(!$output->toBool()) return $output;

            $output = executeQuery('module.insertModulePartConfig', $args);
            return $output;
        }

        /**
         * @brief 모듈 입력
         **/
        function insertModule($args) {
            // begin transaction
            $oDB = &DB::getInstance();
            $oDB->begin();

            // 이미 존재하는 모듈 이름인지 체크
            $output = executeQuery('module.isExistsModuleName', $args);
            if(!$output->toBool() || $output->data->count) {
                $oDB->rollback();
                return new Object(-1, 'msg_module_name_exists');
            }

            // module model 객체 생성
            $oModuleModel = &getModel('module');

            // 선택된 스킨정보에서 colorset을 구함
            $module_path = ModuleHandler::getModulePath($args->module);
            $skin_info = $oModuleModel->loadSkinInfo($module_path, $args->skin);
            $skin_vars->colorset = $skin_info->colorset[0]->name;

            // 변수 정리후 query 실행
            if(!$args->module_srl) $args->module_srl = getNextSequence();
            $args->skin_vars = serialize($skin_vars);
            $output = executeQuery('module.insertModule', $args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // commit
            $oDB->commit();

            $output->add('module_srl',$args->module_srl);
            return $output;
        }

        /**
         * @brief 모듈의 정보를 수정
         **/
        function updateModule($args) {
            // begin transaction
            $oDB = &DB::getInstance();
            $oDB->begin();

            // 이미 존재하는 모듈 이름인지 체크
            $output = executeQuery('module.isExistsModuleName', $args);
            if(!$output->toBool() || $output->data->count) {
                $oDB->rollback();
                return new Object(-1, 'msg_module_name_exists');
            }

            $output = executeQuery('module.updateModule', $args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            $output->add('module_srl',$args->module_srl);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            $oDB->commit();

            return $output;
        }

        /**
         * @brief 모듈을 삭제
         *
         * 모듈 삭제시는 관련 정보들을 모두 삭제 시도한다.
         **/
        function deleteModule($module_srl) {

            // trigger 호출 (before)
            $trigger_obj->module_srl = $module_srl;
            $output = ModuleHandler::triggerCall('module.deleteModule', 'before', $trigger_obj);
            if(!$output->toBool()) return $output;

            // begin transaction
            $oDB = &DB::getInstance();
            $oDB->begin();

            $args->module_srl = $module_srl;

            // module 정보를 DB에서 삭제
            $output = executeQuery('module.deleteModule', $args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // trigger 호출 (after)
            if($output->toBool()) {
                $trigger_output = ModuleHandler::triggerCall('module.deleteModule', 'after', $trigger_obj);
                if(!$trigger_output->toBool()) {
                    $oDB->rollback();
                    return $trigger_output;
                }
            }

            // commit
            $oDB->commit();

            return $output;
        }

        /**
         * @brief 모듈의 기타 정보를 변경
         **/
        function updateModuleSkinVars($module_srl, $skin_vars) {
            // skin_vars 정보 세팅
            $args->module_srl = $module_srl;
            $args->skin_vars = $skin_vars;
            $output = executeQuery('module.updateModuleSkinVars', $args);
            if(!$output->toBool()) return $output;

            return $output;
        }

        /**
         * @brief 모듈의 권한 정보 변경
         **/
        function updateModuleGrant($module_srl, $grants) {
            $args->module_srl = $module_srl;
            $args->grants = $grants;
            $output = executeQuery('module.updateModuleGrant', $args);
            if(!$output->toBool()) return $output;

            return $output;
        }

        /**
         * @brief 모든 모듈의 is_default값을 N 으로 세팅 (기본 모듈 해제)
         **/
        function clearDefaultModule() {
            $output = executeQuery('module.clearDefaultModule');
            if(!$output->toBool()) return $output;

            return $output;
        }

        /**
         * @brief 지정된 menu_srl에 속한 mid 의 menu_srl 을 변경 
         **/
        function updateModuleMenu($args) {
            return executeQuery('module.updateModuleMenu', $args);
        }

        /**
         * @brief 지정된 menu_srl에 속한 mid 의 layout_srl을 변경 
         **/
        function updateModuleLayout($layout_srl, $menu_srl_list) {
            if(!count($menu_srl_list)) return;

            $args->layout_srl = $layout_srl;
            $args->menu_srls = implode(',',$menu_srl_list);
            $output = executeQuery('module.updateModuleLayout', $args);
            return $output;
        }
    }
?>
