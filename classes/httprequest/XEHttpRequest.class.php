<?php
    /**
     * @class HttpRequest
     * @author haneul (haneul@gmail.com)
     * @version 0.1
     * @brief 다른 서버에 HTTP Request를 전송하고 result를 받아오는 클래스
     * @remarks  Connection: keep-alive 는 지원하지 않음
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
	 * @brief Add (key, value) pair to the HTTP request header
	 */
	function AddToHeader($key, $value)
	{
	    $this->m_headers[$key] = $value;
	}

	/**
	 * @brief send HTTP message to the host
	 * @return (result code, response body) 
	 */
	function Send($target, $method="GET", $timeout = 3)
	{
	    $socket = @fsockopen($this->m_host, $this->m_port, $errno, $errstr, $timeout);
	    if(!$socket)
	    {
		return new Object(-1, "socket_connect_failed");
	    }

	    $this->AddToHeader('Host', $this->m_host);
	    $this->AddToHeader('Connection', "close");

	    $crlf = "\r\n";
	    $request = "$method $target HTTP/1.1$crlf"; 

	    foreach($this->m_headers as $equiv => $content)
	    {
		$request .= "$equiv: $content$crlf";
	    }
	    $request .= $crlf;
	    fwrite($socket, $request);

	    list($httpver, $code, $status) = split(' +', rtrim(fgets($socket)));
	    // read response header
	    while(strlen(trim($line = fgets($socket))))
	    {
		list($equiv, $content) = split(' *: *', rtrim($line));
	    }
	    $body =  '';
	    while(!feof($socket))
	    {
		$body .= fgets($socket, 128);
	    }
	    fclose($socket);

	    return array($code, $body);
	}
    }
?>
