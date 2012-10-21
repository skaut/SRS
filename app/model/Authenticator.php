<?php
namespace SRS\Model;
use Nette\Security as NS;

class Authenticator extends \Nette\Object implements NS\IAuthenticator
{
    /** @var \Doctrine\ORM\EntityRepository */
    private $users;



    public function __construct(\Doctrine\ORM\EntityRepository $users)
    {
        $this->users = $users;
    }



    /**
     * Performs an authentication
     * @param  array
     * @return \Nette\Security\Identity
     * @throws \Nette\Security\AuthenticationException
     */
    public function authenticate(array $credentials)
    {
        list($username, $password) = $credentials;
        $user = $this->users->findOneBy(array('username' => $username));

        if (!$user) {
            throw new NS\AuthenticationException("User '$username' not found.", self::IDENTITY_NOT_FOUND);
        }

        if ($user->password !== $this->calculateHash($password)) {
            throw new NS\AuthenticationException("Invalid password.", self::INVALID_CREDENTIAL);
        }

        return new NS\Identity($user->id, $user->role, array(
            'username' => $user->username,
            'email' => $user->email,
        ));
    }



    /**
     * Computes salted password hash.
     * @param  string
     * @return string
     */
    public function calculateHash($password)
    {
        return md5($password . str_repeat('*enter any random salt here*', 10));
    }

}