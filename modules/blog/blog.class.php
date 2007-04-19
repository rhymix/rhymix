<?php
    /**
     * @class  blog
     * @author zero (zero@nzeo.com)
     * @brief  blog 모듈의 high class
     **/

    class blog extends ModuleObject {

        var $search_option = array('title','content','title_content','user_name','user_id','tag'); ///< 검색 옵션

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
            $oModuleController->insertActionForward('blog', 'view', 'dispBlogAdminInsertBlog');
            $oModuleController->insertActionForward('blog', 'view', 'dispBlogAdminDeleteBlog');
            $oModuleController->insertActionForward('blog', 'view', 'dispBlogAdminSkinInfo');
            $oModuleController->insertActionForward('blog', 'view', 'dispBlogAdminCategoryInfo');
            $oModuleController->insertActionForward('blog', 'view', 'dispBlogAdminGrantInfo');
            $oModuleController->insertActionForward('blog', 'controller', 'procBlogAdminUpdateSkinInfo');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function moduleIsInstalled() {
            return new Object();
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            return new Object();
        }

    }
?>
