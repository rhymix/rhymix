<?php

	class Swift_AWSInputByteStream implements Swift_InputByteStream {

		public function __construct( $socket ) {
			$this->socket = $socket;
			$this->buffer = '';
			$this->counter = 0;
		}

		/**
		 * Writes $bytes to the end of the stream.
		 *
		 * Writing may not happen immediately if the stream chooses to buffer.  If
		 * you want to write these bytes with immediate effect, call {@link commit()}
		 * after calling write().
		 *
		 * This method returns the sequence ID of the write (i.e. 1 for first, 2 for
		 * second, etc etc).
		 *
		 * @param string $bytes
		 * @return int
		 */
		public function write($bytes) {

			$block = $this->buffer . $bytes;
			$block_size = strlen( $block );
			$encoded = base64_encode( $block );

			$setback = 0;
			while( substr( $encoded, -1 ) === '=' ) {
				++$setback;
				if( $setback >= $block_size ) {
					$this->buffer = $block; 
					return ++$this->counter;
				}
				$encoded = base64_encode( substr( $block, 0, $setback * -1 ) );
			}

			if( $setback > 0 ) { 
				$this->buffer = substr( $block, $setback * -1 );
			}
			else {
				$this->buffer = '';
			}

			unset( $block );

			$this->socket->write( urlencode( $encoded ) );

			unset( $encoded );

			return ++$this->counter;
		}

		/**
		 * For any bytes that are currently buffered inside the stream, force them
		 * off the buffer.
		 */
		public function commit() {
			// NOP - Since we have a required packet offset (3-bytes), we can't commit arbitrarily.
		}

		public function flushBuffers() {
			if( strlen( $this->buffer ) > 0 ) {
				$this->socket->write( urlencode( base64_encode( $this->buffer ) ) );
			}
			$this->socket->finishWrite();
		}

		/**
		 * Attach $is to this stream.
		 * The stream acts as an observer, receiving all data that is written.
		 * All {@link write()} and {@link flushBuffers()} operations will be mirrored.
		 *
		 * @param Swift_InputByteStream $is
		 */
		public function bind(Swift_InputByteStream $is){}

		/**
		 * Remove an already bound stream.
		 * If $is is not bound, no errors will be raised.
		 * If the stream currently has any buffered data it will be written to $is
		 * before unbinding occurs.
		 *
		 * @param Swift_InputByteStream $is
		 */
		public function unbind(Swift_InputByteStream $is){}

}
