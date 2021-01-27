<?php

namespace League\OAuth2\Client\Test\Provider;

use Lcobucci\JWT\Configuration;
use League\OAuth2\Client\Provider\Apple;

/**
 * Class TestApple
 * @package League\OAuth2\Client\Test\Provider
 * @author Patrick Bußmann <patrick.bussmann@bussmann-it.de>
 */
class TestApple extends Apple
{
    /**
     * {@inheritDoc}
     */
    public function getConfiguration()
    {
        return Configuration::forUnsecuredSigner();
    }

    /**
     * {@inheritDoc}
     */
    public function getLocalKey()
    {
        return null;
    }
}
