<?php
    /**
     * @class  siteAdminView
     * @author NHN (developers@xpressengine.com)
     * @brief  site view class of admin module
     **/

    class siteAdminView extends site {
        var $site_module_info = null;
		var $site_srl = 0;

        /**
         * @brief Initilization
         * @return none
         **/
        function init() {
			
			$oMemberModel = &getModel('member');
			$logged_info = $oMemberModel->getLoggedInfo();

            $oModuleModel = &getModel('module');
			$this->site_module_info = Context::get('current_module_info');
			$this->site_srl = $this->site_module_info->site_srl;

			if (!Context::get('is_logged') || !$oModuleModel->isSiteAdmin($logged_info, $this->site_module_info->site_srl)) return $this->stop('msg_not_permitted');
		
			$this->site_srl = $this->site_module_info->site_srl;
			if(!$this->site_srl) return $this->stop('msg_invalid_request');

			$this->setTemplatePath($this->module_path.'tpl');
			
			
		}
	}
?>
