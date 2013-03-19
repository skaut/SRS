<?php
namespace SRS\Model;
use Nette\Security as NS;
use SRS\Model\Acl\Role;

/**
 * Obsluhuje autentizaci uzivatelu pres skautIS
 */
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
     * @throws \Exception
     */
    public function authenticate(array $credentials)
    {
        list($skautISToken, $skautISUnitId, $skautISRoleId) = $credentials;
        try {
            $skautISUser = $this->skautIS->getUser($skautISToken);
        } catch (\SoapFault $e) {
            \Nette\Diagnostics\Debugger::log($e->getMessage());
            throw new NS\AuthenticationException("Invalid skautIS Token", self::INVALID_CREDENTIAL);
        }
//        $result = $this->skautIS->getMembership($skautISToken, $skautISUnitId);
//        \Nette\Diagnostics\Debugger::dump($result);
        $user = $this->database->getRepository("\SRS\Model\User")->findOneBy(array('skautISUserId' => $skautISUser->ID));
        $roleRegistered = $this->database->getRepository('\SRS\Model\Acl\Role')->findOneBy(array('name' => Role::REGISTERED));
        if ($user == null) {
            if ($roleRegistered == null) {
                throw new \Exception('Nekonzistentni stav. Role pro nove uzivatele by vzdy mela existovat');
            }
            $skautISPerson = $this->skautIS->getPerson($skautISToken, $skautISUser->ID_Person);
            $user = \SRS\Factory\UserFactory::createFromSkautIS($skautISUser, $skautISPerson, $roleRegistered);
            $this->database->persist($user);
            $this->database->flush();
        }

        $netteRoles = array();

        //pokud uzivatel neba roli schvalenou, degradujeme jen na registrovaneho
        if ($user->approved == true) {
            $netteRoles[] = $user->role->name;
        } else {
            $netteRoles[] = $roleRegistered->name;
        }

        return new NS\Identity($user->id, $netteRoles, array(
            'token' => $skautISToken,
            'object' => $user,
        ));
    }

}