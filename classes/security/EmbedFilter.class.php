<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

class EmbedFilter
{
	/**
	 * Deprecated properties
	 * @var array
	 */
	public $whiteUrlList = array();
	public $whiteIframeUrlList = array();
	public $mimeTypeList = array();
	public $extList = array();

	/**
	 * Return EmbedFilter object
	 * 
	 * @return EmbedFilter
	 */
	function getInstance()
	{
		return new self();
	}
	
	public function getWhiteUrlList()
	{
		return Rhymix\Framework\Filters\MediaFilter::getObjectWhitelist();
	}
	
	public function getWhiteIframeUrlList()
	{
		return Rhymix\Framework\Filters\MediaFilter::getIframeWhitelist();
	}
	
	function isWhiteDomain($urlAttribute)
	{
		return Rhymix\Framework\Filters\MediaFilter::matchObjectWhitelist($urlAttribute);
	}
	
	function isWhiteIframeDomain($urlAttribute)
	{
		return Rhymix\Framework\Filters\MediaFilter::matchIframeWhitelist($urlAttribute);
	}
	
	function isWhiteMimetype($mimeType)
	{
		return true;
	}
	
	function isWhiteExt($ext)
	{
		return true;
	}
	
	function check(&$content)
	{
		// This functionality has been moved to the HTMLFilter class.
	}
	
	function checkIframeTag(&$content)
	{
		// This functionality has been moved to the HTMLFilter class.
	}
	
	function checkObjectTag(&$content)
	{
		// This functionality has been moved to the HTMLFilter class.
	}
	
	function checkEmbedTag(&$content)
	{
		// This functionality has been moved to the HTMLFilter class.
	}
	
	function checkParamTag(&$content)
	{
		// This functionality has been moved to the HTMLFilter class.
	}
}
/* End of file : EmbedFilter.class.php */
/* Location: ./classes/security/EmbedFilter.class.php */
