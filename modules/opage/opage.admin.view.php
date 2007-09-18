<?php
    /**
     * @class  opageAdminView
     * @author zero (zero@nzeo.com)
     * @brief  opage 모듈의 admin view 클래스
     **/

    class opageAdminView extends opage {

        var $module_srl = 0;
        var $list_count = 20;
        var $page_count = 10;

        /**
         * @brief 초기화
         **/
        function init() {
            // module_srl이 있으면 미리 체크하여 존재하는 모듈이면 module_info 세팅
            $module_srl = Context::get('module_srl');

            // module model 객체 생성 
            $oModuleModel = &getModel('module');

            // 모듈 카테고리 목록을 구함
            $module_category = $oModuleModel->getModuleCategories();
            Context::set('module_category', $module_category);

            // 템플릿 경로 구함 (opage의 경우 tpl에 관리자용 템플릿 모아놓음)
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * @brief 외부페이지 관리 목록 보여줌
         **/
        function dispOpageAdminContent() {
            $args->sort_index = "module_srl";
            $args->page = Context::get('page');
            $args->list_count = 40;
            $args->page_count = 10;
            $args->s_module_category_srl = Context::get('module_category_srl');
            $output = executeQuery('opage.getOpageList', $args);

            // 템플릿에 쓰기 위해서 context::set
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('opage_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            // 템플릿 파일 지정
            $this->setTemplateFile('index');
        }

        /**
         * @brief 선택된 외부페이지의 정보 출력
         **/
        function dispOpageAdminInfo() {
            // GET parameter에서 module_srl을 가져옴
            $module_srl = Context::get('module_srl');

            // module model 객체 생성 
            if($module_srl) {
                $oOpageModel = &getModel('opage');
                $module_info = $oOpageModel->getOpage($module_srl);
                if(!$module_info) {
                    unset($module_info);
                    unset($module_srl);
                } else {
                    Context::set('module_info',$module_info);
                }

            // module_srl 값이 없다면 그냥 index 외부페이지를 보여줌
            } else {
                return $this->dispOpageAdminContent();
            }

            // 레이아웃이 정해져 있다면 레이아웃 정보를 추가해줌(layout_title, layout)
            if($module_info->layout_srl) {
                $oLayoutModel = &getModel('layout');
                $layout_info = $oLayoutModel->getLayout($module_info->layout_srl);
                $module_info->layout = $layout_info->layout;
                $module_info->layout_title = $layout_info->layout_title;
            }

            // 레이아웃 목록을 구해옴
            $oLayoutMode = &getModel('layout');
            $layout_list = $oLayoutMode->getLayoutList();
            Context::set('layout_list', $layout_list);


            // 템플릿 파일 지정
            $this->setTemplateFile('opage_info');
        }

        /**
         * @brief 외부페이지 추가 폼 출력
         **/
        function dispOpageAdminInsert() {
            // 권한 그룹의 목록을 가져온다
            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups();
            Context::set('group_list', $group_list);

            // module.xml에서 권한 관련 목록을 구해옴
            $grant_list = $this->xml_info->grant;
            Context::set('grant_list', $grant_list);

            // GET parameter에서 module_srl을 가져옴
            $module_srl = Context::get('module_srl');

            // module_srl이 있으면 해당 모듈의 정보를 구해서 세팅
            if($module_srl) {
                $oModuleModel = &getModel('module');
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
                if($module_info->module_srl == $module_srl) Context::set('module_info',$module_info);
                else {
                    unset($module_info);
                    unset($module_srl);
                }
            }

            // module_srl이 없으면 sequence값으로 미리 구해 놓음
            if(!$module_srl) $module_srl = getNextSequence();
            Context::set('module_srl',$module_srl);

            // 레이아웃 목록을 구해옴
            $oLayoutMode = &getModel('layout');
            $layout_list = $oLayoutMode->getLayoutList();
            Context::set('layout_list', $layout_list);


            // 템플릿 파일 지정
            $this->setTemplateFile('opage_insert');
        }


        /**
         * @brief 외부페이지 삭제 화면 출력
         **/
        function dispOpageAdminDelete() {
            $module_srl = Context::get('module_srl');
            if(!$module_srl) return $this->dispContent();

            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
            Context::set('module_info',$module_info);

            // 템플릿 파일 지정
            $this->setTemplateFile('opage_delete');
        }

    }
?>
