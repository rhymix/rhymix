<?php
    /**
     * @class  moduleModel
     * @author zero (zero@nzeo.com)
     * @brief  module 모듈의 Controller class
     **/

    class moduleController extends Module {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 기본 모듈 생성
         **/
        function makeDefaultModule() {
            $oDB = &DB::getInstance();

            // 설치된 기본 모듈이 있는지 확인
            $output = $oDB->executeQuery('module_manager.getDefaultMidInfo');
            if($output->data) return;

            // 기본 모듈 입력
            $args->mid = 'board';
            $args->browser_title = '테스트 모듈';
            $args->is_default = 'Y';
            $args->module = 'board';
            $args->skin = 'default';

            $extra_vars->colorset = 'normal';
            $args->extra_vars = serialize($extra_vars);

            return $this->insertModule($args);
        }

        /**
         * @brief 모듈 입력
         **/
        function insertModule($args) {
            // module model 객체 생성
            $oModuleModel = getModule('module','model');

            // 선택된 스킨정보에서 colorset을 구함
            $skin_info = $oModuleModel->loadSkinInfo($args->module, $args->skin);
            $extra_vars->colorset = $skin_info->colorset[0]->name;

            // db에 입력
            $oDB = &DB::getInstance();
            $args->module_srl = $oDB->getNextSequence();
            $args->extra_vars = serialize($extra_vars);
            $output = $oDB->executeQuery('module_manager.insertModule', $args);
            $output->add('module_srl',$args->module_srl);
            return $output;
        }

        /**
         * @brief 모듈의 정보를 수정
         **/
        function updateModule($args) {
            $oDB = &DB::getInstance();
            $output = $oDB->executeQuery('module_manager.updateModule', $args);
            $output->add('module_srl',$args->module_srl);
            return $output;
        }

        /**
         * @brief 모듈의 기타 정보를 변경
         **/
        function updateModuleExtraVars($module_srl, $extra_vars) {
            $oDB = &DB::getInstance();
            $args->module_srl = $module_srl;
            $args->extra_vars = $extra_vars;
            $output = $oDB->executeQuery('module_manager.updateModuleExtraVars', $args);
            return $output;
        }

        /**
         * @brief 모듈의 권한 정보 변경
         **/
        function updateModuleGrant($module_srl, $grant) {
            $oDB = &DB::getInstance();
            $args->module_srl = $module_srl;
            $args->grant = $grant;
            $output = $oDB->executeQuery('module_manager.updateModuleGrant', $args);
            return $output;
        }

        /**
         * @brief 모듈을 삭제
         *
         * 모듈 삭제시는 관련 정보들을 모두 삭제 시도한다.
         **/
        function deleteModule($module_srl) {
            $oDB = &DB::getInstance();

            // addon 삭제
            // plugin 삭제

            // document 삭제
            $oDocumentController = getModule('document', 'controller');
            $output = $oDocumentController->deleteModuleDocument($module_srl);
            if(!$output->toBool()) return $output;

            // category 삭제
            $output = $oDocumentController->deleteModuleCategory($module_srl);
            if(!$output->toBool()) return $output;

            // trackbacks 삭제
            $oTrackbackController = getModule('trackback','controller');
            $output = $oTrackbackController->deleteModuleTrackbacks($module_srl);
            if(!$output->toBool()) return $output;

            // comments 삭제
            $oCommentController = getModule('comment','controller');
            $output = $oCommentController->deleteModuleComments($module_srl);
            if(!$output->toBool()) return $output;

            // tags 삭제
            $oTagController = getModule('tag','controller');
            $output = $oTagController->deleteModuleTags($module_srl);
            if(!$output->toBool()) return $output;

            // files 삭제
            $output = $oDocumentController->deleteModuleFiles($module_srl);
            if(!$output->toBool()) return $output;

            // module 정보 삭제
            $args->module_srl = $module_srl;
            $output = $oDB->executeQuery('module_manager.deleteModule', $args);

            return $output;
        }

        /**
         * @brief 모든 모듈의 is_default값을 N 으로 세팅 (기본 모듈 해제)
         **/
        function clearDefaultModule() {
            $oDB = &DB::getInstance();
            return  $oDB->executeQuery('module_manager.clearDefaultModule');
        }

    }
?>
