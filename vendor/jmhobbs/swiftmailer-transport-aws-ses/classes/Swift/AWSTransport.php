<?php
	/*
	* This file requires SwiftMailer.
	* (c) 2011 John Hobbs
	*
	* For the full copyright and license information, please view the LICENSE
	* file that was distributed with this source code.
	*/

	/**
	* Sends Messages over AWS.
	* @package Swift
	* @subpackage Transport
	* @author John Hobbs
	*/
	class Swift_AWSTransport extends Swift_Transport_AWSTransport {

		/** the service access key */
		private $AWSAccessKeyId;
		/** the service secret key */
		private $AWSSecretKey;
		/** the service endpoint */
		private $endpoint;
		/** be persistent? **/
		private $persistent;
		/**
		 * Debugging helper.
		 *
		 * If false, no debugging will be done.
		 * If true, debugging will be done with error_log.
		 * Otherwise, this should be a callable, and will recieve the debug message as the first argument.
		 *
		 * @seealso Swift_AWSTransport::setDebug()
		 */
		private $debug;
		/** the response */
		private $response;
		/** the raw socket */
		private $fp;

		/**
		* Create a new AWSTransport.
		* @param string $AWSAccessKeyId Your access key.
		* @param string $AWSSecretKey Your secret key.
		* @param boolean $debug Set to true to enable debug messages in error log.
		* @param string $endpoint The AWS endpoint to use.
		*/
		public function __construct($AWSAccessKeyId = null , $AWSSecretKey = null, $debug = false, $endpoint = 'https://email.us-east-1.amazonaws.com/', $persistent = false) {
			call_user_func_array(
				array($this, 'Swift_Transport_AWSTransport::__construct'),
				Swift_DependencyContainer::getInstance()
					->createDependenciesFor('transport.aws')
				);

			$this->AWSAccessKeyId = $AWSAccessKeyId;
			$this->AWSSecretKey = $AWSSecretKey;
			$this->endpoint = $endpoint;
			$this->debug = $debug;
			$this->persistent = $persistent;
		}

		public function __destruct() {
			if( $this->fp ) { 
				@fclose( $this->fp );
			}
		}

		/**
		* Create a new AWSTransport.
		* @param string $AWSAccessKeyId Your access key.
		* @param string $AWSSecretKey Your secret key.
		*/
		public static function newInstance( $AWSAccessKeyId , $AWSSecretKey ) {
			return new Swift_AWSTransport( $AWSAccessKeyId , $AWSSecretKey );
		}

		public function setAccessKeyId($val) {
			$this->AWSAccessKeyId = $val;
		}

		public function setSecretKey($val) {
			$this->AWSSecretKey = $val;
		}

		public function setDebug($val) {
			$this->debug = $val;
		}

		public function setEndpoint($val) {
			if( $this->fp ) {
				@fclose( $this->fp );
			}
			$this->endpoint = $val;
		}

		public function setPersistent($val) {
			if( $this->fp && ! $val ) { 
				@fclose( $this->fp );
			}
			$this->persistent = $val;
		}

		public function getResponse() {
			return $this->response;
		}

		protected function _debug ( $message ) {
			if ( true === $this->debug ) {
				error_log( $message );
			} elseif ( is_callable($this->debug) ) {
				call_user_func( $this->debug, $message );
			}
		}

		/**
		* Send the given Message.
		*
		* Recipient/sender data will be retreived from the Message API.
		* The return value is the number of recipients who were accepted for delivery.
		*
		* @param Swift_Mime_SimpleMessage $message
		* @param string[] &$failedRecipients to collect failures by-reference
		* @return int
		* @throws AWSConnectionError
		*/
		public function send( Swift_Mime_SimpleMessage $message, &$failedRecipients = null ) {

			if ($evt = $this->_eventDispatcher->createSendEvent($this, $message))
			{
				$this->_eventDispatcher->dispatchEvent($evt, 'beforeSendPerformed');
				if ($evt->bubbleCancelled())
				{
					return 0;
				}
			}

			$this->response = $this->_doSend($message, $failedRecipients);

			$this->_debug("=== Start AWS Response ===");
			$this->_debug($this->response->body);
			$this->_debug("=== End AWS Response ===");

			/** @var bool $success */
			$success = (200 == $this->response->code);
			
			if ($respEvent = $this->_eventDispatcher->createResponseEvent($this, new Swift_Response_AWSResponse( $message, $this->response->xml, $success ), $success))
				$this->_eventDispatcher->dispatchEvent($respEvent, 'responseReceived');

			if ($evt)
			{
				$evt->setResult($success ? Swift_Events_SendEvent::RESULT_SUCCESS : Swift_Events_SendEvent::RESULT_FAILED);
				$this->_eventDispatcher->dispatchEvent($evt, 'sendPerformed');
			}

			if( $success ) {
				return count((array) $message->getTo());
			}
			else {
				return 0;
			}
		}

		protected function getRawSocket () {

			if( ! $this->persistent || ! $this->fp ) {
				$host = parse_url( $this->endpoint, PHP_URL_HOST );
				$path = parse_url( $this->endpoint, PHP_URL_PATH );
				if (is_null($path)) {
					$path = '/';
				}

				$fp = fsockopen( 'ssl://' . $host, 443, $errno, $errstr, 30 );

				if( ! $fp ) {
					throw new AWSConnectionError( "$errstr ($errno)" );
				}

				if( $this->persistent ) {
					$this->fp = $fp;
				}
			}

			return ( $this->persistent ) ? $this->fp : $fp;
		}

		public function ping() {
			return true;
		}

		/**
		 * do send through the API
		 *
		 * @param Swift_Mime_SimpleMessage $message
		 * @param string[] &$failedRecipients to collect failures by-reference
		 * @return AWSResponse
		 */
		protected function _doSend( Swift_Mime_SimpleMessage $message, &$failedRecipients = null )
		{
			$date = date( 'D, j F Y H:i:s O' );
			if( function_exists( 'hash_hmac' ) and in_array( 'sha1', hash_algos() ) ) {
				$hmac = base64_encode( hash_hmac( 'sha1', $date, $this->AWSSecretKey, true ) );
			}
			else {
				$hmac = $this->calculate_RFC2104HMAC( $date, $this->AWSSecretKey );
			}
			$auth = "AWS3-HTTPS AWSAccessKeyId=" . $this->AWSAccessKeyId . ", Algorithm=HmacSHA1, Signature=" . $hmac;

			$fp = $this->getRawSocket();
			$host = parse_url( $this->endpoint, PHP_URL_HOST );
			$path = parse_url( $this->endpoint, PHP_URL_PATH );
			if (is_null($path)) {
				$path = '/';
			}

			$socket = new ChunkedTransferSocket( $fp, $host, $path, "POST", $this->persistent );

			$socket->header("Date", $date);
			$socket->header("X-Amzn-Authorization", $auth);

			$socket->write("Action=SendRawEmail&RawMessage.Data=");

			$ais = new Swift_AWSInputByteStream($socket);
			$message->toByteStream($ais);
			$ais->flushBuffers();

			$result = $socket->read();

			if( ! $this->persistent ) {
				fclose($fp);
			}

			return $result;
		}

		/**
		* Cribbed from php-aws - Thanks!
		* https://github.com/tylerhall/php-aws/blob/master/class.awis.php
		* (c) Tyler Hall
		* MIT License
		*/
		protected function calculate_RFC2104HMAC($data, $key) {
			return base64_encode (
				pack("H*", sha1((str_pad($key, 64, chr(0x00))
				^(str_repeat(chr(0x5c), 64))) .
				pack("H*", sha1((str_pad($key, 64, chr(0x00))
				^(str_repeat(chr(0x36), 64))) . $data))))
			);
		}

		public function isStarted() {}
		public function start() {}
		public function stop() {}

		/**
		 * Register a plugin.
		 *
		 * @param Swift_Events_EventListener $plugin
		 */
		public function registerPlugin(Swift_Events_EventListener $plugin)
		{
			$this->_eventDispatcher->bindEventListener($plugin);
		}

	} // AWSTransport


	/**
	 * Convenience methods to use a socket for chunked transfer in HTTP
	 */
	class ChunkedTransferSocket {

		/**
		 * @param $socket
		 * @param $host
		 * @param $path
		 * @param $method
		 * @param $persistent
		 */
		public function __construct( $socket, $host, $path, $method="POST", $persistent=false ) {

			$this->socket = $socket;
			$this->persistent = $persistent;

			$this->write_started = false;
			$this->write_finished = false;
			$this->read_started = false;
			
			fwrite( $this->socket, "$method $path HTTP/1.1\r\n" );

			$this->header( "Host", $host );
			if( "POST" == $method ) {
				$this->header( "Content-Type", "application/x-www-form-urlencoded" );
			}
			$this->header( "Connection", ( $this->persistent ) ? 'keep-alive' : 'close' );
			$this->header( "Transfer-Encoding", "chunked" );
		}

		/**
		 * Add an HTTP header
		 *
		 * @param $header
		 * @param $value
		 */
		public function header ( $header, $value ) {
			if( $this->write_started ) { throw new InvalidOperationException( "Can not write header, body writing has started." ); }
			fwrite( $this->socket, "$header: $value\r\n" );
			fflush( $this->socket );
		}

		/**
		 * Write a chunk of data
		 * @param $chunk
		 */
		public function write ( $chunk ) {
			if( $this->write_finished ) { throw new InvalidOperationException( "Can not write, reading has started." ); }

			if( ! $this->write_started ) {
				fwrite( $this->socket, "\r\n" ); // Start message body
				$this->write_started = true;
			}
	
			fwrite( $this->socket, sprintf( "%x\r\n", strlen( $chunk ) ) );
			fwrite( $this->socket, $chunk );
			fwrite( $this->socket, "\r\n" );
			fflush( $this->socket );
		}

		/**
		 * Finish writing chunks and get ready to read.
		 */
		public function finishWrite () {
			$this->write("");
			$this->write_finished = true;
		}

		/**
		 * Read the socket for a response
		 */
		public function read () {
			if( ! $this->write_finished ) { $this->finishWrite(); }
			$this->read_started = true;

			$response = new AWSResponse();
			while( ! feof( $this->socket ) ) {
				$line = fgets( $this->socket );
				if( AWSResponse::EOF == $response->line( $line ) ) {
					break;
				}
			}
			$response->complete();

			return $response;
		}

	}

	/**
	 * A wrapper to parse an AWS HTTP response
	 */
	class AWSResponse {

		/**
		 * @var array
		 */
		public $headers = array();

		/**
		 * @var int
		 */
		public $code = 0;

		/**
		 * @var string
		 */
		public $message = '';

		/**
		 * @var string
		 */
		public $body = '';

		/**
		 * @var null|SimpleXMLElement
		 */
		public $xml = null;
		public $content_length;

		const STATE_EMPTY = 0;
		const STATE_HEADERS = 1;
		const STATE_BODY = 2;

		const EOF = 'EOF';

		protected $state = self::STATE_EMPTY;
		protected $content_length_received = 0;

		public function line ( $line ) {

			switch( $this->state ) {
				case self::STATE_EMPTY:
					if( ! $line ) {
						throw new AWSEmptyResponseException();
					}
					$split = explode( ' ', $line );
					$this->code = $split[1];
					$this->message = implode( ' ', array_slice( $split, 2 ) );
					$this->state = self::STATE_HEADERS;
					break;
				case self::STATE_HEADERS:
					if( "\r\n" == $line ) {
						$this->state = self::STATE_BODY;
						break;
					}

					$pos = strpos( $line, ':' );
					if( false === $pos ) { throw new InvalidHeaderException( $line ); }
					$key = substr( $line, 0, $pos );
					$this->headers[$key] = substr( $line, $pos + 2, strlen($line) - $pos - 4 );

					if( strtolower($key) == 'content-length' ) {
						$this->content_length = intval($this->headers[$key]);
					}

					break;
				case self::STATE_BODY:
					$this->content_length_received += strlen($line);
					$this->body .= $line;
					if( isset( $this->content_length ) and $this->content_length_received >= $this->content_length ) {
						return self::EOF;
					}
					break;
			}

		}

		public function complete () {
			$this->xml = simplexml_load_string( $this->body );
		}

	}

	class AWSConnectionError extends Exception {}
	class InvalidOperationException extends Exception {}
	class InvalidHeaderException extends Exception {}
	class AWSEmptyResponseException extends Exception {}
