<?php
namespace SRS\Model;
use Nette\Security as NS;

class Authenticator extends \Nette\Object implements NS\IAuthenticator
{
    /** @var \Doctrine\ORM\EntityManager */
    private $database;

    /** @var \SRS\Model\SkautIS */
    private $skautIS;


    public function __construct(\Doctrine\ORM\EntityManager $database, \SRS\Model\skautIS $skautIS)
    {
        $this->database = $database;
        $this->skautIS = $skautIS;
    }

    /**
     * Performs an authentication
     * @param array $credentials
     * @return \Nette\Security\Identity
     * @throws \Nette\Security\AuthenticationException
     */
    public function authenticate(array $credentials)
    {
        list($username, $skautISToken) = $credentials;
        try
        {
            $skautISUser = $this->skautIS->getUser($skautISToken);
        }
        catch (\SoapFault $e)
        {
            \Nette\Diagnostics\Debugger::log($e->getMessage());
            throw new NS\AuthenticationException("Invalid skautIS Token", self::INVALID_CREDENTIAL);
        }
        $skautISPerson = $this->skautIS->getPerson($skautISToken, $skautISUser->ID_Person);
        $user = $this->database->getRepository("\SRS\Model\User")->findOneBy(array('username' => $skautISUser->UserName));
        if ($user == null)
        {
            $role = $this->database->getRepository('\SRS\Model\Role')->findOneBy(array('name'  => 'registered'));
            $user = \SRS\Factory\UserFactory::createFromSkautIS($skautISUser, $skautISPerson, $role);
            $this->database->persist($user);
            $this->database->flush();
        }

        $netteRoles = array();
        foreach ($user->roles as $role) {
            $netteRoles[] = $role->name;
        }
        return new NS\Identity($user->id, $netteRoles, array(
            'token' => $skautISToken,
            'object' => $user,
        ));
    }

}