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
	function AddToHeader($key, $value)
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

	    $ret->result_code = $code;
	    $ret->body = $body;

	    return $ret;
	}
    }
?>
