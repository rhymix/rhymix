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
            $this->config = $oPlanetModel->getPlanetConfig();
            Context::set('config',$this->config);
            $oPlanetModel->isAccessGranted();

            $this->setTemplatePath($this->module_path."/tpl/");
            $template_path = sprintf("%stpl/",$this->module_path);
            $this->setTemplatePath($template_path);
        }

        function dispPlanetAdminSetup() {

            $oModuleModel = &getModel('module');
            $skin_list = $oModuleModel->getSkins($this->module_path);
            Context::set('skin_list',$skin_list);

            $grant_list = $this->xml_info->grant;
            Context::set('grant_list', $grant_list);

            $oMemberModel = &getModel('member');
            $group_list = $oMemberModel->getGroups();
            Context::set('group_list', $group_list);
            if(is_array($this->config->tagtab)) Context::set('tagtab', join(',',$this->config->tagtab));
            if(is_array($this->config->tagtab_after)) Context::set('tagtab_after', join(',',$this->config->tagtab_after));
            if(is_array($this->config->smstag)) Context::set('smstag', join(',',$this->config->smstag));

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
            $oPlanetModel = &getModel('planet');
            $config = $oPlanetModel->getPlanetConfig();
            $skin = $config->planet_default_skin;

            $oModuleModel = &getModel('module');
            $skin_info = $oModuleModel->loadSkinInfo($this->module_path, $skin);

            // skin_info에 extra_vars 값을 지정
            if(count($skin_info->extra_vars)) {
                foreach($skin_info->extra_vars as $key => $val) {
                    $group = $val->group;
                    $name = $val->name;
                    $type = $val->type;
                    $value = $config->{$name};
                    if($type=="checkbox"&&!$value) $value = array();
                    $skin_info->extra_vars[$key]->value= $value;
                }
            }

            Context::set('skin_info', $skin_info);
            $this->setTemplateFile('skin_info');
        }

    }

?>
