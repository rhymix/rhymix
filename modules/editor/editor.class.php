<?php
    /**
     * @class  editor
     * @author zero (zero@nzeo.com)
     * @brief  editor 모듈의 high class
     **/

    class editor extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('editor', 'view', 'dispEditorAdminIndex');
            $oModuleController->insertActionForward('editor', 'view', 'dispEditorAdminSetupComponent');

            // 기본 에디터 컴포넌트를 추가
            $oEditorController = &getAdminController('editor');
            $oEditorController->insertComponent('colorpicker_text',true);
            $oEditorController->insertComponent('colorpicker_bg',true);
            $oEditorController->insertComponent('emoticon',true);
            $oEditorController->insertComponent('url_link',true);
            $oEditorController->insertComponent('image_link',true);
            $oEditorController->insertComponent('multimedia_link',true);
            $oEditorController->insertComponent('quotation',true);
            $oEditorController->insertComponent('table_maker',true);
            $oEditorController->insertComponent('poll_maker',true);
            $oEditorController->insertComponent('image_gallery',true);

            // 에디터 모듈에서 사용할 디렉토리 생성
            FileHandler::makeDir('./files/cache/editor');

            // 2007. 10. 17 글의 입력(신규 or 수정)이 일어날때마다 자동 저장된 문서를 삭제하는 trigger 추가
            $oModuleController->insertTrigger('document.insertDocument', 'editor', 'controller', 'triggerDeleteSavedDoc', 'after');
            $oModuleController->insertTrigger('document.updateDocument', 'editor', 'controller', 'triggerDeleteSavedDoc', 'after');

            // 2007. 10. 23 모듈의 추가 설정에서 에디터 trigger 추가
            $oModuleController->insertTrigger('module.dispAdditionSetup', 'editor', 'view', 'triggerDispEditorAdditionSetup', 'before');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oModuleModel = &getModel('module');

            // 2007. 10. 17 글의 입력(신규 or 수정)이 일어날때마다 자동 저장된 문서를 삭제하는 trigger 추가
            if(!$oModuleModel->getTrigger('document.insertDocument', 'editor', 'controller', 'triggerDeleteSavedDoc', 'after')) return true;
            if(!$oModuleModel->getTrigger('document.updateDocument', 'editor', 'controller', 'triggerDeleteSavedDoc', 'after')) return true;

            // 2007. 10. 23 모듈의 추가 설정에서 에디터 trigger 추가
            if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'editor', 'view', 'triggerDispEditorAdditionSetup', 'before')) return true;

            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');

            // 2007. 10. 17 글의 입력(신규 or 수정)이 일어날때마다 자동 저장된 문서를 삭제하는 trigger 추가
            if(!$oModuleModel->getTrigger('document.insertDocument', 'editor', 'controller', 'triggerDeleteSavedDoc', 'after')) 
                $oModuleController->insertTrigger('document.insertDocument', 'editor', 'controller', 'triggerDeleteSavedDoc', 'after');
            if(!$oModuleModel->getTrigger('document.updateDocument', 'editor', 'controller', 'triggerDeleteSavedDoc', 'after')) 
                $oModuleController->insertTrigger('document.updateDocument', 'editor', 'controller', 'triggerDeleteSavedDoc', 'after');

            // 2007. 10. 23 모듈의 추가 설정에서 에디터 trigger 추가
            if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'editor', 'view', 'triggerDispEditorAdditionSetup', 'before')) 
                $oModuleController->insertTrigger('module.dispAdditionSetup', 'editor', 'view', 'triggerDispEditorAdditionSetup', 'before');

            return new Object(0, 'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
            // 에디터 컴포넌트 캐시 파일 삭제
            FileHandler::removeFilesInDir("./files/cache/editor");
        }

        /**
         * @brief 권한 체크를 실행하는 method
         * 모듈 객체가 생성된 경우는 직접 권한을 체크하지만 기능성 모듈등 스스로 객체를 생성하지 않는 모듈들의 경우에는
         * ModuleObject에서 직접 method를 호출하여 권한을 확인함
         *
         * isAdminGrant는 관리권한 이양시에만 사용되도록 하고 기본은 false로 return 되도록 하여 잘못된 권한 취약점이 생기지 않도록 주의하여야 함
         **/
        function isAdmin() {
            // 로그인이 되어 있지 않으면 무조건 return false
            $is_logged = Context::get('is_logged');
            if(!$is_logged) return false;

            // 사용자 아이디를 구함
            $logged_info = Context::get('logged_info');

            // 모듈 요청에 사용된 변수들을 가져옴
            $args = Context::getRequestVars();

            // act의 값에 따라서 관리 권한 체크
            switch($args->act) {
                case 'procEditorAdminInsertModuleConfig' :
                        if(!$args->target_module_srl) return false;

                        $oModuleModel = &getModel('module');
                        $module_info = $oModuleModel->getModuleInfoByModuleSrl($args->target_module_srl);
                        if(!$module_info) return false;

                        if($oModuleModel->isModuleAdmin($module_info, $logged_info)) return true; 
                    break;
            }

            return false;
        }
    }
?>
