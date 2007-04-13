<?php
    /**
     * @class  moduleController
     * @author zero (zero@nzeo.com)
     * @brief  module 모듈의 Controller class
     **/

    class moduleController extends module {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 모듈 카테고리 추가
         **/
        function procModuleAdminInsertCategory() {
            $args->title = Context::get('title');
            $output = executeQuery('module.insertModuleCategory', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage("success_registed");
        }

        /**
         * @brief 카테고리의 내용 수정
         **/
        function procModuleAdminUpdateCategory() {
            $mode = Context::get('mode');

            switch($mode) {
                case 'delete' :
                        $output = $this->doDeleteModuleCategory();
                        $msg_code = 'success_deleted';
                    break;
                case 'update' :
                        $output = $this->doUpdateModuleCategory();
                        $msg_code = 'success_updated';
                    break;
            }
            if(!$output->toBool()) return $output;

            $this->setMessage($msg_code);
        }

        /**
         * @brief 기본 모듈 생성
         **/
        function makeDefaultModule() {
            // 설치된 기본 모듈이 있는지 확인
            $output = executeQuery('module.getDefaultMidInfo');
            if($output->data) return;

            // 기본 데이터 세팅
            $args->mid = 'board';
            $args->browser_title = '테스트 모듈';
            $args->is_default = 'Y';
            $args->module = 'board';
            $args->skin = 'default';

            return $this->insertModule($args);
        }

        /**
         * @brief 모듈의 기본 정보 입력
         * 모듈의 정보를 입력받은 데이터를 serialize하여 등록한다.
         **/
        function insertModuleConfig($module, $config) {
            // 변수 정리
            $args->module = $module;
            $args->config = serialize($config);

            // 일단 삭제 (캐쉬 파일도 지운다)
            $output = executeQuery('module.deleteModuleConfig', $args);
            if(!$output->toBool()) return $output;

            @unlink( sprintf('./files/cache/module_info/%s.config.php',$module) );

            // 변수 정리후 query 실행
            $output = executeQuery('module.insertModuleConfig', $args);
            return $output;
        }

        /**
         * @brief 모듈 입력
         **/
        function insertModule($args) {
            // begin transaction
            $oDB = &DB::getInstance();
            $oDB->begin();

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
            $output = executeQuery('module.updateModule', $args);
            if(!$output->toBool()) return $output;

            $output->add('module_srl',$args->module_srl);
            return $output;
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
         * @brief 모듈을 삭제
         *
         * 모듈 삭제시는 관련 정보들을 모두 삭제 시도한다.
         **/
        function deleteModule($module_srl) {

            // begin transaction
            $oDB = &DB::getInstance();
            $oDB->begin();

            $args->module_srl = $module_srl;

            // addon 삭제

            // plugin 삭제

            // document 삭제
            $oDocumentController = &getController('document');
            $output = $oDocumentController->deleteModuleDocument($module_srl);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // category 삭제
            $output = $oDocumentController->deleteModuleCategory($module_srl);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // trackbacks 삭제
            $oTrackbackController = &getController('trackback');
            $output = $oTrackbackController->deleteModuleTrackbacks($module_srl);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // comments 삭제
            $oCommentController = &getController('comment');
            $output = $oCommentController->deleteModuleComments($module_srl);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // tags 삭제
            $oTagController = &getController('tag');
            $output = $oTagController->deleteModuleTags($module_srl);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // 첨부 파일 삭제
            $oFileController = &getController('file');
            $output = $oFileController->deleteModuleFiles($module_srl);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // module 정보를 DB에서 삭제
            $output = executeQuery('module.deleteModule', $args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // commit
            $oDB->commit();

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
         * @brief 모듈 카테고리의 제목 변경
         **/
        function doUpdateModuleCategory() {
            $args->title = Context::get('title');
            $args->module_category_srl = Context::get('module_category_srl');
            return executeQuery('module.updateModuleCategory', $args);
        }

        /**
         * @brief 모듈 카테고리 삭제
         **/
        function doDeleteModuleCategory() {
            $args->module_category_srl = Context::get('module_category_srl');
            return executeQuery('module.deleteModuleCategory', $args);
        }
    }
?>
