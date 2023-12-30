<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * The view class of the integration_search module
 *
 * @author NAVER (developers@xpressengine.com)
 */
class integration_searchView extends integration_search
{
	/**
	 * Target mid
	 * @var array target mid
	 */
	var $target_mid = array();
	/**
	 * Skin
	 * @var string skin name
	 */
	var $skin = 'default';

	/**
	 * Initialization
	 *
	 * @return void
	 */
	public function init()
	{
	}

	/**
	 * Search Result
	 *
	 * @return Object
	 */
	public function IS()
	{
		$oFile = getClass('file');
		$oModuleModel = getModel('module');
		$logged_info = Context::get('logged_info');

		// Redirect to GET if search is requested via POST
		if($_SERVER['REQUEST_METHOD'] !== 'GET')
		{
			$redirect_url = getNotEncodedUrl('', 'mid', Context::get('mid'), 'act', 'IS',
				'search_target', Context::get('search_target'), 'is_keyword', Context::get('is_keyword'),
				'where', Context::get('where'), 'page', Context::get('page'));
			$this->setRedirectUrl($redirect_url);
			return;
		}

		// Check permissions
		if(!$this->grant->access)
		{
			throw new Rhymix\Framework\Exceptions\NotPermitted;
		}

		// Block robots
		$config = $oModuleModel->getModuleConfig('integration_search') ?: new stdClass;
		if (!isset($config->block_robots) || $config->block_robots !== false)
		{
			if (isCrawler())
			{
				throw new Rhymix\Framework\Exceptions\NotPermitted;
			}
		}

		// Set skin path
		if(ends_with('Mobile', get_class($this), false))
		{
			if(!$config->mskin || $config->mskin === '/USE_RESPONSIVE/')
			{
				$template_path = sprintf('%sskins/%s/', $this->module_path, $config->skin);
				if(!is_dir($template_path) || !$config->skin)
				{
					$template_path = sprintf('%sskins/%s/', $this->module_path, 'default');
					$config->mskin = 'default';
				}
			}
			else
			{
				$template_path = sprintf('%sm.skins/%s/', $this->module_path, $config->mskin);
				if(!is_dir($template_path) || !$config->mskin)
				{
					$template_path = sprintf('%sm.skins/%s/', $this->module_path, 'default');
					$config->mskin = 'default';
				}
				if(!is_dir($template_path))
				{
					$template_path = sprintf('%sskins/%s/', $this->module_path, 'default');
					$config->mskin = 'default';
				}
			}
		}
		else
		{
			$template_path = sprintf('%sskins/%s/', $this->module_path, $config->skin);
			if(!is_dir($template_path) || !$config->skin)
			{
				$template_path = sprintf('%sskins/%s/', $this->module_path, 'default');
				$config->skin = 'default';
			}
		}

		$this->setTemplatePath($template_path);
		$skin_vars = ($config->skin_vars) ? unserialize($config->skin_vars) : new stdClass;
		Context::set('module_info', $skin_vars);

		// Include or exclude target modules.
		$target = $config->target;
		if(!$target) $target = 'include';

		if(empty($config->target_module_srl))
			$module_srl_list = array();
		else
			$module_srl_list = explode(',',$config->target_module_srl);

		// https://github.com/xpressengine/xe-core/issues/1522
		// 검색 대상을 지정하지 않았을 때 검색 제한
		if($target === 'include' && !count($module_srl_list))
		{
			throw new Rhymix\Framework\Exception('msg_admin_not_enabled');
		}

		// Set a variable for search keyword
		$is_keyword = Context::get('is_keyword');
		// As the variables from GET or POST will be escaped by setRequestArguments method at Context class, the double_escape variable should be "FALSE", and also the escape function might be useful when this method was called from the other way (for not escaped keyword).
		$is_keyword = escape(trim(utf8_normalize_spaces($is_keyword)), false);
		if (mb_strlen($is_keyword, 'UTF-8') > 250)
		{
			$is_keyword = mb_substr($is_keyword, 0, 250);
		}

		// Set page variables
		$page = (int)Context::get('page');
		if(!$page) $page = 1;

		// Set page title
		$title = config('seo.subpage_title') ?: '$SITE_TITLE - $SUBPAGE_TITLE';
		Context::setBrowserTitle($title, array(
			'site_title' => Context::getSiteTitle(),
			'site_subtitle' => Context::getSiteSubtitle(),
			'subpage_title' => lang('cmd_search') . ': ' . $is_keyword,
			'page' => $page,
		));

		// Search by search tab
		$where = Context::get('where');
		Context::set('where', $where);
		$target_types = $config->target_types ?? ['document' => true, 'comment' => true, 'multimedia' => true, 'file' => true];
		Context::set('target_types', $target_types);

		// Create integration search model object
		if($is_keyword)
		{
			$oIS = integration_searchModel::getInstance();
			Context::set('trackback_module_exist', false);

			switch($where)
			{
				case 'document' :
					$search_target = Context::get('search_target');
					if(!in_array($search_target, array('title','content','title_content','tag'))) $search_target = 'title_content';
					Context::set('search_target', $search_target);
					if ($target_types['document'])
					{
						$output = $oIS->getDocuments($target, $module_srl_list, $search_target, $is_keyword, $page, 10);
					}
					else
					{
						$output = new BaseObject;
					}
					Context::set('output', $output);
					$this->setTemplateFile("document", $page);
					break;
				case 'comment' :
					if ($target_types['comment'])
					{
						$output = $oIS->getComments($target, $module_srl_list, $is_keyword, $page, 10);
					}
					else
					{
						$output = new BaseObject;
					}
					Context::set('output', $output);
					$this->setTemplateFile("comment", $page);
					break;
				case 'multimedia' :
					if ($target_types['multimedia'])
					{
						$output = $oIS->getImages($target, $module_srl_list, $is_keyword, $page,20);
					}
					else
					{
						$output = new BaseObject;
					}
					Context::set('output', $output);
					$this->setTemplateFile("multimedia", $page);
					break;
				case 'file' :
					if ($target_types['file'])
					{
						$output = $oIS->getFiles($target, $module_srl_list, $is_keyword, $page, 20);
					}
					else
					{
						$output = new BaseObject;
					}
					Context::set('output', $output);
					$this->setTemplateFile("file", $page);
					break;
				default :
					if ($target_types['document'])
					{
						$output['document'] = $oIS->getDocuments($target, $module_srl_list, 'title_content', $is_keyword, $page, 5);
					}
					else
					{
						$output['document'] = new BaseObject;
					}
					if ($target_types['comment'])
					{
						$output['comment'] = $oIS->getComments($target, $module_srl_list, $is_keyword, $page, 5);
					}
					else
					{
						$output['comment'] = new BaseObject;
					}
					if ($target_types['multimedia'])
					{
						$output['multimedia'] = $oIS->getImages($target, $module_srl_list, $is_keyword, $page, 5);
					}
					else
					{
						$output['multimedia'] = new BaseObject;
					}
					if ($target_types['file'])
					{
						$output['file'] = $oIS->getFiles($target, $module_srl_list, $is_keyword, $page, 5);
					}
					else
					{
						$output['file'] = new BaseObject;
					}
					$output['trackback'] = new BaseObject;
					Context::set('search_result', $output);
					Context::set('search_target', 'title_content');
					$this->setTemplateFile("index", $page);
					break;
			}
		}
		else
		{
			$this->setTemplateFile("no_keywords");
		}

		$security = new Security();
		$security->encodeHTML('is_keyword', 'search_target', 'where', 'page');
	}
}
/* End of file integration_search.view.php */
/* Location: ./modules/integration_search/integration_search.view.php */
