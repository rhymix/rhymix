<?php

class VirtualXMLDisplayHandler {
	/**
	 * @brief Produce virtualXML compliant content given a module object.\n
	 * @param[in] $oModule the module object
	 **/

	function toDoc(&$oModule)
	{
		$error = $oModule->getError();
		$message = $oModule->getMessage();
		$redirect_url = $oModule->get('redirect_url');
		$request_uri = Context::get('xeRequestURI');
		$request_url = Context::get('xeVirtualRequestUrl');
		if(substr($request_url,-1)!='/') $request_url .= '/';

		if($error === 0) {
			if($message != 'success') $output->message = $message;
			if($redirect_url) $output->url = $redirect_url;
			else $output->url = $request_uri;
		} else {
			if($message != 'fail') $output->message = $message;
		}

		$html = '<script type="text/javascript">'."\n";
		if($output->message) $html .= 'alert("'.$output->message.'");'."\n";
		if($output->url) {
			$url = preg_replace('/#(.+)$/i','',$output->url);
			$html .= 'self.location.href = "'.$request_url.'common/tpl/redirect.html?redirect_url='.urlencode($url).'";'."\n";
		}
		$html .= '</script>'."\n";
		return $html;
	}
}

?>
