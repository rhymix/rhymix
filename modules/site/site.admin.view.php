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

		function dispSiteAdminIndex()
		{
			// Get Module List in Virtual Site
			$site_module_info = Context::get('current_module_info');
			$args->site_srl = $site_module_info->site_srl;
			$columnList = array('module_srl');

			$oModuleModel = &getModel('module');
			$output = $oModuleModel->getModuleSrlList($args, $columnList);
			$moduleSrlList = array();
			if(is_array($output))
			{
				foreach($output AS $key=>$value)
					array_push($moduleSrlList, $value->module_srl);
			}

            // Get statistics
            $args->date = date("Ymd000000", time()-60*60*24);
            $today = date("Ymd");

            // Member Status
			// TODO add site srl
			$oMemberAdminModel = &getAdminModel('member');
			$status->member->todayCount = $oMemberAdminModel->getMemberGroupMemberCountByDate($today);
			$status->member->totalCount = $oMemberAdminModel->getMemberGroupMemberCountByDate();

			// Document Status
			$oDocumentAdminModel = &getAdminModel('document');
			$status->document->todayCount = $oDocumentAdminModel->getDocumentCountByDate($today, $moduleSrlList);
			$status->document->totalCount = $oDocumentAdminModel->getDocumentCountByDate('', $moduleSrlList);

            // Comment Status
			$oCommentModel = &getModel('comment');
			$status->comment->todayCount = $oCommentModel->getCommentCountByDate($today, $moduleSrlList);
			$status->comment->totalCount = $oCommentModel->getCommentCountByDate('', $moduleSrlList);

            // Trackback Status 
			$oTrackbackAdminModel = &getAdminModel('trackback');
			$status->trackback->todayCount = $oTrackbackAdminModel->getTrackbackCountByDate($today, $moduleSrlList);
			$status->trackback->totalCount = $oTrackbackAdminModel->getTrackbackCountByDate('', $moduleSrlList);

            Context::set('status', $status);

            // Latest Document
			$oDocumentModel = &getModel('document');
			$columnList = array('document_srl', 'module_srl', 'category_srl', 'title', 'nick_name', 'member_srl');
			$args->module_srl = $moduleSrlList;
			$args->list_count = 5;;
			$output = $oDocumentModel->getDocumentList($args, false, false, $columnList);
            Context::set('latestDocumentList', $output->data);
			unset($args, $output, $columnList);

			// Latest Comment
			$oCommentModel = &getModel('comment');
			$columnList = array('comment_srl', 'module_srl', 'document_srl', 'content', 'nick_name', 'member_srl');
			$args->module_srl = $moduleSrlList;
			$args->list_count = 5;
			$output = $oCommentModel->getNewestCommentList($args, $columnList);
			if(is_array($output))
			{
				foreach($output AS $key=>$value)
					$value->content = strip_tags($value->content);
			}
            Context::set('latestCommentList', $output);
			unset($args, $output, $columnList);

			//Latest Trackback
			$oTrackbackModel = &getModel('trackback');
			$columnList = array();
			$args->module_srl = $moduleSrlList;
			$args->list_count = 5;
			$output =$oTrackbackModel->getNewestTrackbackList($args);
            Context::set('latestTrackbackList', $output->data);
			unset($args, $output, $columnList);

            $this->setTemplateFile('index');
		}
	}
?>
