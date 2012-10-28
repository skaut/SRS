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
        try {
            $skautISUser = $this->skautIS->getUser($skautISToken);
        }
        catch (\SoapFault $e)  {
            \Nette\Diagnostics\Debugger::log($e->getMessage());
            throw new NS\AuthenticationException("Invalid skautIS Token", self::INVALID_CREDENTIAL);

        }
        $skautISPerson = $this->skautIS->getPerson($skautISToken, $skautISUser->ID_Person);
        $user = $this->database->getRepository("\SRS\Model\User")->findOneBy(array('username' => $skautISUser->UserName));
        if ($user == null) {
            $user = \SRS\Parsers\UserParser::createFromSkautIS($skautISUser, $skautISPerson);
            $this->database->persist($user);
            $this->database->flush();
        }

        return new NS\Identity($user->id, $user->roles, array(
            'token' => $skautISToken,
        ));
    }

}