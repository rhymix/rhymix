<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

class VirtualXMLDisplayHandler
{
	/**
	 * Produce virtualXML compliant content given a module object.\n
	 * @param ModuleObject $oModule the module object
	 * @return string
	 */
	function toDoc(&$oModule)
	{
		$error = $oModule->getError();
		$message = $oModule->getMessage();
		$redirect_url = $oModule->get('redirect_url');
		$request_uri = Context::get('xeRequestURI');
		$request_url = Context::getRequestUri();
		$output = new stdClass();

		if(substr_compare($request_url, '/', -1) !== 0)
		{
			$request_url .= '/';
		}

		if($error === 0)
		{
			if($redirect_url)
			{
				$output->url = $redirect_url;
			}
			else
			{
				$output->url = $request_uri;
			}
		}
		else
		{
			$output->message = $message;
		}

		$html = array();
		$html[] = '<html>';
		$html[] = '<head>';
		$html[] = '<script>';

		if($output->message)
		{
			$html[] = 'alert(' . json_encode($output->message) . ');';
		}

		if($output->url)
		{
			$output->url = preg_replace('/#(.+)$/', '', $output->url);
			$html[] = 'if (opener) {';
			$html[] = '  opener.location.href = ' . json_encode($output->url) . ';';
			$html[] = '} else {';
			$html[] = '  parent.location.href = ' . json_encode($output->url) . ';';
			$html[] = '}';
		}
		
		$html[] = '</script>';
		$html[] = '</head><body></body></html>';
		
		return join("\n", $html);
	}

}
/* End of file VirtualXMLDisplayHandler.class.php */
/* Location: ./classes/display/VirtualXMLDisplayHandler.class.php */
