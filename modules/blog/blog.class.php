<?php
    /**
     * @class  blog
     * @author zero (zero@nzeo.com)
     * @brief  blog 모듈의 high class
     **/

    class blog extends ModuleObject {

        var $skin = "default"; ///< 스킨 이름
        var $list_count = 1; ///< 한 페이지에 나타날 글의 수
        var $page_count = 10; ///< 페이지의 수

        var $editor = 'default'; ///< 에디터 종류

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('blog', 'view', 'dispBlogAdminContent');
            $oModuleController->insertActionForward('blog', 'view', 'dispBlogAdminBlogInfo');
            $oModuleController->insertActionForward('blog', 'view', 'dispBlogAdminBlogAdditionSetup');
            $oModuleController->insertActionForward('blog', 'view', 'dispBlogAdminInsertBlog');
            $oModuleController->insertActionForward('blog', 'view', 'dispBlogAdminDeleteBlog');
            $oModuleController->insertActionForward('blog', 'view', 'dispBlogAdminSkinInfo');
            $oModuleController->insertActionForward('blog', 'view', 'dispBlogAdminCategoryInfo');
            $oModuleController->insertActionForward('blog', 'view', 'dispBlogAdminMenuInfo');
            $oModuleController->insertActionForward('blog', 'view', 'dispBlogAdminGrantInfo');
            $oModuleController->insertActionForward('blog', 'controller', 'procBlogAdminUpdateSkinInfo');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oModuleModel = &getModel('module');

            /**
             * 2007. 10. 17 : 게시판 모듈설정에 추가 설정 액션 설정
             **/
            if(!$oModuleModel->getActionForward('dispBlogAdminBlogAdditionSetup')) return true;

            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');

            /**
             * 2007. 10. 17 : 게시판 모듈설정에 추가 설정 액션 설정
             **/
            if(!$oModuleModel->getActionForward('dispBlogAdminBlogAdditionSetup'))
                $oModuleController->insertActionForward('blog', 'view', 'dispBlogAdminBlogAdditionSetup');

            return new Object(0, 'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
            // 블로그 모듈의 캐시 파일 모두 삭제
            FileHandler::removeDir("./files/cache/blog_category");

            $oModuleModel = &getModel('module');
            $oDocumentController = &getController('document');

            // 블로그 모듈 목록을 모두 구함
            $args->module = 'blog';
            $output = executeQueryArray("module.getMidList", $args);
            $list = $output->data;
            if(!count($list)) return;

            // 블로그 모듈에서 사용되는 모든 메뉴 목록을 재 생성
            foreach($list as $blog_item) {
                $module_srl = $blog_item->module_srl;
                $oDocumentController->makeCategoryFile($module_srl);
            }

        }
    }
?>
