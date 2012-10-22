<?php
namespace SRS\Model;
use Nette\Security as NS;

class Authenticator extends \Nette\Object implements NS\IAuthenticator
{
    /** @var \Doctrine\ORM\EntityRepository */
    private $users;

    /** @var \SRS\Model\SkautIS */
    private $skautIS;



    public function __construct(\Doctrine\ORM\EntityRepository $users, \SRS\Model\skautIS $skautIS)
    {
        $this->users = $users;
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
        try {
            $skautISUser = $this->skautIS->getUser($skautISToken);
        }
        catch (\SoapFault $e)  {
            \Nette\Diagnostics\Debugger::log($e->getMessage());
            throw new NS\AuthenticationException("Invalid skautIS Token", self::INVALID_CREDENTIAL);

        }
        $skautISPerson = $this->skautIS->getPerson($skautISToken, $skautISUser->ID_Person);

        //@TODO synchronizace s mou databazi

        return new NS\Identity($skautISUser->UserName, NULL, array(
            'token' => $skautISToken,
        ));
    }

}