<?php

namespace Rhymix\Framework\Drivers\Push;

use Rhymix\Framework\HTTP;
use Rhymix\Framework\Push;
use Rhymix\Framework\Storage;
use Rhymix\Framework\Drivers\PushInterface;
use Rhymix\Framework\Helpers\CacheItemPoolHelper;
use Google\Auth\CredentialsLoader;
use Google\Auth\FetchAuthTokenCache;
use Google\Auth\Middleware\AuthTokenMiddleware;
use GuzzleHttp\HandlerStack;

/**
 * The FCM HTTP v1 API Push driver.
 */
class FCMv1 extends Base implements PushInterface
{
	/**
	 * Default settings.
	 */
	const API_URL = 'https://fcm.googleapis.com/v1/projects/%s/messages:send';
	const BASE_URL = 'https://www.googleapis.com';
	const SCOPES = ['https://www.googleapis.com/auth/firebase.messaging'];
	const CHUNK_SIZE = 10;

	/**
	 * Config keys used by this driver are stored here.
	 */
	protected static $_required_config = array('service_account');
	protected static $_optional_config = array();

	/**
	 * Get the human-readable name of this Push driver.
	 *
	 * @return string
	 */
	public static function getName(): string
	{
		return 'FCM HTTP v1 API';
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

		// Configure Google OAuth2 access token, with appropriate caching.
		$service_account = Storage::read($this->_config['service_account'] ?? '');
		$service_account = json_decode($service_account, true);
		if (!$service_account || empty($service_account['project_id']))
		{
			$message->addError('FCM error: service account JSON file cannot be decoded');
			return $output;
		}
		$creds = CredentialsLoader::makeCredentials(self::SCOPES, $service_account);
		$cache_helper = new CacheItemPoolHelper(true);
		$cache_creds = new FetchAuthTokenCache($creds, [], $cache_helper);

		// Configure Guzzle middleware.
		$middleware = new AuthTokenMiddleware($cache_creds);
		$stack = HandlerStack::create();
		$stack->push($middleware);

		// Compose common parts of the payload, leaving the token empty.
		$api_url = sprintf(self::API_URL, $service_account['project_id']);
		$payload = ['message' => []];
		$title = $message->getSubject();
		$body = $message->getContent();
		$image = $message->getImage();
		if ($title !== '')
		{
			$payload['message']['notification']['title'] = $title;
		}
		if ($body !== '')
		{
			$payload['message']['notification']['body'] = $body;
		}
		if ($image !== '')
		{
			$payload['message']['notification']['image'] = $image;
		}

		$metadata = $message->getMetadata();
		if (count($metadata))
		{
			$metadata['sound'] = isset($metadata['sound']) ? $metadata['sound'] : 'default';
			$payload['message']['android']['notification'] = $metadata;
			$payload['message']['webpush']['notification'] = $metadata;
			$payload['message']['apns']['payload'] = [
				'aps' => [
					'alert' => ['title' => $title, 'body' => $body],
					'sound' => $metadata['sound'],
					'badge' => $metadata['badge'] ?? 0,
					'category' => $metadata['click_action'] ?? '',
				],
			];
		}

		$data = $message->getData();
		if (count($data))
		{
			$payload['message']['data'] = $data;
		}

		// Send a notification to each token, grouped into chunks to speed up the process.
		$chunked_tokens = $tokens ? array_chunk($tokens, self::CHUNK_SIZE) : [[]];
		foreach ($chunked_tokens as $tokens)
		{
			$requests = [];
			foreach ($tokens as $i => $token)
			{
				$requests[$i] = [
					'url' => $api_url,
					'method' => 'POST',
					'settings' => [
						'auth' => 'google_auth',
						'base_uri' => self::BASE_URL,
						'handler' => $stack,
						'json' => $payload,
					],
				];
				$requests[$i]['settings']['json']['message']['token'] = $token;
			}

			$responses = HTTP::multiple($requests);
			foreach ($responses as $i => $response)
			{
				$status_code = $response->getStatusCode();
				$result_text = $response->getBody()->getContents();
				$result = @json_decode($result_text);
				if ($status_code === 200)
				{
					$output->success[$tokens[$i]] = $result->name ?? '';
				}
				elseif ($result && isset($result->error))
				{
					$error_message = $result->error->message ?? ($result->error->status ?? '');
					$message->addError('FCM error: HTTP ' . $status_code . ' ' . $error_message);
					if (str_contains($error_message, 'not a valid FCM registration token'))
					{
						$output->invalid[$tokens[$i]] = $tokens[$i];
					}
					elseif (str_contains($error_message, 'Requested entity was not found'))
					{
						$output->invalid[$tokens[$i]] = $tokens[$i];
					}
				}
				else
				{
					$message->addError('FCM error: HTTP ' . $status_code . ' ' . $response->getReasonPhrase());
				}
			}
		}

		// Send a notification to each topic.
		$topics = $message->getTopics();
		if (count($topics))
		{
			$requests = [];
			foreach ($topics as $i => $topic)
			{
				$requests[$i] = [
					'url' => $api_url,
					'method' => 'POST',
					'settings' => [
						'auth' => 'google_auth',
						'base_uri' => self::BASE_URL,
						'handler' => $stack,
						'json' => $payload,
					],
				];
				$requests[$i]['settings']['json']['message']['topic'] = $topic;
			}

			$responses = HTTP::multiple($requests);
			foreach ($responses as $i => $response)
			{
				$status_code = $response->getStatusCode();
				$result_text = $response->getBody()->getContents();
				$result = @json_decode($result_text);
				if ($status_code === 200)
				{
					$output->success[$topics[$i]] = $result->name ?? '';
				}
				else
				{
					$error_message = $result->error->message ?? ($result->error->status ?? $response->getReasonPhrase());
					$message->addError('FCM error: HTTP ' . $status_code . ' ' . $error_message);
				}
			}
		}

		return $output;
	}
}
