<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */
/**
 * documentItem class
 * document object
 *
 * @author NAVER (developers@xpressengine.com)
 * @package /modules/document
 * @version 0.1
 */
class documentItem extends BaseObject
{
	/**
	 * Document number
	 * @var int
	 */
	var $document_srl = 0;
	/**
	 * Language code
	 * @var string
	 */
	var $lang_code = null;
	/**
	 * grant
	 * @var bool
	 */
	var $grant_cache = null;
	/**
	 * Status of allow trackback
	 * @var bool
	 */
	var $allow_trackback_status = null;
	/**
	 * Comment page navigation
	 * @var object
	 */
	var $comment_page_navigation = null;
	/**
	 * column list
	 * @var array
	 */
	var $columnList = array();
	/**
	 * allow script access list
	 * @var array
	 */
	var $allowscriptaccessList = array();
	/**
	 * allow script access key
	 * @var int
	 */
	var $allowscriptaccessKey = 0;
	/**
	 * upload file list
	 * @var array
	 */
	var $uploadedFiles = array();
	/**
	 * extra eids
	 * @var array
	 */
	protected $extra_eids = array();

	/**
	 * Constructor
	 * @param int $document_srl
	 * @param bool $load_extra_vars
	 * @param array columnList
	 * @return void
	 */
	function __construct($document_srl = 0, $load_extra_vars = true, $columnList = array())
	{
		$this->document_srl = $document_srl;
		$this->columnList = $columnList;
		$this->_loadFromDB($load_extra_vars);
	}

	function setDocument($document_srl, $load_extra_vars = true)
	{
		$this->document_srl = $document_srl;
		$this->_loadFromDB($load_extra_vars);
	}

	/**
	 * Get data from database, and set the value to documentItem object
	 * @param bool $load_extra_vars
	 * @return void
	 */
	function _loadFromDB($load_extra_vars = true)
	{
		if(!$this->document_srl)
		{
			return;
		}
		
		$document_item = false;
		$columnList = array();
		$reload_counts = true;
		
		if ($this->columnList === false)
		{
			$reload_counts = false;
		}
		$this->columnList = array();

		// cache controll
		$cache_key = 'document_item:' . getNumberingPath($this->document_srl) . $this->document_srl;
		$document_item = Rhymix\Framework\Cache::get($cache_key);
		if($document_item)
		{
			$columnList = array('readed_count', 'voted_count', 'blamed_count', 'comment_count', 'trackback_count');
		}

		if(!$document_item || $reload_counts)
		{
			$args = new stdClass();
			$args->document_srl = $this->document_srl;
			$output = executeQuery('document.getDocument', $args, $columnList);
		}

		if(!$document_item)
		{
			$document_item = $output->data;
			if($document_item)
			{
				Rhymix\Framework\Cache::set($cache_key, $document_item);
			}
		}
		else
		{
			$document_item->readed_count = $output->data->readed_count;
			$document_item->voted_count = $output->data->voted_count;
			$document_item->blamed_count = $output->data->blamed_count;
			$document_item->comment_count = $output->data->comment_count;
			$document_item->trackback_count = $output->data->trackback_count;
		}

		$this->setAttribute($document_item, $load_extra_vars);
	}

	function setAttribute($attribute, $load_extra_vars = true)
	{
		if(!is_object($attribute) || !$attribute->document_srl)
		{
			$this->document_srl = null;
			return;
		}
		
		$this->document_srl = $attribute->document_srl;
		$this->lang_code = $attribute->lang_code ?? null;
		$this->adds($attribute);
		if(isset($attribute->module_srl))
		{
			$this->add('apparent_module_srl', $attribute->module_srl);
			$this->add('origin_module_srl', $attribute->module_srl);
		}
		
		// set XE_DOCUMENT_LIST
		$GLOBALS['XE_DOCUMENT_LIST'][$this->document_srl] = $this;
		
		// set tags
		if($this->get('tags'))
		{
			$this->add('tag_list', $this->getTags());
		}
		
		// set extra vars
		if($load_extra_vars)
		{
			DocumentModel::setToAllDocumentExtraVars();
		}
		
		// set content in user language
		if(isset($GLOBALS['RX_DOCUMENT_LANG'][$this->document_srl]['title']))
		{
			$this->add('title', $GLOBALS['RX_DOCUMENT_LANG'][$this->document_srl]['title']);
		}
		if(isset($GLOBALS['RX_DOCUMENT_LANG'][$this->document_srl]['content']))
		{
			$this->add('content', $GLOBALS['RX_DOCUMENT_LANG'][$this->document_srl]['content']);
		}
	}

	function isExists()
	{
		return (bool) ($this->document_srl);
	}
	
	function isGranted()
	{
		if(!$this->isExists())
		{
			return false;
		}
		
		if (isset($_SESSION['granted_document'][$this->document_srl]))
		{
			return true;
		}
		
		if ($this->grant_cache !== null)
		{
			return $this->grant_cache;
		}
		
		$logged_info = Context::get('logged_info');
		if (!$logged_info || !$logged_info->member_srl)
		{
			return $this->grant_cache = false;
		}
		if ($logged_info->is_admin == 'Y')
		{
			return $this->grant_cache = true;
		}
		if ($this->get('member_srl') && abs($this->get('member_srl')) == $logged_info->member_srl)
		{
			return $this->grant_cache = true;
		}
		
		$grant = ModuleModel::getGrant(ModuleModel::getModuleInfoByModuleSrl($this->get('module_srl')), $logged_info);
		if ($grant->manager)
		{
			return $this->grant_cache = true;
		}
		
		return $this->grant_cache = false;
	}
	
	function setGrant()
	{
		$this->grant_cache = true;
	}
	
	function setGrantForSession()
	{
		$_SESSION['granted_document'][$this->document_srl] = true;
		$this->setGrant();
	}
	
	function isAccessible()
	{
		if(!$this->isExists())
		{
			return false;
		}
		
		if (isset($_SESSION['accessible'][$this->document_srl]) && $_SESSION['accessible'][$this->document_srl] === $this->get('last_update'))
		{
			return true;
		}
		
		$status_list = DocumentModel::getStatusList();
		if ($this->get('status') === $status_list['public'])
		{
			$this->setAccessible();
			return true;
		}
		
		if ($this->isGranted())
		{
			$this->setAccessible();
			return true;
		}
		
		return false;
	}
	
	function setAccessible()
	{
		if(Context::getSessionStatus())
		{
			$_SESSION['accessible'][$this->document_srl] = $this->get('last_update');
		}
	}
	
	function allowComment()
	{
		// init write, document is not exists. so allow comment status is true
		if(!$this->isExists())
		{
			return true;
		}
		
		return (bool) ($this->get('comment_status') == 'ALLOW');
	}

	function allowTrackback()
	{
		static $allow_trackback_status = null;
		if(is_null($allow_trackback_status))
		{
			
			// Check the tarckback module exist
			if(!getClass('trackback'))
			{
				$allow_trackback_status = false;
			}
			else
			{
				// If the trackback module is configured to be disabled, do not allow. Otherwise, check the setting of each module.
				$trackback_config = ModuleModel::getModuleConfig('trackback');
				
				if(!$trackback_config)
				{
					$trackback_config = new stdClass();
				}
				
				if(!isset($trackback_config->enable_trackback)) $trackback_config->enable_trackback = 'Y';
				if($trackback_config->enable_trackback != 'Y') $allow_trackback_status = false;
				else
				{
					$module_srl = $this->get('module_srl');
					// Check settings of each module
					$module_config = ModuleModel::getModulePartConfig('trackback', $module_srl);
					if($module_config->enable_trackback == 'N') $allow_trackback_status = false;
					else if($this->get('allow_trackback')=='Y' || !$this->isExists()) $allow_trackback_status = true;
				}
			}
		}
		return $allow_trackback_status;
	}

	function isLocked()
	{
		if(!$this->isExists())
		{
			return false;
		}
		
		return (bool) ($this->get('comment_status') != 'ALLOW');
	}

	function isEditable()
	{
		return (bool) (!$this->get('member_srl') || $this->isGranted());
	}
	
	function isSecret()
	{
		return (bool) ($this->get('status') == DocumentModel::getConfigStatus('secret'));
	}
	
	function isNotice()
	{
		return (bool) ($this->get('is_notice') === 'Y' || $this->get('is_notice') === 'A');
	}
	
	function useNotify()
	{
		return (bool) ($this->get('notify_message') == 'Y');
	}
	
	function doCart()
	{
		if(!$this->isExists())
		{
			return false;
		}
		
		$this->isCarted() ? $this->removeCart() : $this->addCart();
	}

	function addCart()
	{
		$_SESSION['document_management'][$this->document_srl] = true;
	}

	function removeCart()
	{
		unset($_SESSION['document_management'][$this->document_srl]);
	}

	function isCarted()
	{
		return isset($_SESSION['document_management'][$this->document_srl]);
	}

	/**
	 * Send notify message to document owner
	 * @param string $type
	 * @param string $content
	 * @return void
	 */
	function notify($type, $content)
	{
		if(!$this->isExists())
		{
			return;
		}
		// return if it is not useNotify
		if(!$this->useNotify())
		{
			return;
		}
		// Pass if an author is not a logged-in user
		if(!$this->get('member_srl'))
		{
			return;
		}
		
		// Return if the currently logged-in user is an author
		$logged_info = Context::get('logged_info');
		if($logged_info->member_srl == $this->get('member_srl'))
		{
			return;
		}
		
		// List variables
		$title = ($type ? sprintf('[%s] ', $type) : '') . cut_str(strip_tags($content), 10, '...');
		$content = sprintf('%s<br><br>from : <a href="%s" target="_blank">%s</a>',$content, getFullUrl('', 'document_srl', $this->document_srl), getFullUrl('', 'document_srl', $this->document_srl));
		
		// Send a message
		$sender_member_srl = $logged_info->member_srl ?: $this->get('member_srl');
		getController('communication')->sendMessage($sender_member_srl, $this->get('member_srl'), $title, $content, false, null, false);
	}

	function getLangCode()
	{
		return $this->get('lang_code');
	}

	function getIpAddress()
	{
		if($this->isGranted())
		{
			return $this->get('ipaddress');
		}
		
		return '*' . strstr($this->get('ipaddress'), '.');
	}

	function isExistsHomepage()
	{
		return (bool) trim($this->get('homepage'));
	}

	function getHomepageUrl()
	{
		if(!$url = trim($this->get('homepage')))
		{
			return;
		}
		
		if(!preg_match('@^[a-z]+://@i', $url))
		{
			$url = 'http://' . $url;
		}
		
		return escape($url, false);
	}

	function getMemberSrl()
	{
		return $this->get('member_srl');
	}

	function getUserID()
	{
		return escape($this->get('user_id'), false);
	}

	function getUserName()
	{
		return escape($this->get('user_name'), false);
	}

	function getNickName()
	{
		return escape($this->get('nick_name'), false);
	}

	function getLastUpdater()
	{
		return escape($this->get('last_updater'), false);
	}

	function getTitleText($cut_size = 0, $tail = '...')
	{
		if(!$this->isExists())
		{
			return;
		}
		
		return $cut_size ? cut_str($this->get('title'), $cut_size, $tail) : $this->get('title');
	}

	function getVoted()
	{
		return $this->getMyVote();
	}

	function getMyVote()
	{
		if(!$this->isExists())
		{
			return false;
		}

		if(isset($_SESSION['voted_document'][$this->document_srl]))
		{
			return $_SESSION['voted_document'][$this->document_srl];
		}
		
		$logged_info = Context::get('logged_info');
		if(!$logged_info->member_srl)
		{
			$module_info = ModuleModel::getModuleInfoByModuleSrl($this->get('module_srl'));
			if($module_info->non_login_vote !== 'Y')
			{
				return false;
			}
		}

		$args = new stdClass;
		if($logged_info->member_srl)
		{
			$args->member_srl = $logged_info->member_srl;
		}
		else
		{
			$args->member_srl = 0;
			$args->ipaddress = \RX_CLIENT_IP;
		}
		$args->document_srl = $this->document_srl;
		$output = executeQuery('document.getDocumentVotedLog', $args);
		if(isset($output->data) && $output->data->point)
		{
			return $_SESSION['voted_document'][$this->document_srl] = $output->data->point;
		}
		return $_SESSION['voted_document'][$this->document_srl] = false;
	}

	/**
	 * 게시글에 신고한 이력이 있는지 검사
	 * @return bool|int
	 */
	function getDeclared()
	{
		if(!$this->isExists())
		{
			return false;
		}
		
		$logged_info = Context::get('logged_info');
		if(!$logged_info->member_srl)
		{
			return false;
		}

		if(isset($_SESSION['declared_document'][$this->document_srl]))
		{
			return $_SESSION['declared_document'][$this->document_srl];
		}
		
		$args = new stdClass();
		if($logged_info->member_srl)
		{
			$args->member_srl = $logged_info->member_srl;
		}
		else
		{
			$args->ipaddress = \RX_CLIENT_IP;
		}
		$args->document_srl = $this->document_srl;
		$output = executeQuery('document.getDocumentDeclaredLogInfo', $args);
		$declaredCount = isset($output->data) ? intval($output->data->count) : 0;
		if($declaredCount > 0)
		{
			return $_SESSION['declared_document'][$this->document_srl] = $declaredCount;
		}
		
		return false;
	}

	function getTitle($cut_size = 0, $tail = '...')
	{
		if(!$this->isExists())
		{
			return false;
		}
		
		$title = escape($this->getTitleText($cut_size, $tail), false);
		$this->add('title_color', trim($this->get('title_color')));
		
		$attrs = array();
		if($this->get('title_bold') == 'Y')
		{
			$attrs[] = 'font-weight:bold';
		}
		if($this->get('title_color') && $this->get('title_color') != 'N')
		{
			$attrs[] = 'color:#' . ltrim($this->get('title_color'), '#');
		}
		if(count($attrs))
		{
			return sprintf('<span style="%s">%s</span>', implode(';', $attrs), $title);
		}
		
		return $title;
	}

	function getContentPlainText($strlen = 0)
	{
		if(!$this->isExists())
		{
			return;
		}
		
		if(!$this->isAccessible())
		{
			return lang('msg_is_secret');
		}
		
		$content = $this->get('content');
		$content = trim(utf8_normalize_spaces(html_entity_decode(strip_tags($content))));
		if($strlen)
		{
			$content = cut_str($content, $strlen, '...');
		}
		
		return escape($content);
	}

	function getContentText($strlen = 0)
	{
		if(!$this->isExists())
		{
			return;
		}
		
		if(!$this->isAccessible())
		{
			return lang('msg_is_secret');
		}
		
		$content = $this->get('content');
		$content = preg_replace_callback('/<(object|param|embed)[^>]*/is', array($this, '_checkAllowScriptAccess'), $content);
		$content = preg_replace_callback('/<object[^>]*>/is', array($this, '_addAllowScriptAccess'), $content);
		if($strlen)
		{
			$content = trim(utf8_normalize_spaces(html_entity_decode(strip_tags($content))));
			$content = cut_str($content, $strlen, '...');
		}
		
		return escape($content);
	}

	function _addAllowScriptAccess($m)
	{
		if($this->allowscriptaccessList[$this->allowscriptaccessKey] == 1)
		{
			$m[0] = $m[0].'<param name="allowscriptaccess" value="never"></param>';
		}
		$this->allowscriptaccessKey++;
		return $m[0];
	}

	function _checkAllowScriptAccess($m)
	{
		if($m[1] == 'object')
		{
			$this->allowscriptaccessList[] = 1;
		}

		if($m[1] == 'param')
		{
			if(stripos($m[0], 'allowscriptaccess'))
			{
				$m[0] = '<param name="allowscriptaccess" value="never"';
				if(substr($m[0], -1) == '/')
				{
					$m[0] .= '/';
				}
				$this->allowscriptaccessList[count($this->allowscriptaccessList)-1]--;
			}
		}
		else if($m[1] == 'embed')
		{
			if(stripos($m[0], 'allowscriptaccess'))
			{
				$m[0] = preg_replace('/always|samedomain/i', 'never', $m[0]);
			}
			else
			{
				$m[0] = preg_replace('/\<embed/i', '<embed allowscriptaccess="never"', $m[0]);
			}
		}
		return $m[0];
	}

	function getContent($add_popup_menu = true, $add_content_info = true, $resource_realpath = false, $add_xe_content_class = true, $stripEmbedTagException = false)
	{
		if(!$this->isExists())
		{
			return;
		}
		
		if(!$this->isAccessible())
		{
			return lang('msg_is_secret');
		}
		
		$content = $this->get('content');
		if(!$stripEmbedTagException)
		{
			stripEmbedTagForAdmin($content, $this->get('member_srl'));
		}
		
		// Define a link if using a rewrite module
		if(Context::isAllowRewrite())
		{
			$content = preg_replace('/<a([ \t]+)href=("|\')\.\/\?/i',"<a href=\\2". Context::getRequestUri() ."?", $content);
		}
		// To display a pop-up menu
		if($add_popup_menu)
		{
			$content = sprintf(
				'%s<div class="document_popup_menu"><a href="#popup_menu_area" class="document_%d" onclick="return false">%s</a></div>',
				$content,
				$this->document_srl, lang('cmd_document_do')
			);
		}
		// If additional content information is set
		if($add_content_info)
		{
			$memberSrl = $this->get('member_srl');
			if($memberSrl < 0)
			{
				$memberSrl = 0;
			}
			$content = sprintf(
				'<!--BeforeDocument(%d,%d)--><div class="document_%d_%d rhymix_content xe_content">%s</div><!--AfterDocument(%d,%d)-->',
				$this->document_srl, $memberSrl,
				$this->document_srl, $memberSrl,
				$content,
				$this->document_srl, $memberSrl,
				$this->document_srl, $memberSrl
			);
		}
		// Add xe_content class although accessing content is not required
		elseif($add_xe_content_class)
		{
			$content = sprintf('<div class="rhymix_content xe_content">%s</div>', $content);
		}
		// Change the image path to a valid absolute path if resource_realpath is true
		if($resource_realpath)
		{
			$content = preg_replace_callback('/<img([^>]+)>/i',array($this,'replaceResourceRealPath'), $content);
		}
		
		return $content;
	}

	/**
	 * Return transformed content by Editor codes
	 * @param bool $add_popup_menu
	 * @param bool $add_content_info
	 * @param bool $resource_realpath
	 * @param bool $add_xe_content_class
	 * @return string
	 */
	function getTransContent($add_popup_menu = true, $add_content_info = true, $resource_realpath = false, $add_xe_content_class = true)
	{
		if(!$this->isExists())
		{
			return;
		}
		
		$content = $this->getContent($add_popup_menu, $add_content_info, $resource_realpath, $add_xe_content_class);
		$content = getController('editor')->transComponent($content);
		
		return $content;
	}
	
	function getSummary($str_size = 50, $tail = '...')
	{
		// Remove tags
		$content = $this->getContent(false, false);
		$content = strip_tags(preg_replace('!<(style|script)\b.+?</\\1>!is', '', $content));
		
		// Convert temporarily html entity for truncate
		$content = html_entity_decode($content, ENT_QUOTES);
		
		// Replace all whitespaces to single space
		$content = utf8_trim(utf8_normalize_spaces($content));
		
		// Truncate string
		$content = cut_str($content, $str_size, $tail);
		
		return escape($content);
	}
	
	function getRegdate($format = 'Y.m.d H:i:s', $conversion = true)
	{
		return zdate($this->get('regdate'), $format, $conversion);
	}

	function getRegdateTime()
	{
		return ztime($this->get('regdate'));
	}

	function getRegdateGM($format = 'r')
	{
		return gmdate($format, $this->getRegdateTime());
	}

	function getRegdateDT($format = 'c')
	{
		return Rhymix\Framework\DateTime::formatTimestampForCurrentUser($format, $this->getRegdateTime());
	}

	function getUpdate($format = 'Y.m.d H:i:s', $conversion = true)
	{
		return zdate($this->get('last_update'), $format, $conversion);
	}

	function getUpdateTime()
	{
		return ztime($this->get('last_update'));
	}

	function getUpdateGM($format = 'r')
	{
		return gmdate($format, $this->getUpdateTime());
	}

	function getUpdateDT($format = 'c')
	{
		return Rhymix\Framework\DateTime::formatTimestampForCurrentUser($format, $this->getUpdateTime());
	}

	function getPermanentUrl()
	{
		return getFullUrl('', 'mid', $this->getDocumentMid(), 'document_srl', $this->get('document_srl'));
	}

	/**
	 * @deprecated
	 */
	public function getTrackbackUrl()
	{
		
	}
	
	public function getUrl()
	{
		return getFullUrl('', 'mid', $this->getApparentMid(), 'document_srl', $this->get('document_srl'));
	}

	public function getTags()
	{
		$tag_list = array_map(function($str) { return escape(utf8_trim($str), false); }, explode(',', $this->get('tags')));
		$tag_list = array_filter($tag_list, function($str) { return $str !== ''; });
		return array_unique($tag_list);
	}
	
	public function getHashtags()
	{
		preg_match_all('/(?<!&)#([\pL\pN_]+)/u', strip_tags($this->get('content')), $hashtags);
		$hashtags[1] = array_map(function($str) { return escape($str, false); }, $hashtags[1]);
		return array_unique($hashtags[1]);
	}
	
	/**
	 * Update readed count
	 * @return void
	 */
	function updateReadedCount()
	{
		$oDocumentController = getController('document');
		if($oDocumentController->updateReadedCount($this))
		{
			$readed_count = $this->get('readed_count');
			$this->add('readed_count', $readed_count+1);
		}
	}

	function isExtraVarsExists()
	{
		$module_srl = $this->get('module_srl');
		if(!$module_srl)
		{
			return false;
		}
		$extra_keys = DocumentModel::getExtraKeys($module_srl);
		return $extra_keys ? true : false;
	}

	function getExtraVars()
	{
		$module_srl = $this->get('module_srl');
		if(!$module_srl || !$this->document_srl)
		{
			return array();
		}

		return DocumentModel::getExtraVars($module_srl, $this->document_srl);
	}
	
	function getExtraEids()
	{
		if($this->extra_eids)
		{
			return $this->extra_eids;
		}
		
		$extra_vars = $this->getExtraVars();
		foreach($extra_vars as $idx => $key)
		{
			$this->extra_eids[$key->eid] = $key;
		}
		
		return $this->extra_eids;
	}
	
	function getExtraValue($idx)
	{
		$extra_vars = $this->getExtraVars();
		return isset($extra_vars[$idx]) ? $extra_vars[$idx]->getValue() : '';
	}
	
	function getExtraValueHTML($idx)
	{
		$extra_vars = $this->getExtraVars();
		return isset($extra_vars[$idx]) ? $extra_vars[$idx]->getValueHTML() : '';
	}
	
	function getExtraEidValue($eid)
	{
		$extra_eids = $this->getExtraEids();
		return isset($extra_eids[$eid]) ? $extra_eids[$eid]->getValue() : '';
	}

	function getExtraEidValueHTML($eid)
	{
		$extra_eids = $this->getExtraEids();
		return isset($extra_eids[$eid]) ? $extra_eids[$eid]->getValueHTML() : '';
	}
	
	function getExtraVarsValue($key)
	{
		$extra_vals = unserialize($this->get('extra_vars'));
		return $extra_vals->$key;
	}

	function getCommentCount()
	{
		return $this->get('comment_count');
	}

	function getComments()
	{
		if(!$this->getCommentCount())
		{
			return;
		}
		
		if(!$this->isAccessible())
		{
			return;
		}
		
		// cpage is a number of comment pages
		$cpageStr = sprintf('%d_cpage', $this->document_srl);
		$cpage = Context::get($cpageStr);
		if(!$cpage)
		{
			$cpage = Context::get('cpage');
		}
		if(!$cpage && ($comment_srl = Context::get('comment_srl')))
		{
			$cpage = CommentModel::getCommentPage($this->document_srl, $comment_srl);
		}
		if(!$cpage && ($comment_srl = Context::get('_comment_srl')))
		{
			$cpage = CommentModel::getCommentPage($this->document_srl, $comment_srl);
		}

		// Get a list of comments
		$output = CommentModel::getCommentList($this->document_srl, $cpage);
		if(!$output->toBool() || !count($output->data)) return;
		
		// Create commentItem object from a comment list
		// If admin priviledge is granted on parent posts, you can read its child posts.
		$accessible = array();
		$comment_list = array();
		$setAccessibleComments = Context::getSessionStatus();
		foreach($output->data as $key => $val)
		{
			$oCommentItem = new commentItem();
			$oCommentItem->setAttribute($val);
			// If permission is granted to the post, you can access it temporarily
			if($oCommentItem->isGranted()) $accessible[$val->comment_srl] = true;
			// If the comment is set to private and it belongs child post, it is allowable to read the comment for who has a admin privilege on its parent post
			if($val->parent_srl>0 && $val->is_secret == 'Y' && !$oCommentItem->isAccessible() && $accessible[$val->parent_srl]===true)
			{
				if($setAccessibleComments)
				{
					$oCommentItem->setAccessible();
				}
			}
			$comment_list[$val->comment_srl] = $oCommentItem;
		}
		
		// Cache the vote log for all comments.
		$logged_info = Context::get('logged_info');
		if ($logged_info->member_srl)
		{
			$comment_srls = array();
			foreach ($comment_list as $comment_srl => $comment)
			{
				if (!isset($_SESSION['voted_comment'][$comment_srl]))
				{
					$comment_srls[] = $comment_srl;
				}
			}
			if (count($comment_srls))
			{
				$v_output = executeQueryArray('comment.getCommentVotedLogMulti', array(
					'comment_srls' => $comment_srls,
					'member_srl' => $logged_info->member_srl,
				));
				foreach ($v_output->data ?: [] as $data)
				{
					$_SESSION['voted_comment'][$data->comment_srl] = $data->point;
				}
				foreach ($comment_srls as $comment_srl)
				{
					if (!isset($_SESSION['voted_comment'][$comment_srl]))
					{
						$_SESSION['voted_comment'][$comment_srl] = false;
					}
				}
			}
		}
		
		// Variable setting to be displayed on the skin
		Context::set($cpageStr, $output->page_navigation->cur_page);
		Context::set('cpage', $output->page_navigation->cur_page);
		if($output->total_page>1) $this->comment_page_navigation = $output->page_navigation;
		
		// Call trigger (after)
		$output = ModuleHandler::triggerCall('document.getComments', 'after', $comment_list);

		return $comment_list;
	}

	function getTrackbackCount()
	{
		return $this->get('trackback_count');
	}

	function getTrackbacks()
	{
		if(!$this->document_srl) return;

		if(!$this->allowTrackback() || !$this->get('trackback_count')) return;

		$oTrackbackModel = getModel('trackback');
		if ($oTrackbackModel)
		{
			return $oTrackbackModel->getTrackbackList($this->document_srl);
		}
	}

	function thumbnailExists($width = 80, $height = 0, $type = '')
	{
		if(!$this->document_srl) return false;
		if(!$this->getThumbnail($width, $height, $type)) return false;
		return true;
	}

	function getThumbnail($width = 80, $height = 0, $thumbnail_type = '')
	{
		// Return false if the document doesn't exist
		if(!$this->document_srl || !$this->isAccessible())
		{
			return;
		}

		// Get thumbnail type information from document module's configuration
		$config = DocumentModel::getDocumentConfig();
		if ($config->thumbnail_target === 'none' || $config->thumbnail_type === 'none')
		{
			return;
		}
		if(!in_array($thumbnail_type, array('crop', 'ratio', 'fill', 'stretch', 'center')))
		{
			$thumbnail_type = $config->thumbnail_type ?: 'fill';
		}
		if(!$config->thumbnail_quality)
		{
			$config->thumbnail_quality = 75;
		}
		
		// If not specify its height, create a square
		if(!is_int($width))
		{
			$width = intval($width);
		}
		if(!$height || (!is_int($height) && !ctype_digit(strval($height)) && $height !== 'auto'))
		{
			$height = $width;
		}
		
		// Define thumbnail information
		$thumbnail_path = sprintf('files/thumbnails/%s',getNumberingPath($this->document_srl, 3));
		$thumbnail_file = sprintf('%s%dx%d.%s.jpg', $thumbnail_path, $width, $height, $thumbnail_type);
		$thumbnail_lockfile = sprintf('%s%dx%d.%s.lock', $thumbnail_path, $width, $height, $thumbnail_type);
		$thumbnail_url = RX_BASEURL . $thumbnail_file;
		$thumbnail_file = RX_BASEDIR . $thumbnail_file;

		// Return false if thumbnail file exists and its size is 0. Otherwise, return its path
		if(file_exists($thumbnail_file) || file_exists($thumbnail_lockfile))
		{
			if(filesize($thumbnail_file) < 1)
			{
				return FALSE;
			}
			else
			{
				return $thumbnail_url . '?' . date('YmdHis', filemtime($thumbnail_file));
			}
		}
		
		// Call trigger for custom thumbnails.
		$trigger_obj = (object)[
			'document_srl' => $this->document_srl, 'width' => $width, 'height' => $height,
			'image_type' => 'jpg', 'type' => $thumbnail_type, 'quality' => $config->thumbnail_quality,
			'filename' => $thumbnail_file, 'url' => $thumbnail_url,
		];
		$output = ModuleHandler::triggerCall('document.getThumbnail', 'before', $trigger_obj);
		clearstatcache(true, $thumbnail_file);
		if (file_exists($thumbnail_file) && filesize($thumbnail_file) > 0)
		{
			return $thumbnail_url . '?' . date('YmdHis', filemtime($thumbnail_file));
		}
		
		// Get content if it does not exist.
		if($this->get('content'))
		{
			$content = $this->get('content');
		}
		elseif($config->thumbnail_target !== 'attachment')
		{
			$args = new stdClass();
			$args->document_srl = $this->document_srl;
			$output = executeQuery('document.getDocument', $args);
			$content = $output->data->content;
		}
		
		// Return false if neither attachement nor image files in the document
		if(!$this->get('uploaded_count') && !preg_match("!<img!is", $content)) return;

		// Create lockfile to prevent race condition
		FileHandler::writeFile($thumbnail_lockfile, '', 'w');

		// Target File
		$source_file = null;
		$is_tmp_file = false;

		// Find an image file among attached files if exists
		if($this->hasUploadedFiles())
		{
			$first_image = null;
			foreach($this->getUploadedFiles() as $file)
			{
				if($file->thumbnail_filename && file_exists($file->thumbnail_filename))
				{
					$file->uploaded_filename = $file->thumbnail_filename;
				}
				else
				{
					if($file->direct_download !== 'Y' || !preg_match('/\.(jpe?g|png|gif|webp|bmp)$/i', $file->source_filename))
					{
						continue;
					}
					if(!file_exists($file->uploaded_filename))
					{
						continue;
					}
				}
				if($file->cover_image === 'Y')
				{
					$source_file = FileHandler::getRealPath($file->uploaded_filename);
					break;
				}
				if(!$first_image)
				{
					$first_image = $file->uploaded_filename;
				}
			}
			if(!$source_file && $first_image)
			{
				$source_file = FileHandler::getRealPath($first_image);
			}
		}

		// If not exists, file an image file from the content
		if(!$source_file && $config->thumbnail_target !== 'attachment')
		{
			$external_image_min_width = min(100, round($trigger_obj->width * 0.3));
			$external_image_min_height = min(100, round($trigger_obj->height * 0.3));
			preg_match_all("!<img\s[^>]*?src=(\"|')([^\"' ]*?)(\"|')!is", $content, $matches, PREG_SET_ORDER);
			foreach($matches as $match)
			{
				$target_src = htmlspecialchars_decode(trim($match[2]));
				if(preg_match('/\/(common|modules|widgets|addons|layouts)\//i', $target_src))
				{
					continue;
				}
				else
				{
					if(!preg_match('/^https?:\/\//i',$target_src))
					{
						$target_src = Context::getRequestUri().$target_src;
					}

					$tmp_file = sprintf('./files/cache/tmp/%d', md5(rand(111111,999999).$this->document_srl));
					if(!is_dir('./files/cache/tmp'))
					{
						FileHandler::makeDir('./files/cache/tmp');
					}
					FileHandler::getRemoteFile($target_src, $tmp_file);
					if(!file_exists($tmp_file))
					{
						continue;
					}
					else
					{
						if($is_img = @getimagesize($tmp_file))
						{
							list($_w, $_h, $_t, $_a) = $is_img;
							if($_w < ($external_image_min_width) && ($height === 'auto' || $_h < ($external_image_min_height)))
							{
								continue;
							}
						}
						else
						{
							continue;
						}
						$source_file = $tmp_file;
						$is_tmp_file = true;
						break;
					}
				}
			}
		}
		
		if($source_file)
		{
			$output_file = FileHandler::createImageFile($source_file, $thumbnail_file, $trigger_obj->width, $trigger_obj->height, $trigger_obj->image_type, $trigger_obj->type, $trigger_obj->quality);
		}

		// Remove source file if it was temporary
		if($is_tmp_file)
		{
			FileHandler::removeFile($source_file);
		}

		// Remove lockfile
		FileHandler::removeFile($thumbnail_lockfile);

		// Return the thumbnail path if it was successfully generated
		if($output_file)
		{
			return $thumbnail_url . '?' . date('YmdHis');
		}
		// Create an empty file if thumbnail generation failed
		else
		{
			FileHandler::writeFile($thumbnail_file, '','w');
		}

		return;
	}

	/**
	 * Functions to display icons for new post, latest update, secret(private) post, image/video/attachment
	 * Determine new post and latest update by $time_interval
	 * @param int $time_interval
	 * @return array
	 */
	function getExtraImages($time_interval = 43200)
	{
		if(!$this->document_srl) return;
		// variables for icon list
		$buffs = array();

		$check_files = false;

		// Check if secret post is
		if($this->isSecret()) $buffs[] = "secret";

		// Set the latest time
		$time_check = date("YmdHis", $_SERVER['REQUEST_TIME']-$time_interval);

		// Check new post
		if($this->get('regdate')>$time_check) $buffs[] = "new";
		else if($this->get('last_update')>$time_check) $buffs[] = "update";

		/*
		   $content = $this->get('content');

		// Check image files
		preg_match_all('!<img([^>]*?)>!is', $content, $matches);
		$cnt = count($matches[0]);
		for($i=0;$i<$cnt;$i++) {
		if(preg_match('/editor_component=/',$matches[0][$i])&&!preg_match('/image_(gallery|link)/i',$matches[0][$i])) continue;
		$buffs[] = "image";
		$check_files = true;
		break;
		}

		// Check video files
		if(preg_match('!<embed([^>]*?)>!is', $content) || preg_match('/editor_component=("|\')*multimedia_link/i', $content) ) {
		$buffs[] = "movie";
		$check_files = true;
		}
		 */

		// Check the attachment
		if($this->hasUploadedFiles()) $buffs[] = "file";

		return $buffs;
	}

	/**
	 * Return the status code.
	 * 
	 * @return string
	 */
	function getStatus()
	{
		$status = $this->get('status');
		return $status ?: Document::getDefaultStatus();
	}

	/**
	 * Return the status in human-readable text.
	 * 
	 * @return string
	 */
	function getStatusText()
	{
		$status = $this->get('status');
		$statusList = lang('document.status_name_list');
		if ($status && isset($statusList[$status]))
		{
			return $statusList[$status];
		}
		else
		{
			return $statusList[Document::getDefaultStatus()];
		}
	}

	/**
	 * Return the value obtained from getExtraImages with image tag
	 * @param int $time_check
	 * @return string
	 */
	function printExtraImages($time_check = 43200)
	{
		if (!$this->document_srl)
		{
			return;
		}

		$icons = $this->getExtraImages($time_check);
		if(!count($icons))
		{
			return;
		}

		$documentConfig = DocumentModel::getDocumentConfig();

		if(Mobile::isFromMobilePhone())
		{
			$iconSkin = $documentConfig->micons ?? null;
			$iconType = $documentConfig->micons_type ?? 'gif';
		}
		else
		{
			$iconSkin = $documentConfig->icons ?? null;
			$iconType = $documentConfig->icons_type ?? 'gif';
		}
		if($iconSkin == null)
		{
			$iconSkin = 'default';
			$iconType = 'gif';
		}
		
		$path = sprintf('%s%s', \RX_BASEURL, "modules/document/tpl/icons/$iconSkin/");
		$buff = array();
		foreach($icons as $icon)
		{
			$buff[] = sprintf('<img src="%s%s.%s" alt="%s" title="%s" style="margin-right:2px;" />', $path, $icon, $iconType, $icon, $icon);
		}
		return implode('', $buff);
	}

	function hasUploadedFiles()
	{
		if(!$this->document_srl)
		{
			return false;
		}
		
		if(!$this->isAccessible())
		{
			return false;
		}
		
		return $this->get('uploaded_count')? true : false;
	}

	function getUploadedFiles($sortIndex = 'file_srl')
	{
		if(!$this->document_srl)
		{
			return;
		}
		
		if(!$this->isAccessible())
		{
			return;
		}
		
		if(!$this->get('uploaded_count'))
		{
			return;
		} 
		
		if(!isset($this->uploadedFiles[$sortIndex]))
		{
			$this->uploadedFiles[$sortIndex] = FileModel::getFiles($this->document_srl, array(), $sortIndex, true);
		}
		
		return $this->uploadedFiles[$sortIndex];
	}

	/**
	 * Return Editor html
	 * @return string
	 */
	function getEditor()
	{
		$module_srl = $this->get('module_srl') ?: Context::get('module_srl');
		return EditorModel::getModuleEditor('document', $module_srl, $this->document_srl, 'document_srl', 'content');
	}

	/**
	 * Check whether to have a permission to write comment
	 * Authority to write a comment and to write a document is separated
	 * @return bool
	 */
	function isEnableComment()
	{
		// Return false if not authorized, if a secret document, if the document is set not to allow any comment
		if (!$this->allowComment())
		{
			return false;
		}
		
		if(!$this->isAccessible())
		{
			return false;
		}
		
		return true;
	}

	/**
	 * Return comment editor's html
	 * @return string
	 */
	function getCommentEditor()
	{
		if(!$this->isEnableComment()) return;
		$module_srl = $this->get('module_srl') ?: Context::get('module_srl');
		return EditorModel::getModuleEditor('comment', $module_srl, 0, 'comment_srl', 'content');
	}

	/**
	 * Return author's profile image
	 * @return string
	 */
	function getProfileImage()
	{
		if(!$this->isExists() || $this->get('member_srl') <= 0) return;
		$profile_info = MemberModel::getProfileImage($this->get('member_srl'));
		if(!$profile_info) return;

		return $profile_info->src;
	}

	/**
	 * Return author's signiture
	 * @return string
	 */
	function getSignature()
	{
		// Pass if a document doesn't exist
		if(!$this->isExists() || $this->get('member_srl') <= 0) return;
		// Get signature information
		$signature = MemberModel::getSignature($this->get('member_srl'));
		// Check if a maximum height of signiture is set in the member module
		if(!isset($GLOBALS['__member_signature_max_height']))
		{
			$member_config = ModuleModel::getModuleConfig('member');
			$GLOBALS['__member_signature_max_height'] = $member_config->signature_max_height ?? 100;
		}
		if($signature)
		{
			$max_signature_height = $GLOBALS['__member_signature_max_height'];
			if($max_signature_height) $signature = sprintf('<div style="max-height:%dpx;overflow:auto;overflow-x:hidden;height:expression(this.scrollHeight > %d ? \'%dpx\': \'auto\')">%s</div>', $max_signature_height, $max_signature_height, $max_signature_height, $signature);
		}

		return $signature;
	}

	/**
	 * Change an image path in the content to absolute path
	 * @param array $matches
	 * @return mixed
	 */
	function replaceResourceRealPath($matches)
	{
		return preg_replace('/src=(["\']?)files/i','src=$1'.Context::getRequestUri().'files', $matches[0]);
	}

	/**
	 * Compatible function
	 * For only XE third party
	 */
	function _checkAccessibleFromStatus()
	{
		return $this->isAccessible();
	}

	function getTranslationLangCodes()
	{
		$obj = new stdClass;
		$obj->document_srl = $this->document_srl;
		// -2 is an index for content. We are interested if content has other translations.
		$obj->var_idx = -2;
		$output = executeQueryArray('document.getDocumentTranslationLangCodes', $obj);

		if (!$output->data)
		{
			$output->data = array();
		}
		// add original page's lang code as well
		$origLangCode = new stdClass;
		$origLangCode->lang_code = $this->getLangCode();
		$output->data[] = $origLangCode;

		return $output->data;
	}

	/**
	 * Returns the apparent mid.
	 * 
	 * @return string
	 */
	function getApparentMid()
	{
		return ModuleModel::getMidByModuleSrl($this->get('apparent_module_srl') ?: $this->get('module_srl'));
	}

	/**
	 * Returns the true mid.
	 * 
	 * @return string
	 */
	function getDocumentMid()
	{
		return ModuleModel::getMidByModuleSrl($this->get('module_srl'));
	}

	/**
	 * Returns the document's type (document/page/wiki/board/etc)
	 * @return string
	 */
	function getDocumentType()
	{
		return ModuleModel::getModuleInfoByModuleSrl($this->get('module_srl'))->module;
	}

	/**
	 * Returns the document's alias
	 * @return string
	 */
	function getDocumentAlias()
	{
		return DocumentModel::getAlias($this->document_srl);
	}

	/**
	 * Returns the document's actual title (browser_title)
	 * @return string
	 */
	function getModuleName()
	{
		return ModuleModel::getModuleInfoByModuleSrl($this->get('module_srl'))->browser_title;
	}

	function getBrowserTitle()
	{
		return $this->getModuleName();
	}
}
/* End of file document.item.php */
/* Location: ./modules/document/document.item.php */
