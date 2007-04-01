<?php
    /**
     * @class  layoutController
     * @author zero (zero@nzeo.com)
     * @brief  layout 모듈의 Controller class
     **/

    class layoutController extends layout {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 레이아웃 신규 생성
         * 레이아웃의 신규 생성은 제목만 받아서 layouts테이블에 입력함
         **/
        function procLayoutAdminInsert() {
            $oDB = &DB::getInstance();

            $args->layout_srl = $oDB->getNextSequence();
            $args->layout = Context::get('layout');
            $args->title = Context::get('title');

            $output = $oDB->executeQuery("layout.insertLayout", $args);
            if(!$output->toBool()) return $output;

            $this->add('layout_srl', $args->layout_srl);
        }

        /**
         * @brief 레이아웃 정보 변경
         * 생성된 레이아웃의 제목과 확장변수(extra_vars)를 적용한다
         **/
        function procLayoutAdminUpdate() {
            // module, act, layout_srl, layout, title을 제외하면 확장변수로 판단.. 좀 구리다..
            $extra_vars = Context::getRequestVars();
            unset($extra_vars->module);
            unset($extra_vars->act);
            unset($extra_vars->layout_srl);
            unset($extra_vars->layout);
            unset($extra_vars->title);

            // DB에 입력하기 위한 변수 설정 
            $args = Context::gets('layout_srl','title');
            $args->extra_vars = serialize($extra_vars);

            $oDB = &DB::getInstance();
            $output = $oDB->executeQuery('layout.updateLayout', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_updated');
        }

        /**
         * @brief 레이아웃 삭제
         * 삭제시 메뉴 xml 캐시 파일도 삭제
         **/
        function procLayoutAdminDelete() {
            $layout_srl = Context::get('layout_srl');

            // 캐시 파일 삭제 
            $cache_list = FileHandler::readDir("./files/cache/layout","",false,true);
            if(count($cache_list)) {
                foreach($cache_list as $cache_file) {
                    $pos = strpos($cache_file, $layout_srl.'_');
                    if($pos>0) unlink($cache_file);
                }
            }

            // DB에서 삭제
            $oDB = &DB::getInstance();

            // 레이아웃 메뉴 삭제
            $args->layout_srl = $layout_srl;
            $output = $oDB->executeQuery("layout.deleteLayoutMenus", $args);
            if(!$output->toBool()) return $output;

            // 레이아웃 삭제
            $output = $oDB->executeQuery("layout.deleteLayout", $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_deleted');
        }

        /**
         * @brief 레이아웃에  메뉴 추가
         **/
        function procLayoutAdminInsertMenu() {
            // 입력할 변수 정리
            $source_args = Context::getRequestVars();
            unset($source_args->module);
            unset($source_args->act);
            if($source_args->menu_open_window!="Y") $source_args->menu_open_window = "N";
            if($source_args->menu_expand !="Y") $source_args->menu_expand = "N";
            $source_args->group_srls = str_replace('|@|',',',$source_args->group_srls);
            $source_args->parent_srl = (int)$source_args->parent_srl;

            // 변수를 다시 정리 (form문의 column과 DB column이 달라서)
            $args->menu_srl = $source_args->menu_srl;
            $args->parent_srl = $source_args->parent_srl;
            $args->layout_srl = $source_args->layout_srl;
            $args->menu_id = $source_args->menu_id;
            $args->name = $source_args->menu_name;
            $args->url = trim($source_args->menu_url);
            $args->open_window = $source_args->menu_open_window;
            $args->expand = $source_args->menu_expand;
            $args->normal_btn = $source_args->menu_normal_btn;
            $args->hover_btn = $source_args->menu_hover_btn;
            $args->active_btn = $source_args->menu_active_btn;
            $args->group_srls = $source_args->group_srls;

            $layout = Context::get('layout');

            // 이미 존재하는지를 확인
            $oLayoutModel = &getModel('layout');
            $menu_info = $oLayoutModel->getLayoutMenuInfo($args->menu_srl);

            $oDB = &DB::getInstance();

            // 존재하게 되면 update를 해준다
            if($menu_info->menu_srl == $args->menu_srl) {
                $output = $oDB->executeQuery('layout.updateLayoutMenu', $args);
                if(!$output->toBool()) return $output;

            // 존재하지 않으면 insert를 해준다
            } else {
                $args->listorder = -1*$args->menu_srl;
                $output = $oDB->executeQuery('layout.insertLayoutMenu', $args);
                if(!$output->toBool()) return $output;
            }

            // 해당 메뉴의 정보를 구함
            $layout_info = $oLayoutModel->getLayoutInfo($layout);
            $menu_title = $layout_info->menu->{$args->menu_id}->name;

            // XML 파일을 갱신하고 위치을 넘겨 받음
            $xml_file = $this->makeXmlFile($args->layout_srl, $args->menu_id);

            $this->add('xml_file', $xml_file);
            $this->add('menu_srl', $args->menu_srl);
            $this->add('menu_id', $args->menu_id);
            $this->add('menu_title', $menu_title);

            // 현재 mid에 해당하는 모듈의 layout_srl 을 무조건 변경
            if(eregi("^mid=", $args->url)) {
                $target_args->layout_srl = $args->layout_srl;
                $target_args->mid = substr($args->url,4);
                $output = $oDB->executeQuery("module.updateModuleLayout", $target_args);
                if(!$output->toBool()) return $output;
            }
        }

        /**
         * @brief 레이아웃 메뉴 삭제 
         **/
        function procLayoutAdminDeleteMenu() {
            // 변수 정리 
            $args = Context::gets('layout_srl','layout','menu_srl','menu_id');

            $oLayoutModel = &getModel('layout');

            // 원정보를 가져옴 
            $node_info = $oLayoutModel->getLayoutMenuInfo($args->menu_srl);
            if($node_info->parent_srl) $parent_srl = $node_info->parent_srl;

            $oDB = &DB::getInstance();

            // 자식 노드가 있는지 체크하여 있으면 삭제 못한다는 에러 출력
            $output = $oDB->executeQuery('layout.getChildMenuCount', $args);
            if(!$output->toBool()) return $output;
            if($output->data->count>0) return new Object(-1, msg_cannot_delete_for_child);

            // DB에서 삭제
            $output = $oDB->executeQuery("layout.deleteLayoutMenu", $args);
            if(!$output->toBool()) return $output;

            // 해당 메뉴의 정보를 구함
            $layout_info = $oLayoutModel->getLayoutInfo($args->layout);
            $menu_title = $layout_info->menu->{$args->menu_id}->name;

            // XML 파일을 갱신하고 위치을 넘겨 받음
            $xml_file = $this->makeXmlFile($args->layout_srl, $args->menu_id);

            $this->add('xml_file', $xml_file);
            $this->add('menu_id', $args->menu_id);
            $this->add('menu_title', $menu_title);
            $this->add('menu_srl', $parent_srl);
        }

        /**
         * @brief 레이아웃의 메뉴를 이동
         **/
        function procLayoutAdminMoveMenu() {
            // 변수 설정 
            $menu_id = Context::get('menu_id');
            $source_node_srl = str_replace('menu_'.$menu_id.'_','',Context::get('source_node_srl'));
            $target_node_srl = str_replace('menu_'.$menu_id.'_','',Context::get('target_node_srl'));

            // target_node 의 값을 구함
            $oLayoutModel = &getModel('layout');
            $target_node = $oLayoutModel->getLayoutMenuInfo($target_node_srl);

            // source_node에 target_node_srl의 parent_srl, listorder 값을 입력
            $oDB = &DB::getInstance();
            $source_args->menu_srl = $source_node_srl;
            $source_args->parent_srl = $target_node->parent_srl;
            $source_args->listorder = $target_node->listorder;
            $output = $oDB->executeQuery('layout.updateLayoutMenuParent', $source_args);
            if(!$output->toBool()) return $output;

            // target_node의 listorder값을 +1해 준다
            $target_args->menu_srl = $target_node_srl;
            $target_args->parent_srl = $target_node->parent_srl;
            $target_args->listorder = $target_node->listorder -1;
            $output = $oDB->executeQuery('layout.updateLayoutMenuParent', $target_args);
            if(!$output->toBool()) return $output;

            // xml파일 재생성 
            $xml_file = $this->makeXmlFile($target_node->layout_srl, $menu_id);

            // return 변수 설정
            $this->add('menu_id', $menu_id);
            $this->add('source_node_srl', Context::get('source_node_srl'));
        }

        /**
         * @brief xml 파일을 갱신
         * 관리자페이지에서 메뉴 구성 후 간혹 xml파일이 재생성 안되는 경우가 있는데\n
         * 이럴 경우 관리자의 수동 갱신 기능을 구현해줌\n
         * 개발 중간의 문제인 것 같고 현재는 문제가 생기지 않으나 굳이 없앨 필요 없는 기능
         **/
        function procLayoutAdminMakeXmlFile() {
            // 입력값을 체크 
            $menu_id = Context::get('menu_id');
            $layout = Context::get('layout');
            $layout_srl = Context::get('layout_srl');

            // 해당 메뉴의 정보를 구함
            $oLayoutModel = &getModel('layout');
            $layout_info = $oLayoutModel->getLayoutInfo($layout);
            $menu_title = $layout_info->menu->{$menu_id}->name;

            // xml파일 재생성 
            $xml_file = $this->makeXmlFile($layout_srl, $menu_id);

            // return 값 설정 
            $this->add('menu_id',$menu_id);
            $this->add('menu_title',$menu_title);
            $this->add('xml_file',$xml_file);
        }

        /**
         * @brief 메뉴의 xml 파일을 만들고 위치를 return
         **/
        function makeXmlFile($layout_srl, $menu_id) {
            // xml파일 생성시 필요한 정보가 없으면 그냥 return
            if(!$layout_srl || !$menu_id) return;

            // DB에서 layout_srl에 해당하는 메뉴 목록을 listorder순으로 구해옴 
            $oDB = &DB::getInstance();
            $args->layout_srl = $layout_srl;
            $args->menu_id = $menu_id;
            $output = $oDB->executeQuery("layout.getLayoutMenuList", $args);
            if(!$output->toBool()) return;

            // 캐시 파일의 이름을 지정
            $xml_file = sprintf("./files/cache/layout/%s_%s.xml.php", $layout_srl, $menu_id);
            $php_file = sprintf("./files/cache/layout/%s_%s.php", $layout_srl, $menu_id);

            // 구해온 데이터가 없다면 노드데이터가 없는 xml 파일만 생성
            $list = $output->data;
            if(!$list) {
                $xml_buff = "<root />";
                FileHandler::writeFile($xml_file, $xml_buff);
                return $xml_file;
            }

            // 구해온 데이터가 하나라면 array로 바꾸어줌
            if(!is_array($list)) $list = array($list);

            // 루프를 돌면서 tree 구성
            $list_count = count($list);
            for($i=0;$i<$list_count;$i++) {
                $node = $list[$i];
                $menu_srl = $node->menu_srl;
                $parent_srl = $node->parent_srl;

                $tree[$parent_srl][$menu_srl] = $node;
            }

            // xml 캐시 파일 생성
            $xml_buff = sprintf('<?php header("Content-Type: text/xml; charset=UTF-8"); header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); header("Cache-Control: no-store, no-cache, must-revalidate"); header("Cache-Control: post-check=0, pre-check=0", false); header("Pragma: no-cache"); @session_start(); ?><root>%s</root>', $this->getXmlTree($tree[0], $tree));

            // php 캐시 파일 생성
            $php_output = $this->getPhpCacheCode($tree[0], $tree);
            $php_buff = sprintf('<?php if(!__ZBXE__) exit(); $menu->list = array(%s); ?>', $php_output['buff']);

            // 파일 저장
            FileHandler::writeFile($xml_file, $xml_buff);
            FileHandler::writeFile($php_file, $php_buff);
            return $xml_file;
        }

        /**
         * @brief array로 정렬된 노드들을 parent_srl을 참조하면서 recursive하게 돌면서 xml 데이터 생성
         * 메뉴 xml파일은 node라는 tag가 중첩으로 사용되며 이 xml doc으로 관리자 페이지에서 메뉴를 구성해줌\n
         * (tree_menu.js 에서 xml파일을 바로 읽고 tree menu를 구현)
         **/
        function getXmlTree($source_node, $tree) {
            if(!$source_node) return;
            foreach($source_node as $menu_srl => $node) {
                $child_buff = "";

                // 자식 노드의 데이터 가져옴
                if($menu_srl&&$tree[$menu_srl]) $child_buff = $this->getXmlTree($tree[$menu_srl], $tree);

                // 변수 정리 
                $name = str_replace(array('&','"','<','>'),array('&amp;','&quot;','&lt;','&gt;'),$node->name);
                $url = str_replace(array('&','"','<','>'),array('&amp;','&quot;','&lt;','&gt;'),$node->url);
                $open_window = $node->open_window;
                $expand = $node->expand;
                $normal_btn = str_replace(array('&','"','<','>'),array('&amp;','&quot;','&lt;','&gt;'),$node->normal_btn);
                $hover_btn = str_replace(array('&','"','<','>'),array('&amp;','&quot;','&lt;','&gt;'),$node->hover_btn);
                $active_btn = str_replace(array('&','"','<','>'),array('&amp;','&quot;','&lt;','&gt;'),$node->active_btn);
                $group_srls = $node->group_srls;

                // node->group_srls값이 있으면 
                if($group_srls) $group_check_code = sprintf('($_SESSION["is_admin"]==true||(is_array($_SESSION["group_srls"])&&count(array_intersect($_SESSION["group_srls"], array(%s)))))',$group_srls);
                else $group_check_code = "true";
                $attribute = sprintf(
                        'node_srl="%s" text=\'<?=(%s?"%s":"")?>\' url=\'<?=(%s?"%s":"")?>\' open_window="%s" expand="%s" normal_btn="%s" hover_btn="%s" active_btn="%s" ',
                        $menu_srl,
                        $group_check_code,
                        $name,
                        $group_check_code,
                        $url,
                        $open_window,
                        $expand,
                        $normal_btn,
                        $hover_btn,
                        $active_btn
                );
                
                if($child_buff) $buff .= sprintf('<node %s>%s</node>', $attribute, $child_buff);
                else $buff .=  sprintf('<node %s />', $attribute);
            }
            return $buff;
        }

        /**
         * @brief array로 정렬된 노드들을 php code로 변경하여 return
         * 레이아웃에서 메뉴를 tpl에 사용시 xml데이터를 사용할 수도 있지만 별도의 javascript 사용이 필요하기에
         * php로 된 캐시파일을 만들어서 db이용없이 바로 메뉴 정보를 구할 수 있도록 한다
         * 이 캐시는 ModuleHandler::displayContent() 에서 include하여 Context::set() 한다
         **/
        function getPhpCacheCode($source_node, $tree) {
            $output = array("buff"=>"", "url_list"=>array());
            if(!$source_node) return $output;

            foreach($source_node as $menu_srl => $node) {
                // 자식 노드가 있으면 자식 노드의 데이터를 먼저 얻어옴 
                if($menu_srl&&$tree[$menu_srl]) $child_output = $this->getPhpCacheCode($tree[$menu_srl], $tree);
                else $child_output = array("buff"=>"", "url_list"=>array());

                // 노드의 url에 ://가 있으면 바로 링크, 아니면 제로보드의 링크를 설정한다 ($node->href가 완성된 url)
                if($node->url && !strpos($node->url, '://')) $node->href = "./?".$node->url;
                else $node->href = $node->url;

                // 현재 노드의 url값이 공란이 아니라면 url_list 배열값에 입력
                if($node->url) $child_output['url_list'][] = $node->url;
                $output['url_list'] = array_merge($output['url_list'], $child_output['url_list']);

                // node->group_srls값이 있으면 
                if($node->group_srls) $group_check_code = sprintf('($_SESSION["is_admin"]==true||(is_array($_SESSION["group_srls"])&&count(array_intersect($_SESSION["group_srls"], array(%s)))))',$node->group_srls);
                else $group_check_code = "true";

                // 변수 정리
                $name = str_replace(array('&','"','<','>'),array('&amp;','&quot;','&lt;','&gt;'),$node->name);
                $href = str_replace(array('&','"','<','>'),array('&amp;','&quot;','&lt;','&gt;'),$node->href);
                $url = str_replace(array('&','"','<','>'),array('&amp;','&quot;','&lt;','&gt;'),$node->url);
                $open_window = $node->open_window;
                $normal_btn = str_replace(array('&','"','<','>'),array('&amp;','&quot;','&lt;','&gt;'),$node->normal_btn);
                $hover_btn = str_replace(array('&','"','<','>'),array('&amp;','&quot;','&lt;','&gt;'),$node->hover_btn);
                $active_btn = str_replace(array('&','"','<','>'),array('&amp;','&quot;','&lt;','&gt;'),$node->active_btn);
                $selected = '"'.implode('","',$child_output['url_list']).'"';
                $child_buff = $child_output['buff'];

                // 속성을 생성한다 ( url_list를 이용해서 선택된 메뉴의 노드에 속하는지를 검사한다. 꽁수지만 빠르고 강력하다고 생각;;)
                $attribute = sprintf(
                        '"node_srl"=>"%s","text"=>(%s?"%s":""),"href"=>(%s?"%s":""),"url"=>(%s?"%s":""),"open_window"=>"%s","normal_btn"=>"%s","hover_btn"=>"%s","active_btn"=>"%s","selected"=>(in_array(Context::get("zbxe_url"),array(%s))?1:0),"list"=>array(%s)',
                        $node->menu_srl, 
                        $group_check_code,
                        $name,
                        $group_check_code,
                        $href,
                        $group_check_code,
                        $url,
                        $open_window,
                        $normal_btn,
                        $hover_btn,
                        $active_btn,
                        $selected,
                        $child_buff
                );
                
                // buff 데이터를 생성한다
                $output['buff'] .=  sprintf('%s=>array(%s),', $node->menu_srl, $attribute);
            }
            return $output;
        }
    }
?>
