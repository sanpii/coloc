<?php
declare(strict_types = 1);

namespace App\Security;

use \Symfony\Component\Security\Core\User\UserInterface;

final class User implements UserInterface
{
    private $person;

    public function __construct(\App\Model\Person $person)
    {
        $this->person = $person;
    }

    public function __call($name, $arguments)
    {
        return call_user_func_array([$this->person, $name], $arguments);
    }

    public function getRoles()
    {
        return ['ROLE_USER'];
    }

    public function getPassword()
    {
        return $this->person->password;
    }

    public function getSalt()
    {
    }

    public function getUsername()
    {
        return $this->person->email;
    }

    public function eraseCredentials()
    {
    }

    public function isEqualTo(UserInterface $user)
    {
        if (!$user instanceof Self) {
            return false;
        }

        if ($this->getPassword() !== $user->getPassword()) {
            return false;
        }

        if ($this->getSalt() !== $user->getSalt()) {
            return false;
        }

        if ($this->getUsername() !== $user->getUsername()) {
            return false;
        }

        return true;
    }
}
