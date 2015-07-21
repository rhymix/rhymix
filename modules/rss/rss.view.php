<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * The view class of the rss module
 *
 * @author NAVER (developers@xpressengine.com)
 */
class rssView extends rss
{
	/**
	 * Initialization
	 *
	 * @return void
	 */
	function init()
	{
	}

	/**
	 * Feed output.
	 * When trying to directly print out the RSS, the results variable can be directly specified through $oRssView->rss($document_list)
	 *
	 * @param Object $document_list Document list 
	 * @param string $rss_title Rss title
	 * @param string $add_description Add description
	 */
	function rss($document_list = null, $rss_title = null, $add_description = null)
	{
		$oDocumentModel = getModel('document');
		$oModuleModel = getModel('module');
		$oModuleController = getController('module');
		// Get the content and information for the current requested module if the method is not called from another module
		if(!$document_list)
		{
			$site_module_info = Context::get('site_module_info');
			$site_srl = $site_module_info->site_srl;
			$mid = Context::get('mid'); // The target module id, if absent, then all
			$start_date = (int)Context::get('start_date');
			$end_date = (int)Context::get('end_date');

			$module_srls = array();
			$rss_config = array();
			$total_config = '';
			$total_config = $oModuleModel->getModuleConfig('rss');
			// If one is specified, extract only for this mid
			if($mid)
			{
				$module_srl = $this->module_info->module_srl;
				$config = $oModuleModel->getModulePartConfig('rss', $module_srl);
				if($config->open_rss && $config->open_rss != 'N')
				{
					$module_srls[] = $module_srl; 
					$open_rss_config[$module_srl] = $config->open_rss;
				}
				// If mid is not selected, then get all
			}
			else
			{
				if($total_config->use_total_feed != 'N')
				{
					$rss_config = $oModuleModel->getModulePartConfigs('rss', $site_srl);
					if($rss_config)
					{
						foreach($rss_config as $module_srl => $config)
						{
							if($config && $config->open_rss != 'N' && $config->open_total_feed != 'T_N')
							{
								$module_srls[] = $module_srl;
								$open_rss_config[$module_srl] = $config->open_rss;
							}
						}
					}
				}
			}

			if(!count($module_srls) && !$add_description) return $this->dispError();

			$info = new stdClass;
			$args = new stdClass;

			if($module_srls)
			{
				$args->module_srl = implode(',',$module_srls);
				//$module_list = $oModuleModel->getMidList($args);	//perhaps module_list varialbles not use

				$args->search_target = 'is_secret';
				$args->search_keyword = 'N';
				$args->page = (int)Context::get('page');
				$args->list_count = 15;
				if($total_config->feed_document_count) $args->list_count = $total_config->feed_document_count;
				if(!$args->page || $args->page < 1) $args->page = 1;
				if($start_date || $start_date != 0) $args->start_date = $start_date;
				if($end_date || $end_date != 0) $args->end_date = $end_date;
				if($start_date == 0) unset($start_date);
				if($end_date == 0) unset($end_date);

				$args->sort_index = 'list_order'; 
				$args->order_type = 'asc';
				$output = $oDocumentModel->getDocumentList($args);
				$document_list = $output->data;
				// Extract the feed title and information with Context::getBrowserTitle
				if($mid)
				{
					$info->title = Context::getBrowserTitle();
					$oModuleController->replaceDefinedLangCode($info->title);

					$info->title = str_replace('\'', '&apos;',$info->title);
					if($config->feed_description)
					{
						$info->description = str_replace('\'', '&apos;', htmlspecialchars($config->feed_description, ENT_COMPAT | ENT_HTML401, 'UTF-8', false));
					}
					else
					{
						$info->description = str_replace('\'', '&apos;', htmlspecialchars($this->module_info->description, ENT_COMPAT | ENT_HTML401, 'UTF-8', false));
					}
					$info->link = getUrl('','mid',$mid);
					$info->feed_copyright = str_replace('\'', '&apos;', htmlspecialchars($feed_config->feed_copyright, ENT_COMPAT | ENT_HTML401, 'UTF-8', false));
					if(!$info->feed_copyright)
					{
						$info->feed_copyright = str_replace('\'', '&apos;', htmlspecialchars($total_config->feed_copyright, ENT_COMPAT | ENT_HTML401, 'UTF-8', false));
					}
				}
			}
		}

		if(!$info->title)
		{
			if($rss_title) $info->title = $rss_title;
			else if($total_config->feed_title) $info->title = $total_config->feed_title;
			else
			{
				$site_module_info = Context::get('site_module_info');
				$info->title = $site_module_info->browser_title;
			}

			$oModuleController->replaceDefinedLangCode($info->title);
			$info->title = str_replace('\'', '&apos;', htmlspecialchars($info->title, ENT_COMPAT | ENT_HTML401, 'UTF-8', false));
			$info->description = str_replace('\'', '&apos;', htmlspecialchars($total_config->feed_description, ENT_COMPAT | ENT_HTML401, 'UTF-8', false));
			$info->link = Context::getRequestUri();
			$info->feed_copyright = str_replace('\'', '&apos;', htmlspecialchars($total_config->feed_copyright, ENT_COMPAT | ENT_HTML401, 'UTF-8', false));
		}
		if($add_description) $info->description .= "\r\n".$add_description;

		if($total_config->image) $info->image = Context::getRequestUri().str_replace('\'', '&apos;', htmlspecialchars($total_config->image, ENT_COMPAT | ENT_HTML401, 'UTF-8', false));
		switch(Context::get('format'))
		{
			case 'atom':
				$info->date = date('Y-m-d\TH:i:sP');
				if($mid) { $info->id = getUrl('','mid',$mid,'act','atom','page',Context::get('page'),'start_date',Context::get('start_date'),'end_date',Context::get('end_date')); }
				else { $info->id = getUrl('','module','rss','act','atom','page',Context::get('page'),'start_date',Context::get('start_date'),'end_date',Context::get('end_date')); }
				break;
			case 'rss1.0':
				$info->date = date('Y-m-d\TH:i:sP');
				break;
			default:
				$info->date = date("D, d M Y H:i:s").' '.$GLOBALS['_time_zone'];
				break;
		}

		if($_SERVER['HTTPS']=='on') $proctcl = 'https://';
		else $proctcl = 'http://';

		$temp_link = explode('/', $info->link);
		if($temp_link[0]=='' && $info->link)
		{
			$info->link = $proctcl.$_SERVER['HTTP_HOST'].$info->link;
		}

		$temp_id = explode('/', $info->id);
		if($temp_id[0]=='' && $info->id)
		{
			$info->id = $proctcl.$_SERVER['HTTP_HOST'].$info->id;
		}

		$info->language = str_replace('jp','ja',Context::getLangType());
		// Set the variables used in the RSS output
		Context::set('info', $info);
		Context::set('feed_config', $config);
		Context::set('open_rss_config', $open_rss_config);
		Context::set('document_list', $document_list);
		// Force the result output to be of XMLRPC
		Context::setResponseMethod("XMLRPC");
		// Perform the preprocessing function of the editor component as the results are obtained
		$path = $this->module_path.'tpl/';
		//if($args->start_date || $args->end_date) $file = 'xe_rss';
		//else $file = 'rss20';
		switch (Context::get('format'))
		{
			case 'xe':
				$file = 'xe_rss';
				break;
			case 'atom':
				$file = 'atom10';
				break;
			case 'rss1.0':
				$file = 'rss10';
				break;
			default:
				$file = 'rss20';
				break;
		}

		$oTemplate = new TemplateHandler();

		$content = $oTemplate->compile($path, $file);
		Context::set('content', $content);
		// Set the template file
		$this->setTemplatePath($path);
		$this->setTemplateFile('display');
	}

	/**
	 * ATOM output
	 *
	 * @return Object
	 */
	function atom()
	{
		Context::set('format', 'atom');
		$this->rss();
	}

	/**
	 * Error output
	 *
	 * @return Object
	 */
	function dispError()
	{
		// Prepare the output message
		$this->rss(null, null, Context::getLang('msg_rss_is_disabled') );
	}

	/**
	 * Additional configurations for a service module
	 * Receive the form for the form used by rss
	 *
	 * @param string $obj Will be inserted content in template
	 * @return Object
	 */
	function triggerDispRssAdditionSetup(&$obj)
	{
		$current_module_srl = Context::get('module_srl');
		$current_module_srls = Context::get('module_srls');

		if(!$current_module_srl && !$current_module_srls)
		{
			// Get information of the selected module
			$current_module_info = Context::get('current_module_info');
			$current_module_srl = $current_module_info->module_srl;
			if(!$current_module_srl) return new Object();
		}
		// Get teh RSS configurations for the selected module
		$oRssModel = getModel('rss');
		$rss_config = $oRssModel->getRssModuleConfig($current_module_srl);
		Context::set('rss_config', $rss_config);
		// Set the template file
		$oTemplate = &TemplateHandler::getInstance();
		$tpl = $oTemplate->compile($this->module_path.'tpl', 'rss_module_config');
		$obj .= $tpl;

		return new Object();
	}
}
/* End of file rss.view.php */
/* Location: ./modules/rss/rss.view.php */
