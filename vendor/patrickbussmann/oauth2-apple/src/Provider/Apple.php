<?php

namespace League\OAuth2\Client\Provider;

use Exception;
use Firebase\JWT\JWK;
use InvalidArgumentException;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\LocalFileReference;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key;
use League\OAuth2\Client\Grant\AbstractGrant;
use League\OAuth2\Client\Provider\Exception\AppleAccessDeniedException;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Token\AccessTokenInterface;
use League\OAuth2\Client\Token\AppleAccessToken;
use League\OAuth2\Client\Tool\BearerAuthorizationTrait;
use Psr\Http\Message\ResponseInterface;

class Apple extends AbstractProvider
{
    use BearerAuthorizationTrait;

    /**
     * Default scopes
     *
     * @var array
     */
    public $defaultScopes = ['name', 'email'];

    /**
     * @var string the team id
     */
    protected $teamId;

    /**
     * @var string the key file id
     */
    protected $keyFileId;

    /**
     * @var string the key file path
     */
    protected $keyFilePath;

    /**
     * Constructs Apple's OAuth 2.0 service provider.
     *
     * @param array $options
     * @param array $collaborators
     */
    public function __construct(array $options = [], array $collaborators = [])
    {
        if (empty($options['teamId'])) {
            throw new InvalidArgumentException('Required option not passed: "teamId"');
        }

        if (empty($options['keyFileId'])) {
            throw new InvalidArgumentException('Required option not passed: "keyFileId"');
        }

        if (empty($options['keyFilePath'])) {
            throw new InvalidArgumentException('Required option not passed: "keyFilePath"');
        }

        parent::__construct($options, $collaborators);
    }

    /**
     * Creates an access token from a response.
     *
     * The grant that was used to fetch the response can be used to provide
     * additional context.
     *
     * @param  array         $response
     * @param  AbstractGrant $grant
     * @return AccessTokenInterface
     */
    protected function createAccessToken(array $response, AbstractGrant $grant)
    {
        return new AppleAccessToken($this->getAppleKeys(), $response);
    }

    /**
     * @return string[] Apple's JSON Web Keys
     */
    private function getAppleKeys()
    {
        $response = $this->httpClient->request('GET', 'https://appleid.apple.com/auth/keys');

        if ($response && $response->getStatusCode() === 200) {
            return JWK::parseKeySet(json_decode($response->getBody()->getContents(), true));
        }

        return [];
    }

    /**
     * Get the string used to separate scopes.
     *
     * @return string
     */
    protected function getScopeSeparator()
    {
        return ' ';
    }

    /**
     * Change response mode when scope requires it
     *
     * @param array $options
     *
     * @return array
     */
    protected function getAuthorizationParameters(array $options)
    {
        $options = parent::getAuthorizationParameters($options);
        if (strpos($options['scope'], 'name') !== false || strpos($options['scope'], 'email') !== false) {
            $options['response_mode'] = 'form_post';
        }
        return $options;
    }

    /**
     * @param AccessToken $token
     *
     * @return mixed
     */
    protected function fetchResourceOwnerDetails(AccessToken $token)
    {
        return json_decode(array_key_exists('user', $_GET) ? $_GET['user']
            : (array_key_exists('user', $_POST) ? $_POST['user'] : '[]'), true) ?: [];
    }

    /**
     * Get authorization url to begin OAuth flow
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return 'https://appleid.apple.com/auth/authorize';
    }

    /**
     * Get access token url to retrieve token
     *
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return 'https://appleid.apple.com/auth/token';
    }

    /**
     * Get provider url to fetch user details
     *
     * @param AccessToken $token
     *
     * @return string
     * @throws Exception
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        throw new Exception('No Apple ID REST API available yet!');
    }

    /**
     * Get the default scopes used by this provider.
     *
     * This should not be a complete list of all scopes, but the minimum
     * required for the provider user interface!
     *
     * @return array
     */
    protected function getDefaultScopes()
    {
        return $this->defaultScopes;
    }

    /**
     * Check a provider response for errors.
     *
     * @param  ResponseInterface $response
     * @param  array             $data     Parsed response data
     * @return void
     * @throws AppleAccessDeniedException
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        if ($response->getStatusCode() >= 400) {
            throw new AppleAccessDeniedException(
                array_key_exists('error', $data) ? $data['error'] : $response->getReasonPhrase(),
                array_key_exists('code', $data) ? $data['code'] : $response->getStatusCode(),
                $response
            );
        }
    }

    /**
     * Generate a user object from a successful user details request.
     *
     * @param array $response
     * @param AccessToken $token
     * @return AppleResourceOwner
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new AppleResourceOwner(
            array_merge(
                $response,
                [
                    'email' => isset($token->getValues()['email'])
                        ? $token->getValues()['email'] : (isset($response['email']) ? $response['email'] : null),
                    'isPrivateEmail' => $token instanceof AppleAccessToken ? $token->isPrivateEmail() : null
                ]
            ),
            $token->getResourceOwnerId()
        );
    }

    /**
     * {@inheritDoc}
     */
    public function getAccessToken($grant, array $options = [])
    {
        $configuration = $this->getConfiguration();
        $time = new \DateTimeImmutable();
        $time = $time->setTime($time->format('H'), $time->format('i'), $time->format('s'));
        $expiresAt = $time->modify('+1 Hour');
        $expiresAt = $expiresAt->setTime($expiresAt->format('H'), $expiresAt->format('i'), $expiresAt->format('s'));

        $token = $configuration->builder()
            ->issuedBy($this->teamId)
            ->permittedFor('https://appleid.apple.com')
            ->issuedAt($time)
            ->expiresAt($expiresAt)
            ->relatedTo($this->clientId)
            ->withHeader('alg', 'ES256')
            ->withHeader('kid', $this->keyFileId)
            ->getToken($configuration->signer(), $configuration->signingKey());

        $options += [
            'client_secret' => $token->toString()
        ];

        return parent::getAccessToken($grant, $options);
    }

    /**
     * @return Configuration
     */
    public function getConfiguration()
    {
        if (method_exists(Signer\Ecdsa\Sha256::class, 'create')) {
            return Configuration::forSymmetricSigner(
                Signer\Ecdsa\Sha256::create(),
                $this->getLocalKey()
            );
        } else {
            return Configuration::forSymmetricSigner(
                new Signer\Ecdsa\Sha256(),
                $this->getLocalKey()
            );
        }
    }

    /**
     * @return Key
     */
    public function getLocalKey()
    {
        return LocalFileReference::file($this->keyFilePath);
    }
}
