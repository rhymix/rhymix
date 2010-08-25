<?php
	class analyticsAdminView extends analytics{
		function init(){
			 $template_path = sprintf("%stpl/",$this->module_path);
			 $this->setTemplatePath($template_path);
		}

		function dispAnalyticsAdminContent(){
			$oModuleModel = &getModel('module');
			
			$module_config = $oModuleModel->getModuleConfig('analytics');

			if ($module_config->api_key){
				Context::set('api_key', $module_config->api_key);
			}
			
			$this->setTemplateFile('index');
		}
		
		function _setDefaultData($method){
			$oModuleModel = &getModel('module');
			
			$module_config = $oModuleModel->getModuleConfig('analytics');
			Context::set('api_key', $module_config->api_key);
			Context::set('method', $method);
			
			$end_date = $this->_getEndDate(Context::get('end_date'));
			$start_date = $this->_getStartDate(Context::get('start_date'), $end_date);
			
			Context::set('start_date', $start_date);
			Context::set('end_date', $end_date);
			
			$return_param = array('api_key' => $module_config->api_key
								 ,'method' => $method
								 ,'start_date' => $start_date
								 ,'end_date' => $end_date);

			return $return_param;
		}

		function dispAnalyticsVisitInfoAdminVisitChart(){
			$param = $this->_setDefaultData('visit');
			
			Context::set('isOnlyEndDate', 'false');
			Context::set('chart_type', 'line');

			$json_url = $this->_getJSONData($param['api_key'], 'visit', array('start_date' => $param['start_date'], 'end_date' => $param['end_date']));
			Context::set('json_url', $json_url);

			$this->setTemplateFile('visit_info_visitTime');
		}

		function dispAnalyticsVisitInfoAdminVisitPageViewChart(){
			$param = $this->_setDefaultData('visitPageView');
			
			Context::set('isOnlyEndDate', 'false');
			Context::set('chart_type', 'line');
		
			$json_url = $this->_getJSONData($param['api_key'], 'visitPageView', array('start_date' => $param['start_date'], 'end_date' => $param['end_date']));
			Context::set('json_url', $json_url);

			$this->setTemplateFile('visit_info_visitTime');
		}

		function dispAnalyticsVisitInfoAdminVisitTimeChart(){
			$param = $this->_setDefaultData('visitTime');
			
			Context::set('isOnlyEndDate', 'false');
			Context::set('chart_type', 'line');

			$json_url = $this->_getJSONData($param['api_key'], 'visitTime', array('start_date' => $param['start_date'], 'end_date' => $param['end_date']));
			Context::set('json_url', $json_url);

			debugPrint($json_url);
			
			$this->setTemplateFile('visit_info_visitTime');
		}

		function dispAnalyticsVisitInfoAdminVisitDayChart(){
			global $lang;

			$param = $this->_setDefaultData('visitDay');
			
			Context::set('isOnlyEndDate', 'false');
			Context::set('chart_type', 'line');

			$json_data = $this->_getJSONData($param['api_key'], 'visitDay', array('start_date' => $param['start_date'], 'end_date' => $param['end_date']));
			Context::set('json_url', $json_data);
			
			$this->setTemplateFile('visit_info_visitTime');
		}

		function dispAnalyticsVisitInfoAdminVisitBackChart(){
			global $lang;

			$param = $this->_setDefaultData('visitBack');
			
			Context::set('isOnlyEndDate', 'true');
			Context::set('chart_type', 'pie');

			$json_data = $this->_getJSONData($param['api_key'], 'visitBack', array('start_date' => $param['start_date'], 'end_date' => $param['end_date']));
			Context::set('json_url', $json_data);

			$this->setTemplateFile('visit_info_visitTime');
		}

		function dispAnalyticsVisitInfoAdminVisitStayTimeChart(){
			$param = $this->_setDefaultData('visitStayTime');
			
			Context::set('isOnlyEndDate', 'false');
			Context::set('chart_type', 'pie');

			$json_url = $this->_getJSONData($param['api_key'], 'visitStayTime', array('start_date' => $param['start_date'], 'end_date' => $param['end_date']));
			Context::set('json_url', $json_url);

			$this->setTemplateFile('visit_info_visitTime');
		}

		function dispAnalyticsVisitInfoAdminVisitPathChart(){
			$param = $this->_setDefaultData('visitPath');
			
			Context::set('isOnlyEndDate', 'false');
			Context::set('chart_type', 'pie');

			$json_url = $this->_getJSONData($param['api_key'], 'visitPath', array('start_date' => $param['start_date'], 'end_date' => $param['end_date']));
			Context::set('json_url', $json_url);

			$this->setTemplateFile('visit_info_visitTime');
		}

		/**
		 * 유입경로 분석
		 *
		 **/
		
		function _setPageData($args){
			global $lang;
			
			$page = Context::get('page');
			if (!$page)
				$page = 1;

			$param = array();
			$param['start_date'] = $args['start_date'];
			$param['end_date'] = $args['end_date'];
			$param['page'] = $page;

			$xml_obj = $this->_getXMLData($args['api_key'], $args['method'], $param);
			
			Context::set('parsed_data', $xml_obj->response);
			Context::set('value_names', $lang->analytics_api_valuname[$args['method']]);	
			
			//페이지 핸들링을 위한 데이터
			$page_navigation = new PageHandler($xml_obj->response->total_count, $xml_obj->response->page_total_count->body, $page, 5);
			Context::set('page_navigation', $page_navigation);
			Context::set('page', $page);
		}
		
		function _dispPageContent($method){
			$param = $this->_setDefaultData($method);

			Context::set('isOnlyEndDate', 'false');
			
			$page = Context::get('page');
			$json_url = $this->_getJSONData($param['api_key'], $method, array('start_date' => $param['start_date'], 'end_date' => $param['end_date'], 'page' => $page));
			Context::set('json_url', $json_url);

			$this->setTemplateFile('page_info_pagePop');

		}

		function dispAnalyticsComeInfoAdminComeEngineChart(){
			$param = $this->_setDefaultData('comeEngine');
			
			Context::set('isOnlyEndDate', 'false');
			Context::set('chart_type', 'pie');

			$page = Context::get('page');
			$json_url = $this->_getJSONData($param['api_key'], 'comeEngine', array('start_date' => $param['start_date'], 'end_date' => $param['end_date'], 'page' => $page));
			Context::set('json_url', $json_url);
			
			$this->setTemplateFile('come_info_chart');
		}
		
		function dispAnalyticsComeInfoAdminComeSearchTextChart(){
			$param = $this->_setDefaultData('comeSearchText');
			
			Context::set('isOnlyEndDate', 'false');
			Context::set('chart_type', 'pie');

			$page = Context::get('page');
			$json_url = $this->_getJSONData($param['api_key'], 'comeSearchText', array('start_date' => $param['start_date'], 'end_date' => $param['end_date'], 'page' => $page));
			Context::set('json_url', $json_url);
			
			$this->setTemplateFile('come_info_chart');
		}


		function dispAnalyticsComeInfoAdminComeUrlChart(){
			$param = $this->_setDefaultData('comeUrl');

			Context::set('isOnlyEndDate', 'false');
			
			$page = Context::get('page');
			$json_url = $this->_getJSONData($param['api_key'], 'comeUrl', array('start_date' => $param['start_date'], 'end_date' => $param['end_date'], 'page' => $page));
			Context::set('json_url', $json_url);
			
			$this->setTemplateFile('come_info_table');
		}

		function dispAnalyticsPageInfoAdminPagePopChart()
		{
			$this->_dispPageContent('pagePop');
		}

		function dispAnalyticsPageInfoAdminPageDrillDownChart()
		{
			global $lang;

			$_default_data = $this->_setDefaultData('pageDrillDown');
			
			Context::set('isOnlyEndDate', 'false');

			$param = array();
			$param['start_date'] = $_default_data['start_date'];
			$param['end_date'] = $_default_data['end_date'];
			
			$json_url = $this->_getJSONData($_default_data['api_key'], $_default_data['method'], $param);
			Context::set('json_url', $json_url);
			
			$this->setTemplateFile('page_info_pageDrillDown');
		}

		function dispAnalyticsPageInfoAdminPageStartChart(){
			$this->_dispPageContent('pageStart');
		}

		function dispAnalyticsPageInfoAdminPageEndChart(){
			$this->_dispPageContent('pageEnd');
		}

		function dispAnalyticsPageInfoAdminPageReturnChart(){
			$this->_dispPageContent('pageReturn');
		}
		
		function _getEndDate($_date)
		{
			if (!$_date)
				$date = date('Y-m-d', strtotime('now'));
			else
				$date = date('Y-m-d', strtotime($_date));

			return $date;
		}

		function _getStartDate($_date, $_end_date)
		{
			if (!$_date)
			{
				$_date = strtotime('-7 day', strtotime($_end_date));
				$date = date('Y-m-d', $_date);
			}
			else
				$date = date('Y-m-d', strtotime($_date));

			return $date;
		}
	}
?>
