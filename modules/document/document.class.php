<?php
    /**
     * @class  document
     * @author zero (zero@nzeo.com)
     * @brief  document 모듈의 high 클래스
     **/

    require_once(_XE_PATH_.'modules/document/document.item.php');

    class document extends ModuleObject {

        // 관리자페이지에서 사용할 검색 옵션
        var $search_option = array('title','content','title_content','user_name',); ///< 검색 옵션

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');

            $oDB = &DB::getInstance();
            $oDB->addIndex("documents","idx_module_list_order", array("module_srl","list_order"));
            $oDB->addIndex("documents","idx_module_update_order", array("module_srl","update_order"));
            $oDB->addIndex("documents","idx_module_readed_count", array("module_srl","readed_count"));
            $oDB->addIndex("documents","idx_module_voted_count", array("module_srl","voted_count"));
            $oDB->addIndex("documents","idx_module_notice", array("module_srl","is_notice"));
            $oDB->addIndex("documents","idx_module_document_srl", array("module_srl","document_srl"));
            $oDB->addIndex("documents","idx_module_blamed_count", array("module_srl","blamed_count"));
            $oDB->addIndex("document_aliases", "idx_module_title", array("module_srl","alias_title"), true);

            // 2007. 10. 17 모듈이 삭제될때 등록된 글도 모두 삭제하는 트리거 추가
            $oModuleController->insertTrigger('module.deleteModule', 'document', 'controller', 'triggerDeleteModuleDocuments', 'after');

            // 2009. 01. 29 Added a trigger for additional setup
            $oModuleController->insertTrigger('module.dispAdditionSetup', 'document', 'view', 'triggerDispDocumentAdditionSetup', 'before');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oDB = &DB::getInstance();
            $oModuleModel = &getModel('module');

            /**
             * 2007. 7. 25 : 알림 필드(notify_message) 추가
             **/
            if(!$oDB->isColumnExists("documents","notify_message")) return true;

            /**
             * 2007. 8. 23 : document테이블에 결합 인덱스 적용
             **/
            if(!$oDB->isIndexExists("documents","idx_module_list_order")) return true;
            if(!$oDB->isIndexExists("documents","idx_module_update_order")) return true;
            if(!$oDB->isIndexExists("documents","idx_module_readed_count")) return true;
            if(!$oDB->isIndexExists("documents","idx_module_voted_count")) return true;

            // 2007. 10. 17 모듈이 삭제될때 등록된 글도 모두 삭제하는 트리거 추가
            if(!$oModuleModel->getTrigger('module.deleteModule', 'document', 'controller', 'triggerDeleteModuleDocuments', 'after')) return true;

            // 2007. 10. 25 문서 분류에 parent_srl, expand를 추가
            if(!$oDB->isColumnExists("document_categories","parent_srl")) return true;
            if(!$oDB->isColumnExists("document_categories","expand")) return true;
            if(!$oDB->isColumnExists("document_categories","group_srls")) return true;

            // 2007. 11. 20 게시글에 module_srl + is_notice 복합인덱스 만들기
            if(!$oDB->isIndexExists("documents","idx_module_notice")) return true;

            // 2008. 02. 18 게시글에 module_srl + document_srl 복합인덱스 만들기 (manian님 확인)
            if(!$oDB->isIndexExists("documents","idx_module_document_srl")) return true;

            /**
             * 2007. 12. 03 : 확장변수(extra_vars) 컬럼이 없을 경우 추가
             **/
            if(!$oDB->isColumnExists("documents","extra_vars")) return true;

            // 2008. 04. 23 blamed count 컬럼 추가
            if(!$oDB->isColumnExists("documents", "blamed_count")) return true;
            if(!$oDB->isIndexExists("documents","idx_module_blamed_count")) return true;
            if(!$oDB->isColumnExists("document_voted_log", "point")) return true;

            // 2008-12-15 문서 분류에 color를 추가
            if(!$oDB->isColumnExists("document_categories", "color")) return true;

            /**
             * 2009. 01. 29 : 확장변수 값 테이블에 lang_code가 없을 경우 추가
             **/
            if(!$oDB->isColumnExists("document_extra_vars","lang_code")) return true;

            if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'document', 'view', 'triggerDispDocumentAdditionSetup', 'before')) return true;

            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            $oDB = &DB::getInstance();
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');

            /**
             * 2007. 7. 25 : 알림 필드(notify_message) 추가
             **/
            if(!$oDB->isColumnExists("documents","notify_message")) {
                $oDB->addColumn('documents',"notify_message","char",1);
            }

            /**
             * 2007. 8. 23 : document테이블에 결합 인덱스 적용
             **/
            if(!$oDB->isIndexExists("documents","idx_module_list_order")) {
                $oDB->addIndex("documents","idx_module_list_order", array("module_srl","list_order"));
            }

            if(!$oDB->isIndexExists("documents","idx_module_update_order")) {
                $oDB->addIndex("documents","idx_module_update_order", array("module_srl","update_order"));
            }

            if(!$oDB->isIndexExists("documents","idx_module_readed_count")) {
                $oDB->addIndex("documents","idx_module_readed_count", array("module_srl","readed_count"));
            }

            if(!$oDB->isIndexExists("documents","idx_module_voted_count")) {
                $oDB->addIndex("documents","idx_module_voted_count", array("module_srl","voted_count"));
            }

            // 2007. 10. 17 모듈이 삭제될때 등록된 글도 모두 삭제하는 트리거 추가
            if(!$oModuleModel->getTrigger('module.deleteModule', 'document', 'controller', 'triggerDeleteModuleDocuments', 'after'))
                $oModuleController->insertTrigger('module.deleteModule', 'document', 'controller', 'triggerDeleteModuleDocuments', 'after');

            // 2007. 10. 25 문서 분류에 parent_srl, expand를 추가
            if(!$oDB->isColumnExists("document_categories","parent_srl")) $oDB->addColumn('document_categories',"parent_srl","number",12,0);
            if(!$oDB->isColumnExists("document_categories","expand")) $oDB->addColumn('document_categories',"expand","char",1,"N");
            if(!$oDB->isColumnExists("document_categories","group_srls")) $oDB->addColumn('document_categories',"group_srls","text");

            // 2007. 11. 20 게시글에 module_srl + is_notice 복합인덱스 만들기
            if(!$oDB->isIndexExists("documents","idx_module_notice")) $oDB->addIndex("documents","idx_module_notice", array("module_srl","is_notice"));

            /**
             * 2007. 12. 03 : 확장변수(extra_vars) 컬럼이 없을 경우 추가
             **/
            if(!$oDB->isColumnExists("documents","extra_vars")) $oDB->addColumn('documents','extra_vars','text');

            /**
             * 2008. 02. 18 게시글에 module_srl + document_srl 복합인덱스 만들기 (manian님 확인)
             **/
            if(!$oDB->isIndexExists("documents","idx_module_document_srl")) $oDB->addIndex("documents","idx_module_document_srl", array("module_srl","document_srl"));

            // 2008. 04. 23 blamed count 컬럼 추가
            if(!$oDB->isColumnExists("documents", "blamed_count")) {
                $oDB->addColumn('documents', 'blamed_count', 'number', 11, 0, true);
                $oDB->addIndex('documents', 'idx_blamed_count', array('blamed_count'));
            }

            if(!$oDB->isIndexExists("documents","idx_module_blamed_count")) {
                $oDB->addIndex('documents', 'idx_module_blamed_count', array('module_srl', 'blamed_count'));
            }

            if(!$oDB->isColumnExists("document_voted_log", "point"))
            $oDB->addColumn('document_voted_log', 'point', 'number', 11, 0, true);


            if(!$oDB->isColumnExists("document_categories","color")) $oDB->addColumn('document_categories',"color","char",7);

            /**
             * 2009. 01. 29 : 확장변수 값 테이블에 lang_code가 없을 경우 추가
             **/
            if(!$oDB->isColumnExists("document_extra_vars","lang_code")) $oDB->addColumn('document_extra_vars',"lang_code","varchar",10);

            // 2009. 01. 29 Added a trigger for additional setup
            if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'document', 'view', 'triggerDispDocumentAdditionSetup', 'before')) 
                $oModuleController->insertTrigger('module.dispAdditionSetup', 'document', 'view', 'triggerDispDocumentAdditionSetup', 'before');


            return new Object(0,'success_updated');

        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
            // 게시글 분류 캐시 파일 삭제
            FileHandler::removeFilesInDir(_XE_PATH_."files/cache/document_category");
        }

    }
?>
