<?php
    /**
     * @class  lifepodAdminView
     * @author zero (zero@nzeo.com)
     * @brief  lifepod 모듈의 admin view class
     * 모듈 관리자 기능은 생성된 모듈 목록, 신규 등록 및 수정, 권한의 설정으로 이루어진다.
     **/

    class lifepodAdminView extends lifepod {

        /**
         * @brief 초기화
         * 손쉬운 사용을 위해서 module_srl이 넘어올 경우 해당 모듈의 모듈 정보를 미리 구해서 세팅해 놓도록 한다.
         * 각 method에서 하거나 별도의 method를 만들어서 호출하면 되지만 코드의 양을 줄이고 직관성을 높이기 위해서 설정한 코드이다.
         **/
        function init() {
            // module_srl값을 구해온다.
            $module_srl = Context::get('module_srl');

            if(!$module_srl && $this->module_srl) {
                $module_srl = $this->module_srl;
                Context::set('module_srl', $module_srl);
            }

            // module info를 구하기 위해 module model 객체 생성 
            $oModuleModel = &getModel('module');

            // 모듈 카테고리 목록을 구함
            $module_category = $oModuleModel->getModuleCategories();
            Context::set('module_category', $module_category); 

            // module_srl이 있다면 요청된 모듈의 정보를 미리 구해 놓음
            if($module_srl) {
                $module_info = $oModuleModel->getModuleInfoByModuleSrl($module_srl);
                if(!$module_info) {
                    Context::set('module_srl','');
                    $this->act = 'list';
                } else {
                    $this->module_info = $module_info;
                    Context::set('module_info',$module_info);
                }
            }

            // 템플릿 경로 지정, 관리자 페이지를 위한 템플릿은 별도의 스킨 기능이 없이 ./modules/모듈/tpl/ 에 위치해 놓기에 바로 지정을 해 놓는다.
            $template_path = sprintf("%stpl/",$this->module_path);
            $this->setTemplatePath($template_path);
        }

        /**
         * @brief 생성된 calendar들의 목록을 보여줌
         * lifepod이라는 module명으로 등록된 모듈을 구하기 위해서 몇가지 설정을 한 후에 쿼리를 수행한다.
         * 쿼리수행은 executeQuery(모듈명.쿼리아이디, 인자변수) 로 하게 되며 이 쿼리아이디에 해당하는 xml파일은 모듈의 queries디렉토리에 지정이 되어 있다.
         *
         * 이 특정 module의 목록은 module model객체에서 구할 수 있지만 검색등의 각 모듈마다 다른 조건 때문에 각 모듈별로 쿼리를 생성해 놓는다.
         * 모든 모듈의 결과물(mid)는 modules 테이블에 저장이 된다.
         **/
        function dispLifepodAdminContent() {
            $args->sort_index = "module_srl"; ///< 정렬 순서는 모듈의 sequence값으로 하고 정렬은 역순. 즉 생성된 순으로 한다.
            $args->page = Context::get('page'); ///< 현재 페이지를 설정
            $args->list_count = 40; ///< 한페이지에 40개씩 보여주기로 고정.
            $args->page_count = 10; ///< 페이지의 수는 10개로 제한.
            $args->s_module_category_srl = Context::get('module_category_srl'); ///< 모듈분류값을 인자로 추가
            $output = executeQuery('lifepod.getLifepodList', $args); ///< lifepod.getGuesbookList 쿼리 실행 (./modules/lifepod/query/getLifepodList.xml)

            /**
             * 템플릿에 쓰기 위해서 context::set
             * xml query에 navigation이 있고 list_count가 정의되어 있으면 결과 변수에 아래 5가지의 값이 세팅이 된다.
             **/
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('lifepod_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            // 템플릿 파일 지정 (./modules/lifepod/tpl/index.html파일이 지정이 됨)
            $this->setTemplateFile('index');
        }

        /**
         * @brief 선택된 모듈의 정보 출력
         **/
        function dispLifepodAdminLifepodInfo() {
            // module_srl 값이 없다면 그냥 index 페이지를 보여줌
            if(!Context::get('module_srl')) return $this->dispLifepodAdminContent();

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
            $this->setTemplateFile('lifepod_info');
        }

        /**
         * @brief 모듈 설정 폼 출력
         **/
        function dispLifepodAdminInsertLifepod() {
            // 스킨 목록을 구해옴
            $oModuleModel = &getModel('module');
            $skin_list = $oModuleModel->getSkins($this->module_path);
            Context::set('skin_list',$skin_list);

            // 레이아웃 목록을 구해옴
            $oLayoutMode = &getModel('layout');
            $layout_list = $oLayoutMode->getLayoutList();
            Context::set('layout_list', $layout_list);

            // 템플릿 파일 지정
            $this->setTemplateFile('lifepod_insert');
        }

        /**
         * @brief 모듈 삭제 화면 출력
         **/
        function dispLifepodAdminDeleteLifepod() {
            if(!Context::get('module_srl')) return $this->dispLifepodAdminContent();

            $module_info = Context::get('module_info');

            Context::set('module_info',$module_info);

            // 템플릿 파일 지정
            $this->setTemplateFile('lifepod_delete');
        }

        /**
         * @brief 스킨 정보 보여줌
         **/
        function dispLifepodAdminSkinInfo() {
            // 공통 모듈 권한 설정 페이지 호출
            $oModuleAdminModel = &getAdminModel('module');
            $skin_content = $oModuleAdminModel->getModuleSkinHTML($this->module_info->module_srl);
            Context::set('skin_content', $skin_content);

            $this->setTemplateFile('skin_info');
        }

        /**
         * @brief 권한 목록 출력
         **/
        function dispLifepodAdminGrantInfo() {
            // 공통 모듈 권한 설정 페이지 호출
            $oModuleAdminModel = &getAdminModel('module');
            $grant_content = $oModuleAdminModel->getModuleGrantHTML($this->module_info->module_srl, $this->xml_info->grant);
            Context::set('grant_content', $grant_content);

            $this->setTemplateFile('grant_list');
        }
    }
?>
