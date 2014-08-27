<?php

namespace theses;

use Symfony\Component\Security\Core\User\AdvancedUserInterface;

class User implements AdvancedUserInterface
{
    private $email;
    private $displayName;
    private $username;
    private $password;
    private $profile;
    private $role;
    private $enabled;

    static function fromAttributes(array $attributes)
    {
        $user = new static;
        $user->id = $attributes['id'];
        $user->username = $attributes['username'];
        $user->password = $attributes['password'];
        $user->email = $attributes['email'];
        $user->displayName = $attributes['display_name'];
        $user->role = $attributes['role'];
        $user->nickname = $attributes['nickname'];
        $user->bio = $attributes['bio'];
        $user->enabled = (bool) $attributes['enabled'];

        return $user;
    }

    function getDisplayName()
    {
        return $this->displayName;
    }

    function getNickname()
    {
        return $this->nickname;
    }

    function getEmail()
    {
        return $this->email;
    }

    function getBio()
    {
        return $this->bio;
    }

    function getRoles()
    {
        return [$this->role];
    }

    function getPassword()
    {
        return $this->password;
    }

    function getSalt()
    {
    }

    function getUsername()
    {
        return $this->username;
    }

    function eraseCredentials()
    {
        $this->password = null;
    }

    function isAccountNonExpired()
    {
        return true;
    }

    function isAccountNonLocked()
    {
        return true;
    }

    function isCredentialsNonExpired()
    {
        return true;
    }

    function isEnabled()
    {
        return (bool) $this->enabled;
    }
}
