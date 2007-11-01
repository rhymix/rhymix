<?php
    /**
     * @class  blogAdminController
     * @author zero (zero@nzeo.com)
     * @brief  blog 모듈의 admin controller class
     **/

    class blogAdminController extends blog {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 권한 추가
         **/
        function procBlogAdminInsertGrant() {
            $module_srl = Context::get('module_srl');

            // 현 모듈의 권한 목록을 가져옴
            $grant_list = $this->xml_info->grant;

            if(count($grant_list)) {
                foreach($grant_list as $key => $val) {
                    $group_srls = Context::get($key);
                    if($group_srls) $arr_grant[$key] = explode('|@|',$group_srls);
                }
                $grants = serialize($arr_grant);
            }

            $oModuleController = &getController('module');
            $oModuleController->updateModuleGrant($module_srl, $grants);

            $this->add('module_srl',Context::get('module_srl'));
            $this->setMessage('success_registed');
        }

        /**
         * @brief 스킨 정보 업데이트
         **/
        function procBlogAdminUpdateSkinInfo() {
            // module_srl에 해당하는 정보들을 가져오기
            $module_srl = Context::get('module_srl');

            $oModuleModel = &getModel('module');
            $oModuleController = &getController('module');

            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
            $skin = $module_info->skin;

            // 스킨의 정보르 구해옴 (extra_vars를 체크하기 위해서)
            $skin_info = $oModuleModel->loadSkinInfo($this->module_path, $skin);

            // 입력받은 변수들을 체크 (mo, act, module_srl, page등 기본적인 변수들 없앰)
            $obj = Context::getRequestVars();
            unset($obj->act);
            unset($obj->module_srl);
            unset($obj->page);

            // 원 skin_info에서 extra_vars의 type이 image일 경우 별도 처리를 해줌
            if($skin_info->extra_vars) {
                foreach($skin_info->extra_vars as $vars) {
                    if($vars->type!='image') continue;

                    $image_obj = $obj->{$vars->name};

                    // 삭제 요청에 대한 변수를 구함
                    $del_var = $obj->{"del_".$vars->name};
                    unset($obj->{"del_".$vars->name});
                    if($del_var == 'Y') {
                        @unlink($module_info->{$vars->name});
                        continue;
                    }

                    // 업로드 되지 않았다면 이전 데이터를 그대로 사용
                    if(!$image_obj['tmp_name']) {
                        $obj->{$vars->name} = $module_info->{$vars->name};
                        continue;
                    }

                    // 정상적으로 업로드된 파일이 아니면 무시
                    if(!is_uploaded_file($image_obj['tmp_name'])) {
                        unset($obj->{$vars->name});
                        continue;
                    }

                    // 이미지 파일이 아니어도 무시
                    if(!eregi("\.(jpg|jpeg|gif|png)$", $image_obj['name'])) {
                        unset($obj->{$vars->name});
                        continue;
                    }

                    // 경로를 정해서 업로드
                    $path = sprintf("./files/attach/images/%s/", $module_srl);

                    // 디렉토리 생성
                    if(!FileHandler::makeDir($path)) return false;

                    $filename = $path.$image_obj['name'];

                    // 파일 이동
                    if(!move_uploaded_file($image_obj['tmp_name'], $filename)) {
                        unset($obj->{$vars->name});
                        continue;
                    }

                    // 변수를 바꿈
                    unset($obj->{$vars->name});
                    $obj->{$vars->name} = $filename;
                }
            }

            // 메뉴 관리
            $menus = get_object_vars($skin_info->menu);
            if(count($menus)) {
                foreach($menus as $menu_id => $val) {
                    $menu_srl = Context::get($menu_id);
                    if($menu_srl) {
                        $obj->menu->{$menu_id} = $menu_srl;
                        $obj->{$menu_id} = $menu_srl;
                        $menu_srl_list[] = $menu_srl;
                    }
                }

                // 정해진 메뉴가 있으면 모듈 및 메뉴에 대한 레이아웃 연동
                if(count($menu_srl_list)) {
                    // 해당 메뉴와 레이아웃 값을 매핑
                    $oMenuAdminController = &getAdminController('menu');
                    $oMenuAdminController->updateMenuLayout($module_srl, $menu_srl_list);

                    // 해당 메뉴에 속한 mid의 layout값을 모두 변경
                    $oModuleController->updateModuleLayout($module_srl, $menu_srl_list);
                }
            }

            // serialize하여 저장
            $oDocumentModel = &getModel('document');
            $obj->category_xml_file = $oDocumentModel->getCategoryXmlFile($module_srl);
            $obj->mid = $module_info->mid;
            $skin_vars = serialize($obj);

            $oModuleController->updateModuleSkinVars($module_srl, $skin_vars);

            // 레이아웃 확장변수 수정
            $layout_args->mid = $obj->mid;
            $layout_args->extra_vars = $skin_vars;
            $layout_args->layout_srl = $module_srl;
            $oLayoutAdminController = &getAdminController('layout');
            $output = $oLayoutAdminController->updateLayout($layout_args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            $this->setLayoutPath('./common/tpl');
            $this->setLayoutFile('default_layout.html');
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile("top_refresh.html");
        }

        /**
         * @brief 블로그 추가
         **/
        function procBlogAdminInsertBlog() {
            // 일단 입력된 값들을 모두 받아서 db 입력항목과 그외 것으로 분리
            $args = Context::gets('module_srl','module_category_srl','blog_name','skin','browser_title','description','is_default','header_text','footer_text','admin_id');
            $args->module = 'blog';
            $args->mid = $args->blog_name;
            unset($args->blog_name);
            if($args->is_default!='Y') $args->is_default = 'N';

            // 기본 값외의 것들을 정리
            $extra_var = delObjectVars(Context::getRequestVars(), $args);
            unset($extra_var->act);
            unset($extra_var->page);
            unset($extra_var->blog_name);

            // 확장변수(20개로 제한된 고정 변수) 체크
            $user_defined_extra_vars = array();
            foreach($extra_var as $key => $val) {
                if(substr($key,0,11)!='extra_vars_') continue;
                preg_match('/^extra_vars_([0-9]+)_(.*)$/i', $key, $matches);
                if(!$matches[1] || !$matches[2]) continue;

                $user_defined_extra_vars[$matches[1]]->{$matches[2]} = $val;
                unset($extra_var->{$key});
            }
            for($i=1;$i<=20;$i++) if(!$user_defined_extra_vars[$i]->name) unset($user_defined_extra_vars[$i]);
            $extra_var->extra_vars = $user_defined_extra_vars;

            $oDB = &DB::getInstance();
            $oDB->begin();

            // module_srl이 넘어오면 원 모듈이 있는지 확인
            if($args->module_srl) {
                $oModuleModel = &getModel('module');
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($args->module_srl);

                // 만약 원래 모듈이 없으면 새로 입력하기 위한 처리
                if($module_info->module_srl != $args->module_srl) unset($args->module_srl);
            }

            // $extra_var를 serialize
            $args->extra_vars = serialize($extra_var);

            // module 모듈의 controller 객체 생성
            $oModuleController = &getController('module');

            // is_default=='Y' 이면
            if($args->is_default=='Y') $oModuleController->clearDefaultModule();

            // module_srl의 값에 따라 insert/update
            if(!$args->module_srl) {
                // 블로그 등록
                $output = $oModuleController->insertModule($args);
                if(!$output->toBool()) {
                    $oDB->rollback();
                    return $output;
                }

                // 글작성, 파일첨부, 댓글 파일첨부, 관리에 대한 권한 지정
                if($output->toBool()) {
                    $oMemberModel = &getModel('member');
                    $admin_group = $oMemberModel->getAdminGroup();
                    $admin_group_srl = $admin_group->group_srl;

                    $module_srl = $output->get('module_srl');
                    $grants = serialize(array('write_document'=>array($admin_group_srl), 'fileupload'=>array($admin_group_srl), 'comment_fileupload'=>array($admin_group_srl), 'manager'=>array($admin_group_srl)));

                    $output = $oModuleController->updateModuleGrant($module_srl, $grants);
                    if(!$output->toBool()) {
                        $oDB->rollback();
                        return $output;
                    }
                }

                // 레이아웃 등록
                $layout_args->layout_srl = $layout_args->module_srl = $module_srl;
                $layout_args->layout = 'blog';
                $layout_args->title = sprintf('%s - %s',$args->browser_title, $args->mid);
                $layout_args->layout_path = sprintf('./modules/blog/skins/%s/layout.html', $args->skin);

                $oLayoutController = &getAdminController('layout');
                $output = $oLayoutController->insertLayout($layout_args);
                if(!$output->toBool()) {
                    $oDB->rollback();
                    return $output;
                }

                // 기본 카테고리 등록
                $category_args->module_srl = $args->module_srl;
                $category_args->category_srl = getNextSequence();
                $category_args->title = 'Story';
                $category_args->expand = 'N';
                $this->procBlogAdminInsertCategory($category_args);

                $msg_code = 'success_registed';
            } else {
                // 블로그 데이터 수정
                $output = $oModuleController->updateModule($args);
                if(!$output->toBool()) {
                    $oDB->rollback();
                    return $output;
                }

                // 레이아웃 수정
                $layout_args->mid = $args->mid;
                $layout_args->layout_srl = $layout_args->module_srl = $module_srl = $output->get('module_srl');
                $layout_args->title = $args->browser_title;
                $layout_args->layout_path = sprintf('./modules/blog/skins/%s/layout.html', $args->skin);

                $oLayoutAdminController = &getAdminController('layout');
                $output = $oLayoutAdminController->updateLayout($layout_args);
                if(!$output->toBool()) {
                    $oDB->rollback();
                    return $output;
                }

                $msg_code = 'success_updated';
            }

            $oDB->commit();

            $this->add('page',Context::get('page'));
            $this->add('module_srl',$output->get('module_srl'));
            $this->setMessage($msg_code);
        }

        /**
         * @brief 카테고리 추가
         **/
        function procBlogAdminInsertCategory($args = null) {
            // 입력할 변수 정리
            if(!$args) $args = Context::gets('module_srl','category_srl','parent_srl','title','expand','group_srls');

            if($args->expand !="Y") $args->expand = "N";
            $args->group_srls = str_replace('|@|',',',$args->group_srls);
            $args->parent_srl = (int)$args->parent_srl;

            $oDocumentController = &getController('document');
            $oDocumentModel = &getModel('document');

            $oDB = &DB::getInstance();
            $oDB->begin();

            // 이미 존재하는지를 확인
            if($args->category_srl) {
                $category_info = $oDocumentModel->getCategory($args->category_srl);
                if($category_info->category_srl != $args->category_srl) $args->category_srl = null;
            }

            // 존재하게 되면 update를 해준다
            if($args->category_srl) {
                $output = $oDocumentController->updateCategory($args);
                if(!$output->toBool()) {
                    $oDB->rollback();
                    return $output;
                }

            // 존재하지 않으면 insert를 해준다
            } else {
                $output = $oDocumentController->insertCategory($args);
                if(!$output->toBool()) {
                    $oDB->rollback();
                    return $output;
                }
            }

            // XML 파일을 갱신하고 위치을 넘겨 받음
            $xml_file = $oDocumentController->makeCategoryXmlFile($args->module_srl);

            $oDB->commit();

            $this->add('xml_file', $xml_file);
            $this->add('module_srl', $args->module_srl);
            $this->add('category_srl', $args->category_srl);
            $this->add('parent_srl', $args->parent_srl);
        }


        /**
         * @brief 블로그 삭제
         **/
        function procBlogAdminDeleteBlog() {
            $module_srl = Context::get('module_srl');

            $oDB = &DB::getInstance();
            $oDB->begin();

            // 블로그 모듈 삭제
            $oModuleController = &getController('module');
            $output = $oModuleController->deleteModule($module_srl);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // 레이아웃 삭제
            $layout_args->layout_srl = $layout_args->module_srl = $module_srl;
            $oLayoutAdminController = &getAdminController('layout');
            $output = $oLayoutAdminController->deleteLayout($layout_args);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            $oDB->commit();

            $this->add('module','blog');
            $this->add('page',Context::get('page'));
            $this->setMessage('success_deleted');
        }

        /**
         * @brief 카테고리 삭제
         **/
        function procBlogAdminDeleteCategory() {
            // 변수 정리 
            $args = Context::gets('module_srl','category_srl');

            $oDB = &DB::getInstance();
            $oDB->begin();

            $oDocumentModel = &getModel('document');

            // 원정보를 가져옴 
            $category_info = $oDocumentModel->getCategory($args->category_srl);
            if($category_info->parent_srl) $parent_srl = $category_info->parent_srl;

            // 자식 노드가 있는지 체크하여 있으면 삭제 못한다는 에러 출력
            if($oDocumentModel->getCategoryChlidCount($args->category_srl)) return new Object(-1, 'msg_cannot_delete_for_child');

            // DB에서 삭제
            $oDocumentController = &getController('document');
            $output = $oDocumentController->deleteCategory($args->category_srl);
            if(!$output->toBool()) {
                $oDB->rollback();
                return $output;
            }

            // XML 파일을 갱신하고 위치을 넘겨 받음
            $xml_file = $oDocumentController->makeCategoryXmlFile($args->module_srl);

            $oDB->commit();

            $this->add('xml_file', $xml_file);
            $this->add('category_srl', $parent_srl);
            $this->setMessage('success_deleted');
        }

        /**
         * @brief 카테고리 이동
         **/
        function procBlogAdminMoveCategory() {
            $source_category_srl = Context::get('source_category_srl');
            $target_category_srl = Context::get('target_category_srl');

            $oDocumentModel = &getModel('document');
            $oDocumentController = &getController('document');

            $target_category = $oDocumentModel->getCategory($target_category_srl);
            $source_category = $oDocumentModel->getCategory($source_category_srl);

            // source_category에 target_category_srl의 parent_srl, listorder 값을 입력
            $source_args->category_srl = $source_category_srl;
            $source_args->parent_srl = $target_category->parent_srl;
            $source_args->listorder = $target_category->listorder;
            $output = $oDocumentController->updateCategory($source_args);
            if(!$output->toBool()) return $output;

            // target_category의 listorder값을 +1해 준다
            $target_args->category_srl = $target_category_srl;
            $target_args->parent_srl = $target_category->parent_srl;
            $target_args->listorder = $target_category->listorder -1;
            $output = $oDocumentController->updateCategory($target_args);
            if(!$output->toBool()) return $output;

            // xml파일 재생성 
            $xml_file = $oDocumentController->makeCategoryXmlFile($source_category->module_srl);

            // return 변수 설정
            $this->add('xml_file', $xml_file);
            $this->add('source_category_srl', $source_category_srl);
        }

        /**
         * @brief xml 파일을 갱신
         * 관리자페이지에서 메뉴 구성 후 간혹 xml파일이 재생성 안되는 경우가 있는데\n
         * 이럴 경우 관리자의 수동 갱신 기능을 구현해줌\n
         * 개발 중간의 문제인 것 같고 현재는 문제가 생기지 않으나 굳이 없앨 필요 없는 기능
         **/
        function procBlogAdminMakeXmlFile() {
            // 입력값을 체크 
            $module_srl = Context::get('module_srl');

            // xml파일 재생성 
            $oDocumentController = &getController('document');
            $xml_file = $oDocumentController->makeCategoryXmlFile($module_srl);

            // return 값 설정 
            $this->add('xml_file',$xml_file);
        }
    }
?>
