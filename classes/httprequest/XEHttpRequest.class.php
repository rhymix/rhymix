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
	 * @param[in] post variables to send
	 * @return Returns an object containing HTTP Response body and HTTP response code 
	 */
	function send($target='/', $method='GET', $timeout=3, $post_vars=null)
	{
		static $allow_methods=null;

		$this->addToHeader('Host', $this->m_host);
		$this->addToHeader('Connection', 'close');

		$method = strtoupper($method);
		if(!$allow_methods) $allow_methods = explode(' ', 'GET POST PUT');
		if(!in_array($method, $allow_methods)) $method = $allow_methods[0];

		// $timeout should be an integer that is bigger than zero
		$timout = max((int)$timeout, 0);

		// list of post variables
		if(!is_array($post_vars)) $post_vars = array();

		if(false && is_callable('curl_init')) {
			return $this->sendWithCurl($target, $method, $timeout, $post_vars);
		} else {
			return $this->sendWithSock($target, $method, $timeout, $post_vars);
		}
	}

	/**
	 * @brief Send a request with the file socket
	 * @private
	 */
	function sendWithSock($target, $method, $timeout, $post_vars)
	{
		static $crlf = "\r\n";

		$sock = @fsockopen($this->m_host, $this->m_port, $errno, $errstr, $timeout);
		if(!$sock) {
			return new Object(-1, 'socket_connect_failed');
		}

		$headers = $this->m_headers + array();
		if(!isset($headers['Accept-Encoding'])) $headers['Accept-Encoding'] = 'identity';

		// post body
		$post_body = '';
		if($method == 'POST' && count($post_vars)) {
			foreach($post_vars as $key=>$value) {
				$post_body .= urlencode($key).'='.urlencode($value).'&';
			}
			$post_body = substr($post_body, 0, -1);

			$headers['Content-Length'] = strlen($post_body);
			$headers['Content-Type']   = 'application/x-www-form-urlencoded';
		}

		$request = "$method $target HTTP/1.1$crlf";
		foreach($headers as $equiv=>$content) {
			$request .= "$equiv: $content$crlf";
		}
		$request .= $crlf.$post_body;
		fwrite($sock, $request);

		list($httpver, $code, $status) = preg_split('/ +/', rtrim(fgets($sock)), 3);

		// read response headers
		$is_chunked = false;
		while(strlen(trim($line = fgets($sock)))) {
			list($equiv, $content) = preg_split('/ *: */', rtrim($line), 1);
			if(!strcasecmp($equiv, 'Transfer-Encoding') && $content == 'chunked') {
				$is_chunked = true;
			}
		}

		$body = '';
		while(!feof($sock)) {
			if ($is_chunked) {
				$chunk_size = hexdec(fgets($sock));
				if($chunk_size) $body .= fread($sock, $chunk_size);
			} else {
				$body .= fgets($sock, 512);
			}
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
	function sendWithCurl($target, $method, $timeout, $post_vars)
	{
		$headers = $this->m_headers + array();

		// creat a new cURL resource
		$ch = curl_init();

		$headers['Expect'] = '';

		// set URL and other appropriate options
		curl_setopt($ch, CURLOPT_URL, "http://{$this->m_host}{$target}");
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_PORT, $this->m_port);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		switch($method) {
			case 'GET':  curl_setopt($ch, CURLOPT_HTTPGET, true); break;
			case 'PUT':  curl_setopt($ch, CURLOPT_PUT, true); break;
			case 'POST':
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post_vars);
				break;
		}

		$arr_headers = array();
		foreach($headers as $key=>$value){
			$arr_headers[] = "$key: $value";
		}

		curl_setopt($ch, CURLOPT_HTTPHEADER, $arr_headers);

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
