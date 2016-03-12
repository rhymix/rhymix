<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

class EmbedFilter
{
	/**
	 * allow script access list
	 * @var array
	 */
	var $allowscriptaccessList = array();
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
		// This functionality has been moved to the HTMLFilter class.
	}

	/**
	 * Check iframe tag in the content.
	 * @return void
	 */
	function checkIframeTag(&$content)
	{
		// This functionality has been moved to the HTMLFilter class.
	}

	/**
	 * Check object tag in the content.
	 * @return void
	 */
	function checkObjectTag(&$content)
	{
		// This functionality has been moved to the HTMLFilter class.
	}

	/**
	 * Check embed tag in the content.
	 * @return void
	 */
	function checkEmbedTag(&$content)
	{
		// This functionality has been moved to the HTMLFilter class.
	}

	/**
	 * Check param tag in the content.
	 * @return void
	 */
	function checkParamTag(&$content)
	{
		// This functionality has been moved to the HTMLFilter class.
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
