<?php
    /**
     * @class  menu
     * @author zero (zero@nzeo.com)
     * @brief  menu 모듈의 high class
     **/

    class menu extends ModuleObject {

        /**
         * @brief 설치시 추가 작업이 필요할시 구현
         **/
        function moduleInstall() {
            // 메뉴 사용할 디렉토리 생성
            FileHandler::makeDir('./files/cache/menu');

            return new Object();
        }

        /**
         * @brief 설치가 이상이 없는지 체크하는 method
         **/
        function checkUpdate() {
            $oDB = &DB::getInstance();

            // 2009. 02. 11 menu 테이블에 site_srl 추가
            if(!$oDB->isColumnExists('menu', 'site_srl')) return true;

            return false;
        }

        /**
         * @brief 업데이트 실행
         **/
        function moduleUpdate() {
            $oDB = &DB::getInstance();

            // 2009. 02. 11 menu 테이블에 site_srl 추가
            if(!$oDB->isColumnExists('menu', 'site_srl')) {
                $oDB->addColumn('menu','site_srl','number',11,0,true);
            }

            return new Object(0, 'success_updated');
        }

        /**
         * @brief 캐시 파일 재생성
         **/
        function recompileCache() {
            // 메뉴 모듈의 캐시 파일 모두 삭제
            FileHandler::removeFilesInDir("./files/cache/menu");

            $oMenuAdminController = &getAdminController('menu');

            // 블로그 모듈 목록을 모두 구함
            $output = executeQueryArray("menu.getMenus");
            $list = $output->data;
            if(!count($list)) return;

            // 메뉴 모듈에서 사용되는 모든 메뉴 목록을 재 생성
            foreach($list as $menu_item) {
                $menu_srl = $menu_item->menu_srl;
                $oMenuAdminController->makeXmlFile($menu_srl);
            }
        }
    }
?>
