<?php
    /**
     * @class  document 
     * @author zero (zero@nzeo.com)
     * @brief  document 모듈의 high 클래스
     **/

    require_once('./modules/document/document.item.php');

    class document extends ModuleObject {

        // 관리자페이지에서 사용할 검색 옵션
        var $search_option = array('title','content','title_content','user_name',); ///< 검색 옵션

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('document', 'view', 'dispDocumentAdminList');
            $oModuleController->insertActionForward('document', 'view', 'dispDocumentPrint');
            $oModuleController->insertActionForward('document', 'view', 'dispDocumentAdminConfig');
            $oModuleController->insertActionForward('document', 'view', 'dispDocumentAdminManageDocument');
            $oModuleController->insertActionForward('document', 'view', 'dispDocumentAdminDeclared');

            $oDB = &DB::getInstance();
            $oDB->addIndex("documents","idx_module_list_order", array("module_srl","list_order"));
            $oDB->addIndex("documents","idx_module_update_order", array("module_srl","update_order"));
            $oDB->addIndex("documents","idx_module_readed_count", array("module_srl","readed_count"));
            $oDB->addIndex("documents","idx_module_voted_count", array("module_srl","voted_count"));
            $oDB->addIndex("documents","idx_module_notice", array("module_srl","is_notice"));

            // 2007. 10. 17 모듈이 삭제될때 등록된 글도 모두 삭제하는 트리거 추가
            $oModuleController->insertTrigger('module.deleteModule', 'document', 'controller', 'triggerDeleteModuleDocuments', 'after');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oDB = &DB::getInstance();
            $oModuleModel = &getModel('module');

            /**
             * 2007. 7. 23 : 확장변수(extra_vars1~20까지 추가)
             **/
            if(!$oDB->isColumnExists("documents","extra_vars20")) return true;

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

            /**
             * 2007. 10. 11 : 관리자 페이지의 기본 설정 Action 추가, 게시글 관리 action 추가
             **/
            if(!$oModuleModel->getActionForward('dispDocumentAdminConfig')) return true;
            if(!$oModuleModel->getActionForward('dispDocumentAdminManageDocument')) return true;

            // 2007. 10. 17 모듈이 삭제될때 등록된 글도 모두 삭제하는 트리거 추가
            if(!$oModuleModel->getTrigger('module.deleteModule', 'document', 'controller', 'triggerDeleteModuleDocuments', 'after')) return true;

            /**
             * 2007. 10. 18 : 관리자 페이지의 신고된 목록 보기 action 추가
             **/
            if(!$oModuleModel->getActionForward('dispDocumentAdminDeclared')) return true;

            // 2007. 10. 25 문서 분류에 parent_srl, expand를 추가
            if(!$oDB->isColumnExists("document_categories","parent_srl")) return true;
            if(!$oDB->isColumnExists("document_categories","expand")) return true;
            if(!$oDB->isColumnExists("document_categories","group_srls")) return true;

            // 2007. 11. 20 게시글에 module_srl + is_notice 복합인덱스 만들기
            if(!$oDB->isIndexExists("documents","idx_module_notice")) return true;

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
             * 2007. 7. 23 : 확장변수(extra_vars1~20까지 추가)
             **/
            if(!$oDB->isColumnExists("documents","extra_vars20")) {
                for($i=1;$i<=20;$i++) {
                    $column_name = "extra_vars".$i;
                    $oDB->addColumn('documents',$column_name,'text');
                }
            }

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

            /**
             * 2007. 10. 11 : 관리자 페이지의 기본 설정 Action 추가, 게시글 관리 action 추가
             **/
            if(!$oModuleModel->getActionForward('dispDocumentAdminConfig')) 
                $oModuleController->insertActionForward('document', 'view', 'dispDocumentAdminConfig');
            if(!$oModuleModel->getActionForward('dispDocumentAdminManageDocument')) 
                $oModuleController->insertActionForward('document', 'view', 'dispDocumentAdminManageDocument');

            // 2007. 10. 17 모듈이 삭제될때 등록된 글도 모두 삭제하는 트리거 추가
            if(!$oModuleModel->getTrigger('module.deleteModule', 'document', 'controller', 'triggerDeleteModuleDocuments', 'after')) 
                $oModuleController->insertTrigger('module.deleteModule', 'document', 'controller', 'triggerDeleteModuleDocuments', 'after');

            /**
             * 2007. 10. 18 : 관리자 페이지의 신고된 목록 보기 action 추가
             **/
            if(!$oModuleModel->getActionForward('dispDocumentAdminDeclared')) 
                $oModuleController->insertActionForward('document', 'view', 'dispDocumentAdminDeclared');

            // 2007. 10. 25 문서 분류에 parent_srl, expand를 추가
            if(!$oDB->isColumnExists("document_categories","parent_srl")) $oDB->addColumn('document_categories',"parent_srl","number",12,0);
            if(!$oDB->isColumnExists("document_categories","expand")) $oDB->addColumn('document_categories',"expand","char",1,"N");
            if(!$oDB->isColumnExists("document_categories","group_srls")) $oDB->addColumn('document_categories',"group_srls","text");

            // 2007. 11. 20 게시글에 module_srl + is_notice 복합인덱스 만들기
            if(!$oDB->isIndexExists("documents","idx_module_notice")) $oDB->addIndex("documents","idx_module_notice", array("module_srl","is_notice"));

            return new Object(0,'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
        }
    }
?>
