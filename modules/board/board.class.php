<?php
    /**
     * @class  board
     * @author zero (zero@nzeo.com)
     * @brief  board 모듈의 high class
     **/

    class board extends ModuleObject {

        var $search_option = array('title','content','title_content','user_name','nick_name','user_id','tag'); ///< 검색 옵션

        var $skin = "default"; ///< 스킨 이름
        var $list_count = 20; ///< 한 페이지에 나타날 글의 수
        var $page_count = 10; ///< 페이지의 수
        var $category_list = NULL; ///< 카테고리 목록

        var $editor = 'default'; ///< 에디터 종류

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // action forward에 등록 (관리자 모드에서 사용하기 위함)
            $oModuleController = &getController('module');
            $oModuleController->insertActionForward('board', 'view', 'dispBoardAdminContent');
            $oModuleController->insertActionForward('board', 'view', 'dispBoardAdminBoardInfo');
            $oModuleController->insertActionForward('board', 'view', 'dispBoardAdminInsertBoard');
            $oModuleController->insertActionForward('board', 'view', 'dispBoardAdminDeleteBoard');
            $oModuleController->insertActionForward('board', 'view', 'dispBoardAdminSkinInfo');
            $oModuleController->insertActionForward('board', 'view', 'dispBoardAdminCategoryInfo');
            $oModuleController->insertActionForward('board', 'view', 'dispBoardAdminGrantInfo');
            $oModuleController->insertActionForward('board', 'controller', 'procBoardAdminUpdateSkinInfo');

            // 기본 게시판 생성
            $output = executeQuery('module.getDefaultMidInfo');
            if($output->data) return new Object();

            // 기본 모듈을 찾음
            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByMid();

            // 기본 모듈이 없으면 새로 등록
            if(!$module_info->module_srl)  {
                $args->board_name = 'board';
                $args->browser_title = 'test module';
                $args->is_default = 'Y';
                $args->skin = 'xe_list';

                // board 라는 이름의 모듈이 있는지 확인
                $module_info = $oModuleModel->getModuleInfoByMid($args->board_name);
                if($module_info->module_srl) $args->module_srl = $module_info->module_srl;
                else $args->module_srl = 0;

                // 게시판 controller 생성
                $oBoardController = &getAdminController('board');
                $oBoardController->procBoardAdminInsertBoard($args);
            }

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            return new Object();
        }

    }
?>
