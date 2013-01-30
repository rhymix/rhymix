<?php
	/**
	 * trackbackController class
	 * trackback module's Controller class
	 *
	 * @author NHN (developers@xpressengine.com)
	 * @package /modules/trackback
	 * @version 0.1
	 */
    class trackbackController extends trackback {
		/**
		 * Initialization
		 * @return void
		 */
        function init() {
        }

		/**
		 * Trackbacks sent
		 * @return object
		 */
        function procTrackbackSend() {
            // Yeokingeul to post numbers and shipping addresses Wanted
            $document_srl = Context::get('target_srl');
            $trackback_url = Context::get('trackback_url');
            $charset = Context::get('charset');
            if(!$document_srl || !$trackback_url || !$charset) return new Object(-1, 'msg_invalid_request');
            // Login Information Wanted
            $logged_info = Context::get('logged_info');
            if(!$logged_info->member_srl) return new Object(-1, 'msg_not_permitted');
            // Posts of the information obtained permission to come and check whether
            $oDocumentModel = &getModel('document');
            $oDocument = $oDocumentModel->getDocument($document_srl);
            if(!$oDocument->isExists() || !$oDocument->getSummary()) return new Object(-1, 'msg_invalid_request');
            if($oDocument->getMemberSrl() != $logged_info->member_srl) return new Object(-1, 'msg_not_permitted');
            // Specify the title of the module, the current article
            $oModuleModel = &getModel('module');
            $module_info = $oModuleModel->getModuleInfoByModuleSrl($oDocument->get('module_srl'));
            Context::setBrowserTitle($module_info->browser_title);
            // Shipping yeokingeul
            $output = $this->sendTrackback($oDocument, $trackback_url, $charset);
			if($output->toBool() && !in_array(Context::getRequestMethod(),array('XMLRPC','JSON'))) {
				global $lang;
				htmlHeader();
				alertScript($lang->success_registed);
				reload(true);
				closePopupScript();
				htmlFooter();
				Context::close();
				exit;
			}
			return $output;
        }

		/**
		 * Trackback List
		 * @return void
		 */
        function procTrackbackGetList()
		{
			if(!Context::get('is_logged')) return new Object(-1,'msg_not_permitted');
			$trackbackSrls = Context::get('trackback_srls');
			if($trackbackSrls) $trackbackSrlList = explode(',', $trackbackSrls);

			global $lang;
			if(count($trackbackSrlList) > 0) {
				$oTrackbackAdminModel = &getAdminModel('trackback');
				$args->trackbackSrlList = $trackbackSrlList;
				$args->list_count = 100;
				$output = $oTrackbackAdminModel->getTotalTrackbackList($args);

				if(is_array($output->data)) $trackbackList = $output->data;
				else
				{
					unset($_SESSION['trackback_management']);
					$trackbackList = array();
					$this->setMessage($lang->no_trackbacks);
				}
			}
			else
			{
				$trackbackList = array();
				$this->setMessage($lang->no_trackbacks);
			}

			$this->add('trackback_list', $trackbackList);
        }

		/**
		 * Trackbacks send documents from the popup menu add a menu
		 * @parma array $menu_list
		 */
        function triggerSendTrackback(&$menu_list) {
            $logged_info = Context::get('logged_info');
            if(!$logged_info->member_srl) return new Object();
            // Post number and the current login information requested Wanted
            $document_srl = Context::get('target_srl');
            $oDocumentModel = &getModel('document');
            $oDocument = $oDocumentModel->getDocument($document_srl);
            if(!$oDocument->isExists()) return new Object();
            if($oDocument->getMemberSrl() != $logged_info->member_srl) return new Object();
            // Add a link sent yeokingeul
            $oDocumentController = &getController('document');
            $url = getUrl('','module','trackback','act','dispTrackbackSend','document_srl', $document_srl);
            $oDocumentController->addDocumentPopupMenu($url,'cmd_send_trackback','','popup');

            return new Object();
        }

		/**
		 * Delete document in the document to delete the trigger Trackbacks
		 * @param object $obj
		 * @return Object
		 */
        function triggerDeleteDocumentTrackbacks(&$obj) {
            $document_srl = $obj->document_srl;
            if(!$document_srl) return new Object();

            return $this->deleteTrackbacks($document_srl, true);
        }

		/**
		 * Deletion module that deletes all the trigger yeokingeul
		 * @param object $obj
		 * @return Object
		 */
        function triggerDeleteModuleTrackbacks(&$obj) {
            $module_srl = $obj->module_srl;
            if(!$module_srl) return new Object();

            $oTrackbackController = &getAdminController('trackback');
            return $oTrackbackController->deleteModuleTrackbacks($module_srl);
        }

		/**
		 * Trackback inserted
		 * @return Object
		 */
        function trackback() {
            // Output is set to XMLRPC
            Context::setRequestMethod("XMLRPC");
            // When receiving the necessary variables yeokingeul Wanted
            $obj = Context::gets('document_srl','blog_name','url','title','excerpt');
            if(!$obj->document_srl || !$obj->url || !$obj->title || !$obj->excerpt) return $this->stop('fail');
            // Checks for correct trackback url
            $given_key = Context::get('key');
            $oTrackbackModel = &getModel('trackback');
            $key = $oTrackbackModel->getTrackbackKey($obj->document_srl);
            if($key != $given_key) return $this->stop('fail');
            // Yeokingeul module out of the default settings
            $module_srl = Context::get('module_srl');
            $oModuleModel = &getModel('module');
            $config = $oModuleModel->getModulePartConfig('trackback', $module_srl);
            $enable_trackback = $config->enable_trackback;
            if(!$enable_trackback) {
                $config = $oModuleModel->getModuleConfig('trackback');
                $enable_trackback = $config->enable_trackback;
            }
            
            // If managers were banned does not Trackbacks
            if($enable_trackback == 'N') return $this->stop('fail');

            return $this->insertTrackback($obj);
        }

		/**
		 * Trackback inserted
		 * @param object $obj
		 * @param bool $manual_inserted
		 * @return Object
		 */
        function insertTrackback($obj, $manual_inserted = false) {
            // List trackback
            $obj = Context::convertEncoding($obj);
            if(!$obj->blog_name) $obj->blog_name = $obj->title;
            $obj->excerpt = strip_tags($obj->excerpt);
            // Call a trigger (before)
            $output = ModuleHandler::triggerCall('trackback.insertTrackback', 'before', $obj);
            if(!$output->toBool()) return $output;
            // Document_srl see passed in GET, if an error ~
            $document_srl = $obj->document_srl;

            if(!$manual_inserted) {
                // Imported document model object, it permits you to wonbongeul
                $oDocumentModel = &getModel('document');
                $oDocument = $oDocumentModel->getDocument($document_srl);
                // If you do not allow two or trackback wonbongeul error display
                if(!$oDocument->isExists()) return $this->stop('fail');
                if(!$oDocument->allowTrackback()) return new Object(-1,'fail');

                $obj->module_srl = $oDocument->get('module_srl');
            }
            // Enter Trackbacks
            $obj->trackback_srl = getNextSequence();
            $obj->list_order = $obj->trackback_srl*-1;
            $output = executeQuery('trackback.insertTrackback', $obj);
            if(!$output->toBool()) return $output;
            // If there is more to enter the article number yeokingeul Rounds
            if(!$manual_inserted) {
                // trackback model object creation
                $oTrackbackModel = &getModel('trackback');
                // All the article number yeokingeul guhaeom
                $trackback_count = $oTrackbackModel->getTrackbackCount($document_srl);
                // document controller object creation
                $oDocumentController = &getController('document');
                // Update the number of posts that yeokingeul
                $output = $oDocumentController->updateTrackbackCount($document_srl, $trackback_count);
                // Return result
                if(!$output->toBool()) return $output;
            }
            // Notify wonbongeul (notify_message) if there is a Send a message
            if(!$manual_inserted) $oDocument->notify(Context::getLang('trackback'), $obj->excerpt);
            // Call a trigger (after)
            $output = ModuleHandler::triggerCall('trackback.insertTrackback', 'after', $obj);
            if(!$output->toBool()) return $output;

            return new Object();
        }

		/**
		 * Deleting a single yeokingeul
		 * @param int $trackback_srl
		 * @param bool $is_admin
		 * @return object
		 */
        function deleteTrackback($trackback_srl, $is_admin = false) {
            // trackback model object creation
            $oTrackbackModel = &getModel('trackback');
            // Make sure that you want to delete Trackbacks
            $trackback = $oTrackbackModel->getTrackback($trackback_srl);
            if($trackback->data->trackback_srl != $trackback_srl) return new Object(-1, 'msg_invalid_request');
            $document_srl = $trackback->data->document_srl;
            // Call a trigger (before)
            $output = ModuleHandler::triggerCall('trackback.deleteTrackback', 'before', $trackback);
            if(!$output->toBool()) return $output;
            // Create a document model object
            $oDocumentModel = &getModel('document');
            // Check if a permossion is granted
            if(!$is_admin && !$oDocumentModel->isGranted($document_srl)) return new Object(-1, 'msg_not_permitted');

            $args->trackback_srl = $trackback_srl;
            $output = executeQuery('trackback.deleteTrackback', $args);
            if(!$output->toBool()) return new Object(-1, 'msg_error_occured');
            // Obtain the number of yeokingeul Update
            $trackback_count = $oTrackbackModel->getTrackbackCount($document_srl);
            // document controller object creation
            $oDocumentController = &getController('document','controller');
            // Update the number of posts that yeokingeul
            $output = $oDocumentController->updateTrackbackCount($document_srl, $trackback_count);
            $output->add('document_srl', $document_srl);
            // Call a trigger (before)
            $output = ModuleHandler::triggerCall('trackback.deleteTrackback', 'after', $trackback);
            if(!$output->toBool()) return $output;

            return $output;
        }

		/**
		 * Delete All RSS Trackback
		 * @param int $document_srl
		 * @return object
		 */
        function deleteTrackbacks($document_srl) {
            // Delete
            $args->document_srl = $document_srl;
            $output = executeQuery('trackback.deleteTrackbacks', $args);

            return $output;
        }

		/**
		 * Trackbacks sent to
		 * After sending the results are not sticky and handling
		 * @param documentItem $oDocument
		 * @param string $trackback_url
		 * @param string $charset
		 * @return Object
		 */
        function sendTrackback($oDocument, $trackback_url, $charset) {
            $oModuleController = &getController('module');

            // Information sent by
            $http = parse_url($trackback_url);

            $obj->blog_name = str_replace(array('&lt;','&gt;','&amp;','&quot;'), array('<','>','&','"'), Context::getBrowserTitle());
            $oModuleController->replaceDefinedLangCode($obj->blog_name);
            $obj->title = $oDocument->getTitleText();
            $obj->excerpt = $oDocument->getSummary(200);
            $obj->url = getFullUrl('','document_srl',$oDocument->document_srl);

            // blog_name, title, excerpt, url charset of the string to the requested change
            if($charset && function_exists('iconv')) {
                foreach($obj as $key=>$val) {
                    $obj->{$key} = iconv('UTF-8',$charset,$val);
                }
            }

            $content =
                sprintf(
                    "title=%s&".
                    "url=%s&".
                    "blog_name=%s&".
                    "excerpt=%s",
                    urlencode($obj->title),
                    urlencode($obj->url),
                    urlencode($obj->blog_name),
                    urlencode($obj->excerpt)
                );

			$buff = FileHandler::getRemoteResource($trackback_url, $content, 3, 'POST', 'application/x-www-form-urlencoded');

			$oXmlParser = new XmlParser();
			$xmlDoc = $oXmlParser->parse($buff);

			if($xmlDoc->response->error->body == '0')
			{
				return new Object(0, 'msg_trackback_send_success');
			}
			else
			{
				if($xmlDoc->response->message->body)
				{
					return new Object(-1, sprintf('%s: %s', Context::getLang('msg_trackback_send_failed'), $xmlDoc->response->message->body));
				}
				else
				{
					return new Object(-1, 'msg_trackback_send_failed');
				}
			}
        }

		/**
		 * Within a specific time of a specific ipaddress Trackbacks delete all
		 * @param int $time
		 * @param string $ipaddress
		 * @param string $url
		 * @param string $blog_name
		 * @param string $title
		 * @param string $excerpt
		 * @return void
		 */
        function deleteTrackbackSender($time, $ipaddress, $url, $blog_name, $title, $excerpt) {
            $obj->regdate = date("YmdHis",time()-$time);
            $obj->ipaddress = $ipaddress;
            $obj->url = $url;
            $obj->blog_name = $blog_name;
            $obj->title = $title;
            $obj->excerpt = $excerpt;
            $output = executeQueryArray('trackback.getRegistedTrackbacks', $obj);
            if(!$output->data || !count($output->data)) return;

            foreach($output->data as $trackback) {
                $trackback_srl = $trackback->trackback_srl;
                $this->deleteTrackback($trackback_srl, true);
            }
        }

		function triggerCopyModule(&$obj)
		{
			$oModuleModel = &getModel('module');
			$trackbackConfig = $oModuleModel->getModulePartConfig('trackback', $obj->originModuleSrl);

			$oModuleController = &getController('module');
			if(is_array($obj->moduleSrlList))
			{
				foreach($obj->moduleSrlList AS $key=>$moduleSrl)
				{
					$oModuleController->insertModulePartConfig('trackback', $moduleSrl, $trackbackConfig);
				}
			}
		}
    }
?>
