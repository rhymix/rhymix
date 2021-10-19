<?php

namespace League\OAuth2\Client\Token;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use GuzzleHttp\ClientInterface;
use InvalidArgumentException;

class AppleAccessToken extends AccessToken
{
    /**
     * @var string
     */
    protected $idToken;

    /**
     * @var string
     */
    protected $email;

    /**
     * @var boolean
     */
    protected $isPrivateEmail;

    /**
     * Constructs an access token.
     *
     * @param string[] $keys Valid Apple JWT keys
     * @param array $options An array of options returned by the service provider
     *     in the access token request. The `access_token` option is required.
     * @throws InvalidArgumentException if `access_token` is not provided in `$options`.
     *
     * @throws \Exception
     */
    public function __construct(array $keys, array $options = [])
    {
        if (array_key_exists('refresh_token', $options)) {
            if (empty($options['id_token'])) {
                throw new InvalidArgumentException('Required option not passed: "id_token"');
            }

            $decoded = null;
            $last = end($keys);
            foreach ($keys as $key) {
                try {
                    $decoded = JWT::decode($options['id_token'], $key, ['RS256']);
                    break;
                } catch (\Exception $exception) {
                    if ($last === $key) {
                        throw $exception;
                    }
                }
            }
            if (null === $decoded) {
                throw new \Exception('Got no data within "id_token"!');
            }
            $payload = json_decode(json_encode($decoded), true);

            $options['resource_owner_id'] = $payload['sub'];

            if (isset($payload['email_verified']) && $payload['email_verified']) {
                $options['email'] = $payload['email'];
            }

            if (isset($payload['is_private_email'])) {
                $this->isPrivateEmail = $payload['is_private_email'];
            }
        }

        parent::__construct($options);

        if (isset($options['id_token'])) {
            $this->idToken = $options['id_token'];
        }

        if (isset($options['email'])) {
            $this->email = $options['email'];
        }
    }

    /**
     * @return string
     */
    public function getIdToken()
    {
        return $this->idToken;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @return boolean
     */
    public function isPrivateEmail()
    {
        return $this->isPrivateEmail;
    }
}
