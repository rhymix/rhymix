<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

class Purifier
{

	private $_cacheDir;
	private $_htmlPurifier;
	private $_config;
	private $_def;

	public function Purifier()
	{
		$this->_checkCacheDir();

		// purifier setting
		require_once _XE_PATH_ . 'classes/security/htmlpurifier/library/HTMLPurifier.auto.php';
		require_once 'HTMLPurifier.func.php';

		$this->_setConfig();
	}

	public function getInstance()
	{
		if(!isset($GLOBALS['__PURIFIER_INSTANCE__']))
		{
			$GLOBALS['__PURIFIER_INSTANCE__'] = new Purifier();
		}
		return $GLOBALS['__PURIFIER_INSTANCE__'];
	}

	private function _setConfig()
	{
		$whiteDomainRegex = $this->_getWhiteDomainRegx();
		//$allowdClasses = array('emoticon');

		$this->_config = HTMLPurifier_Config::createDefault();
		$this->_config->set('HTML.TidyLevel', 'light');
		$this->_config->set('Output.FlashCompat', TRUE);
		$this->_config->set('HTML.SafeObject', TRUE);
		$this->_config->set('HTML.SafeEmbed', TRUE);
		$this->_config->set('HTML.SafeIframe', TRUE);
		$this->_config->set('URI.SafeIframeRegexp', $whiteDomainRegex);
		$this->_config->set('Cache.SerializerPath', $this->_cacheDir);
		$this->_config->set('Attr.AllowedFrameTargets', array('_blank'));
		//$this->_config->set('Attr.AllowedClasses', $allowdClasses);

		$this->_def = $this->_config->getHTMLDefinition(TRUE);
	}

	private function _setDefinition(&$content)
	{
		// add attribute for edit component
		$editComponentAttrs = $this->_searchEditComponent($content);
		if(is_array($editComponentAttrs))
		{
			foreach($editComponentAttrs AS $k => $v)
			{
				$this->_def->addAttribute('img', $v, 'CDATA');
				$this->_def->addAttribute('div', $v, 'CDATA');
			}
		}

		// add attribute for widget component
		$widgetAttrs = $this->_searchWidget($content);
		if(is_array($widgetAttrs))
		{
			foreach($widgetAttrs AS $k => $v)
			{
				$this->_def->addAttribute('img', $v, 'CDATA');
			}
		}
	}

	/**
	 * Search attribute of edit component tag
	 * @param string $content
	 * @return array
	 */
	private function _searchEditComponent($content)
	{
		preg_match_all('!<(?:(div)|img)([^>]*)editor_component=([^>]*)>(?(1)(.*?)</div>)!is', $content, $m);

		$attributeList = array();
		if(is_array($m[2]))
		{
			foreach($m[2] as $key => $value)
			{
				unset($script, $m2);
				$script = " {$m[2][$key]} editor_component={$m[3][$key]}";

				if(preg_match_all('/([a-z0-9_-]+)="([^"]+)"/is', $script, $m2))
				{
					foreach($m2[1] as $value2)
					{
						//SECISSUE check style attr
						if($value2 == 'style')
						{
							continue;
						}
						$attributeList[] = $value2;
					}
				}
			}
		}

		return array_unique($attributeList);
	}

	/**
	 * Search edit component tag
	 * @param string $content
	 * @return array
	 */
	private function _searchWidget(&$content)
	{
		preg_match_all('!<(?:(div)|img)([^>]*)class="zbxe_widget_output"([^>]*)>(?(1)(.*?)</div>)!is', $content, $m);

		$attributeList = array();
		if(is_array($m[3]))
		{
			$content = str_replace('<img class="zbxe_widget_output"', '<img src="" class="zbxe_widget_output"', $content);

			foreach($m[3] as $key => $value)
			{
				if (preg_match_all('/([a-z0-9_-]+)="([^"]+)"/is', $m[3][$key], $m2))
				{
					foreach($m2[1] as $value2)
					{
						//SECISSUE check style attr
						if($value2 == 'style')
						{
							continue;
						}
						$attributeList[] = $value2;
					}
				}
			}
		}
		return array_unique($attributeList);
	}

	private function _getWhiteDomainRegx()
	{
		$oEmbedFilter = EmbedFilter::getInstance();
		$whiteIframeUrlList = $oEmbedFilter->getWhiteIframeUrlList();

		$whiteDomain = array();
		foreach($whiteIframeUrlList as $value)
		{
			$whiteDomain[] = preg_quote($value, '%');
		}

		$whiteDomainRegex = '%^(' . implode('|', $whiteDomain) . ')%';

		return $whiteDomainRegex;
	}

	private function _checkCacheDir()
	{
		// check htmlpurifier cache directory
		$this->_cacheDir = _XE_PATH_ . 'files/cache/htmlpurifier';
		FileHandler::makeDir($this->_cacheDir);
	}

	public function purify(&$content)
	{
		$this->_setDefinition($content);
		$this->_htmlPurifier = new HTMLPurifier($this->_config);

		$content = $this->_htmlPurifier->purify($content);
	}

}
/* End of file : Purifier.class.php */
/* Location: ./classes/security/Purifier.class.php */
