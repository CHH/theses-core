<?php

namespace theses;

use Symfony\Component\Security\Core\User\UserInterface;

class User implements UserInterface
{
    public $email;
    public $displayName;
    public $username;
    public $password;
    public $profile;
    public $role;

    function getRoles()
    {
        return [$this->role];
    }

    function getPassword()
    {
        $this->password;
    }

    function getSalt()
    {
        return $this->username;
    }

    function getUsername()
    {
        return $this->username;
    }

    function eraseCredentials()
    {
        $this->password = null;
    }
}
