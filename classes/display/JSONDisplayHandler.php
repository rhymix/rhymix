<?php

class JSONDisplayHandler {
	function toDoc(&$oModule)
	{
		$variables = $oModule->getVariables();
		$variables['error'] = $oModule->getError();
		$variables['message'] = $oModule->getMessage();
		$json = preg_replace("(\r\n|\n)",'\n',json_encode2($variables));
		return $json;
	}

	/**
	 * @brief print a HTTP HEADER for JSON, which is encoded in UTF-8
	 **/
	function printHeader() {
		header("Content-Type: text/html; charset=UTF-8");
		header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
		header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
		header("Cache-Control: no-store, no-cache, must-revalidate");
		header("Cache-Control: post-check=0, pre-check=0", false);
		header("Pragma: no-cache");
	}
}
