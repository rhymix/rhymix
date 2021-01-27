<?php

namespace League\OAuth2\Client\Test\Provider;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Psr7\Response;
use Lcobucci\JWT\Configuration;
use League\OAuth2\Client\Provider\Apple;
use League\OAuth2\Client\Provider\AppleResourceOwner;
use League\OAuth2\Client\Token\AccessToken;
use League\OAuth2\Client\Tool\QueryBuilderTrait;
use PHPUnit\Framework\TestCase;
use Mockery as m;

class AppleTest extends TestCase
{
    use QueryBuilderTrait;

    /**
     * @return Apple
     */
    private function getProvider()
    {
        return new Apple([
            'clientId' => 'mock.example',
            'teamId' => 'mock.team.id',
            'keyFileId' => 'mock.file.id',
            'keyFilePath' => __DIR__ . '/p256-private-key.p8',
            'redirectUri' => 'none'
        ]);
    }

    public function testMissingTeamIdDuringInstantiationThrowsException()
    {
        $this->expectException('InvalidArgumentException');
        new Apple([
            'clientId' => 'mock.example',
            'keyFileId' => 'mock.file.id',
            'keyFilePath' => __DIR__ . '/p256-private-key.p8',
            'redirectUri' => 'none'
        ]);
    }

    public function testMissingKeyFileIdDuringInstantiationThrowsException()
    {
        $this->expectException('InvalidArgumentException');
        new Apple([
            'clientId' => 'mock.example',
            'teamId' => 'mock.team.id',
            'keyFilePath' => __DIR__ . '/p256-private-key.p8',
            'redirectUri' => 'none'
        ]);
    }

    public function testMissingKeyFilePathDuringInstantiationThrowsException()
    {
        $this->expectException('InvalidArgumentException');
        new Apple([
            'clientId' => 'mock.example',
            'teamId' => 'mock.team.id',
            'keyFileId' => 'mock.file.id',
            'redirectUri' => 'none'
        ]);
    }

    public function testMissingKeyDuringInstantiationThrowsException()
    {
        $this->expectException('InvalidArgumentException');
        $this->getProvider()->getLocalKey();
    }

    public function testAuthorizationUrl()
    {
        $provider = $this->getProvider();
        $url = $provider->getAuthorizationUrl();
        $uri = parse_url($url);
        parse_str($uri['query'], $query);

        $this->assertArrayHasKey('client_id', $query);
        $this->assertArrayHasKey('redirect_uri', $query);
        $this->assertArrayHasKey('state', $query);
        $this->assertArrayHasKey('scope', $query);
        $this->assertArrayHasKey('response_type', $query);
        $this->assertArrayHasKey('response_mode', $query);
        $this->assertNotNull($provider->getState());
    }

    public function testScopes()
    {
        $provider = $this->getProvider();
        $scopeSeparator = ' ';
        $options = ['scope' => [uniqid(), uniqid()]];
        $query = ['scope' => implode($scopeSeparator, $options['scope'])];
        $url = $provider->getAuthorizationUrl($options);
        $encodedScope = $this->buildQueryString($query);
        $this->assertNotFalse(strpos($url, $encodedScope));
    }

    public function testGetAuthorizationUrl()
    {
        $provider = $this->getProvider();
        $url = $provider->getAuthorizationUrl();
        $uri = parse_url($url);

        $this->assertEquals('/auth/authorize', $uri['path']);
    }

    public function testGetBaseAccessTokenUrl()
    {
        $provider = $this->getProvider();
        $params = [];

        $url = $provider->getBaseAccessTokenUrl($params);
        $uri = parse_url($url);

        $this->assertEquals('/auth/token', $uri['path']);
    }

    public function testGetAccessToken()
    {
        $this->expectException('UnexpectedValueException');
        $provider = new TestApple([
            'clientId' => 'mock.example',
            'teamId' => 'mock.team.id',
            'keyFileId' => 'mock.file.id',
            'keyFilePath' => __DIR__ . '/../../resources/p256-private-key.p8',
            'redirectUri' => 'none'
        ]);
        $provider = m::mock($provider);


        $configuration = Configuration::forUnsecuredSigner();

        $time = new \DateTimeImmutable();
        $expiresAt = $time->modify('+1 Hour');
        $token = $configuration->builder()
            ->issuedBy('test-team-id')
            ->permittedFor('https://appleid.apple.com')
            ->issuedAt($time)
            ->expiresAt($expiresAt)
            ->relatedTo('test-client')
            ->withHeader('alg', 'RS256')
            ->withHeader('kid', 'test')
            ->getToken($configuration->signer(), $configuration->signingKey());

        $client = m::mock(ClientInterface::class);
        $client->shouldReceive('request')
            ->times(1)
            ->andReturn(new Response(200, [], file_get_contents('https://appleid.apple.com/auth/keys')));
        $client->shouldReceive('send')
            ->times(1)
            ->andReturn(new Response(200, [], json_encode([
                'access_token' => 'aad897dee58fe4f66bf220c181adaf82b.0.mrwxq.hmiE0djj1vJqoNisKmF-pA',
                'token_type' => 'Bearer',
                'expires_in' => 3600,
                'refresh_token' => 'r4a6e8b9c50104b78bc86b0d2649353fa.0.mrwxq.54joUj40j0cpuMANRtRjfg',
                'id_token' => $token->toString()
            ])));
        $provider->setHttpClient($client);

        $provider->getAccessToken('authorization_code', [
            'code' => 'hello-world'
        ]);
    }

    public function testFetchingOwnerDetails()
    {
        $provider = $this->getProvider();
        $class = new \ReflectionClass($provider);
        $method = $class->getMethod('fetchResourceOwnerDetails');
        $method->setAccessible(true);

        $arr = [
            'name' => 'John Doe'
        ];
        $_POST['user'] = json_encode($arr);
        $data = $method->invokeArgs($provider, [new AccessToken(['access_token' => 'hello'])]);

        $this->assertEquals($arr, $data);
    }

    /**
     * @see https://github.com/patrickbussmann/oauth2-apple/issues/12
     */
    public function testFetchingOwnerDetailsIssue12()
    {
        $provider = $this->getProvider();
        $class = new \ReflectionClass($provider);
        $method = $class->getMethod('fetchResourceOwnerDetails');
        $method->setAccessible(true);

        $_POST['user'] = '';
        $data = $method->invokeArgs($provider, [new AccessToken(['access_token' => 'hello'])]);

        $this->assertEquals([], $data);
    }

    public function testNotImplementedGetResourceOwnerDetailsUrl()
    {
        $this->expectException('Exception');
        $provider = $this->getProvider();
        $provider->getResourceOwnerDetailsUrl(new AccessToken(['access_token' => 'hello']));
    }

    public function testCheckResponse()
    {
        $this->expectException('\League\OAuth2\Client\Provider\Exception\AppleAccessDeniedException');
        $provider = $this->getProvider();
        $class = new \ReflectionClass($provider);
        $method = $class->getMethod('checkResponse');
        $method->setAccessible(true);

        $method->invokeArgs($provider, [new Response(400, []), [
            'error' => 'invalid_client',
            'code' => 400
        ]]);
    }

    public function testCreationOfResourceOwner()
    {
        $provider = $this->getProvider();
        $class = new \ReflectionClass($provider);
        $method = $class->getMethod('createResourceOwner');
        $method->setAccessible(true);

        /** @var AppleResourceOwner $data */
        $data = $method->invokeArgs($provider, [
            [
                'email' => 'john@doe.com',// <- Fake E-Mail from user input
                'name' => [
                    'firstName' => 'John',
                    'lastName' => 'Doe'
                ]
            ],
            new AccessToken([
                'access_token' => 'hello',
                'email' => 'john@doe.de',
                'resource_owner_id' => '123.4.567'
            ])
        ]);
        $this->assertEquals('john@doe.de', $data->getEmail());
        $this->assertEquals('Doe', $data->getLastName());
        $this->assertEquals('John', $data->getFirstName());
        $this->assertEquals('123.4.567', $data->getId());
        $this->assertFalse($data->isPrivateEmail());
        $this->assertArrayHasKey('name', $data->toArray());
    }
}
