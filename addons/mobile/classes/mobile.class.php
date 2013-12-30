<?php
/**
 * Mobile XE Library Class ver 0.1
 * @author NAVER (developers@xpressengine.com) / lang_select : misol
 * @brief XE library for WAP tag output
 */
class mobileXE
{
	// Base url
	var $homeUrl = NULL;
	var $upperUrl = NULL;
	var $nextUrl = NULL;
	var $prevUrl = NULL;
	var $etcBtn = NULL;
	// Variable for menu navigation
	var $childs = null;
	// Basic variable
	var $title = NULL;
	var $content = NULL;
	var $mobilePage = 0;
	var $totalPage = 1;
	var $charset = 'UTF-8';
	var $no = 0;
	// Navigation-related variables
	var $menu = null;
	var $listed_items = null;
	var $node_list = null;
	var $index_mid = null;
	// Navigation On/Off status value
	var $navigationMode = 0;
	// XE module information currently requested
	var $module_info = null;
	// Currently running instance of the module
	var $oModule = null;

	// Deck size
	var $deckSize = 1024;
	// Changing the language setting
	var $languageMode = 0;
	var $lang = null;
	/**
	 * @brief getInstance
	 */
	function &getInstance()
	{
		static $instance = null;

		if(!$instance)
		{

			$browserType = mobileXE::getBrowserType();
			if(!$browserType) return;

			$class_file = sprintf('%saddons/mobile/classes/%s.class.php', _XE_PATH_, $browserType);
			require_once($class_file);
			// Download mobile language settings (cookies, not willing to come up when you click create cache file ...- is initialized ..)
			$this->lang = FileHandler::readFile('./files/cache/addons/mobile/setLangType/personal_settings/'.md5(trim($_SERVER['HTTP_USER_AGENT']).trim($_SERVER['HTTP_PHONE_NUMBER']).trim($_SERVER['HTTP_HTTP_PHONE_NUMBER'])).'.php');
			if($this->lang)
			{
				$lang_supported = Context::get('lang_supported');
				$this->lang = str_replace(array('<?php /**','**/ ?>'),array('',''),$this->lang);
				if(isset($lang_supported[$this->lang])) Context::setLangType($this->lang);
			}
			Context::loadLang(_XE_PATH_.'addons/mobile/lang');

			$instance = new wap();

			$mobilePage = (int)Context::get('mpage');
			if(!$mobilePage) $mobilePage = 1;

			$instance->setMobilePage($mobilePage);
		}
		return $instance;
	}

	/**
	 * @brief constructor
	 */
	function mobileXE()
	{
		// Check navigation mode
		if(Context::get('nm'))
		{
			$this->navigationMode = 1;
			$this->cmid = (int)Context::get('cmid');
		}

		if(Context::get('lcm'))
		{
			$this->languageMode = 1;
			$this->lang = Context::get('sel_lang');
		}
	}

	/**
	 * @brief Check navigation mode
	 * navigationMode settings and modules of information must be menu_srl return to navigation mode = true
	 */
	function isNavigationMode()
	{
		return ($this->navigationMode && $this->module_info->menu_srl)?true:false;
	}

	/**
	 * @brief Check langchange mode
	 * true return should be set languageMode
	 */
	function isLangChange()
	{
		if($this->languageMode) return true;
		else return false;
	}

	/**
	 * @brief Language settings
	 * Cookies Since you set your phone to store language-specific file, file creation
	 */
	function setLangType()
	{
		$lang_supported = Context::get('lang_supported');
		// Make sure that the language variables and parameters are valid
		if($this->lang && isset($lang_supported[$this->lang]))
		{
			$langbuff = FileHandler::readFile('./files/cache/addons/mobile/setLangType/personal_settings/'.md5(trim($_SERVER['HTTP_USER_AGENT']).trim($_SERVER['HTTP_PHONE_NUMBER']).trim($_SERVER['HTTP_HTTP_PHONE_NUMBER'])).'.php');
			if($langbuff) FileHandler::removeFile('./files/cache/addons/mobile/setLangType/personal_settings/'.md5(trim($_SERVER['HTTP_USER_AGENT']).trim($_SERVER['HTTP_PHONE_NUMBER']).trim($_SERVER['HTTP_HTTP_PHONE_NUMBER'])).'.php');
			$langbuff = '<?php /**'.$this->lang.'**/ ?>';
			FileHandler::writeFile('./files/cache/addons/mobile/setLangType/personal_settings/'.md5(trim($_SERVER['HTTP_USER_AGENT']).trim($_SERVER['HTTP_PHONE_NUMBER']).trim($_SERVER['HTTP_HTTP_PHONE_NUMBER'])).'.php',$langbuff);
		}
	}

	/**
	 * @brief Information currently requested module settings
	 */
	function setModuleInfo(&$module_info)
	{
		if($this->module_info) return; 
		$this->module_info = $module_info;
	}

	/**
	 * @brief Set the module instance is currently running
	 */
	function setModuleInstance(&$oModule)
	{
		if($this->oModule) return;
		// Save instance
		$this->oModule = $oModule;
		// Of the current module if there is a menu by menu
		$menu_cache_file = sprintf(_XE_PATH_.'files/cache/menu/%d.php', $this->module_info->menu_srl);
		if(!file_exists($menu_cache_file)) return;

		include $menu_cache_file;
		// One-dimensional arrangement of menu changes
		$this->getListedItems($menu->list, $listed_items, $node_list);

		$this->listed_items = $listed_items;
		$this->node_list = $node_list;
		$this->menu = $menu->list;

		$k = array_keys($node_list);
		$v = array_values($node_list);
		$this->index_mid = $k[0];
		// The depth of the current menu, the top button to specify if one or more
		$cur_menu_item = $listed_items[$node_list[$this->module_info->mid]];
		if($cur_menu_item['parent_srl'])
		{
			$parent_srl = $cur_menu_item['parent_srl'];
			if($parent_srl && $listed_items[$parent_srl])
			{
				$parent_item = $listed_items[$parent_srl];
				if($parent_item) $this->setUpperUrl(getUrl('','mid',$parent_item['mid']), Context::getLang('cmd_go_upper'));
			}
		}
		elseif (!$this->isNavigationMode())
		{
			$this->setUpperUrl(getUrl('','mid',$this->index_mid,'nm','1','cmid',0), Context::getLang('cmd_view_sitemap'));
		}
	}

	/**
	 * @brief Access the browser's header to determine the return type of the browser
	 * Mobile browser, if not null return
	 */
	function getBrowserType()
	{
		if(Context::get('smartphone')) return null;
		// Determine the type of browser
		$browserAccept = $_SERVER['HTTP_ACCEPT'];
		$userAgent = $_SERVER['HTTP_USER_AGENT'];
		$wap_sid = $_SERVER['HTTP_X_UP_SUBNO'];

		if(stripos($userAgent, "SKT11") !== FALSE || stripos($browserAccept, "skt") !== FALSE)
		{
			Context::set('mobile_skt',1);
			return "wml";
		}
		elseif(stripos($browserAccept, "hdml") !== FALSE) return "hdml";
		elseif(stripos($userAgent, "cellphone") !== FALSE) return  "mhtml";
		return null;
	}

	/**
	 * @brief Specify charset
	 */
	function setCharSet($charset = 'UTF-8')
	{
		if(!$charset) $charset = 'UTF-8';
		// SKT supports the euc-kr
		if(Context::get('mobile_skt')==1) $charset = 'euc-kr';

		$this->charset = $charset;
	}

	/**
	 * @brief Limited capacity of mobile devices, specifying a different virtual page
	 */
	function setMobilePage($page=1)
	{
		if(!$page) $page = 1;
		$this->mobilePage = $page;
	}

	/**
	 * @brief Mokrokhyeong child menu for specifying the data set
	 */
	function setChilds($childs)
	{
		// If more than nine the number of menu paging processing itself
		$menu_count = count($childs);
		if($menu_count>9)
		{
			$startNum = ($this->mobilePage-1)*9;
			$idx = 0;
			$new_childs = array();
			foreach($childs as $k => $v)
			{
				if($idx >= $startNum && $idx < $startNum+9)
				{
					$new_childs[$k] = $v;
				}
				$idx ++;
			}
			$childs = $new_childs;

			$this->totalPage = (int)(($menu_count-1)/9)+1;
			// next/prevUrl specify
			if($this->mobilePage>1)
			{
				$url = getUrl('mid',$_GET['mid'],'mpage',$this->mobilePage-1);
				$text = sprintf('%s (%d/%d)', Context::getLang('cmd_prev'), $this->mobilePage-1, $this->totalPage);
				$this->setPrevUrl($url, $text);
			}

			if($this->mobilePage<$this->totalPage)
			{
				$url = getUrl('mid',$_GET['mid'],'mpage',$this->mobilePage+1);
				$text = sprintf('%s (%d/%d)', Context::getLang('cmd_next'), $this->mobilePage+1, $this->totalPage);
				$this->setNextUrl($url, $text);
			}
		} 
		$this->childs = $childs;
	}

	/**
	 * @brief Check the menu to be output
	 */
	function hasChilds()
	{
		return count($this->childs)?true:0;
	}

	/**
	 * @brief Returns the child menu
	 */
	function getChilds()
	{
		return $this->childs;
	}

	/**
	 * @brief Specify title
	 */
	function setTitle($title)
	{
		$oModuleController = getController('module');
		$this->title = $title;
		$oModuleController->replaceDefinedLangCode($this->title);
	}

	/**
	 * @brief return title
	 */
	function getTitle()
	{
		return $this->title;
	}

	/**
	 * @brief Content Cleanup
	 * In HTML content, the ability to extract text and links
	 */
	function setContent($content)
	{
		$oModuleController = getController('module');
		$allow_tag_array = array('<a>','<br>','<p>','<b>','<i>','<u>','<em>','<small>','<strong>','<big>','<table>','<tr>','<td>');
		// Links/wrap, remove all tags except gangjoman
		$content = strip_tags($content, implode($allow_tag_array));
		// Margins tab removed
		$content = str_replace("\t", "", $content);
		// Repeat two more times the space and remove julnanumeul
		$content = preg_replace('/( ){2,}/s', '', $content);
		$content = preg_replace("/([\r\n]+)/s", "\r\n", $content);
		$content = preg_replace(array("/<a/i","/<\/a/i","/<b/i","/<\/b/i","/<br/i"),array('<a','</a','<b','</b','<br'),$content);
		$content = str_replace(array("<br>","<br />"), array("<br/>","<br/>"), $content);

		while(strpos($content, '<br/><br/>'))
		{
			$content = str_replace('<br/><br/>','<br/>',$content);
		}
		// If the required size of a deck of mobile content to write down all the dividing pages
		$contents = array();
		while($content)
		{
			$tmp = $this->cutStr($content, $this->deckSize, '');
			$contents[] = $tmp;
			$content = substr($content, strlen($tmp));

			//$content = str_replace(array('&','<','>','"','&amp;nbsp;'), array('&amp;','&lt;','&gt;','&quot;',' '), $content);

			foreach($allow_tag_array as $tag)
			{
				if($tag == '<br>') continue;
				$tag_open_pos = strpos($content, str_replace('>','',$tag));
				$tag_close_pos = strpos($content, str_replace('<','</',$tag));
				if($tag_open_pos!==false && $tag_close_pos || $tag_close_pos < $tag_open_pos)
				{
					$contents[count($contents)-1] .= substr($content, 0, $tag_close_pos + strlen($tag) + 1);
					$content = substr($content, $tag_close_pos + strlen($tag) + 1);
				}
			}

			$tag_open_pos = strpos($content, '&');
			$tag_close_pos = strpos($content, ';');
			if($tag_open_pos!==false && $tag_close_pos || $tag_close_pos < $tag_open_pos)
			{
				$contents[count($contents)-1] .= substr($content, 0, $tag_close_pos + 1);
				$content = substr($content, $tag_close_pos + 1);
			}
		}

		$this->totalPage = count($contents);
		// next/prevUrl specify
		if($this->mobilePage>1)
		{
			$url = getUrl('mid',$_GET['mid'],'mpage',$this->mobilePage-1);
			$text = sprintf('%s (%d/%d)', Context::getLang('cmd_prev'), $this->mobilePage-1, $this->totalPage);
			$this->setPrevUrl($url, $text);
		}

		if($this->mobilePage<$this->totalPage)
		{
			$url = getUrl('mid',$_GET['mid'],'mpage',$this->mobilePage+1);
			$text = sprintf('%s (%d/%d)', Context::getLang('cmd_next'), $this->mobilePage+1, $this->totalPage);
			$this->setNextUrl($url, $text);
		}

		$this->content = $contents[$this->mobilePage-1];
		$oModuleController->replaceDefinedLangCode($this->content);
		$content = str_replace(array('$','\''), array('$$','&apos;'), $content);
	}

	/**
	 * @brief cutting the number of byte functions
	 */
	function cutStr($string, $cut_size)
	{
		return preg_match('/.{'.$cut_size.'}/su', $string, $arr) ? $arr[0] : $string; 
	}

	/**
	 * @brief Return content
	 */
	function getContent()
	{
		return $this->content;
	}

	/**
	 * @brief Specifies the home url
	 */
	function setHomeUrl($url, $text)
	{
		if(!$url) $url = '#';
		$this->homeUrl->url = $url;
		$this->homeUrl->text = $text;
	}

	/**
	 * @brief Specify upper url
	 */
	function setUpperUrl($url, $text)
	{
		if(!$url) $url = '#';
		$this->upperUrl->url = $url;
		$this->upperUrl->text = $text;
	}

	/**
	 * @brief Specify prev url
	 */
	function setPrevUrl($url, $text)
	{
		if(!$url) $url = '#';
		$this->prevUrl->url = $url;
		$this->prevUrl->text = $text;
	}

	/**
	 * @brief Specify next url
	 */
	function setNextUrl($url, $text)
	{
		if(!$url) $url = '#';
		$this->nextUrl->url = $url;
		$this->nextUrl->text = $text;
	}

	/**
	 * @brief Next, Previous, Top button assignments other than
	 */
	function setEtcBtn($url, $text)
	{
		if(!$url) $url = '#';
		$etc['url'] = $url;
		$etc['text'] = htmlspecialchars($text);
		$this->etcBtn[] = $etc;
	}

	/**
	 * @brief display
	 */
	function display()
	{
		// Home button assignments
		$this->setHomeUrl(getUrl(), Context::getLang('cmd_go_home'));
		// Specify the title
		if(!$this->title) $this->setTitle(Context::getBrowserTitle());

		ob_start();
		// Output header
		$this->printHeader();
		// Output title
		$this->printTitle();
		// Information output
		$this->printContent();
		// Button output
		$this->printBtn();
		// Footer output
		$this->printFooter();

		$content = ob_get_clean();
		// After conversion output
		if(strtolower($this->charset) == 'utf-8') print $content;
		else print iconv('UTF-8',$this->charset."//TRANSLIT//IGNORE", $content);

		exit();
	}

	/**
	 * @brief Move page
	 */
	function movepage($url)
	{
		header("location:$url");
		exit();
	}

	/**
	 * @brief And returns a list of serial numbers in
	 */
	function getNo()
	{
		$this->no++;
		$str = $this->no;
		return $str;
	}

	/**
	 * @brief XE is easy to use Menu module is relieved during the function, value
	 */
	function getListedItems($menu, &$listed_items, &$node_list)
	{
		if(!count($menu)) return;
		foreach($menu as $node_srl => $item)
		{
			if(preg_match('/^([a-zA-Z0-9\_\-]+)$/', $item['url']))
			{
				$mid = $item['mid'] = $item['url'];
				$node_list[$mid] = $node_srl;
			}
			else
			{
				$mid = $item['mid'] = null;
			}

			$listed_items[$node_srl] = $item;
			$this->getListedItems($item['list'], $listed_items, $node_list);
		}
	}

	/**
	 * @brief XE navigation output
	 */
	function displayNavigationContent()
	{
		$childs = array();

		if($this->cmid)
		{
			$cur_item = $this->listed_items[$this->cmid];
			$upper_srl = $cur_item['parent_srl'];;
			$list = $cur_item['list'];;
			$this->setUpperUrl(getUrl('cmid',$upper_srl), Context::getLang('cmd_go_upper'));
			if(preg_match('/^([a-zA-Z0-9\_\-]+)$/', $cur_item['url']))
			{
				$obj = array();
				$obj['href'] = getUrl('','mid',$cur_item['url']);
				$obj['link'] = $obj['text'] = '['.$cur_item['text'].']';
				$childs[] = $obj;
			}

		}
		else
		{
			$list = $this->menu;
			$upper_srl = 0;
		}

		if(count($list))
		{
			foreach($list as $key => $val)
			{
				if(!$val['text']) continue;
				$obj = array();
				if(!count($val['list']))
				{
					$obj['href'] = getUrl('','mid',$val['url']);
				}
				else
				{
					$obj['href'] = getUrl('cmid',$val['node_srl']);
				}
				$obj['link'] = $obj['text'] = $val['text'];
				$childs[] = $obj;
			}
			$this->setChilds($childs);
		}
		// Output
		$this->display();
	}

	/**
	 * @brief Language Settings menu, the output
	 */
	function displayLangSelect()
	{
		$childs = array();

		$this->lang = FileHandler::readFile('./files/cache/addons/mobile/setLangType/personal_settings/'.md5(trim($_SERVER['HTTP_USER_AGENT']).trim($_SERVER['HTTP_PHONE_NUMBER']).trim($_SERVER['HTTP_HTTP_PHONE_NUMBER'])).'.php');
		if($this->lang)
		{
			$this->lang = str_replace(array('<?php /**','**/ ?>'),array('',''),$this->lang);
			Context::setLangType($this->lang);
		}
		$lang_supported = Context::get('lang_supported');
		$lang_type = Context::getLangType();
		$obj = array();
		$obj['link'] = $obj['text'] = Context::getLang('president_lang').' : '.$lang_supported[$lang_type];
		$obj['href'] = getUrl('sel_lang',$lang_type);
		$childs[] = $obj;

		if(is_array($lang_supported))
		{
			foreach($lang_supported as $key => $val)
			{
				$obj = array();
				$obj['link'] = $obj['text'] = $val;
				$obj['href'] = getUrl('sel_lang',$key);
				$childs[] = $obj;
			}
		}

		$this->setChilds($childs);

		$this->display();
	}

	/**
	 * @brief Module to create a class object of the WAP WAP ready
	 */
	function displayModuleContent()
	{
		// Create WAP class objects of the selected module
		$oModule = &getWap($this->module_info->module);
		if(!$oModule || !method_exists($oModule, 'procWAP') ) return;

		$vars = get_object_vars($this->oModule);
		if(count($vars)) foreach($vars as $key => $val) $oModule->{$key}  = $val;
		// Run
		$oModule->procWAP($this);
		// Output
		$this->display();
	}

	/**
	 * @brief WAP content is available as a separate output if the final results
	 */
	function displayContent()
	{
		Context::set('layout','none');
		// Compile a template
		$oTemplate = new TemplateHandler();
		$oContext = &Context::getInstance();

		$content = $oTemplate->compile($this->oModule->getTemplatePath(), $this->oModule->getTemplateFile());
		$this->setContent($content);
		// Output
		$this->display();
	}
}
/* End of file mobile.class.php */
/* Location: ./addons/mobile/classes/mobile.class.php */
