<?php
    /**
     * @class  point
     * @author zero (zero@nzeo.com)
     * @brief  point모듈의 high class
     **/

    class point extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('point', 'view', 'dispPointAdminConfig');
            $oModuleController->insertActionForward('point', 'view', 'dispPointAdminModuleConfig');
            $oModuleController->insertActionForward('point', 'view', 'dispPointAdminActConfig');
            $oModuleController->insertActionForward('point', 'view', 'dispPointAdminPointList');

            // 포인트 정보를 기록할 디렉토리 생성
            FileHandler::makeDir('./files/member_extra_info/point');

            $oModuleController = &getController('module');

            // 최고레벨
            $config->max_level = 30;

            // 레벨별 점수
            for($i=1;$i<=30;$i++) {
                $config->level_step[$i] = pow($i,2)*90;
            }

            // 회원가입
            $config->signup_point = 10;

            // 로그인 가입
            $config->login_point = 5;

            // 포인트 호칭
            $config->point_name = 'point';

            // 레벨 아이콘 디렉토리
            $config->level_icon = "default";

            // 점수가 없을때 다운로드 금지 기능
            $config->disable_download = false;

            /**
             * 모듈별 기본 점수 및 각 action 정의 (게시판,블로그외에 어떤 모듈이 생길지 모르니 act값을 명시한다
             **/

            // 글작성
            $config->insert_document = 10;

            $config->insert_document_act = 'procBoardInsertDocument';
            $config->delete_document_act = 'procBoardDeleteDocument';

            // 댓글작성
            $config->insert_comment = 5;

            $config->insert_comment_act = 'procBoardInsertComment,procBlogInsertComment';
            $config->delete_comment_act = 'procBoardDeleteComment,procBlogDeleteComment';

            // 업로드
            $config->upload_file = 5;

            $config->upload_file_act = 'procFileUpload';
            $config->delete_file_act = 'procFileDelete';

            // 다운로드
            $config->download_file = -5;
            $config->download_file_act = 'procFileDownload';

            // 설정 저장
            $oModuleController->insertModuleConfig('point', $config);

            // 빠른 실행을 위해서 act list를 캐싱
            $oPointController = &getAdminController('point');
            $oPointController->cacheActList();

            // 가입/글작성/댓글작성/파일업로드/다운로드에 대한 트리거 추가
            $oModuleController->insertTrigger('member.insertMember', 'point', 'controller', 'triggerInsertMember', 'after');
            $oModuleController->insertTrigger('document.insertDocument', 'point', 'controller', 'triggerInsertDocument', 'after');
            $oModuleController->insertTrigger('document.deleteDocument', 'point', 'controller', 'triggerDeleteDocument', 'after');
            $oModuleController->insertTrigger('comment.insertComment', 'point', 'controller', 'triggerInsertComment', 'after');
            $oModuleController->insertTrigger('comment.deleteComment', 'point', 'controller', 'triggerDeleteComment', 'after');
            $oModuleController->insertTrigger('file.insertFile', 'point', 'controller', 'triggerInsertFile', 'after');
            $oModuleController->insertTrigger('file.deleteFile', 'point', 'controller', 'triggerDeleteFile', 'after');
            $oModuleController->insertTrigger('file.downloadFile', 'point', 'controller', 'triggerBeforeDownloadFile', 'before');
            $oModuleController->insertTrigger('file.downloadFile', 'point', 'controller', 'triggerDownloadFile', 'after');
            $oModuleController->insertTrigger('member.doLogin', 'point', 'controller', 'triggerAfterLogin', 'after');
            $oModuleController->insertTrigger('module.dispAdditionSetup', 'point', 'view', 'triggerDispPointAdditionSetup', 'after');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            // point 모듈 정보 가져옴
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModuleConfig('point');

            // 가입/글작성/댓글작성/파일업로드/다운로드에 대한 트리거 추가
            if(!$oModuleModel->getTrigger('member.insertMember', 'point', 'controller', 'triggerInsertMember', 'after')) return true;
            if(!$oModuleModel->getTrigger('document.insertDocument', 'point', 'controller', 'triggerInsertDocument', 'after')) return true;
            if(!$oModuleModel->getTrigger('document.deleteDocument', 'point', 'controller', 'triggerDeleteDocument', 'after')) return true;
            if(!$oModuleModel->getTrigger('comment.insertComment', 'point', 'controller', 'triggerInsertComment', 'after')) return true;
            if(!$oModuleModel->getTrigger('comment.deleteComment', 'point', 'controller', 'triggerDeleteComment', 'after')) return true;
            if(!$oModuleModel->getTrigger('file.insertFile', 'point', 'controller', 'triggerInsertFile', 'after')) return true;
            if(!$oModuleModel->getTrigger('file.deleteFile', 'point', 'controller', 'triggerDeleteFile', 'after')) return true;
            if(!$oModuleModel->getTrigger('file.downloadFile', 'point', 'controller', 'triggerBeforeDownloadFile', 'before')) return true;
            if(!$oModuleModel->getTrigger('file.downloadFile', 'point', 'controller', 'triggerDownloadFile', 'after')) return true;
            if(!$oModuleModel->getTrigger('member.doLogin', 'point', 'controller', 'triggerAfterLogin', 'after')) return true;
            if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'point', 'view', 'triggerDispPointAdditionSetup', 'after')) return true;

            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            // point 모듈 정보 가져옴
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');

            $config = $oModuleModel->getModuleConfig('point');

            // 가입/글작성/댓글작성/파일업로드/다운로드에 대한 트리거 추가
            if(!$oModuleModel->getTrigger('member.insertMember', 'point', 'controller', 'triggerInsertMember', 'after')) 
                $oModuleController->insertTrigger('member.insertMember', 'point', 'controller', 'triggerInsertMember', 'after');
            if(!$oModuleModel->getTrigger('document.insertDocument', 'point', 'controller', 'triggerInsertDocument', 'after')) 
                $oModuleController->insertTrigger('document.insertDocument', 'point', 'controller', 'triggerInsertDocument', 'after');
            if(!$oModuleModel->getTrigger('document.deleteDocument', 'point', 'controller', 'triggerDeleteDocument', 'after')) 
                $oModuleController->insertTrigger('document.deleteDocument', 'point', 'controller', 'triggerDeleteDocument', 'after');
            if(!$oModuleModel->getTrigger('comment.insertComment', 'point', 'controller', 'triggerInsertComment', 'after')) 
                $oModuleController->insertTrigger('comment.insertComment', 'point', 'controller', 'triggerInsertComment', 'after');
            if(!$oModuleModel->getTrigger('comment.deleteComment', 'point', 'controller', 'triggerDeleteComment', 'after')) 
                $oModuleController->insertTrigger('comment.deleteComment', 'point', 'controller', 'triggerDeleteComment', 'after');
            if(!$oModuleModel->getTrigger('file.insertFile', 'point', 'controller', 'triggerInsertFile', 'after')) 
                $oModuleController->insertTrigger('file.insertFile', 'point', 'controller', 'triggerInsertFile', 'after');
            if(!$oModuleModel->getTrigger('file.deleteFile', 'point', 'controller', 'triggerDeleteFile', 'after')) 
                $oModuleController->insertTrigger('file.deleteFile', 'point', 'controller', 'triggerDeleteFile', 'after');
            if(!$oModuleModel->getTrigger('file.downloadFile', 'point', 'controller', 'triggerBeforeDownloadFile', 'before')) 
                $oModuleController->insertTrigger('file.downloadFile', 'point', 'controller', 'triggerBeforeDownloadFile', 'before');
            if(!$oModuleModel->getTrigger('file.downloadFile', 'point', 'controller', 'triggerDownloadFile', 'after')) 
                $oModuleController->insertTrigger('file.downloadFile', 'point', 'controller', 'triggerDownloadFile', 'after');
            if(!$oModuleModel->getTrigger('member.doLogin', 'point', 'controller', 'triggerAfterLogin', 'after'))
                $oModuleController->insertTrigger('member.doLogin', 'point', 'controller', 'triggerAfterLogin', 'after');
            if(!$oModuleModel->getTrigger('module.dispAdditionSetup', 'point', 'view', 'triggerDispPointAdditionSetup', 'after')) 
                $oModuleController->insertTrigger('module.dispAdditionSetup', 'point', 'view', 'triggerDispPointAdditionSetup', 'after');

            return new Object(0, 'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
            // point action 파일 재정의
            $oPointAdminController = &getAdminController('point');
            $oPointAdminController->cacheActList();

        }
    }
?>
