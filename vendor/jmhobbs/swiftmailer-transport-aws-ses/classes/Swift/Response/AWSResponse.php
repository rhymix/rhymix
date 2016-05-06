<?php

class Swift_Response_AWSResponse {
	
	protected $message;
	
	protected $body;
	
	public function __construct( Swift_Mime_Message $message, $body = null )
	{
		$this->message = $message;
		$this->body = $body;
	}
	
	function getMessage()
	{
		return $this->message;
	}

	function getBody()
	{
		return $this->body;
	}

	function setMessage( $message )
	{
		$this->message = $message;
		return $this;
	}

	function setBody( $body )
	{
		$this->body = $body;
		return $this;
	}
}
