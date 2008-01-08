<?php
    /**
     * @class  file
     * @author zero (zero@nzeo.com)
     * @brief  file 모듈의 high 클래스
     **/

    class file extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('file', 'view', 'dispFileAdminList');
            $oModuleController->insertActionForward('file', 'view', 'dispFileAdminConfig');
            $oModuleController->insertActionForward('file', 'controller', 'procFileUpload');
            $oModuleController->insertActionForward('file', 'controller', 'procFileDelete');
            $oModuleController->insertActionForward('file', 'controller', 'procFileDownload');
            
            // 첨부파일의 기본 설정 저장
            $config->allowed_filesize = '2';
            $config->allowed_attach_size = '2';
            $config->allowed_filetypes = '*.*';
            $oModuleController->insertModuleConfig('file', $config);

            // file 모듈에서 사용할 디렉토리 생성
            FileHandler::makeDir('./files/attach/images');
            FileHandler::makeDir('./files/attach/binaries');

            // 2007. 10. 17 글/댓글의 입력/수정/삭제에 대한 trigger 등록
            $oModuleController->insertTrigger('document.insertDocument', 'file', 'controller', 'triggerCheckAttached', 'before');
            $oModuleController->insertTrigger('document.insertDocument', 'file', 'controller', 'triggerAttachFiles', 'after');
            $oModuleController->insertTrigger('document.updateDocument', 'file', 'controller', 'triggerCheckAttached', 'before');
            $oModuleController->insertTrigger('document.updateDocument', 'file', 'controller', 'triggerAttachFiles', 'after');
            $oModuleController->insertTrigger('document.deleteDocument', 'file', 'controller', 'triggerDeleteAttached', 'after');
            $oModuleController->insertTrigger('comment.insertComment', 'file', 'controller', 'triggerCommentCheckAttached', 'before');
            $oModuleController->insertTrigger('comment.insertComment', 'file', 'controller', 'triggerCommentAttachFiles', 'after');
            $oModuleController->insertTrigger('comment.updateComment', 'file', 'controller', 'triggerCommentCheckAttached', 'before');
            $oModuleController->insertTrigger('comment.updateComment', 'file', 'controller', 'triggerCommentAttachFiles', 'after');
            $oModuleController->insertTrigger('comment.deleteComment', 'file', 'controller', 'triggerCommentDeleteAttached', 'after');

            // 2007. 10. 17 모듈이 삭제될때 등록된 첨부파일도 모두 삭제하는 트리거 추가
            $oModuleController->insertTrigger('module.deleteModule', 'file', 'controller', 'triggerDeleteModuleFiles', 'after');

            // 2007. 10. 19 출력하기 전에 file 권한등을 세팅하는 트리거 호출
            $oModuleController->insertTrigger('module.dispAdditionSetup', 'file', 'view', 'triggerDispFileAdditionSetup', 'before');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oModuleModel = &getModel('module');

            // 2007. 10. 17 글/댓글의 입력/수정/삭제에 대한 trigger 등록
            if(!$oModuleModel->getTrigger('document.insertDocument', 'file', 'controller', 'triggerCheckAttached', 'before')) return true;
            if(!$oModuleModel->getTrigger('document.insertDocument', 'file', 'controller', 'triggerAttachFiles', 'after')) return true;
            if(!$oModuleModel->getTrigger('document.updateDocument', 'file', 'controller', 'triggerCheckAttached', 'before')) return true;
            if(!$oModuleModel->getTrigger('document.updateDocument', 'file', 'controller', 'triggerAttachFiles', 'after')) return true;
            if(!$oModuleModel->getTrigger('document.deleteDocument', 'file', 'controller', 'triggerDeleteAttached', 'after')) return true;
            if(!$oModuleModel->getTrigger('comment.insertComment', 'file', 'controller', 'triggerCommentCheckAttached', 'before')) return true;
            if(!$oModuleModel->getTrigger('comment.insertComment', 'file', 'controller', 'triggerCommentAttachFiles', 'after')) return true;
            if(!$oModuleModel->getTrigger('comment.updateComment', 'file', 'controller', 'triggerCommentCheckAttached', 'before')) return true;
            if(!$oModuleModel->getTrigger('comment.updateComment', 'file', 'controller', 'triggerCommentAttachFiles', 'after')) return true;
            if(!$oModuleModel->getTrigger('comment.deleteComment', 'file', 'controller', 'triggerCommentDeleteAttached', 'after')) return true;

            // 2007. 10. 17 모듈이 삭제될때 등록된 첨부파일도 모두 삭제하는 트리거 추가
            if(!$oModuleModel->getTrigger('module.deleteModule', 'file', 'controller', 'triggerDeleteModuleFiles', 'after')) return true;

            // 2007. 10. 19 출력하기 전에 file 권한등을 세팅하는 트리거 호출
            if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'file', 'view', 'triggerDispFileAdditionSetup', 'before')) return true;

            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');

            // 2007. 10. 17 글/댓글의 입력/수정/삭제에 대한 trigger 등록
            if(!$oModuleModel->getTrigger('document.insertDocument', 'file', 'controller', 'triggerCheckAttached', 'before')) 
                $oModuleController->insertTrigger('document.insertDocument', 'file', 'controller', 'triggerCheckAttached', 'before');

            if(!$oModuleModel->getTrigger('document.insertDocument', 'file', 'controller', 'triggerAttachFiles', 'after')) 
                $oModuleController->insertTrigger('document.insertDocument', 'file', 'controller', 'triggerAttachFiles', 'after');
                
            if(!$oModuleModel->getTrigger('document.updateDocument', 'file', 'controller', 'triggerCheckAttached', 'before')) 
                $oModuleController->insertTrigger('document.updateDocument', 'file', 'controller', 'triggerCheckAttached', 'before');

            if(!$oModuleModel->getTrigger('document.updateDocument', 'file', 'controller', 'triggerAttachFiles', 'after')) 
                $oModuleController->insertTrigger('document.updateDocument', 'file', 'controller', 'triggerAttachFiles', 'after');

            if(!$oModuleModel->getTrigger('document.deleteDocument', 'file', 'controller', 'triggerDeleteAttached', 'after')) 
                $oModuleController->insertTrigger('document.deleteDocument', 'file', 'controller', 'triggerDeleteAttached', 'after');

            if(!$oModuleModel->getTrigger('comment.insertComment', 'file', 'controller', 'triggerCommentCheckAttached', 'before')) 
                $oModuleController->insertTrigger('comment.insertComment', 'file', 'controller', 'triggerCommentCheckAttached', 'before');

            if(!$oModuleModel->getTrigger('comment.insertComment', 'file', 'controller', 'triggerCommentAttachFiles', 'after')) 
                $oModuleController->insertTrigger('comment.insertComment', 'file', 'controller', 'triggerCommentAttachFiles', 'after');
                
            if(!$oModuleModel->getTrigger('comment.updateComment', 'file', 'controller', 'triggerCommentCheckAttached', 'before')) 
                $oModuleController->insertTrigger('comment.updateComment', 'file', 'controller', 'triggerCommentCheckAttached', 'before');

            if(!$oModuleModel->getTrigger('comment.updateComment', 'file', 'controller', 'triggerCommentAttachFiles', 'after')) 
                $oModuleController->insertTrigger('comment.updateComment', 'file', 'controller', 'triggerCommentAttachFiles', 'after');

            if(!$oModuleModel->getTrigger('comment.deleteComment', 'file', 'controller', 'triggerCommentDeleteAttached', 'after')) 
                $oModuleController->insertTrigger('comment.deleteComment', 'file', 'controller', 'triggerCommentDeleteAttached', 'after');

            // 2007. 10. 17 모듈이 삭제될때 등록된 첨부파일도 모두 삭제하는 트리거 추가
            if(!$oModuleModel->getTrigger('module.deleteModule', 'file', 'controller', 'triggerDeleteModuleFiles', 'after')) 
                $oModuleController->insertTrigger('module.deleteModule', 'file', 'controller', 'triggerDeleteModuleFiles', 'after');

            // 2007. 10. 19 출력하기 전에 file 권한등을 세팅하는 트리거 호출
            if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'file', 'view', 'triggerDispFileAdditionSetup', 'before')) 
                $oModuleController->insertTrigger('module.dispAdditionSetup', 'file', 'view', 'triggerDispFileAdditionSetup', 'before');

            return new Object(0, 'success_updated');
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

            // 모듈 요청에 사용된 변수들을 가져옴
            $args = Context::getRequestVars();

            // act의 값에 따라서 관리 권한 체크
            switch($args->act) {
                case 'procFileAdminInsertModuleConfig' :
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
