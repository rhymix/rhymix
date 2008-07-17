<?php
    /**
     * @class  menuAdminController
     * @author zero (zero@nzeo.com)
     * @brief  menu 모듈의 admin controller class
     **/

    class menuAdminController extends menu {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 메뉴 추가
         **/
        function procMenuAdminInsert() {
            // 입력할 변수 정리
            $args->title = Context::get('title');
            $args->menu_srl = getNextSequence();
            $args->listorder = $args->menu_srl * -1;

            $output = executeQuery('menu.insertMenu', $args);
            if(!$output->toBool()) return $output;

            $this->add('menu_srl', $args->menu_srl);
            $this->setMessage('success_registed');
        }

        /**
         * @brief 메뉴 제목 변경 
         **/
        function procMenuAdminUpdate() {
            // 입력할 변수 정리
            $args->title = Context::get('title');
            $args->menu_srl = Context::get('menu_srl');

            $output = executeQuery('menu.updateMenu', $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_registed');
        }

        /**
         * @brief 메뉴 삭제
         * menu_item과 xml 캐시 파일 모두 삭제
         **/
        function procMenuAdminDelete() {
            $menu_srl = Context::get('menu_srl');

            // 캐시 파일 삭제 
            $cache_list = FileHandler::readDir("./files/cache/menu","",false,true);
            if(count($cache_list)) {
                foreach($cache_list as $cache_file) {
                    $pos = strpos($cache_file, $menu_srl.'_');
                    if($pos>0)FileHandler::removeFile($cache_file);
                }
            }

            // 이미지 버튼 모두 삭제
            $image_path = sprintf('./files/attach/menu_button/%s', $menu_srl);
            FileHandler::removeDir($image_path);

            $args->menu_srl = $menu_srl;

            // 메뉴 메뉴 삭제
            $output = executeQuery("menu.deleteMenuItems", $args);
            if(!$output->toBool()) return $output;

            // 메뉴 삭제
            $output = executeQuery("menu.deleteMenu", $args);
            if(!$output->toBool()) return $output;

            $this->setMessage('success_deleted');
        }

        /**
         * @brief 메뉴에 아이템 추가
         **/
        function procMenuAdminInsertItem() {
            // 입력할 변수 정리
            $source_args = Context::getRequestVars();
            unset($source_args->module);
            unset($source_args->act);
            if($source_args->menu_open_window!="Y") $source_args->menu_open_window = "N";
            if($source_args->menu_expand !="Y") $source_args->menu_expand = "N";
            $source_args->group_srls = str_replace('|@|',',',$source_args->group_srls);
            $source_args->parent_srl = (int)$source_args->parent_srl;

            // 메뉴 이름 체크
            $lang_supported = Context::get('lang_supported');
            $name_inserted = false;
            foreach($lang_supported as $key => $val) {
                $menu_name[$key] = $source_args->{"menu_name_".strtolower($key )};
                if($menu_name[$key]) $name_inserted = true;
            }
            if(!$name_inserted) {
                global $lang;
                return new Object(-1, sprintf($lang->filter->isnull, $lang->menu_name));
            }

            // 변수를 다시 정리 (form문의 column과 DB column이 달라서)
            $args->menu_srl = $source_args->menu_srl;
            $args->menu_item_srl = $source_args->menu_item_srl;
            $args->parent_srl = $source_args->parent_srl;
            $args->menu_srl = $source_args->menu_srl;
            $args->menu_id = $source_args->menu_id;
            $args->name = serialize($menu_name);
            $args->url = trim($source_args->menu_url);
            $args->open_window = $source_args->menu_open_window;
            $args->expand = $source_args->menu_expand;
            $args->normal_btn = $source_args->normal_btn;
            $args->hover_btn = $source_args->hover_btn;
            $args->active_btn = $source_args->active_btn;
            $args->group_srls = $source_args->group_srls;

            // 이미 존재하는지를 확인
            $oMenuModel = &getAdminModel('menu');
            $item_info = $oMenuModel->getMenuItemInfo($args->menu_item_srl);

            // 존재하게 되면 update를 해준다
            if($item_info->menu_item_srl == $args->menu_item_srl) {
                $output = executeQuery('menu.updateMenuItem', $args);
                if(!$output->toBool()) return $output;

            // 존재하지 않으면 insert를 해준다
            } else {
                $args->listorder = -1*$args->menu_item_srl;
                $output = executeQuery('menu.insertMenuItem', $args);
                if(!$output->toBool()) return $output;
            }

            // 해당 메뉴의 정보를 구함
            $menu_info = $oMenuModel->getMenu($args->menu_srl);
            $menu_title = $menu_info->title;

            // XML 파일을 갱신하고 위치을 넘겨 받음
            $xml_file = $this->makeXmlFile($args->menu_srl);

            // url이 mid일 경우 기록 남김 
            if(preg_match('/^([a-zA-Z0-9\_\-]+)$/', $args->url)) {
                $mid = $args->url;

                $mid_args->menu_srl = $args->menu_srl;
                $mid_args->mid = $mid;

                // menu_srl에 해당하는 레이아웃 값을 구함
                $output = executeQuery('menu.getMenuLayout', $args); 

                // 해당 모듈에 레이아웃 값이 정해져 있지 않으면 지정
                $oModuleModel = &getModel('module');
                $module_info = $oModuleModel->getModuleInfoByMid($mid);
                if(!$module_info->layout_srl&&$output->data->layout_srl) $mid_args->layout_srl = $output->data->layout_srl;

                // 해당 mid의 메뉴값을 선택된 메뉴로 변경
                $oModuleController = &getController('module');
                $oModuleController->updateModuleMenu($mid_args);
            }

            $this->add('xml_file', $xml_file);
            $this->add('menu_srl', $args->menu_srl);
            $this->add('menu_item_srl', $args->menu_item_srl);
            $this->add('menu_title', $menu_title);
            $this->add('parent_srl', $args->parent_srl);
        }

        /**
         * @brief 메뉴 메뉴 삭제 
         **/
        function procMenuAdminDeleteItem() {
            // 변수 정리 
            $args = Context::gets('menu_srl','menu_item_srl');

            $oMenuAdminModel = &getAdminModel('menu');

            // 원정보를 가져옴 
            $item_info = $oMenuAdminModel->getMenuItemInfo($args->menu_item_srl);
            if($item_info->parent_srl) $parent_srl = $item_info->parent_srl;

            // 자식 노드가 있는지 체크하여 있으면 삭제 못한다는 에러 출력
            $output = executeQuery('menu.getChildMenuCount', $args);
            if(!$output->toBool()) return $output;
            if($output->data->count>0) return new Object(-1, 'msg_cannot_delete_for_child');

            // DB에서 삭제
            $output = executeQuery("menu.deleteMenuItem", $args);
            if(!$output->toBool()) return $output;

            // 해당 메뉴의 정보를 구함
            $menu_info = $oMenuAdminModel->getMenu($args->menu_srl);
            $menu_title = $menu_info->title;

            // XML 파일을 갱신하고 위치을 넘겨 받음
            $xml_file = $this->makeXmlFile($args->menu_srl);

            // 이미지 버튼 모두 삭제
            if($item_info->normal_btn) FileHandler::removeFile($item_info->normal_btn);
            if($item_info->hover_btn) FileHandler::removeFile($item_info->hover_btn);
            if($item_info->active_btn) FileHandler::removeFile($item_info->active_btn);

            $this->add('xml_file', $xml_file);
            $this->add('menu_title', $menu_title);
            $this->add('menu_item_srl', $parent_srl);
            $this->setMessage('success_deleted');
        }

        /**
         * @brief 메뉴의 메뉴를 이동
         **/
        function procMenuAdminMoveItem() {
            // 변수 설정 
            $menu_id = Context::get('menu_id');
            $source_item_srl = str_replace('menu_'.$menu_id.'_','',Context::get('source_item_srl'));
            $target_item_srl = str_replace('menu_'.$menu_id.'_','',Context::get('target_item_srl'));

            // target_item 의 값을 구함
            $oMenuModel = &getAdminModel('menu');
            $target_item = $oMenuModel->getMenuItemInfo($target_item_srl);

            // source_item에 target_item_srl의 parent_srl, listorder 값을 입력
            $source_args->menu_item_srl = $source_item_srl;
            $source_args->parent_srl = $target_item->parent_srl;
            $source_args->listorder = $target_item->listorder;
            $output = executeQuery('menu.updateMenuItemParent', $source_args);
            if(!$output->toBool()) return $output;

            // target_item의 listorder값을 +1해 준다
            $target_args->menu_item_srl = $target_item_srl;
            $target_args->parent_srl = $target_item->parent_srl;
            $target_args->listorder = $target_item->listorder -1;
            $output = executeQuery('menu.updateMenuItemParent', $target_args);
            if(!$output->toBool()) return $output;

            // xml파일 재생성 
            $xml_file = $this->makeXmlFile($target_item->menu_srl);

            // return 변수 설정
            $this->add('menu_srl', $target_item->menu_srl);
            $this->add('xml_file', $xml_file);
            $this->add('source_item_srl', $source_item_srl);
        }

        /**
         * @brief xml 파일을 갱신
         * 관리자페이지에서 메뉴 구성 후 간혹 xml파일이 재생성 안되는 경우가 있는데\n
         * 이럴 경우 관리자의 수동 갱신 기능을 구현해줌\n
         * 개발 중간의 문제인 것 같고 현재는 문제가 생기지 않으나 굳이 없앨 필요 없는 기능
         **/
        function procMenuAdminMakeXmlFile() {
            // 입력값을 체크 
            $menu_srl = Context::get('menu_srl');

            // 해당 메뉴의 정보를 구함
            $oMenuAdminModel = &getAdminModel('menu');
            $menu_info = $oMenuAdminModel->getMenu($menu_srl);
            $menu_title = $menu_info->title;

            // xml파일 재생성 
            $xml_file = $this->makeXmlFile($menu_srl);

            // return 값 설정 
            $this->add('menu_title',$menu_title);
            $this->add('xml_file',$xml_file);
        }

        /**
         * @brief 메뉴 이미지 버튼을 등록
         **/
        function procMenuAdminUploadButton() {
            $menu_srl = Context::get('menu_srl');
            $menu_item_srl = Context::get('menu_item_srl');
            $target = Context::get('target');
            $target_file = Context::get($target);

            // 필수 요건이 없거나 업로드된 파일이 아니면 오류 발생
            if(!$menu_srl || !$menu_item_srl || !$target_file || !is_uploaded_file($target_file['tmp_name']) || !preg_match('/\.(gif|jpeg|jpg|png)/i',$target_file['name'])) {
                Context::get('error_messge', Context::getLang('msg_invalid_request'));

            // 요건을 만족하고 업로드된 파일이면 지정된 위치로 이동
            } else {
                $tmp_arr = explode('.',$target_file['name']);
                $ext = $tmp_arr[count($tmp_arr)-1];

                $path = sprintf('./files/attach/menu_button/%d/', $menu_srl);
                $filename = sprintf('%s%d.%s.%s', $path, $menu_item_srl, $target, $ext);

                if(!is_dir($path)) FileHandler::makeDir($path);

                move_uploaded_file($target_file['tmp_name'], $filename);
                Context::set('filename', $filename);
            }


            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('menu_file_uploaded');
        }

        /**
         * @brief 등록된 메뉴 이미지 제거
         **/
        function procMenuAdminDeleteButton() {
            $menu_srl = Context::get('menu_srl');
            $menu_item_srl = Context::get('menu_item_srl');
            $target = Context::get('target');
            $filename = Context::get('filename');
            FileHandler::removeFile($filename);

            $this->add('target', $target);
        }

        /**
         * @brief 메뉴의 xml 파일을 만들고 위치를 return
         **/
        function makeXmlFile($menu_srl) {
            // xml파일 생성시 필요한 정보가 없으면 그냥 return
            if(!$menu_srl) return;
            
            // DB에서 menu_srl에 해당하는 메뉴 아이템 목록을 listorder순으로 구해옴 
            $args->menu_srl = $menu_srl;
            $args->sort_index = 'listorder';
            $output = executeQuery('menu.getMenuItems', $args);
            if(!$output->toBool()) return;

            // 캐시 파일의 이름을 지정
            $xml_file = sprintf("./files/cache/menu/%s.xml.php", $menu_srl);
            $php_file = sprintf("./files/cache/menu/%s.php", $menu_srl);

            // 구해온 데이터가 없다면 노드데이터가 없는 xml 파일만 생성
            $list = $output->data;
            if(!$list) {
                $xml_buff = "<root />";
                FileHandler::writeFile($xml_file, $xml_buff);
                FileHandler::writeFile($php_file, '<?php if(!defined("__ZBXE__")) exit(); ?>');
                return $xml_file;
            }

            // 구해온 데이터가 하나라면 array로 바꾸어줌
            if(!is_array($list)) $list = array($list);

            // 루프를 돌면서 tree 구성
            $list_count = count($list);
            for($i=0;$i<$list_count;$i++) {
                $node = $list[$i];
                $menu_item_srl = $node->menu_item_srl;
                $parent_srl = $node->parent_srl;

                $tree[$parent_srl][$menu_item_srl] = $node;
            }

            // 캐시 파일의 권한과 그룹 설정을 위한 공통 헤더
            $header_script = 
                '$lang_type = Context::getLangType(); '.
                '$is_logged = Context::get(\'is_logged\'); '.
                '$logged_info = Context::get(\'logged_info\'); '.
                'if($is_logged) {'.
                    'if($logged_info->is_admin=="Y") $is_admin = true; '.
                    'else $is_admin = false; '.
                    '$group_srls = array_keys($logged_info->group_list); '.
                '} else { '.
                    '$is_admin = false; '.
                    '$group_srsl = array(); '.
                '} ';

            // xml 캐시 파일 생성 (xml캐시는 따로 동작하기에 session 지정을 해주어야 함)
            $xml_buff = sprintf(
                '<?php '.
                'define(\'__ZBXE__\', true); '.
                'require_once(\'../../../config/config.inc.php\'); '.
                '$oContext = &Context::getInstance(); '.
                '$oContext->init(); '.
                'header("Content-Type: text/xml; charset=UTF-8"); '.
                'header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); '.
                'header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT"); '.
                'header("Cache-Control: no-store, no-cache, must-revalidate"); '.
                'header("Cache-Control: post-check=0, pre-check=0", false); '.
                'header("Pragma: no-cache"); '.
                '%s'.
                '?>'.
                '<root>%s</root>', 
                $header_script,
                $this->getXmlTree($tree[0], $tree)
            );

            // php 캐시 파일 생성
            $php_output = $this->getPhpCacheCode($tree[0], $tree);
            $php_buff = sprintf(
                '<?php '.
                'if(!defined("__ZBXE__")) exit(); '.
                '%s; '.
                '%s; '.
                '$menu->list = array(%s); '.
                '?>', 
                $header_script,
                $php_output['name'], 
                $php_output['buff']
            );

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

            $oMenuAdminModel = &getAdminModel('menu');

            foreach($source_node as $menu_item_srl => $node) {
                $child_buff = "";

                // 자식 노드의 데이터 가져옴
                if($menu_item_srl&&$tree[$menu_item_srl]) $child_buff = $this->getXmlTree($tree[$menu_item_srl], $tree);

                // 변수 정리 
                $names = $oMenuAdminModel->getMenuItemNames($node->name);
                foreach($names as $key => $val) {
                    $name_arr_str .= sprintf('"%s"=>"%s",',$key, str_replace('\\','\\\\',htmlspecialchars($val)));
                }
                $name_str = sprintf('$_names = array(%s); print $_names[$lang_type];', $name_arr_str);

                $url = str_replace(array('&','"','<','>'),array('&amp;','&quot;','&lt;','&gt;'),$node->url);
                if(preg_match('/^([0-9a-zA-Z\_\-]+)$/', $node->url)) {
                    $href = getUrl('','mid',$node->url);
                    $pos = strpos($href, $_SERVER['HTTP_HOST']);
                    if($pos !== false) $href = substr($href, $pos+strlen($_SERVER['HTTP_HOST']));
                } else $href = $url;
                $open_window = $node->open_window;
                $expand = $node->expand;

                $normal_btn = $node->normal_btn;
                if($normal_btn && preg_match('/^\.\/files\/attach\/menu_button/i',$normal_btn)) $normal_btn = str_replace(array('&','"','<','>'),array('&amp;','&quot;','&lt;','&gt;'),$normal_btn);
                else $normal_btn = '';
                $hover_btn = $node->hover_btn;
                if($hover_btn && preg_match('/^\.\/files\/attach\/menu_button/i',$hover_btn)) $hover_btn = str_replace(array('&','"','<','>'),array('&amp;','&quot;','&lt;','&gt;'),$hover_btn);
                else $hover_btn = '';
                $active_btn = $node->active_btn;
                if($active_btn && preg_match('/^\.\/files\/attach\/menu_button/i',$active_btn)) $active_btn = str_replace(array('&','"','<','>'),array('&amp;','&quot;','&lt;','&gt;'),$active_btn);
                else $active_btn = '';

                $group_srls = $node->group_srls;

                if($normal_btn) {
                    if(preg_match('/\.png$/',$normal_btn)) $classname = 'class=&quot;iePngFix&quot;';
                    else $classname = '';
                    if($hover_btn) $hover_str = sprintf('onmouseover=&quot;this.src=\'%s\'&quot;', $hover_btn); else $hover_str = '';
                    if($active_btn) $active_str = sprintf('onmousedown=&quot;this.src=\'%s\'&quot;', $active_btn); else $active_str = '';
                    $link = sprintf('&lt;img src=&quot;%s&quot; onmouseout=&quot;this.src=\'%s\'&quot; alt=&quot;<?php print htmlspecialchars($_names[$lang_type]) ?>&quot; %s %s %s /&gt;', $normal_btn, $normal_btn, $hover_str, $active_str, $classname);
                } else {
                    $link = '<?php print $_names[$lang_type]; ?>';
                }

                // node->group_srls값이 있으면 
                if($group_srls) $group_check_code = sprintf('($is_admin==true||(is_array($group_srls)&&count(array_intersect($group_srls, array(%s)))))',$group_srls);
                else $group_check_code = "true";
                $attribute = sprintf(
                    'node_srl="%s" parent_srl="%s" text="<?php if(%s) { %s }?>" url="<?php print(%s?"%s":"")?>" href="<?php print(%s?"%s":"")?>" open_window="%s" expand="%s" normal_btn="%s" hover_btn="%s" active_btn="%s" link="<?php if(%s) {?>%s<?php }?>"',
                    $menu_item_srl,
                    $node->parent_srl,
                    $group_check_code,
                    $name_str,
                    $group_check_code,
                    $url,
                    $group_check_code,
                    $href,
                    $open_window,
                    $expand,
                    $normal_btn,
                    $hover_btn,
                    $active_btn,
                    $group_check_code,
                    $link
                );
                
                if($child_buff) $buff .= sprintf('<node %s>%s</node>', $attribute, $child_buff);
                else $buff .=  sprintf('<node %s />', $attribute);
            }
            return $buff;
        }

        /**
         * @brief array로 정렬된 노드들을 php code로 변경하여 return
         * 메뉴에서 메뉴를 tpl에 사용시 xml데이터를 사용할 수도 있지만 별도의 javascript 사용이 필요하기에
         * php로 된 캐시파일을 만들어서 db이용없이 바로 메뉴 정보를 구할 수 있도록 한다
         * 이 캐시는 ModuleHandler::displayContent() 에서 include하여 Context::set() 한다
         **/
        function getPhpCacheCode($source_node, $tree) {
            $output = array("buff"=>"", "url_list"=>array());
            if(!$source_node) return $output;

            $oMenuAdminModel = &getAdminModel('menu');

            foreach($source_node as $menu_item_srl => $node) {
                // 자식 노드가 있으면 자식 노드의 데이터를 먼저 얻어옴 
                if($menu_item_srl&&$tree[$menu_item_srl]) $child_output = $this->getPhpCacheCode($tree[$menu_item_srl], $tree);
                else $child_output = array("buff"=>"", "url_list"=>array());

                // 변수 정리 
                $names = $oMenuAdminModel->getMenuItemNames($node->name);
                foreach($names as $key => $val) {
                    $name_arr_str .= sprintf('"%s"=>"%s",',$key, str_replace('\\','\\\\',htmlspecialchars($val)));
                }
                $name_str = sprintf('$_menu_names[%d] = array(%s); %s', $node->menu_item_srl, $name_arr_str, $child_output['name']);

                // 현재 노드의 url값이 공란이 아니라면 url_list 배열값에 입력
                if($node->url) $child_output['url_list'][] = $node->url;
                $output['url_list'] = array_merge($output['url_list'], $child_output['url_list']);

                // node->group_srls값이 있으면 
                if($node->group_srls) $group_check_code = sprintf('($is_admin==true||(is_array($group_srls)&&count(array_intersect($group_srls, array(%s)))))',$node->group_srls);
                else $group_check_code = "true";

                // 변수 정리
                $href = str_replace(array('&','"','<','>'),array('&amp;','&quot;','&lt;','&gt;'),$node->href);
                $url = str_replace(array('&','"','<','>'),array('&amp;','&quot;','&lt;','&gt;'),$node->url);
                if(preg_match('/^([0-9a-zA-Z\_\-]+)$/i', $node->url)) {
                    $href = getUrl('','mid',$node->url);
                    $pos = strpos($href, $_SERVER['HTTP_HOST']);
                    if($pos !== false) $href = substr($href, $pos+strlen($_SERVER['HTTP_HOST']));
                } else $href = $url;
                $open_window = $node->open_window;
                $normal_btn = str_replace(array('&','"','<','>'),array('&amp;','&quot;','&lt;','&gt;'),$node->normal_btn);
                $hover_btn = str_replace(array('&','"','<','>'),array('&amp;','&quot;','&lt;','&gt;'),$node->hover_btn);
                $active_btn = str_replace(array('&','"','<','>'),array('&amp;','&quot;','&lt;','&gt;'),$node->active_btn);
                $selected = '"'.implode('","',$child_output['url_list']).'"';
                $child_buff = $child_output['buff'];
                $expand = $node->expand;

                $normal_btn = $node->normal_btn;
                if($normal_btn && preg_match('/^\.\/files\/attach\/menu_button/i',$normal_btn)) $normal_btn = str_replace(array('&','"','<','>'),array('&amp;','&quot;','&lt;','&gt;'),$normal_btn);
                else $normal_btn = '';

                $hover_btn = $node->hover_btn;
                if($hover_btn && preg_match('/^\.\/files\/attach\/menu_button/i',$hover_btn)) $hover_btn = str_replace(array('&','"','<','>'),array('&amp;','&quot;','&lt;','&gt;'),$hover_btn);
                else $hover_btn = '';

                $active_btn = $node->active_btn;
                if($active_btn && preg_match('/^\.\/files\/attach\/menu_button/i',$active_btn)) $active_btn = str_replace(array('&','"','<','>'),array('&amp;','&quot;','&lt;','&gt;'),$active_btn);
                else $active_btn = '';

                $group_srls = $node->group_srls;

                if($normal_btn) {
                    if(preg_match('/\.png$/',$normal_btn)) $classname = 'class=\"iePngFix\"';
                    else $classname = '';
                    if($hover_btn) $hover_str = sprintf('onmouseover=\"this.src=\'%s\'\"', $hover_btn); else $hover_str = '';
                    if($active_btn) $active_str = sprintf('onmousedown=\"this.src=\'%s\'\"', $active_btn); else $active_str = '';
                    $link = sprintf('"<img src=\"%s\" onmouseout=\"this.src=\'%s\'\" alt=\"".$_menu_names[%d][$lang_type]."\" %s %s %s />"', $normal_btn, $normal_btn, $node->menu_item_srl, $hover_str, $active_str, $classname);
                    if($active_btn) $link_active = sprintf('"<img src=\"%s\" alt=\"".$_menu_names[%d][$lang_type]."\" %s />"', $active_btn, $node->menu_item_srl, $classname);
                    else $link_active = $link;
                } else {
                    $link_active = $link = sprintf('$_menu_names[%d][$lang_type]', $node->menu_item_srl);
                }

                // 속성을 생성한다 ( url_list를 이용해서 선택된 메뉴의 노드에 속하는지를 검사한다. 꽁수지만 빠르고 강력하다고 생각;;)
                $attribute = sprintf(
                    '"node_srl"=>"%s","parent_srl"=>"%s","text"=>(%s?$_menu_names[%d][$lang_type]:""),"href"=>(%s?"%s":""),"url"=>(%s?"%s":""),"open_window"=>"%s","normal_btn"=>"%s","hover_btn"=>"%s","active_btn"=>"%s","selected"=>(array(%s)&&in_array(Context::get("mid"),array(%s))?1:0),"expand"=>"%s", "list"=>array(%s),  "link"=>(%s? ( array(%s)&&in_array(Context::get("mid"),array(%s)) ?%s:%s):""),',
                    $node->menu_item_srl,
                    $node->parent_srl,
                    $group_check_code,
                    $node->menu_item_srl,
                    $group_check_code,
                    $href,
                    $group_check_code,
                    $url,
                    $open_window,
                    $normal_btn,
                    $hover_btn,
                    $active_btn,
                    $selected,
                    $selected,
                    $expand,
                    $child_buff,
                    $group_check_code,
                    $selected,
                    $selected,
                    $link_active,
                    $link
                );
                
                // buff 데이터를 생성한다
                $output['buff'] .=  sprintf('%s=>array(%s),', $node->menu_item_srl, $attribute);
                $output['name'] .= $name_str;
            }
            return $output;
        }

        /**
         * @brief 메뉴와 레이아웃 매핑
         * 레이아웃에서 메뉴를 지정할때 지정된 메뉴의 기본 레이아웃을 매핑
         **/
        function updateMenuLayout($layout_srl, $menu_srl_list) {
            if(!count($menu_srl_list)) return;

            // 일단 menu_srls의 값을 지움
            $args->menu_srls = implode(',',$menu_srl_list);
            $output = executeQuery('menu.deleteMenuLayout', $args);
            if(!$output->toBool()) return $output;

            $args->layout_srl = $layout_srl;

            // menu_srls, layout_srl 매핑
            for($i=0;$i<count($menu_srl_list);$i++) {
                $args->menu_srl = $menu_srl_list[$i];
                $output = executeQuery('menu.insertMenuLayout', $args);
                if(!$output->toBool()) return $output;
            }
        }

    }
?>
