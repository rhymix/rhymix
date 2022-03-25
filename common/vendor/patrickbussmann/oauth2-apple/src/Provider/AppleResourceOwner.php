<?php namespace League\OAuth2\Client\Provider;

use League\OAuth2\Client\Tool\ArrayAccessorTrait;

/**
 * @property array $response
 * @property string $uid
 */
class AppleResourceOwner extends GenericResourceOwner
{
    use ArrayAccessorTrait;

    /**
     * Raw response
     *
     * @var array
     */
    protected $response = [];

    /**
     * @var string|null
     */
    private $email;

    /**
     * @var boolean true when its private relay from apple else the user mail address
     */
    private $isPrivateEmail;

    /**
     * Gets resource owner attribute by key. The key supports dot notation.
     *
     * @param string $key
     *
     * @return mixed
     */
    public function getAttribute($key)
    {
        return $this->getValueByKey($this->response, (string) $key);
    }

    /**
     * Get user first name
     *
     * @return string|null
     */
    public function getFirstName()
    {
        $name = $this->getAttribute('name');
        if (isset($name)) {
            return $name['firstName'];
        }
        return null;
    }

    /**
     * Get user user id
     *
     * @return string|null
     */
    public function getId()
    {
        return $this->resourceOwnerId;
    }

    /**
     * Get user last name
     *
     * @return string|null
     */
    public function getLastName()
    {
        $name = $this->getAttribute('name');
        if (isset($name)) {
            return $name['lastName'];
        }
        return null;
    }

    /**
     * Get user email, if available
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->getAttribute('email');
    }

    /**
     * @return bool
     */
    public function isPrivateEmail()
    {
        return (bool) $this->getAttribute('isPrivateEmail');
    }

    /**
     * Return all of the owner details available as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }
}
