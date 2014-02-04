<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * The admin controller class of the rss module
 *
 * @author NAVER (developers@xpressengine.com)
 */
class rssAdminController extends rss
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
	 * All RSS feeds configurations
	 *
	 * @return void
	 */
	function procRssAdminInsertConfig()
	{
		$oModuleModel = getModel('module');
		$total_config = $oModuleModel->getModuleConfig('rss');

		$config_vars = Context::getRequestVars();
		$config_vars->feed_document_count = (int)$config_vars->feed_document_count;

		if(!$config_vars->use_total_feed) $alt_message = 'msg_invalid_request';
		if(!in_array($config_vars->use_total_feed, array('Y','N'))) $config_vars->open_rss = 'Y';

		if($config_vars->image || $config_vars->del_image)
		{
			$image_obj = $config_vars->image;
			$config_vars->image = $total_config->image;
			// Get a variable for the delete request
			if($config_vars->del_image == 'Y' || $image_obj)
			{
				FileHandler::removeFile($config_vars->image);
				$config_vars->image = '';
				$total_config->image = '';
			}
			// Ignore if the file is not the one which has been successfully uploaded
			if($image_obj['tmp_name'] && is_uploaded_file($image_obj['tmp_name']) && checkUploadedFile($image_obj['tmp_name']))
			{
				// Ignore if the file is not an image (swf is accepted ~)
				$image_obj['name'] = Context::convertEncodingStr($image_obj['name']);

				if(!preg_match("/\.(jpg|jpeg|gif|png)$/i", $image_obj['name'])) $alt_message = 'msg_rss_invalid_image_format';
				else
				{
					// Upload the file to a path
					$path = './files/attach/images/rss/';
					// Create a directory
					if(!FileHandler::makeDir($path)) $alt_message = 'msg_error_occured';
					else
					{
						$filename = $path.$image_obj['name'];

						// Move the file
						if(!move_uploaded_file($image_obj['tmp_name'], $filename)) $alt_message = 'msg_error_occured';
						else
						{
							$config_vars->image = $filename;
						}
					}
				}
			}
		}
		if(!$config_vars->image && $config_vars->del_image != 'Y') $config_vars->image = $total_config->image;

		$output = $this->setFeedConfig($config_vars);

		if(!$alt_message) $alt_message = 'success_updated';

		$alt_message = Context::getLang($alt_message);
		$this->setMessage($alt_message, 'info');

		//$this->setLayoutPath('./common/tpl');
		//$this->setLayoutFile('default_layout.html');
		//$this->setTemplatePath($this->module_path.'tpl');
		//$this->setTemplateFile("top_refresh.html");

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispRssAdminIndex');
		$this->setRedirectUrl($returnUrl);
	}

	public function procRssAdminDeleteFeedImage()
	{
		$delImage = Context::get('del_image');

		$oModuleModel = getModel('module');
		$originConfig = $oModuleModel->getModuleConfig('rss');

		// Get a variable for the delete request
		if($delImage == 'Y')
		{
			FileHandler::removeFile($originConfig->image);

			$originConfig->image = '';
			$output = $this->setFeedConfig($originConfig);
			return new Object(0, 'success_updated');
		}
		return new Object(-1, 'fail_to_delete');
	}

	/**
	 * RSS Module configurations
	 *
	 * @return void
	 */
	function procRssAdminInsertModuleConfig()
	{
		$config_vars = Context::getRequestVars();

		$openRssList = $config_vars->open_rss;
		$openTotalFeedList = $config_vars->open_total_feed;
		$feedDescriptionList = $config_vars->feed_description;
		$feedCopyrightList = $config_vars->feed_copyright;
		$targetModuleSrl = $config_vars->target_module_srl;

		if($targetModuleSrl && !is_array($openRssList))
		{
			$openRssList = array($targetModuleSrl => $openRssList);
			$openTotalFeedList = array($targetModuleSrl => $openTotalFeedList);
			$feedDescriptionList = array($targetModuleSrl => $feedDescriptionList);
			$feedCopyrightList = array($targetModuleSrl => $feedCopyrightList);
		}

		if(is_array($openRssList))
		{
			foreach($openRssList AS $module_srl=>$open_rss)
			{
				if(!$module_srl || !$open_rss)
				{
					return new Object(-1, 'msg_invalid_request');
				}

				if(!in_array($open_rss, array('Y','H','N'))) $open_rss = 'N';

				$this->setRssModuleConfig($module_srl, $open_rss, $openTotalFeedList[$module_srl], $feedDescriptionList[$module_srl], $feedCopyrightList[$module_srl]);
			}
		}

		//$this->setError(0);
		$this->setMessage('success_updated', 'info');

		$returnUrl = Context::get('success_return_url') ? Context::get('success_return_url') : getNotEncodedUrl('', 'module', 'admin', 'act', 'dispBoardAdminContent');
		$this->setRedirectUrl($returnUrl);
	}

	/**
	 * A funciton to configure all Feeds of the RSS module
	 *
	 * @param Object $config RSS all feeds config list
	 * @return Object
	 */
	function setFeedConfig($config)
	{
		$oModuleController = getController('module');
		$oModuleController->insertModuleConfig('rss',$config);
		return new Object();
	}

	/**
	 * A function t configure the RSS module
	 *
	 * @param integer $module_srl Module_srl
	 * @param string $open_rss Choose open rss type. Y : Open all, H : Open summary, N : Not open
	 * @param string $open_total_feed N : use open total feed, T_N : not use open total feed
	 * @param string $feed_description Default value is 'N'
	 * @param string $feed_copyright Default value is 'N'
	 * @return Object
	 */
	function setRssModuleConfig($module_srl, $open_rss, $open_total_feed = 'N', $feed_description = 'N', $feed_copyright = 'N')
	{
		$oModuleController = getController('module');
		$config = new stdClass;
		$config->open_rss = $open_rss;
		$config->open_total_feed = $open_total_feed;
		if($feed_description != 'N') { $config->feed_description = $feed_description; }
		if($feed_copyright != 'N') { $config->feed_copyright = $feed_copyright; }
		$oModuleController->insertModulePartConfig('rss',$module_srl,$config);
		return new Object();
	}
}
/* End of file rss.admin.controller.php */
/* Location: ./modules/rss/rss.admin.controller.php */
