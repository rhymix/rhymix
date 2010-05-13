<?php
require_once 'HTTP/Request2.php';

class HTTP_Request extends HTTP_Request2
{
	private $reponse = null;

	public function addHeader($name, $value)
	{
		$this->setHeader($name, $value);
	}

	public function sendRequest($saveBody = true)
	{
		$response = $this->send();
		$this->response = $response;
		return $response;
	}

	public function getResponseCode() {
		if($this->response)
		{
			return $this->response->getStatus();
		}
	}

	public function getResponseHeader() {
		if($this->response)
		{
			return $this->response->getHeader();	
		}
	}

	public function getResponseBody() {
		if($this->response)
		{
			return $this->response->getBody();
		}
	}

	public function getResponseCookies() {
		if($this->response)
		{
			return $this->response->getCookies();
		}
	}

	public function addPostData($name, $value, $preencoded = false)
	{
		$this->addPostParameter($name, $value);
	}

}

?>
