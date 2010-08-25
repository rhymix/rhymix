<?php

	class analyticsAdminController extends analytics {
		
		function init(){
		}
		
		function procAnalyticsAdminInsertAPIKey() {
			// API KEY 값을 가지고 온다.
			$args = Context::gets('api_key');
			
			// module controlle 객체 생성하여 Key값 저장
			$oModuleController = &getController('module');
			$output = $oModuleController->insertModuleConfig('analytics', $args);
			return $output;
		}
	}
?>
