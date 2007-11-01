<?php
    /**
     * @class  blogAdminView
     * @author zero (zero@nzeo.com)
     * @brief  blog 모듈의 admin view class
     **/

    class blogAdminView extends blog {

        /**
         * @brief 초기화
         *
         * blog 모듈은 일반 사용과 관리자용으로 나누어진다.\n
         **/
        function init() {
            // module_srl이 있으면 미리 체크하여 존재하는 모듈이면 module_info 세팅
            $module_srl = Context::get('module_srl');
            if(!$module_srl && $this->module_srl) {
                $module_srl = $this->module_srl;
                Context::set('module_srl', $module_srl);
            }

            // module model 객체 생성 
            $oModuleModel = &getModel('module');

            // module_srl이 넘어오면 해당 모듈의 정보를 미리 구해 놓음
            if($module_srl) {
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
                if($module_info->module_srl == $module_srl) {
                    $this->module_info = $module_info;
                    Context::set('module_info',$module_info);
                }
            }

            // 모듈 카테고리 목록을 구함
            $module_category = $oModuleModel->getModuleCategories();
            Context::set('module_category', $module_category);

            // 만약 블로그 서비스 페이지에서 관리자 기능 호출시 요청된 블로그의 정보와 레이아웃 가져옴
            if($this->mid) {
                $oView = &getView('blog');
                $oView->setModuleInfo($this->module_info, $this->xml_info);
                $oView->init();
            }

            // 템플릿 경로 지정 (blog의 경우 tpl에 관리자용 템플릿 모아놓음)
            $this->setTemplatePath($this->module_path."tpl");
        }

        /**
         * @brief 블로그 관리 목록 보여줌
         **/
        function dispBlogAdminContent() {
            // 등록된 blog 모듈을 불러와 세팅
            $args->sort_index = "module_srl";
            $args->page = Context::get('page');
            $args->list_count = 40;
            $args->page_count = 10;
            $args->s_module_category_srl = Context::get('module_category_srl');
            $output = executeQuery('blog.getBlogList', $args);

            // 템플릿에 쓰기 위해서 context::set
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('blog_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            // 템플릿 파일 지정
            $this->setTemplateFile('index');
        }

        /**
         * @brief 선택된 블로그의 정보 출력
         **/
        function dispBlogAdminBlogInfo() {

            // module_srl 값이 없다면 그냥 index 페이지를 보여줌
            if(!Context::get('module_srl')) return $this->dispBlogAdminContent();

            // 레이아웃이 정해져 있다면 레이아웃 정보를 추가해줌(layout_title, layout)
            if($this->module_info->layout_srl) {
                $oLayoutModel = &getModel('layout');
                $layout_info = $oLayoutModel->getLayout($this->module_info->layout_srl);
                $this->module_info->layout = $layout_info->layout;
                $this->module_info->layout_title = $layout_info->layout_title;
            }

            // 정해진 스킨이 있으면 해당 스킨의 정보를 구함
            if($this->module_info->skin) {
                $oModuleModel = &getModel('module');
                $skin_info = $oModuleModel->loadSkinInfo($this->module_path, $this->module_info->skin);
                $this->module_info->skin_title = $skin_info->title;
            }

            // 템플릿 파일 지정
            $this->setTemplateFile('blog_info');
        }

        /**
         * @brief 블로그 추가 폼 출력
         **/
        function dispBlogAdminInsertBlog() {

            // 스킨 목록을 구해옴
            $oModuleModel = &getModel('module');
            $skin_list = $oModuleModel->getSkins($this->module_path);
            Context::set('skin_list',$skin_list);

            // 템플릿 파일 지정
            $this->setTemplateFile('blog_insert');
        }

        /**
         * @brief 블로그 추가 설정 보여줌
         * 추가설정은 서비스형 모듈들에서 다른 모듈과의 연계를 위해서 설정하는 페이지임
         **/
        function dispBlogAdminBlogAdditionSetup() {
            // content는 다른 모듈에서 call by reference로 받아오기에 미리 변수 선언만 해 놓음
            $content = '';

            // 추가 설정을 위한 트리거 호출 
            // 블로그 모듈이지만 차후 다른 모듈에서의 사용도 고려하여 trigger 이름을 공용으로 사용할 수 있도록 하였음
            $output = ModuleHandler::triggerCall('module.dispAdditionSetup', 'before', $content);
            $output = ModuleHandler::triggerCall('module.dispAdditionSetup', 'after', $content);
            Context::set('setup_content', $content);

            // 템플릿 파일 지정
            $this->setTemplateFile('addition_setup');
        }

        /**
         * @brief 블로그 삭제 화면 출력
         **/
        function dispBlogAdminDeleteBlog() {

            if(!Context::get('module_srl')) return $this->dispBlogAdminContent();

            $module_info = Context::get('module_info');

            $oDocumentModel = &getModel('document');
            $document_count = $oDocumentModel->getDocumentCount($module_info->module_srl);
            $module_info->document_count = $document_count;

            Context::set('module_info',$module_info);

            // 템플릿 파일 지정
            $this->setTemplateFile('blog_delete');
        }

        /**
         * @brief 스킨 정보 보여줌
         **/
        function dispBlogAdminSkinInfo() {

            // 현재 선택된 모듈의 스킨의 정보 xml 파일을 읽음
            $module_info = Context::get('module_info');
            $skin = $module_info->skin;

            $oModuleModel = &getModel('module');
            $skin_info = $oModuleModel->loadSkinInfo($this->module_path, $skin);

            // skin_info에 extra_vars 값을 지정
            if(count($skin_info->extra_vars)) {
                foreach($skin_info->extra_vars as $key => $val) {
                    $name = $val->name;
                    $type = $val->type;
                    $value = $module_info->{$name};
                    if($type=="checkbox"&&!$value) $value = array();
                    $skin_info->extra_vars[$key]->value= $value;
                }
            }

            // skin_info에 menu값을 지정 
            if(count($skin_info->menu)) {
                foreach($skin_info->menu as $key => $val) {
                    if($module_info->{$key}) $skin_info->menu->{$key}->menu_srl = $module_info->{$key};
                }
            }

            // 메뉴를 가져옴
            $oMenuAdminModel = &getAdminModel('menu');
            $menu_list = $oMenuAdminModel->getMenus();
            Context::set('menu_list', $menu_list);

            Context::set('skin_info', $skin_info);
            $this->setTemplateFile('skin_info');
        }

        /**
         * @brief 카테고리의 정보 출력
         **/
        function dispBlogAdminCategoryInfo() {
            // module_srl을 구함
            $module_srl = $this->module_info->module_srl;

            // 카테고리 정보를 가져옴
            $oDocumentModel = &getModel('document');
            $category_xml_file = $oDocumentModel->getCategoryXmlFile($module_srl);

            Context::set('category_xml_file', $category_xml_file);
            Context::addJsFile('./common/js/tree_menu.js');

            Context::set('layout','none');
            $this->setTemplateFile('category_list');
        }

        /**
         * @brief 권한 목록 출력
         **/
        function dispBlogAdminGrantInfo() {
            // module_srl을 구함
            $module_srl = Context::get('module_srl');

            // module.xml에서 권한 관련 목록을 구해옴
            $grant_list = $this->xml_info->grant;
            Context::set('grant_list', $grant_list);

            // 권한 그룹의 목록을 가져온다
            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups();
            Context::set('group_list', $group_list);

            $this->setTemplateFile('grant_list');
        }
    }
?>
