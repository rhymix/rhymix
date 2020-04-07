<?php

class Swift_Response_AWSResponse {
	/**
	 * @var Swift_Mime_SimpleMessage
	 */
	protected $message;

	/**
	 * @var null|SimpleXMLElement
	 */
	protected $body;

	/**
	 * @var bool
	 */
	protected $success;

	/**
	 * Swift_Response_AWSResponse constructor.
	 *
	 * @param Swift_Mime_SimpleMessage $message
	 * @param null $body
	 * @param bool $success
	 */
	public function __construct( Swift_Mime_SimpleMessage $message, $body = null, $success = false )
	{
		$this->message = $message;
		$this->body = $body;
		$this->success = $success;
	}

	/**
	 * @return string
	 */
	function __toString()
    	{
		if(!$this->getBody())
			return "No response body available.";

		//success
		if($this->getBody()->ResponseMetadata)
			return "Success! RequestId: " . $this->getBody()->ResponseMetadata->RequestId;

		//failure
		if($this->getBody()->Error && $this->getBody()->Error->Message)
			return (string) $this->getBody()->Error->Message;

		return "Unknown Response";
    	}
	
	/**
	 * @return Swift_Mime_SimpleMessage
	 */
	function getMessage()
	{
		return $this->message;
	}

	/**
	 * @return null|SimpleXMLElement
	 */
	function getBody()
	{
		return $this->body;
	}

	/**
	 * @return bool
	 */
	public function isSuccess()
	{
		return $this->success;
	}

	/**
	 * @param $message
	 *
	 * @return $this
	 */
	function setMessage( $message )
	{
		$this->message = $message;
		return $this;
	}

	/**
	 * @param $body
	 *
	 * @return $this
	 */
	function setBody( $body )
	{
		$this->body = $body;
		return $this;
	}

	/**
	 * @param bool $success
	 *
	 * @return Swift_Response_AWSResponse
	 */
	public function setSuccess( $success )
	{
		$this->success = $success;
		return $this;
	}


}
