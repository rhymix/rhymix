<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * The admin controller class of the rss module
 *
 * @author NAVER (developers@xpressengine.com)
 */
class rssAdminController extends rss
{
	function init()
	{
	}
	
	/**
	 * configuration
	 */
	function procRssAdminInsertConfig()
	{
		$vars = Context::getRequestVars();
		$config = getModel('rss')->getConfig();
		
		if($img_file = $vars->image)
		{
			// Delete image file
			if($config->image)
			{
				FileHandler::removeFile($config->image);
			}
			
			$vars->image = '';
			
			// Upload image file
			if($img_file['tmp_name'] && is_uploaded_file($img_file['tmp_name']))
			{
				$path = 'files/attach/images/rss';
				$file_ext = strtolower(array_pop(explode('.', $img_file['name'])));
				$file_name = sprintf('%s/feed_image.%s', $path, $file_ext);
				
				// If file exists, delete
				if(file_exists($file_name))
				{
					FileHandler::removeFile($file_name);
				}
				
				// Check image file extension
				if(!in_array($file_ext, array('jpg', 'jpeg', 'gif', 'png')))
				{
					$msg['error'] = 'msg_rss_invalid_image_format';
				}
				// Move the file
				else if(!FileHandler::makeDir($path) || !@move_uploaded_file($img_file['tmp_name'], $file_name))
				{
					$msg['error'] = 'file.msg_file_upload_error';
				}
				// Success
				else
				{
					$vars->image = $file_name;
				}
			}
		}
		
		if(!in_array($vars->use_total_feed, array('Y','N')))
		{
			$vars->open_rss = 'Y';
		}
		$vars->feed_document_count = intval($vars->feed_document_count);
		if ($vars->feed_document_count < 1 || $vars->feed_document_count > 1000)
		{
			$vars->feed_document_count = 20;
		}
		
		getController('module')->updateModuleConfig('rss', $vars);
		
		if(isset($msg['error']))
		{
			throw new Rhymix\Framework\Exception($msg['error']);
		}
		else
		{
			$this->setMessage(isset($msg) ? $msg : 'success_updated');
		}
		
		$this->setRedirectUrl(Context::get('success_return_url') ?: getNotEncodedUrl('', 'module', 'admin', 'act', 'dispRssAdminIndex'));
	}
	
	/**
	 * Part configuration
	 */
	function procRssAdminInsertModuleConfig()
	{
		$vars = Context::getRequestVars();
		
		if($vars->target_module_srl)
		{
			$target_module_srls = explode(',', $vars->target_module_srl);
		}
		else
		{
			$target_module_srls = array_keys($vars->open_rss ?: []);
		}
		
		if(!count($target_module_srls))
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		
		foreach($target_module_srls as $module_srl)
		{
			if(!$module_srl = intval($module_srl))
			{
				continue;
			}
			
			$config = new stdClass;
			if(isset($vars->open_rss[$module_srl]))
			{
				$config->open_rss = $vars->open_rss[$module_srl];
				$config->open_total_feed = $vars->open_total_feed[$module_srl];
				$config->feed_description = $vars->feed_description[$module_srl];
			}
			else
			{
				$config->open_rss = $vars->open_rss ?: [];
				$config->open_total_feed = $vars->open_total_feed;
				$config->feed_description = $vars->feed_description;
				$config->feed_copyright = $vars->feed_copyright;
			}
			
			if(!in_array($config->open_rss, array('Y', 'H', 'N')))
			{
				$config->open_rss = 'N';
			}
			if(!in_array($config->open_total_feed, array('N', 'T_N')))
			{
				$config->open_total_feed = 'T_N';
			}
			
			getController('module')->updateModulePartConfig('rss', $module_srl, $config);
		}

		$this->setMessage('success_updated');
		$this->setRedirectUrl(Context::get('success_return_url') ?: getNotEncodedUrl('', 'module', 'admin', 'act', 'dispRssAdminIndex'));
	}
	
	function procRssAdminDeleteFeedImage()
	{
		$config = getModel('rss')->getConfig();
		if(!$config->image)
		{
			throw new Rhymix\Framework\Exceptions\InvalidRequest;
		}
		
		FileHandler::removeFile($config->image);
		
		$config->image = '';
		getController('module')->insertModuleConfig('rss', $config);
	}
	
	/**
	 * Compatible function
	 */
	function setFeedConfig($config)
	{
		getController('module')->insertModuleConfig('rss', $config);
		return new BaseObject();
	}
	
	/**
	 * Compatible function
	 */
	function setRssModuleConfig($module_srl, $open_rss, $open_total_feed = 'N', $feed_description = 'N', $feed_copyright = 'N')
	{
		$config = new stdClass;
		$config->open_rss = $open_rss;
		$config->open_total_feed = $open_total_feed;
		
		if($feed_description != 'N')
		{
			$config->feed_description = $feed_description;
		}
		if($feed_copyright != 'N')
		{
			$config->feed_copyright = $feed_copyright;
		}
		
		getController('module')->insertModulePartConfig('rss', $module_srl, $config);
		return new BaseObject();
	}
}
/* End of file rss.admin.controller.php */
/* Location: ./modules/rss/rss.admin.controller.php */
