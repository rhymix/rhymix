<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

include _XE_PATH_ . 'classes/security/phphtmlparser/src/htmlparser.inc';

class EmbedFilter
{

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
	var $whiteUrlDefaultFile = './classes/security/conf/whitelist.php';
	var $whiteUrlList = array();
	var $whiteIframeUrlList = array();
	var $mimeTypeList = array();
	var $extList = array();
	var $parser = NULL;

	/**
	 * @constructor
	 * @return void
	 */
	function __construct()
	{
		$this->_makeWhiteDomainList();
	}

	/**
	 * Return EmbedFilter object
	 * This method for singleton
	 * @return EmbedFilter
	 */
	function getInstance()
	{
		if(!isset($GLOBALS['__EMBEDFILTER_INSTANCE__']))
		{
			$GLOBALS['__EMBEDFILTER_INSTANCE__'] = new EmbedFilter();
		}
		return $GLOBALS['__EMBEDFILTER_INSTANCE__'];
	}

	public function getWhiteUrlList()
	{
		return $this->whiteUrlList;
	}

	public function getWhiteIframeUrlList()
	{
		return $this->whiteIframeUrlList;
	}

	/**
	 * Check the content.
	 * @return void
	 */
	function check(&$content)
	{
		$content = preg_replace_callback('/<(object|param|embed)[^>]*/is', array($this, '_checkAllowScriptAccess'), $content);
		$content = preg_replace_callback('/<object[^>]*>/is', array($this, '_addAllowScriptAccess'), $content);

		$this->checkObjectTag($content);
		$this->checkEmbedTag($content);
		$this->checkIframeTag($content);
		$this->checkParamTag($content);
	}

	/**
	 * Check object tag in the content.
	 * @return void
	 */
	function checkObjectTag(&$content)
	{
		preg_match_all('/<\s*object\s*[^>]+(?:\/?>?)/is', $content, $m);
		$objectTagList = $m[0];
		if($objectTagList)
		{
			foreach($objectTagList AS $key => $objectTag)
			{
				$isWhiteDomain = true;
				$isWhiteMimetype = true;
				$isWhiteExt = true;
				$ext = '';

				$parser = new HtmlParser($objectTag);
				while($parser->parse())
				{
					if(is_array($parser->iNodeAttributes))
					{
						foreach($parser->iNodeAttributes AS $attrName => $attrValue)
						{
							// data url check
							if($attrValue && strtolower($attrName) == 'data')
							{
								$ext = strtolower(substr(strrchr($attrValue, "."), 1));
								$isWhiteDomain = $this->isWhiteDomain($attrValue);
							}

							// mime type check
							if(strtolower($attrName) == 'type' && $attrValue)
							{
								$isWhiteMimetype = $this->isWhiteMimetype($attrValue);
							}
						}
					}
				}

				if(!$isWhiteDomain || !$isWhiteMimetype)
				{
					$content = str_replace($objectTag, htmlspecialchars($objectTag, ENT_COMPAT | ENT_HTML401, 'UTF-8', false), $content);
				}
			}
		}
	}

	/**
	 * Check embed tag in the content.
	 * @return void
	 */
	function checkEmbedTag(&$content)
	{
		preg_match_all('/<\s*embed\s*[^>]+(?:\/?>?)/is', $content, $m);
		$embedTagList = $m[0];
		if($embedTagList)
		{
			foreach($embedTagList AS $key => $embedTag)
			{
				$isWhiteDomain = TRUE;
				$isWhiteMimetype = TRUE;
				$isWhiteExt = TRUE;
				$ext = '';

				$parser = new HtmlParser($embedTag);
				while($parser->parse())
				{
					if(is_array($parser->iNodeAttributes))
					{
						foreach($parser->iNodeAttributes AS $attrName => $attrValue)
						{
							// src url check
							if($attrValue && strtolower($attrName) == 'src')
							{
								$ext = strtolower(substr(strrchr($attrValue, "."), 1));
								$isWhiteDomain = $this->isWhiteDomain($attrValue);
							}

							// mime type check
							if(strtolower($attrName) == 'type' && $attrValue)
							{
								$isWhiteMimetype = $this->isWhiteMimetype($attrValue);
							}
						}
					}
				}

				if(!$isWhiteDomain || !$isWhiteMimetype)
				{
					$content = str_replace($embedTag, htmlspecialchars($embedTag, ENT_COMPAT | ENT_HTML401, 'UTF-8', false), $content);
				}
			}
		}
	}

	/**
	 * Check iframe tag in the content.
	 * @return void
	 */
	function checkIframeTag(&$content)
	{
		// check in Purifier class
		return;

		preg_match_all('/<\s*iframe\s*[^>]+(?:\/?>?)/is', $content, $m);
		$iframeTagList = $m[0];
		if($iframeTagList)
		{
			foreach($iframeTagList AS $key => $iframeTag)
			{
				$isWhiteDomain = TRUE;
				$ext = '';

				$parser = new HtmlParser($iframeTag);
				while($parser->parse())
				{
					if(is_array($parser->iNodeAttributes))
					{
						foreach($parser->iNodeAttributes AS $attrName => $attrValue)
						{
							// src url check
							if(strtolower($attrName) == 'src' && $attrValue)
							{
								$ext = strtolower(substr(strrchr($attrValue, "."), 1));
								$isWhiteDomain = $this->isWhiteIframeDomain($attrValue);
							}
						}
					}
				}

				if(!$isWhiteDomain)
				{
					$content = str_replace($iframeTag, htmlspecialchars($iframeTag, ENT_COMPAT | ENT_HTML401, 'UTF-8', false), $content);
				}
			}
		}
	}

	/**
	 * Check param tag in the content.
	 * @return void
	 */
	function checkParamTag(&$content)
	{
		preg_match_all('/<\s*param\s*[^>]+(?:\/?>?)/is', $content, $m);
		$paramTagList = $m[0];
		if($paramTagList)
		{
			foreach($paramTagList AS $key => $paramTag)
			{
				$isWhiteDomain = TRUE;
				$isWhiteExt = TRUE;
				$ext = '';

				$parser = new HtmlParser($paramTag);
				while($parser->parse())
				{
					if($parser->iNodeAttributes['name'] && $parser->iNodeAttributes['value'])
					{
						$name = strtolower($parser->iNodeAttributes['name']);
						if($name == 'movie' || $name == 'src' || $name == 'href' || $name == 'url' || $name == 'source')
						{
							$ext = strtolower(substr(strrchr($parser->iNodeAttributes['value'], "."), 1));
							$isWhiteDomain = $this->isWhiteDomain($parser->iNodeAttributes['value']);

							if(!$isWhiteDomain)
							{
								$content = str_replace($paramTag, htmlspecialchars($paramTag, ENT_COMPAT | ENT_HTML401, 'UTF-8', false), $content);
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Check white domain in object data attribute or embed src attribute.
	 * @return string
	 */
	function isWhiteDomain($urlAttribute)
	{
		if(is_array($this->whiteUrlList))
		{
			foreach($this->whiteUrlList AS $key => $value)
			{
				if(preg_match('@^https?://' . preg_quote($value, '@') . '@i', $urlAttribute))
				{
					return TRUE;
				}
			}
		}
		return FALSE;
	}

	/**
	 * Check white domain in iframe src attribute.
	 * @return string
	 */
	function isWhiteIframeDomain($urlAttribute)
	{
		if(is_array($this->whiteIframeUrlList))
		{
			foreach($this->whiteIframeUrlList AS $key => $value)
			{
				if(preg_match('@^https?://' . preg_quote($value, '@') . '@i', $urlAttribute))
				{
					return TRUE;
				}
			}
		}
		return FALSE;
	}

	/**
	 * Check white mime type in object type attribute or embed type attribute.
	 * @return string
	 */
	function isWhiteMimetype($mimeType)
	{
		if(isset($this->mimeTypeList[$mimeType]))
		{
			return TRUE;
		}
		return FALSE;
	}

	function isWhiteExt($ext)
	{
		if(isset($this->extList[$ext]))
		{
			return TRUE;
		}
		return FALSE;
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
				$this->allowscriptaccessList[count($this->allowscriptaccessList) - 1]--;
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

	function _addAllowScriptAccess($m)
	{
		if($this->allowscriptaccessList[$this->allowscriptaccessKey] == 1)
		{
			$m[0] = $m[0] . '<param name="allowscriptaccess" value="never"></param>';
		}
		$this->allowscriptaccessKey++;
		return $m[0];
	}

	/**
	 * Make white domain list cache file from xml config file.
	 * @param $whitelist array
	 * @return void
	 */
	function _makeWhiteDomainList($whitelist = NULL)
	{
		$whiteUrlDefaultFile = FileHandler::getRealPath($this->whiteUrlDefaultFile);
		$whiteUrlDefaultList = (include $whiteUrlDefaultFile);
		$this->extList = $whiteUrlDefaultList['extensions'];
		$this->mimeTypeList = $whiteUrlDefaultList['mime'];
		$this->whiteUrlList = array();
		$this->whiteIframeUrlList = array();

		if($whitelist !== NULL)
		{
			if(!is_array($whitelist) || !isset($whitelist['object']) || !isset($whitelist['iframe']))
			{
				$whitelist = array(
					'object' => isset($whitelist->object) ? $whitelist->object : array(),
					'iframe' => isset($whitelist->iframe) ? $whitelist->iframe : array(),
				);
			}
			foreach ($whitelist['object'] as $prefix)
			{
				$this->whiteUrlList[] = preg_match('@^https?://(.*)$@i', $prefix, $matches) ? $matches[1] : $prefix;
			}
			foreach ($whitelist['iframe'] as $prefix)
			{
				$this->whiteIframeUrlList[] = preg_match('@^https?://(.*)$@i', $prefix, $matches) ? $matches[1] : $prefix;
			}
		}
		else
		{
			foreach ($whiteUrlDefaultList['object'] as $prefix)
			{
				$this->whiteUrlList[] = $prefix;
			}
			foreach ($whiteUrlDefaultList['iframe'] as $prefix)
			{
				$this->whiteIframeUrlList[] = $prefix;
			}
			
			$db_info = Context::getDBInfo();
			if(isset($db_info->embed_white_object) && count($db_info->embed_white_object))
			{
				foreach ($db_info->embed_white_object as $prefix)
				{
					$this->whiteUrlList[] = preg_match('@^https?://(.*)$@i', $prefix, $matches) ? $matches[1] : $prefix;
				}
			}
			if(isset($db_info->embed_white_iframe) && count($db_info->embed_white_iframe))
			{
				foreach ($db_info->embed_white_iframe as $prefix)
				{
					$this->whiteIframeUrlList[] = preg_match('@^https?://(.*)$@i', $prefix, $matches) ? $matches[1] : $prefix;
				}
			}
		}

		$this->whiteUrlList = array_unique($this->whiteUrlList);
		$this->whiteIframeUrlList = array_unique($this->whiteIframeUrlList);
		natcasesort($this->whiteUrlList);
		natcasesort($this->whiteIframeUrlList);
	}
}
/* End of file : EmbedFilter.class.php */
/* Location: ./classes/security/EmbedFilter.class.php */
