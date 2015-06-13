<?php

namespace Eab\Elm\MainBundle\Entity;

//use FOS\UserBundle\Model\User as BaseUser;
use eZ\Publish\Core\MVC\Symfony\Security\User as eZUser;
use Doctrine\ORM\Mapping as ORM;

class User extends eZUser
{
    protected $user;

    public function __construct()
    {
        parent::__construct();
        echo "Eab\Elm\MainBundle\Entity\User::__construct()";
        $this->user = $this->getUsername();
        // your own logic
    }

    /**
     * Serializes the user.
     *
     * The serialized data have to contain the fields used by the equals method and the username.
     *
     * @return string
     */
    public function serialize()
    {
        return serialize(array(
            $this->password,
            $this->salt,
            $this->usernameCanonical,
            $this->username,
            $this->expired,
            $this->locked,
            $this->credentialsExpired,
            $this->enabled,
            $this->id,
        ));
    }
    /**
     * Unserializes the user.
     *
     * @param string $serialized
     */
    public function unserialize($serialized)
    {
        $data = unserialize($serialized);
        // add a few extra elements in the array to ensure that we have enough keys when unserializing
        // older data which does not include all properties.
        $data = array_merge($data, array_fill(0, 2, null));
        list(
            $this->password,
            $this->salt,
            $this->usernameCanonical,
            $this->username,
            $this->expired,
            $this->locked,
            $this->credentialsExpired,
            $this->enabled,
            $this->id
        ) = $data;
    }
}
