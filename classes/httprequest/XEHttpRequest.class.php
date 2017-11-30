<?php
/* Copyright (C) NAVER <http://www.navercorp.com> */

/**
 * - HttpRequest class
 * - a class that is designed to be used for sending out HTTP request to an external server and retrieving response
 * - Connection: keep-alive is not supported
 * @author NAVER (developers@xpressengine.com)
 * @package /classes/httprequest
 * @version 0.1
 * @deprecated
 */
class XEHttpRequest
{

	/**
	 * target host
	 * @var string
	 */
	var $m_host;

	/**
	 * target Port
	 * @var int
	 */
	var $m_port;

	/**
	 * target scheme 
	 * @var string
	 */
	var $m_scheme;

	/**
	 * target header
	 * @var array
	 */
	var $m_headers;

	/**
	 * constructor
	 * @return void
	 */
	function __construct($host, $port, $scheme='')
	{
		$this->m_host = $host;
		$this->m_port = $port;
		$this->m_scheme = $scheme;
		$this->m_headers = array();
	}

	/**
	 * Mether to add key/value pair to the HTTP request header
	 * @param int|string $key HTTP header element
	 * @param string $value value string for HTTP header element
	 * @return void
	 */
	function addToHeader($key, $value)
	{
		$this->m_headers[$key] = $value;
	}

	/**
	 * Send HTTP message to the host
	 * @param string $target ip or url of the external server
	 * @param string $method HTTP method such as GET and POST
	 * @param int $timeout time out value for HTTP request expiration
	 * @param array $post_vars variables to send
	 * @return object Returns an object containing HTTP Response body and HTTP response code
	 */
	function send($target = '/', $method = 'GET', $timeout = 3, $post_vars = NULL)
	{
		static $allow_methods = NULL;

		$this->addToHeader('Host', $this->m_host);
		$this->addToHeader('Connection', 'close');

		$method = strtoupper($method);
		if(!$allow_methods)
		{
			$allow_methods = explode(' ', 'GET POST PUT');
		}
		if(!in_array($method, $allow_methods))
		{
			$method = $allow_methods[0];
		}

		// $timeout should be an integer that is bigger than zero
		$timout = max((int) $timeout, 0);

		// list of post variables
		if(!is_array($post_vars))
		{
			$post_vars = array();
		}

		if(FALSE && is_callable('curl_init'))
		{
			return $this->sendWithCurl($target, $method, $timeout, $post_vars);
		}
		else
		{
			return $this->sendWithSock($target, $method, $timeout, $post_vars);
		}
	}

	/**
	 * Send a request with the file socket
	 * @param string $target ip or url of the external server
	 * @param string $method HTTP method such as GET and POST
	 * @param int $timeout time out value for HTTP request expiration
	 * @param array $post_vars variables to send
	 * @return object Returns an object containing HTTP Response body and HTTP response code
	 */
	function sendWithSock($target, $method, $timeout, $post_vars)
	{
		static $crlf = "\r\n";

		$scheme = '';
		if($this->m_scheme=='https')
		{
			$scheme = 'ssl://';
		}

		$sock = @fsockopen($scheme . $this->m_host, $this->m_port, $errno, $errstr, $timeout);
		if(!$sock)
		{
			return new BaseObject(-1, 'socket_connect_failed');
		}

		$headers = $this->m_headers + array();
		if(!isset($headers['Accept-Encoding']))
		{
			$headers['Accept-Encoding'] = 'identity';
		}

		// post body
		$post_body = '';
		if($method == 'POST' && count($post_vars))
		{
			foreach($post_vars as $key => $value)
			{
				$post_body .= urlencode($key) . '=' . urlencode($value) . '&';
			}
			$post_body = substr($post_body, 0, -1);

			$headers['Content-Length'] = strlen($post_body);
			$headers['Content-Type'] = 'application/x-www-form-urlencoded';
		}

		$request = "$method $target HTTP/1.1$crlf";
		foreach($headers as $equiv => $content)
		{
			$request .= "$equiv: $content$crlf";
		}
		$request .= $crlf . $post_body;
		fwrite($sock, $request);

		list($httpver, $code, $status) = preg_split('/ +/', rtrim(fgets($sock)), 3);

		// read response headers
		$is_chunked = FALSE;
		while(strlen(trim($line = fgets($sock))))
		{
			list($equiv, $content) = preg_split('/ *: */', rtrim($line), 2);
			if(!strcasecmp($equiv, 'Transfer-Encoding') && $content == 'chunked')
			{
				$is_chunked = TRUE;
			}
		}

		$body = '';
		while(!feof($sock))
		{
			if($is_chunked)
			{
				$chunk_size = hexdec(fgets($sock));
				if($chunk_size)
				{
					$body .= fgets($sock, $chunk_size+1);
				}
			}
			else
			{
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
	 * Send a request with the curl library
	 * @param string $target ip or url of the external server
	 * @param string $method HTTP method such as GET and POST
	 * @param int $timeout time out value for HTTP request expiration
	 * @param array $post_vars variables to send
	 * @return object Returns an object containing HTTP Response body and HTTP response code
	 */
	function sendWithCurl($target, $method, $timeout, $post_vars)
	{
		$headers = $this->m_headers + array();

		// creat a new cURL resource
		$ch = curl_init();

		$headers['Expect'] = '';

		// set URL and other appropriate options
		curl_setopt($ch, CURLOPT_URL, "http://{$this->m_host}{$target}");
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_PORT, $this->m_port);
		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
		curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

		switch($method)
		{
			case 'GET': curl_setopt($ch, CURLOPT_HTTPGET, true);
				break;
			case 'PUT': curl_setopt($ch, CURLOPT_PUT, true);
				break;
			case 'POST':
				curl_setopt($ch, CURLOPT_POST, true);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $post_vars);
				break;
		}

		$arr_headers = array();
		foreach($headers as $key => $value)
		{
			$arr_headers[] = "$key: $value";
		}

		curl_setopt($ch, CURLOPT_HTTPHEADER, $arr_headers);

		$body = curl_exec($ch);
		if(curl_errno($ch))
		{
			return new BaseObject(-1, 'socket_connect_failed');
		}

		$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		$ret = new stdClass;
		$ret->result_code = $code;
		$ret->body = $body;

		return $ret;
	}

}
/* End of file XEHttpRequest.class.php */
/* Location: ./classes/httprequest/XEHttpRequest.class.php */
