<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

class XMLDisplayHandler
{

	/**
	 * Produce XML compliant content given a module object.\n
	 * @param ModuleObject $oModule the module object
	 * @return string
	 */
	function toDoc(&$oModule)
	{
		$variables = $oModule->getVariables();

		$xmlDoc = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<response>\n";
		$xmlDoc .= sprintf("<error>%s</error>\n", $oModule->getError());
		$xmlDoc .= sprintf("<message>%s</message>\n", str_replace(array('<', '>', '&'), array('&lt;', '&gt;', '&amp;'), $oModule->getMessage()));

		$xmlDoc .= $this->_makeXmlDoc($variables);

		$xmlDoc .= "</response>";

		return $xmlDoc;
	}

	/**
	 * produce XML code given variable object\n
	 * @param object $obj 
	 * @return string
	 */
	function _makeXmlDoc($obj)
	{
		if(!count($obj))
		{
			return;
		}

		$xmlDoc = '';

		foreach($obj as $key => $val)
		{
			if(is_numeric($key))
			{
				$key = 'item';
			}

			if(is_string($val))
			{
				$xmlDoc .= sprintf('<%s><![CDATA[%s]]></%s>%s', $key, $val, $key, "\n");
			}
			else if(!is_array($val) && !is_object($val))
			{
				$xmlDoc .= sprintf('<%s>%s</%s>%s', $key, $val, $key, "\n");
			}
			else
			{
				$xmlDoc .= sprintf('<%s>%s%s</%s>%s', $key, "\n", $this->_makeXmlDoc($val), $key, "\n");
			}
		}

		return $xmlDoc;
	}

}
/* End of file XMLDisplayHandler.class.php */
/* Location: ./classes/display/XMLDisplayHandler.class.php */
