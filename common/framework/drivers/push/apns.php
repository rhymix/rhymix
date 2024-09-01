<?php

namespace Rhymix\Framework\Drivers\Push;

use Rhymix\Framework\Push;
use Rhymix\Framework\Drivers\PushInterface;

/**
 * The APNs (Apple) Push driver.
 */
class APNs extends Base implements PushInterface
{
	/**
	 * Config keys used by this driver are stored here.
	 */
	protected static $_required_config = array('certificate', 'passphrase');
	protected static $_optional_config = array();

	/**
	 * Get the human-readable name of this Push driver.
	 *
	 * @return string
	 */
	public static function getName(): string
	{
		return 'APNs';
	}

	/**
	 * Check if the current Push driver is supported on this server.
	 *
	 * This method returns true on success and false on failure.
	 *
	 * @return bool
	 */
	public static function isSupported(): bool
	{
		return true;
	}

	/**
	 * Send a message.
	 *
	 * This method returns true on success and false on failure.
	 *
	 * @param object $message
	 * @param array $tokens
	 * @return \stdClass
	 */
	public function send(Push $message, array $tokens): \stdClass
	{
		$output = new \stdClass;
		$output->success = [];
		$output->invalid = [];
		$output->needUpdate = [];

		// Set parameters
		$local_cert = $this->_config['certificate'];
		$passphrase = $this->_config['passphrase'];
		$metadata = $message->getMetadata();

		$payload = json_encode([
			'aps' => [
				'alert' => [
					'title' => $message->getSubject(),
					'body' => $message->getContent(),
				],
				'sound' => isset($metadata['sound']) ? $metadata['sound'] : 'default',
			],
		]);

		foreach($tokens as $row)
		{
			$ctx = stream_context_create();
			stream_context_set_option($ctx, 'ssl', 'local_cert', $local_cert);
			stream_context_set_option($ctx, 'ssl', 'passphrase', $passphrase);

			$fp = stream_socket_client('ssl://gateway.push.apple.com:2195', $err, $errstr, 5, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
			if(!$fp)
			{
				$message->addError('Failed to connect socket - error code: '. $err .' - '. $errstr);
			}
			$msg = chr(0) . pack('n', 32) . pack('H*', $row->token) . pack('n', strlen($payload)) . $payload;
			$result = fwrite($fp, $msg, strlen($msg));
			if(!$result)
			{
				$message->addError('APNs return empty response.');
			}
			$output->success[] = $row->token;
			fclose($fp);
		}

		return $output;
	}
}
