<?php
    /**
     * @class  integration_searchAdminView
     * @author zero (zero@nzeo.com)
     * @brief  integration_search module의 admin view class
     *
     * 통합검색 관리
     *
     **/

    class integration_searchAdminView extends integration_search {

        var $config = null;

        /**
         * @brief 초기화
         **/
        function init() {
            // 설정 정보를 받아옴 (module model 객체를 이용)
            $oModuleModel = &getModel('module');
            $this->config = $oModuleModel->getModuleConfig('integration_search');
            if(!$this->config->target_mid) $this->config->target_mid = array();
            Context::set('config',$this->config);

            $this->setTemplatePath($this->module_path."/tpl/");
        }

        /**
         * @brief 모듈 선정 및 스킨 설정
         **/
        function dispIntegration_searchAdminContent() {
            // 스킨 목록을 구해옴
            $oModuleModel = &getModel('module');
            $skin_list = $oModuleModel->getSkins($this->module_path);
            Context::set('skin_list',$skin_list);

            // 생성된 mid목록을 구함
            $mid_list = $oModuleModel->getMidList();
            Context::set('mid_list',$mid_list);

            // 샘플코드
            Context::set('sample_code', htmlspecialchars('<form action="{getUrl()}" method="get"><input type="hidden" name="mid" value="{$mid}" /><input type="hidden" name="act" value="IS" /><input type="text" name="is_keyword" class="inputTypeText" value="{$is_keyword}" /><span class="button"><input type="submit" value="{$lang->cmd_search}" /></span></a>') );

            $this->setTemplateFile("index");
        }

        /**
         * @brief 스킨 설정
         **/
        function dispIntegration_searchAdminSkinInfo() {
            $oModuleModel = &getModel('module');
            $skin_info = $oModuleModel->loadSkinInfo($this->module_path, $this->config->skin);
            $skin_vars = unserialize($this->config->skin_vars);

            // skin_info에 extra_vars 값을 지정
            if(count($skin_info->extra_vars)) {
                foreach($skin_info->extra_vars as $key => $val) {
                    $name = $val->name;
                    $type = $val->type;
                    $value = $skin_vars->{$name};
                    if($type=="checkbox"&&!$value) $value = array();
                    $skin_info->extra_vars[$key]->value= $value;
                }
            }
            Context::set('skin_info', $skin_info);
            Context::set('skin_vars', $skin_vars);

            $this->setTemplateFile("skin_info");
        }
    }
?>
