<?php
    /**
     * @class  menuAdminModel
     * @author zero (zero@nzeo.com)
     * @version 0.1
     * @brief  menu 모듈의 admin model class
     **/

    class menuAdminModel extends menu {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 전체 메뉴 목록을 구해옴
         **/
        function getMenuList($obj) {

            $args->sort_index = $obj->sort_index;
            $args->page = $obj->page?$obj->page:1;
            $args->list_count = $obj->list_count?$obj->list_count:20;
            $args->page_count = $obj->page_count?$obj->page_count:10;

            // document.getDocumentList 쿼리 실행
            $output = executeQuery('menu.getMenuList', $args);

            // 결과가 없거나 오류 발생시 그냥 return
            if(!$output->toBool()||!count($output->data)) return $output;

            return $output;
        }

        /**
         * @brief 등록된 모든 메뉴를 return
         **/
        function getMenus() {
            // 일단 DB에서 정보를 가져옴
            $args->menu_srl = $menu_srl;
            $output = executeQuery('menu.getMenus', $args);
            if(!$output->data) return;
            $menus = $output->data;
            if(!is_array($menus)) $menus = array($menus);
            return $menus;
        }

        /**
         * @brief DB 에 생성된 한개의 메뉴 정보를 구함
         * 생성된 메뉴의 DB정보+XML정보를 return
         **/
        function getMenu($menu_srl) {
            // 일단 DB에서 정보를 가져옴
            $args->menu_srl = $menu_srl;
            $output = executeQuery('menu.getMenu', $args);
            if(!$output->data) return;
            
            $menu_info = $output->data;
            $menu_info->xml_file = sprintf('./files/cache/menu/%s.xml.php',$menu_srl);
            $menu_info->php_file = sprintf('./files/cache/menu/%s.php',$menu_srl);
            return $menu_info;
        }

        /**
         * @brief 특정 menu_srl의 아이템 정보를 return
         * 이 정보중에 group_srls의 경우는 , 로 연결되어 들어가며 사용시에는 explode를 통해 array로 변환 시킴
         **/
        function getMenuItemInfo($menu_item_srl) {
            // menu_item_srl이 있으면 해당 메뉴의 정보를 가져온다
            $args->menu_item_srl = $menu_item_srl;
            $output = executeQuery('menu.getMenuItem', $args);
            $node = $output->data;
            if($node->group_srls) $node->group_srls = explode(',',$node->group_srls);
            else $node->group_srls = array();
            return $node;
        }

        /**
         * @brief 특정 menu_srl의 정보를 이용하여 템플릿을 구한후 return
         * 관리자 페이지에서 특정 메뉴의 정보를 추가하기 위해 서버에서 tpl을 컴파일 한후 컴파일 된 html을 직접 return
         **/
        function getMenuAdminTplInfo() {
            // 해당 메뉴의 정보를 가져오기 위한 변수 설정
            $menu_item_srl = Context::get('menu_item_srl');
            $parent_srl = Context::get('parent_srl');

            // 회원 그룹의 목록을 가져옴
            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups();
            Context::set('group_list', $group_list);

            // parent_srl이 있고 menu_item_srl이 없으면 하부 메뉴 추가임
            if(!$menu_item_srl && $parent_srl) {
                // 상위 메뉴의 정보를 가져옴
                $parent_info = $this->getMenuItemInfo($parent_srl);

                // 추가하려는 메뉴의 기본 변수 설정 
                $item_info->menu_item_srl = getNextSequence();
                $item_info->parent_srl = $parent_srl;
                $item_info->parent_menu_name = $parent_info->name;

            // root에 메뉴 추가하거나 기존 메뉴의 수정일 경우
            } else {
                // menu_item_srl 이 있으면 해당 메뉴의 정보를 가져온다
                if($menu_item_srl) $item_info = $this->getMenuItemInfo($menu_item_srl);

                // 찾아진 값이 없다면 신규 메뉴 추가로 보고 menu_item_srl값만 구해줌
                if(!$item_info->menu_item_srl) {
                    $item_info->menu_item_srl = getNextSequence();
                }
            }

            Context::set('item_info', $item_info);

            // template 파일을 직접 컴파일한후 tpl변수에 담아서 return한다.
            $oTemplate = &TemplateHandler::getInstance();
            $tpl = $oTemplate->compile($this->module_path.'tpl', 'menu_item_info');

            // return 할 변수 설정
            $this->add('tpl', $tpl);
        }

    }
?>
