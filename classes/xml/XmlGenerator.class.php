<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * XmlGenerator class
 * @author NAVER (developers@xpressengine.com)
 * @package /classes/xml
 * @version 0.1
 */
class XmlGenerator
{

	/**
	 * object change to xml
	 * @param object $xml
	 * @return string
	 */
	function obj2xml($xml)
	{
		$buff = "<?xml version=\"1.0\" encoding=\"UTF-8\" ?>\n";

		foreach($xml as $nodeName => $nodeItem)
		{
			$buff .= $this->_makexml($nodeItem);
		}
		return $buff;
	}

	/**
	 * object change to xml
	 * @param object $node node in xml object
	 * @return string
	 */
	function _makexml($node)
	{
		$body = '';
		foreach($node as $key => $value)
		{
			switch($key)
			{
				case 'node_name' : break;
				case 'attrs' : {
						$attrs = '';
						if(isset($value))
						{
							foreach($value as $attrName => $attrValue)
							{
								$attrs .= sprintf(' %s="%s"', $attrName, htmlspecialchars($attrValue, ENT_COMPAT | ENT_HTML401, 'UTF-8', false));
							}
						}
					}
					break;
				case 'body' :
					$body = $value;
					break;
				default : {
						if(is_array($value))
						{
							foreach($value as $idx => $arrNode)
							{
								$body .= $this->_makexml($arrNode);
							}
						}
						else if(is_object($value))
						{
							$body = $this->_makexml($value);
						}
					}
			}
		}
		return sprintf('<%s%s>%s</%s>' . "\n", $node->node_name, $attrs, $body, $node->node_name);
	}

}
/* End of file XmlGenerator.class.php */
/* Location: ./classes/xml/XmlGenerator.class.php */
