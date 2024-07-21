<?php

namespace Rhymix\Framework\Drivers\SMS;

use Rhymix\Framework\Storage;

/**
 * The NAVER Cloud SENS SMS driver.
 */
class Ncloud_Sens extends Base implements \Rhymix\Framework\Drivers\SMSInterface
{
	/**
	 * API endpoint URL
	 */
	const API_HOST = 'https://sens.apigw.ntruss.com';
	const API_PATH = '/sms/v2/services/%s/%s';
	const TIMEOUT = 10;

	/**
	 * API specifications.
	 */
	protected static $_spec = array(
		'max_recipients'              => 100,
		'sms_max_length'              => 90,
		'sms_max_length_in_charset'   => 'CP949',
		'lms_supported'               => true,
		'lms_supported_country_codes' => array(82),
		'lms_max_length'              => 2000,
		'lms_max_length_in_charset'   => 'CP949',
		'lms_subject_supported'       => true,
		'lms_subject_max_length'      => 40,
		'mms_supported'               => true,
		'mms_supported_country_codes' => array(82),
		'mms_max_length'              => 2000,
		'mms_max_length_in_charset'   => 'CP949',
		'mms_subject_supported'       => true,
		'mms_subject_max_length'      => 40,
		'image_allowed_types'         => array('jpg'),
		'image_max_dimensions'        => array(1500, 1440),
		'image_max_filesize'          => 300000,
		'delay_supported'             => true,
	);

	/**
	 * Config keys used by this driver are stored here.
	 */
	protected static $_required_config = array('service_id', 'api_key', 'api_secret');
	protected static $_optional_config = array();

	/**
	 * Get the human-readable name of this SMS driver.
	 *
	 * @return string
	 */
	public static function getName()
	{
		return 'NAVER Cloud SENS';
	}

	/**
	 * Check if the current SMS driver is supported on this server.
	 *
	 * This method returns true on success and false on failure.
	 *
	 * @return bool
	 */
	public static function isSupported()
	{
		return true;
	}

	/**
	 * Create a signature for NAVER Cloud gateway server.
	 *
	 * @param string $method
	 * @param string $uri
	 * @param string $timestamp
	 * @param string $access_key
	 * @param string $secret_key
	 * @return string
	 */
	protected static function _makeSignature($method, $uri, $timestamp, $access_key, $secret_key): string
	{
		$content = "$method $uri\n$timestamp\n$access_key";
		return base64_encode(hash_hmac('sha256', $content, $secret_key, true));
	}

	/**
	 * Upload an attachment and get the file ID.
	 *
	 * @param string $filename
	 * @param string $path
	 * @return ?string
	 */
	protected function _uploadFile(string $filename, string $path): ?string
	{
		// Return null if the file cannot be read.
		if (!Storage::exists($path) || !Storage::isReadable($path) || !Storage::isFile($path))
		{
			return null;
		}

		// Generate the NAVER cloud gateway signature.
		$timestamp = floor(microtime(true) * 1000);
		$uri = sprintf(self::API_PATH, $this->_config['service_id'], 'files');
		$signature = self::_makeSignature('POST', $uri, $timestamp, $this->_config['api_key'], $this->_config['api_secret']);
		$headers = array(
			'x-ncp-apigw-timestamp' => $timestamp,
			'x-ncp-iam-access-key' => $this->_config['api_key'],
			'x-ncp-apigw-signature-v2' => $signature,
		);

		// Send the API request.
		try
		{
			$url = self::API_HOST . sprintf(self::API_PATH, $this->_config['service_id'], 'files');
			$request = \Rhymix\Framework\HTTP::post($url, [], $headers, [], [
				'timeout' => self::TIMEOUT,
				'json' => [
					'fileName' => $filename,
					'fileBody' => base64_encode(file_get_contents($path)),
				],
			]);
			$result = @json_decode($request->getBody()->getContents());
			if (!empty($result->fileId))
			{
				return $result->fileId;
			}
			else
			{
				return null;
			}
		}
		catch (\Exception $e)
		{
			return null;
		}
	}

	/**
	 * Send a message.
	 *
	 * This method returns true on success and false on failure.
	 *
	 * @param array $messages
	 * @param object $original
	 * @return bool
	 */
	public function send(array $messages, \Rhymix\Framework\SMS $original)
	{
		$status = true;
		$file_ids = [];

		foreach ($messages as $i => $message)
		{
			// Basic information
			$data = array();
			$data['type'] = $message->type;
			$data['from'] = str_replace('-', '', \Rhymix\Framework\Korea::formatPhoneNumber($message->from));
			if ($message->country && $message->country != 82)
			{
				$data['countryCode'] = $message->country;
			}

			// Subject and content
			if ($message->type !== 'SMS' && $message->subject)
			{
				$data['subject'] = $message->subject;
			}
			$data['content'] = $message->content;

			// Recipients
			foreach ($message->to as $num)
			{
				$data['messages'][] = [
					'to' => str_replace('-', '', \Rhymix\Framework\Korea::formatPhoneNumber($num)),
				];
			}

			// Image attachment
			if (!empty($message->image))
			{
				$path = realpath($message->image);
				if (!isset($file_ids[$path]))
				{
					$file_ids[$path] = $this->_uploadFile('image.jpg', $path);
				}
				if (isset($file_ids[$path]))
				{
					$data['files'][] = ['fileId' => $file_ids[$path]];
				}
			}

			// Set delay
			if ($message->delay && $message->delay > time() + 900)
			{
				$data['reserveTime'] = gmdate('Y-m-d H:i', $message->delay + (3600 * 9));
			}

			// Generate the NAVER cloud gateway signature.
			$timestamp = floor(microtime(true) * 1000);
			$uri = sprintf(self::API_PATH, $this->_config['service_id'], 'messages');
			$signature = self::_makeSignature('POST', $uri, $timestamp, $this->_config['api_key'], $this->_config['api_secret']);
				$headers = array(
				'x-ncp-apigw-timestamp' => $timestamp,
				'x-ncp-iam-access-key' => $this->_config['api_key'],
				'x-ncp-apigw-signature-v2' => $signature,
			);

			// Send the API request.
			try
			{
				$url = self::API_HOST . sprintf(self::API_PATH, $this->_config['service_id'], 'messages');
				$request = \Rhymix\Framework\HTTP::post($url, [], $headers, [], [
					'timeout' => self::TIMEOUT,
					'json' => $data,
				]);
				$status_code = $request->getStatusCode();
				$response = $request->getBody()->getContents();
				$result = $response ? @json_decode($response) : null;
				if (isset($result->statusName))
				{
					if ($result->statusName != 'success')
					{
						$original->addError(trim('NAVER Cloud SENS: ' . $result->statusCode . ' ' . $result->statusName));
						$status = false;
					}
				}
				else
				{
					$original->addError(trim('NAVER Cloud SENS: ' . $status_code . ' ' . ($result->errorMessage ?? $response)));
					$status = false;
				}
			}
			catch (\Exception $e)
			{
				$original->addError('NAVER Cloud SENS: ' . $e->getMessage());
				$status = false;
			}
		}

		return $status;
	}
}
