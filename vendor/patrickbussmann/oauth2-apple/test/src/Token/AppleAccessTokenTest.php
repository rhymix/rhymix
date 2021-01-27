<?php

namespace League\OAuth2\Client\Test\Token;

use GuzzleHttp\Psr7\Response;
use League\OAuth2\Client\Token\AppleAccessToken;
use PHPUnit\Framework\TestCase;
use Mockery as m;

class AppleAccessTokenTest extends TestCase
{
    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testCreatingAccessToken()
    {
        $externalJWTMock = m::mock('overload:Firebase\JWT\JWT');
        $externalJWTMock->shouldReceive('decode')
            ->with('something', 'examplekey', ['RS256'])
            ->once()
            ->andReturn([
                'sub' => '123.abc.123'
            ]);

        $externalJWKMock = m::mock('overload:Firebase\JWT\JWK');
        $externalJWKMock->shouldReceive('parseKeySet')
            ->once()
            ->andReturn(['examplekey']);

        $accessToken = new AppleAccessToken($this->getClient(1), [
            'access_token' => 'access_token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => 'abc.0.def',
            'id_token' => 'something'
        ]);
        $this->assertEquals('something', $accessToken->getIdToken());
        $this->assertEquals('123.abc.123', $accessToken->getResourceOwnerId());
        $this->assertEquals('access_token', $accessToken->getToken());
    }

    public function testCreatingRefreshToken()
    {
        $refreshToken = new AppleAccessToken($this->getClient(0), [
            'access_token' => 'access_token',
            'token_type' => 'Bearer',
            'expires_in' => 3600
        ]);
        $this->assertEquals('access_token', $refreshToken->getToken());
    }

    private function getClient($times)
    {
        $client = m::mock('GuzzleHttp\ClientInterface');
        if ($times > 0) {
            $client->shouldReceive('request')
                ->times($times)
                ->withArgs(['GET', 'https://appleid.apple.com/auth/keys'])
                ->andReturn(new Response())
            ;
        }

        return $client;
    }
}
