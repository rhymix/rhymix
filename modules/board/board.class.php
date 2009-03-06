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

            // 2007. 10. 17 아이디 클릭시 나타나는 팝업메뉴에 작성글 보기 기능 추가
            $oModuleController->insertTrigger('member.getMemberMenu', 'board', 'controller', 'triggerMemberMenu', 'after');

            // 기본 게시판 생성
            $output = executeQuery('module.getDefaultMidInfo');
            if($output->data) return new Object();

            // 기본 모듈을 찾음
            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getDefaultMid();

            // 기본 모듈이 없으면 새로 등록
            if(!$module_info->module_srl) {
                $args->mid = 'board';
                $args->module = 'board';
                $args->browser_title = 'test module';
                $args->is_default = 'Y';
                $args->skin = 'xe_default';
                $args->site_srl = 0;

                // board 라는 이름의 모듈이 있는지 확인
                $module_info = $oModuleModel->getModuleInfoByMid($args->board_name);
                if($module_info->module_srl) {
                    $args->module_srl = $module_info->module_srl;
                    $oModuleController->updateModule($args);
                } else {
                    $args->module_srl = 0;
                    $oModuleController->insertModule($args);
                }
            }

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oModuleModel = &getModel('module');

            // 2007. 10. 17 아이디 클릭시 나타나는 팝업메뉴에 작성글 보기 기능 추가
            if(!$oModuleModel->getTrigger('member.getMemberMenu', 'board', 'controller', 'triggerMemberMenu', 'after')) return true;
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

            return new Object(0, 'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
        }

    }
?>
