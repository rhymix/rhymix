<?php
/**
 * @class HttpRequest
 * @author NHN (developers@xpressengine.com)
 * @version 0.1
 * @brief a class that is designed to be used for sending out HTTP request to an external server and retrieving response
 * @remarks  Connection: keep-alive is not supported
 */
class XEHttpRequest {
	/// target host
	var $m_host;
	/// target Port
	var $m_port;
	/// header array 
	var $m_headers;

	/**
	 * @brief Constructor 
	 */
	function XEHttpRequest($host, $port)
	{
	    $this->m_host = $host;
	    $this->m_port = $port;
	    $this->m_headers = array();
	}

	/**
	 * @brief mether to add key/value pair to the HTTP request header
     * @param[in] key HTTP header element
     * @param[in] value value string for HTTP header element
	 */
	function addToHeader($key, $value)
	{
	    $this->m_headers[$key] = $value;
	}

	/**
	 * @brief send HTTP message to the host
     * @param[in] target ip or url of the external server
     * @param[in] method HTTP method such as GET and POST
     * @param[in] timeout time out value for HTTP request expiration
	 * @return Returns an object containing HTTP Response body and HTTP response code 
	 */
	function send($target='/', $method='GET', $timeout=3)
	{
		static $allow_methods=null;

		$this->addToHeader('Host', $this->m_host);
		$this->addToHeader('Connection', 'close');

		$method = strtoupper($method);
		if(!$allow_methods) $allow_methods = explode(' ', 'GET POST PUT DELETE');
		if(!in_array($method, $allow_methods)) $method = $allow_methods[0];

		// $timeout should be an integer that is bigger than zero
		$timout = max((int)$timeout, 0);

		if(is_callable('curl_init')) {
			return $this->sendWithCurl($target, $method, $timeout);
		} else {
			return $this->sendWithSock($target, $method, $timeout);
		}
	}

	/**
	 * @brief Send a request with the file socket
	 * @private
	 */
	function sendWithSock($target, $method, $timeout)
	{
		static $crlf = "\r\n";

		$sock = @fsockopen($this->m_host, $this->m_port, $errno, $errstr, $timeout);
		if(!$sock) {
			return new Object(-1, 'socket_connect_failed');
		}

		$request = "$method $target HTTP/1.1$crlf";

		foreach($this->m_headers as $equiv=>$content) {
			$request .= "$equiv: $content$crlf";
		}
		$request .= $crlf;
		fwrite($sock, $request);

		list($httpver, $code, $status) = split(' +', rtrim(fgets($sock)));

		// read response headers
		while(strlen(trim($line = fgets($sock)))) {
			list($equiv, $content) = split(' *: *', rtrim($line));
		}

		$body = '';
		while(!feof($sock)) {
			$body .= fgets($sock, 512);
		}
		fclose($sock);

		$ret = new stdClass;
		$ret->result_code = $code;
		$ret->body = $body;

		return $ret;
	}

	/**
	 * @brief Send a request with the curl library
	 * @private
	 */
	function sendWithCurl($target, $method, $timeout)
	{
		// creat a new cURL resource
		$ch = curl_init();

		// set URL and other appropriate options
		curl_setopt($ch, CURLOPT_URL, "http://{$this->m_host}{$target}");
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_PORT, $this->m_port);
		curl_setopt($ch, CURLOPT_CONNECTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		switch($method) {
			case 'GET':  curl_setopt($ch, CURLOPT_HTTPGET, true); break;
			case 'POST': curl_setopt($ch, CURLOPT_POST, true); break;
			case 'PUT':  curl_setopt($ch, CURLOPT_PUT, true); break;
		}

		$body = curl_exec($ch);
		if(curl_errno($ch)) {
			return new Object(-1, 'socket_connect_failed');
		}

		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		$ret = new stdClass;
		$ret->result_code = $code;
		$ret->body = $body;

		return $ret;
	}
}
?>
