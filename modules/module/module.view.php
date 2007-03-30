<?php
    /**
     * @class  moduleView
     * @author zero (zero@nzeo.com)
     * @brief  module 모듈의 View class
     **/

    class moduleView extends module {

        /**
         * @brief 초기화
         **/
        function init() {
            // template path 지정
            $this->setTemplatePath($this->module_path.'tpl');
        }

        /**
         * @brief 모듈 관리자 페이지
         **/
        function dispModuleAdminContent() {
            $this->dispModuleAdminList();
        }

        /**
         * @brief 모듈 목록 출력
         **/
        function dispModuleAdminList() {
            // 모듈 목록을 구해서 
            $oModuleModel = &getModel('module');
            $module_list = $oModuleModel->getModuleList();
            Context::set('module_list', $module_list);

            // 템플릿 파일 지정
            $this->setTemplateFile('module_list');
        }

        /**
         * @brief 모듈의 상세 정보(conf/info.xml)를 팝업 출력
         **/
        function dispModuleAdminInfo() {
            // 모듈 목록을 구해서 
            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoXml(Context::get('selected_module'));
            Context::set('module_info', $module_info);

            // 레이아웃을 팝업으로 지정
            $this->setLayoutFile('popup_layout');

            // 템플릿 파일 지정
            $this->setTemplateFile('module_info');
        }

        /**
         * @brief 모듈 카테고리 목록
         **/
        function dispModuleAdminCategory() {
            $module_category_srl = Context::get('module_category_srl');
            
            // 모듈 목록을 구해서 
            $oModuleModel = &getModel('module');

            // 선택된 카테고리가 있으면 해당 카테고리의 정보 수정 페이지로
            if($module_category_srl) {
                $selected_category  = $oModuleModel->getModuleCategory($module_category_srl);
                Context::set('selected_category', $selected_category);

                // 템플릿 파일 지정
                $this->setTemplateFile('category_update_form');

            // 아니면 전체 목록
            } else {
                $category_list = $oModuleModel->getModuleCategories();
                Context::set('category_list', $category_list);

                // 템플릿 파일 지정
                $this->setTemplateFile('category_list');
            }
        }

    }
?>
