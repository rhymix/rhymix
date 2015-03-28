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
			if($message != 'success')
			{
				$output->message = $message;
			}

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
			if($message != 'fail')
			{
				$output->message = $message;
			}
		}

		$html = array();
		$html[] = '<script type="text/javascript">';
		$html[] = '//<![CDATA[';

		if($output->message)
		{
			$html[] = 'alert("' . $output->message . '");';
		}

		if($output->url)
		{
			$url = preg_replace('/#(.+)$/i', '', $output->url);
			$html[] = 'self.location.href = "' . $request_url . 'common/tpl/redirect.html?redirect_url=' . urlencode($url) . '";';
		}
		$html[] = '//]]>';
		$html[] = '</script>';

		return join(PHP_EOL, $html);
	}

}
/* End of file VirtualXMLDisplayHandler.class.php */
/* Location: ./classes/display/VirtualXMLDisplayHandler.class.php */
