<?php
    /**
     * @class  planetAdminView
     * @author sol (sol@ngleader.com)
     * @brief  planet 모듈의 admin view class
     **/

    class planetAdminView extends planet {

        /**
         * @brief 초기화
         **/
        function init() {
            $oPlanetModel = &getModel('planet');
            $this->module_info = $oPlanetModel->getPlanetConfig();
            Context::set('module_info',$this->module_info);

            $this->setTemplatePath($this->module_path."/tpl/");
            $template_path = sprintf("%stpl/",$this->module_path);
            $this->setTemplatePath($template_path);
        }

        function dispPlanetAdminSetup() {

            $oModuleModel = &getModel('module');
            $skin_list = $oModuleModel->getSkins($this->module_path);
            Context::set('skin_list',$skin_list);

            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups();
            Context::set('group_list', $group_list);
            if(is_array($this->module_info->tagtab)) Context::set('tagtab', join(',',$this->module_info->tagtab));
            if(is_array($this->module_info->tagtab_after)) Context::set('tagtab_after', join(',',$this->module_info->tagtab_after));
            if(is_array($this->module_info->smstag)) Context::set('smstag', join(',',$this->module_info->smstag));

            // 레이아웃 목록을 구해옴
            $oLayoutMode = &getModel('layout');
            $layout_list = $oLayoutMode->getLayoutList();
            Context::set('layout_list', $layout_list);


            $this->setTemplateFile('setup');
        }

        function dispPlanetAdminList() {

            $page = Context::get('page');
            if(!$page) $page = 1;

            $oPlanetModel = &getModel('planet');
            $output = $oPlanetModel->getPlanetList(20, $page, 'regdate');

            Context::set('planet_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);

            $this->setTemplateFile('list');
        }

        function dispPlanetAdminInsert() {
            $module_srl = Context::get('module_srl');
            if($module_srl) {
                $oPlanetModel = &getModel('planet');
                Context::set('planet', $oPlanetModel->getPlanet($module_srl));
            }

            $this->setTemplateFile('insert');
        }

        function dispPlanetAdminDelete() {
            if(!Context::get('module_srl')) return $this->dispPlanetAdminList();
            $module_srl = Context::get('module_srl');

            $oPlanetModel = &getModel('planet');
            $oPlanet = $oPlanetModel->getPlanet($module_srl);
            $planet_info = $oPlanet->getObjectVars();

            $oDocumentModel = &getModel('document');
            $document_count = $oDocumentModel->getDocumentCount($planet_info->module_srl);
            $planet_info->document_count = $document_count;

            Context::set('planet_info',$planet_info);

            // 템플릿 파일 지정
            $this->setTemplateFile('planet_delete');
        }

        function dispPlanetAdminSkinInfo() {
            // 공통 모듈 권한 설정 페이지 호출
            $oModuleAdminModel = &getAdminModel('module');
            $skin_content = $oModuleAdminModel->getModuleSkinHTML($this->module_info->module_srl);
            Context::set('skin_content', $skin_content);

            $this->setTemplateFile('skin_info');
        }

        /**
         * @brief 권한 목록 출력
         **/
        function dispPlanetAdminGrantInfo() {
            Context::set('module_srl', $this->module_info->module_srl);

            // 공통 모듈 권한 설정 페이지 호출
            $oModuleAdminModel = &getAdminModel('module');
            $grant_content = $oModuleAdminModel->getModuleGrantHTML($this->module_info->module_srl, $this->xml_info->grant);
            Context::set('grant_content', $grant_content);

            $this->setTemplateFile('grant_list');
        }
    }

?>
