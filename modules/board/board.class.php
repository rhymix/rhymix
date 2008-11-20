<?php
    /**
     * @class  board
     * @author zero (zero@nzeo.com)
     * @brief  board 모듈의 high class
     **/

    class board extends ModuleObject {

        var $search_option = array('title','content','title_content','comment','user_name','nick_name','user_id','tag'); ///< 검색 옵션

        var $order_target = array('list_order', 'update_order', 'regdate', 'voted_count', 'readed_count', 'comment_count', 'title'); // 정렬 옵션

        var $skin = "default"; ///< 스킨 이름
        var $list_count = 20; ///< 한 페이지에 나타날 글의 수
        var $page_count = 10; ///< 페이지의 수
        var $category_list = NULL; ///< 카테고리 목록


        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('board', 'view', 'dispBoardAdminContent');
            $oModuleController->insertActionForward('board', 'view', 'dispBoardTagList');
            $oModuleController->insertActionForward('board', 'view', 'dispBoardAdminBoardInfo');
            $oModuleController->insertActionForward('board', 'view', 'dispBoardAdminInsertBoard');
            $oModuleController->insertActionForward('board', 'view', 'dispBoardAdminDeleteBoard');
            $oModuleController->insertActionForward('board', 'view', 'dispBoardAdminSkinInfo');
            $oModuleController->insertActionForward('board', 'view', 'dispBoardAdminCategoryInfo');
            $oModuleController->insertActionForward('board', 'view', 'dispBoardAdminGrantInfo');
            $oModuleController->insertActionForward('board', 'view', 'dispBoardAdminBoardAdditionSetup');
            $oModuleController->insertActionForward('board', 'controller', 'procBoardAdminUpdateSkinInfo');

            // 2007. 10. 17 아이디 클릭시 나타나는 팝업메뉴에 작성글 보기 기능 추가
            $oModuleController->insertTrigger('member.getMemberMenu', 'board', 'controller', 'triggerMemberMenu', 'after');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oModuleModel = &getModel('module');

            // 2007. 10. 17 아이디 클릭시 나타나는 팝업메뉴에 작성글 보기 기능 추가
            if(!$oModuleModel->getTrigger('member.getMemberMenu', 'board', 'controller', 'triggerMemberMenu', 'after')) return true;

            /**
             * 2007. 10. 17 : 게시판 모듈설정에 추가 설정 액션 설정
             **/
            if(!$oModuleModel->getActionForward('dispBoardAdminBoardAdditionSetup')) return true;

            /**
             * 2007. 11. 27 : 태그 목록 보기 액션 설정
             **/
            if(!$oModuleModel->getActionForward('dispBoardTagList')) return true;

            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');

            // 2007. 10. 17 아이디 클릭시 나타나는 팝업메뉴에 작성글 보기 기능 추가
            if(!$oModuleModel->getTrigger('member.getMemberMenu', 'board', 'controller', 'triggerMemberMenu', 'after'))
                $oModuleController->insertTrigger('member.getMemberMenu', 'board', 'controller', 'triggerMemberMenu', 'after');

            /**
             * 2007. 10. 17 : 게시판 모듈설정에 추가 설정 액션 설정
             **/
            if(!$oModuleModel->getActionForward('dispBoardAdminBoardAdditionSetup'))
                $oModuleController->insertActionForward('board', 'view', 'dispBoardAdminBoardAdditionSetup');

            /**
             * 2007. 11. 27 : 태그 목록 보기 액션 설정
             **/
            if(!$oModuleModel->getActionForward('dispBoardTagList')) 
                $oModuleController->insertActionForward('board', 'view', 'dispBoardTagList');

            return new Object(0, 'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
        }

        /**
         * @brief Action중 Admin이 들어갔을 경우 권한 체크
         **/
        function checkAdminActionGrant() {
            if(!Context::get('is_logged')) return false;

            $logged_info = Context::get('logged_info');
            if($logged_info->is_admin=='Y') return true;

            $actions = array('getBoardAdminCategoryTplInfo','dispBoardAdminCategoryInfo','procBoardAdminInsertCategory','procBoardAdminUpdateCategory','procBoardAdminDeleteCategory','procBoardAdminMoveCategory');
            if(!in_array($this->act, $actions)) return false;

            $oModuleModel = &getModel('module');
            if($oModuleModel->isSiteAdmin()) return true;

            return false;
        }
    }
?>
