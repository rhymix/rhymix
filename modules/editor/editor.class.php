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

            // 2009. 04. 14 editor component 변환 코드를 trigger로 독립
            $oModuleController->insertTrigger('display', 'editor', 'controller', 'triggerEditorComponentCompile', 'before');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $db_info = Context::getDBInfo ();
            $oModuleModel = &getModel('module');

            $oDB = &DB::getInstance();

            // 2009. 06. 15 자동저장시 module_srl 을 저장
            if(!$oDB->isColumnExists("editor_autosave","module_srl")) return true;

            // 2009. 06. 15 module_srl을 인덱스로
            if ($db_info->db_type == 'cubrid') {
              if(!$oDB->isIndexExists("editor_autosave",$oDB->prefix."editor_autosave_idx_module_srl")) return true;
            }
            else {
              if(!$oDB->isIndexExists("editor_autosave","idx_module_srl")) return true;
            }


            // 2007. 10. 17 글의 입력(신규 or 수정)이 일어날때마다 자동 저장된 문서를 삭제하는 trigger 추가
            if(!$oModuleModel->getTrigger('document.insertDocument', 'editor', 'controller', 'triggerDeleteSavedDoc', 'after')) return true;
            if(!$oModuleModel->getTrigger('document.updateDocument', 'editor', 'controller', 'triggerDeleteSavedDoc', 'after')) return true;

            // 2007. 10. 23 모듈의 추가 설정에서 에디터 trigger 추가
            if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'editor', 'view', 'triggerDispEditorAdditionSetup', 'before')) return true;

            // 2009. 04. 14 editor component 변환 코드를 trigger로 독립
            if(!$oModuleModel->getTrigger('display', 'editor', 'controller', 'triggerEditorComponentCompile', 'before')) return true;

            // 2009. 06. 19 사용하지 않는 트리거 제거
            if($oModuleModel->getTrigger('file.getIsPermitted', 'editor', 'controller', 'triggerSrlSetting', 'before')) return true;

            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            $db_info = Context::getDBInfo ();
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');

            $oDB = &DB::getInstance();

            // 자동저장시 module_srl 을 저장 2009.6.15
            if(!$oDB->isColumnExists("editor_autosave","module_srl")) 
                $oDB->addColumn("editor_autosave","module_srl","number",11);

            // module_srl을 인덱스로
            if ($db_info->db_type == 'cubrid') {
              if(!$oDB->isIndexExists("editor_autosave",$oDB->prefix."editor_autosave_idx_module_srl")) 
                  $oDB->addIndex("editor_autosave",$oDB->prefix."editor_autosave_idx_module_srl", "module_srl");
            }
            else {
              if(!$oDB->isIndexExists("editor_autosave","idx_module_srl")) 
                  $oDB->addIndex("editor_autosave","idx_module_srl", "module_srl");
            }

            // 2007. 10. 17 글의 입력(신규 or 수정)이 일어날때마다 자동 저장된 문서를 삭제하는 trigger 추가
            if(!$oModuleModel->getTrigger('document.insertDocument', 'editor', 'controller', 'triggerDeleteSavedDoc', 'after')) 
                $oModuleController->insertTrigger('document.insertDocument', 'editor', 'controller', 'triggerDeleteSavedDoc', 'after');
            if(!$oModuleModel->getTrigger('document.updateDocument', 'editor', 'controller', 'triggerDeleteSavedDoc', 'after')) 
                $oModuleController->insertTrigger('document.updateDocument', 'editor', 'controller', 'triggerDeleteSavedDoc', 'after');

            // 2007. 10. 23 모듈의 추가 설정에서 에디터 trigger 추가
            if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'editor', 'view', 'triggerDispEditorAdditionSetup', 'before')) 
                $oModuleController->insertTrigger('module.dispAdditionSetup', 'editor', 'view', 'triggerDispEditorAdditionSetup', 'before');

            // 2009. 04. 14 editor component 변환 코드를 trigger로 독립
            if(!$oModuleModel->getTrigger('display', 'editor', 'controller', 'triggerEditorComponentCompile', 'before')) 
                $oModuleController->insertTrigger('display', 'editor', 'controller', 'triggerEditorComponentCompile', 'before');

            // 2009. 06. 19 사용하지 않는 트리거 제거
            if($oModuleModel->getTrigger('file.getIsPermitted', 'editor', 'controller', 'triggerSrlSetting', 'before')) 
                $oModuleController->deleteTrigger('file.getIsPermitted', 'editor', 'controller', 'triggerSrlSetting', 'before');

            return new Object(0, 'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
            // 에디터 컴포넌트 캐시 파일 삭제
            FileHandler::removeFilesInDir("./files/cache/editor");
        }
    }
?>
