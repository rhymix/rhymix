<<<<<<< .working
<?php
    /**
     * @class  pollAdminView
     * @author NHN (developers@xpressengine.com)
     * @brief The admin view class of the poll module
     **/

    class pollAdminView extends poll {

        /**
         * @brief Initialization
         **/
        function init() {
        }

        /**
         * @brief Administrator's Page
         **/
        function dispPollAdminList() {
            // Arrange the search options
            $search_target = trim(Context::get('search_target'));
            $search_keyword = trim(Context::get('search_keyword'));

            if($search_target && $search_keyword) {
                switch($search_target) {
                    case 'title' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_title= $search_keyword;
                        break;
                    case 'regdate' :
                            $args->s_regdate = $search_keyword;
                        break;
                    case 'ipaddress' :
                            $args->s_ipaddress= $search_keyword;
                        break;
                }
            }
            // Options to get a list of pages
            $args->page = Context::get('page');
            $args->list_count = 50; // The number of posts to show on one page
            $args->page_count = 10; // The number of pages to display in the page navigation

            $args->sort_index = 'P.list_order'; // Sorting value

            // Get the list
            $oPollAdminModel = &getAdminModel('poll');
            $output = $oPollAdminModel->getPollListWithMember($args);

			// check poll type. document or comment
			if(is_array($output->data))
			{
				$uploadTargetSrlList = array();
				foreach($output->data AS $key=>$value)
				{
					array_push($uploadTargetSrlList, $value->upload_target_srl);
				}

            	$oDocumentModel = &getModel('document');
				$targetDocumentOutput = $oDocumentModel->getDocuments($uploadTargetSrlList);
				if(!is_array($targetDocumentOutput)) $targetDocumentOutput = array();

				$oCommentModel = &getModel('comment');
				$columnList = array('comment_srl', 'document_srl');
				$targetCommentOutput = $oCommentModel->getComments($uploadTargetSrlList, $columnList);
				if(!is_array($targetCommentOutput)) $targetCommentOutput = array();

				foreach($output->data AS $key=>$value)
				{
					if(array_key_exists($value->upload_target_srl, $targetDocumentOutput))
						$value->document_srl = $value->upload_target_srl;

					if(array_key_exists($value->upload_target_srl, $targetCommentOutput))
					{
						$value->comment_srl = $value->upload_target_srl;
						$value->document_srl = $targetCommentOutput[$value->comment_srl]->document_srl;
					}
				}
			}

            // Configure the template variables
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('poll_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);
            Context::set('module_list', $module_list);
            // Set a template
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('poll_list');
        }

        /**
         * @brief Confgure the poll skin and colorset
         **/
        function dispPollAdminConfig() {
            $oModuleModel = &getModel('module');
            // Get the configuration information
            $config = $oModuleModel->getModuleConfig('poll');
            Context::set('config', $config);
            // Get the skin information
            $skin_list = $oModuleModel->getSkins($this->module_path);
            Context::set('skin_list', $skin_list);

            if(!$skin_list[$config->skin]) $config->skin = "default";
            // Set the skin colorset once the configurations is completed
            Context::set('colorset_list', $skin_list[$config->skin]->colorset);
        
            // Set a template
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('config');
        }

        /**
         * @brief Poll Results
         **/
        function dispPollAdminResult() {
            // Popup layout
            $this->setLayoutFile("popup_layout");
            // Draw results
            $args->poll_srl = Context::get('poll_srl'); 
            $args->poll_index_srl = Context::get('poll_index_srl'); 

            $output = executeQuery('poll.getPoll', $args);
            if(!$output->data) return $this->stop('msg_poll_not_exists');
            $poll->stop_date = $output->data->stop_date;
            $poll->poll_count = $output->data->poll_count;

            $output = executeQuery('poll.getPollTitle', $args);
            if(!$output->data) return $this->stop('msg_poll_not_exists');

            $poll->poll[$args->poll_index_srl]->title = $output->data->title;
            $poll->poll[$args->poll_index_srl]->checkcount = $output->data->checkcount;
            $poll->poll[$args->poll_index_srl]->poll_count = $output->data->poll_count;

            $output = executeQuery('poll.getPollItem', $args);
            foreach($output->data as $key => $val) {
                $poll->poll[$val->poll_index_srl]->item[] = $val;
            }

            $poll->poll_srl = $poll_srl;

            Context::set('poll',$poll);
            // Configure the skin and the colorset for the default configuration
            $oModuleModel = &getModel('module');
            $poll_config = $oModuleModel->getModuleConfig('poll');
            Context::set('poll_config', $poll_config);

            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('result');
        }
    }
?>
=======
<?php
    /**
     * @class  pollAdminView
     * @author NHN (developers@xpressengine.com)
     * @brief  poll모듈의 admin view class
     **/

    class pollAdminView extends poll {

        /**
         * @brief 초기화
         **/
        function init() {
        }

        /**
         * @brief 관리자 페이지
         **/
        function dispPollAdminList() {
            // 검색 옵션 정리
            $search_target = trim(Context::get('search_target'));
            $search_keyword = trim(Context::get('search_keyword'));

            if($search_target && $search_keyword) {
                switch($search_target) {
                    case 'title' :
                            if($search_keyword) $search_keyword = str_replace(' ','%',$search_keyword);
                            $args->s_title= $search_keyword;
                        break;
                    case 'regdate' :
                            $args->s_regdate = $search_keyword;
                        break;
                    case 'ipaddress' :
                            $args->s_ipaddress= $search_keyword;
                        break;
                }
            }
            // 목록을 구하기 위한 옵션
            $args->page = Context::get('page'); ///< 페이지
            $args->list_count = 50; ///< 한페이지에 보여줄 글 수
            $args->page_count = 10; ///< 페이지 네비게이션에 나타날 페이지의 수

            $args->sort_index = 'list_order'; ///< 소팅 값

            // 목록 구함
            $oPollAdminModel = &getAdminModel('poll');
            $output = $oPollAdminModel->getPollList($args);

            // 템플릿 변수 설정
            Context::set('total_count', $output->total_count);
            Context::set('total_page', $output->total_page);
            Context::set('page', $output->page);
            Context::set('poll_list', $output->data);
            Context::set('page_navigation', $output->page_navigation);
            Context::set('module_list', $module_list);			
			
			$security = new Security();				
			$security->encodeHTML('poll_list..title');

            // 템플릿 지정
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('poll_list');
        }

        /**
         * @brief 설문조사 스킨, 컬러셋 설정
         **/
        function dispPollAdminConfig() {
            $oModuleModel = &getModel('module');

            // 설정 정보 가져오기
            $config = $oModuleModel->getModuleConfig('poll');
            Context::set('config', $config);

            // 스킨 정보 가져오기
            $skin_list = $oModuleModel->getSkins($this->module_path);
            Context::set('skin_list', $skin_list);

            if(!$skin_list[$config->skin]) $config->skin = "default";

            // 설정된 스킨의 컬러셋 설정
            Context::set('colorset_list', $skin_list[$config->skin]->colorset);
			
			$security = new Security();				
			$security->encodeHTML('config..');
			$security->encodeHTML('skin_list..title');
			$security->encodeHTML('colorset_list..name','colorset_list..title');
			
            // 템플릿 지정
            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('config');
        }

        /**
         * @brief 설문조사 결과
         **/
        function dispPollAdminResult() {
            // 팝업 레이아웃
            $this->setLayoutFile("popup_layout");

            // 결과 뽑기
            $args->poll_srl = Context::get('poll_srl'); 
            $args->poll_index_srl = Context::get('poll_index_srl'); 

            $output = executeQuery('poll.getPoll', $args);
            if(!$output->data) return $this->stop('msg_poll_not_exists');
            $poll->stop_date = $output->data->stop_date;
            $poll->poll_count = $output->data->poll_count;

            $output = executeQuery('poll.getPollTitle', $args);
            if(!$output->data) return $this->stop('msg_poll_not_exists');

            $poll->poll[$args->poll_index_srl]->title = $output->data->title;
            $poll->poll[$args->poll_index_srl]->checkcount = $output->data->checkcount;
            $poll->poll[$args->poll_index_srl]->poll_count = $output->data->poll_count;

            $output = executeQuery('poll.getPollItem', $args);
            foreach($output->data as $key => $val) {
                $poll->poll[$val->poll_index_srl]->item[] = $val;
            }

            $poll->poll_srl = $poll_srl;

            Context::set('poll',$poll);

            // 기본 설정의 스킨, 컬러셋 설정 
            $oModuleModel = &getModel('module');
            $poll_config = $oModuleModel->getModuleConfig('poll');
            Context::set('poll_config', $poll_config);

            $this->setTemplatePath($this->module_path.'tpl');
            $this->setTemplateFile('result');
        }
    }
?>
>>>>>>> .merge-right.r9269
