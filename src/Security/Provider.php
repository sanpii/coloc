<?php
declare(strict_types = 1);

namespace App\Security;

use \PommProject\Foundation\Pomm;
use \Symfony\Component\Security\Core\User\UserInterface;
use \Symfony\Component\Security\Core\User\UserProviderInterface;
use \Symfony\Component\Security\Core\Exception\UsernameNotFoundException;
use \Symfony\Component\Security\Core\Exception\UnsupportedUserException;

final class Provider implements UserProviderInterface
{
    private $pomm;

    public function __construct(Pomm $pomm)
    {
        $this->pomm = $pomm;
    }

    public function loadUserByUsername($username)
    {
        $persons = $this->pomm['db']->getModel('\App\Model\PersonModel')
            ->findWhere('email = $*', [$username]);

        if (count($persons) !== 1) {
            throw new UsernameNotFoundException(
                sprintf('Username "%s" does not exist.', $username)
            );
        }

        return new User($persons->get(0));
    }

    public function refreshUser(UserInterface $user)
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(
                sprintf('Instances of "%s" are not supported.', get_class($user))
            );
        }

        return $this->loadUserByUsername($user->getUsername());
    }

    public function supportsClass($class)
    {
        return User::class === $class;
    }
}
