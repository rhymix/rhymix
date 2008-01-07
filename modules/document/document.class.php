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

            /**
             * 2007. 12. 03 : 확장변수(extra_vars) 컬럼이 없을 경우 추가
             **/
            if(!$oDB->isColumnExists("documents","extra_vars")) return true;


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

            /**
             * 2007. 12. 03 : 확장변수(extra_vars) 컬럼이 없을 경우 추가
             **/
            if(!$oDB->isColumnExists("documents","extra_vars")) $oDB->addColumn('documents','extra_vars','text');

            return new Object(0,'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
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
            $user_id = $logged_info->user_id;

            // 모듈 요청에 사용된 변수들을 가져옴
            $args = Context::getRequestVars();

            // act의 값에 따라서 관리 권한 체크
            switch($args->act) {
                // 게시글 목록에서 글을 체크하는 경우 해당 글의 모듈 정보를 구해서 관리자 여부를 체크
                case 'procDocumentAdminAddCart' :
                        if(!$args->srl) return false;

                        $oModuleModel = &getModel('module');
                        $module_info = $oModuleModel->getModuleInfoByDocumentSrl($args->srl);
                        if(!$module_info) return false;

                        if(is_array($module_info->admin_id) && in_array($user_id, $module_info->admin_id)) return true;
                    break;

                // 체크된 게시글을 관리하는 action
                case 'dispDocumentAdminManageDocument' :
                        // 세션 정보에 게시글이 담겨 있으면 return true 해줌
                        $flag_list = $_SESSION['document_management'];
                        if(count($flag_list)) return true;
                    break;

                // 체크된 게시글을 다른 모듈로 이동 또는 복사, 삭제 할때
                case 'procDocumentAdminManageCheckedDocument' :
                        switch($args->type) {
                            // 이동과 복사의 경우에는 대상 모듈의 정보를 체크
                            case 'move' :
                            case 'copy' :
                                    if($args->target_module) {

                                        $oModuleModel = &getModel('module');
                                        $module_info = $oModuleModel->getModuleInfoByModuleSrl($args->target_module);
                                        if(!$module_info) return false;

                                        if(is_array($module_info->admin_id) && in_array($user_id, $module_info->admin_id)) return true;
                                    }
                                break;


                            // 삭제일 경우는 세션에 저장된 글이 있으면 return true
                            case 'delete' :
                                    $flag_list = $_SESSION['document_management'];
                                    if(count($flag_list)) return true;
                                break;
                        }
                    break;

            }
            return false;
        }
    }
?>
