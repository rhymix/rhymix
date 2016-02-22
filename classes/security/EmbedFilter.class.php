<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

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
		$this->checkParamTag($content);
	}

	/**
	 * Check iframe tag in the content.
	 * @return void
	 */
	function checkIframeTag(&$content)
	{
		// check in Purifier class
		return;
	}

	/**
	 * Check object tag in the content.
	 * @return void
	 */
	function checkObjectTag(&$content)
	{
		$content = preg_replace_callback('/<\s*object\s*[^>]+(?:\/?>?)/is', function($m) {
			$html = Sunra\PhpSimple\HtmlDomParser::str_get_html($m[0]);
			foreach ($html->find('object') as $element)
			{
				if ($element->data && !$this->isWhiteDomain($element->data))
				{
					return escape($m[0], false);
				}
				if ($element->type && !$this->isWhiteMimetype($element->type))
				{
					return escape($m[0], false);
				}
			}
			return $m[0];
		}, $content);
	}

	/**
	 * Check embed tag in the content.
	 * @return void
	 */
	function checkEmbedTag(&$content)
	{
		$content = preg_replace_callback('/<\s*embed\s*[^>]+(?:\/?>?)/is', function($m) {
			$html = Sunra\PhpSimple\HtmlDomParser::str_get_html($m[0]);
			foreach ($html->find('embed') as $element)
			{
				if ($element->src && !$this->isWhiteDomain($element->src))
				{
					return escape($m[0], false);
				}
				if ($element->type && !$this->isWhiteMimetype($element->type))
				{
					return escape($m[0], false);
				}
			}
			return $m[0];
		}, $content);
	}

	/**
	 * Check param tag in the content.
	 * @return void
	 */
	function checkParamTag(&$content)
	{
		$content = preg_replace_callback('/<\s*param\s*[^>]+(?:\/?>?)/is', function($m) {
			$html = Sunra\PhpSimple\HtmlDomParser::str_get_html($m[0]);
			foreach ($html->find('param') as $element)
			{
				foreach (array('movie', 'src', 'href', 'url', 'source') as $attr)
				{
					if ($element->$attr && !$this->isWhiteDomain($element->$attr))
					{
						return escape($m[0], false);
					}
				}
			}
			return $m[0];
		}, $content);
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
		$whiteUrlDefaultList = (include RX_BASEDIR . 'common/defaults/whitelist.php');
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
			if ($embedfilter_object = config('embedfilter.object'))
			{
				foreach ($embedfilter_object as $prefix)
				{
					$this->whiteUrlList[] = preg_match('@^https?://(.*)$@i', $prefix, $matches) ? $matches[1] : $prefix;
				}
			}
			if ($embedfilter_iframe = config('embedfilter.iframe'))
			{
				foreach ($embedfilter_iframe as $prefix)
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
