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
        $roleRegistered = $this->database->getRepository('\SRS\Model\Acl\Role')->findOneBy(array('name'  => 'Registrovaný'));
        if ($user == null)
        {

            if ($roleRegistered == null) {
                throw new \Nette\NotImplementedException('Nekonzistentni stav. Role pro nove uzivatele by vzdy mela existovat');
            }
            $user = \SRS\Factory\UserFactory::createFromSkautIS($skautISUser, $skautISPerson, $roleRegistered);
            $this->database->persist($user);
            $this->database->flush();
        }

        $netteRoles = array();

        //pokud uzivatel neba roli schvalenou, degradujeme jen na registrovaneho
        if ($user->approved == true) {
        $netteRoles[] = $user->role->name;
        }
        else {
            $netteRoles[] = $roleRegistered->name;
        }

        return new NS\Identity($user->id, $netteRoles, array(
            'token' => $skautISToken,
            'object' => $user,
        ));
    }

}