<?php

class XMLDisplayHandler {
	/**
	 * @brief Produce XML compliant content given a module object.\n
	 * @param[in] $oModule the module object
	 **/
	function toDoc(&$oModule)
	{
		$variables = $oModule->getVariables();

		$xmlDoc  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<response>\n";
		$xmlDoc .= sprintf("<error>%s</error>\n",$oModule->getError());
		$xmlDoc .= sprintf("<message>%s</message>\n",str_replace(array('<','>','&'),array('&lt;','&gt;','&amp;'),$oModule->getMessage()));

		$xmlDoc .= $this->_makeXmlDoc($variables);

		$xmlDoc .= "</response>";

		return $xmlDoc;
	}

	/**
	 * @brief print a HTTP HEADER for XML, which is encoded in UTF-8
	 **/
	function printHeader() {
		header("Content-Type: text/xml; charset=UTF-8");
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
	}

	/**
	 * @brief produce XML code given variable object\n
	 * @param[in] $oModule the module object
	 **/
	function _makeXmlDoc($obj) {
		if(!count($obj)) return;

		$xmlDoc = '';

		foreach($obj as $key => $val) {
			if(is_numeric($key)) $key = 'item';

			if(is_string($val)) $xmlDoc .= sprintf('<%s><![CDATA[%s]]></%s>%s', $key, $val, $key,"\n");
			else if(!is_array($val) && !is_object($val)) $xmlDoc .= sprintf('<%s>%s</%s>%s', $key, $val, $key,"\n");
			else $xmlDoc .= sprintf('<%s>%s%s</%s>%s',$key, "\n", $this->_makeXmlDoc($val), $key, "\n");
		}

		return $xmlDoc;
	}
}

?>
