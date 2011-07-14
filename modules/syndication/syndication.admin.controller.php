<?php
    /**
     * @class  syndicationAdminController
     * @author NHN (developers@xpressengine.com)
     * @brief syndication module admin Controller class
     **/

    class syndicationAdminController extends syndication {

        function init() {
        }

        function procSyndicationAdminInsertService() {
            $oModuleController = &getController('module');
            $oSyndicationController = &getController('syndication');
            $oSyndicationModel = &getModel('syndication');

            $config->target_services = explode('|@|',Context::get('target_services'));
            $config->site_url = preg_replace('/\/+$/is','',Context::get('site_url'));
            $config->year = Context::get('year');
            if(!$config->site_url) return new Object(-1,'msg_site_url_is_null');

            $oModuleController->insertModuleConfig('syndication',$config);
            $oSyndicationController->ping($oSyndicationModel->getID('site'), 'site');

            $except_module = Context::get('except_module');
            $output = executeQuery('syndication.deleteExceptModules');
            if(!$output->toBool()) return $output;

			if ($except_module){
				$modules = explode(',',$except_module);
				for($i=0,$c=count($modules);$i<$c;$i++) {
					$args->module_srl = $modules[$i];
					$output = executeQuery('syndication.insertExceptModule',$args);
					if(!$output->toBool()) return $output;
				}
			}

            $this->setMessage('success_applied');
			if(!in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispSyndicationAdminConfig');
				header('location:'.$returnUrl);
				return;
			}
        }

		function procSyndicationAdminCheckSitePingResult(){
			$site_url = trim(Context::get('site_url'));
			if(!$site_url) return new Object(-1,'msg_invalid_request');

            $oSyndicationModel = &getModel('syndication');

            $id = $oSyndicationModel->getID('site');
			if(substr($site_url,-1)!='/') $site_url .= '/';
			$site_ping = sprintf('http://%s?module=syndication&act=getSyndicationList&id=%s&type=site', $site_url, $id);

			$headers = array();
			$headers['Connection'] = 'TE, close';
			$headers['User-Agent'] = 'Mozilla/4.0 (compatible; NaverBot/1.0; http://help.naver.com/customer_webtxt_02.jsp)';

			$xml = FileHandler::getRemoteResource($site_ping, null, 3, 'GET', '', $headers);
			if(!$xml) return new Object(-1, 'msg_ping_test_error');

			$oXmlParser = new XmlParser(); 
			$oXml = $oXmlParser->parse($xml);
			
			if(!$oXml || !is_object($oXml) || !$oXml->entry || !$oXml->entry->id || !$oXml->entry->title) {
				$this->setMessage('msg_ping_test_error');
				$this->add('ping_result',$xml);
			}else{
				$this->setMessage('msg_success_ping_test');
			}
		}

		function procSyndicationAdminCheckApiStatus(){
			$target_service = Context::get('target_service');
			if(!$target_service) return new Object(-1,'msg_invalid_request');

			$status_url = trim($this->statuses[$target_service]);
			if(!$status_url) return new Object(-1,'msg_syndication_status_not_support');

            $oModuleModel = &getModel('module');

            $config = $oModuleModel->getModuleConfig('syndication');
            $site_url = preg_replace('/^(http|https):\/\//i','',$config->site_url);

			$method = 'getSyndicationStatus' . ucfirst(strtolower($target_service));
			if(!method_exists($this, $method)) return new Object(-1,'msg_syndication_status_not_support');

			$output = call_user_func(array(&$this,$method),$site_url);
			if(!$output->toBool()) return $output;

			$this->add('result_status',$output->get('result_status'));
		}

		function getSyndicationStatusNaver($site_url){
			$status_url = trim($this->statuses['Naver']);

			$xml = FileHandler::getRemoteResource(sprintf($status_url,$site_url), null, 3, 'GET', 'application/xml');
			$oXmlParser = new XmlParser(); 
			$oXml = $oXmlParser->parse($xml);
			$oStatus = $oXml->syndication_status;
			
			if($oStatus->error->body != 0) return new Object(-1,$oStatus->message->body);

			$result->site_name = $oStatus->site_name->body;
			$result->first_update = $oStatus->first_update->body;
			$result->last_update = $oStatus->last_update->body;
			$result->visit_ok_count = $oStatus->visit_ok_count->body;
			$result->visit_fail_count = $oStatus->visit_fail_count->body;
			$result->status = $oStatus->status->body;

			if(!$oStatus->sync || !$oStatus->sync->article){
				$oArticleList = array();
			}else{
				$oArticleList = $oStatus->sync->article;
				if(!is_array($oArticleList)) $oArticleList = array($oArticleList); 
			}
			
			if(count($oArticleList)>0){
				$article_count = array();
				foreach($oArticleList as $article){
					$article_count[$article->attrs->date] = $article->body;
				}
				
				$result->article_count = $article_count;
				$result->max_article_count = max($result->article_count);

			}

			Context::set('result', $result);
			$oTemplateHandler = &TemplateHandler::getInstance();
			$html = $oTemplateHandler->compile($this->module_path.'tpl', 'naver_result');

			$output = new Object();
			$output->add('result_status', $html);
			return $output;
		}
    }
?>
