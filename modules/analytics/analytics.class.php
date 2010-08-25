<?php
	class analytics extends ModuleObject{
		var	$api_url = 'http://analytics.openapi.naver.com/?method=%s&apikey=%s';

		function moduleInstall(){
			$oModuleModel = &getModel('module');
			$oModuleController = &getController('module');	
			
			if (!$oModuleModel->getTrigger('display', 'analytics', 'controller', 'triggerBeforeDisplay', 'before'))
				$oModuleController->insertTrigger('display', 'analytics', 'controller', 'triggerBeforeDisplay', 'before');

			return new Object();
		}

		function checkUpdate(){
			$oModuleModel = &getModel('module');

			if (!$oModuleModel->getTrigger('display', 'analytics', 'controller', 'triggerBeforeDisplay', 'before')) return true;
			return false;
		}

		function moduleUpdate(){
			$oModuleModel = &getModel('module');
			$oModuleController = &getController('module');	
			
			if (!$oModuleModel->getTrigger('display', 'analytics', 'controller', 'triggerBeforeDisplay', 'before'))
				$oModuleController->insertTrigger('display', 'analytics', 'controller', 'triggerBeforeDisplay', 'before');

		}
		
		function _getXMLData($api_key, $method, $param)
		{
			$url = $this->_getApiUrl($method, $api_key, $param);
			$data = FileHandler::getRemoteResource($url, null, 3, 'GET', '');
			
			if (!$data) return new Object(-1, 'msg_invalid_request');

			$oParser = new XmlParser;
			
			$xml_obj = $oParser->parse($data);
			
			return $xml_obj;
		}

		function _getJSONData($api_key, $method, $param)
		{
			$param['jsonp'] = '?';

			$url = $this->_getApiUrl($method, $api_key, $param);
	/*		$data = FileHandler::getRemoteResource($url, null, 3, 'GET', '');
		
			if (!$data) return new Object(-1, 'msg_invalid_request');

			$json_data = json_decode($data);
			
			debugPrint($json_data);*/
			return $url;
		}


		function _getApiUrl($method, $api_key, $param=array())
		{
			$url = sprintf($this->api_url, $method, $api_key);
			foreach($param as $key => $val)
			{
				$url = $url.'&'.$key.'='.$val;
			}
			return $url;
		}
	}
?>
