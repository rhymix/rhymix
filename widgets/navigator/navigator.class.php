<?php
    /**
     * @class navigator 
     * @author zero (zero@nzeo.com)
     * @brief 메뉴 출력기
     * @version 0.1
     **/

    class navigator extends WidgetHandler {

        /**
         * @brief 위젯의 실행 부분
         *
         * ./widgets/위젯/conf/info.xml 에 선언한 extra_vars를 args로 받는다
         * 결과를 만든후 print가 아니라 return 해주어야 한다
         **/
        function proc($args) {
            $oModuleModel = &getModel('module');

            // $args->menu_srl이 지정되어 있으면 해당 메뉴로, 그렇지 않다면 현재 레이아웃의 메뉴를 구함
            if(!$args->menu_srl) {
                $current_module_info = Context::get('current_module_info');
                $args->layout_srl = $current_module_info->layout_srl;

                $oLayoutModel = &getModel('layout');
                $layout_info = $oLayoutModel->getLayout($current_module_info->layout_srl);
                if(!$layout_info) return;

                if($layout_info->extra_var_count) {
                    foreach($layout_info->extra_var as $var_id => $val) {
                        $layout_info->{$var_id} = $val->value;
                    }
                    if(!$layout_info->menu_count) return;

                    // 레이아웃 정보중 menu를 Context::set
                    foreach($layout_info->menu as $menu_id => $menu) {
                        if(file_exists($menu->php_file)) {
                            $args->menu_srl = $menu->menu_srl;
                            @include($menu->php_file);
                        }
                        break;
                    }
                } else return;
            } else {
                $php_file = sprintf('%sfiles/cache/menu/%d.php', _XE_PATH_, $args->menu_srl);
                @include($php_file);
            }
            if(!$menu) return;

            // 시작 depth가 2이상, 즉 상위 메뉴 선택 이후 하위 메뉴 출력시 처리
            if($args->start_depth == 2 && count($menu->list)) {
                $t_menu = null;
                foreach($menu->list as $key => $val) {
                    if($val['selected']) {
                        $t_menu->list = $val['list'];
                        break;
                    }
                }
                $menu = $t_menu;
            }

            $widget_info->menu = $menu->list;

            $this->_arrangeMenu($arranged_list, $menu->list, 0);
            $widget_info->arranged_menu = $arranged_list;

            // men XML 파일
            $widget_info->xml_file = sprintf('%sfiles/cache/menu/%d.xml.php',getUrl(''), $args->menu_srl);
            $widget_info->menu_srl = $args->menu_srl;

            if($this->selected_node_srl) $widget_info->selected_node_srl = $this->selected_node_srl;
            Context::set('widget_info', $widget_info);

            // 템플릿 컴파일
            $tpl_path = sprintf('%sskins/%s', $this->widget_path, $args->skin);
            $tpl_file = 'navigator';

            Context::set('colorset', $args->colorset);

            $oTemplate = &TemplateHandler::getInstance();
            return $oTemplate->compile($tpl_path, $tpl_file);
        }

        /**
         * @brief 메뉴를 1차원 배열로 변경
         **/
        function _arrangeMenu(&$menu, $list, $depth) {
            if(!count($list)) return;
            $idx = 0;
            $list_order = array();
            foreach($list as $key => $val) {
                if(!$val['text']) continue;
                $obj = null;
                $obj->href = $val['href'];
                $obj->url = $val['url'];
                $obj->node_srl = $val['node_srl'];
                $obj->parent_srl = $val['parent_srl'];
                $obj->title = $obj->text = $val['text'];
                $obj->expand = $val['expand']=='Y'?true:false;
                $obj->depth = $depth;
                $obj->selected = $val['selected'];
                $obj->child_count = 0;
                $obj->childs = array();

                if(Context::get('mid') == $obj->url){
                    $selected = true;
                    $this->selected_node_srl = $obj->node_srl;
                    $obj->selected = true;
                }else{
                    $selected = false;
                }

                $list_order[$idx++] = $obj->node_srl;

                // 부모 카테고리가 있으면 자식노드들의 데이터를 적용
                if($obj->parent_srl) {

                    $parent_srl = $obj->parent_srl;
                    $expand = $obj->expand;
                    if($selected) $expand = true;

                    while($parent_srl) {
                        $menu[$parent_srl]->childs[] = $obj->node_srl;
                        $menu[$parent_srl]->child_count = count($menu[$parent_srl]->childs);
                        if($expand) $menu[$parent_srl]->expand = $expand;

                        $parent_srl = $menu[$parent_srl]->parent_srl;
                    }
                }

                $menu[$key] = $obj;

                if(count($val['list'])) $this->_arrangeMenu($menu, $val['list'], $depth+1);
            }
            $menu[$list_order[0]]->first = true;
            $menu[$list_order[count($list_order)-1]]->last = true;
        }
    }
?>
