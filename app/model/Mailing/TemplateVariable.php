<?php

namespace App\Model\Mailing;

use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;


/**
 * Entita proměnná v automatickém e-mailu.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity
 * @ORM\Table(name="mail_template_variable")
 */
class TemplateVariable
{
    /**
     * Název semináře.
     */
    const SEMINAR_NAME = 'seminar_name';

    /**
     * Role uživatele.
     */
    const USERS_ROLES = 'users_roles';

    /**
     * Podakce uživatele.
     */
    const USERS_SUBEVENTS = 'users_subevents';

    /**
     * Název programu.
     */
    const PROGRAM_NAME = 'program_name';

    /**
     * Podakce přihlášky.
     */
    const APPLICATION_SUBEVENTS = 'application_subevents';

    /**
     * Splatnost přihlášky.
     */
    const APPLICATION_MATURITY = 'application_maturity';

    /**
     * Poplatek přihlášky.
     */
    const APPLICATION_FEE = 'application_fee';

    /**
     * Variabilní symbol přihlášky.
     */
    const APPLICATION_VARIABLE_SYMBOL = 'application_variable_symbol';

    /**
     * Odhlášení ze semináře a změna rolí povolena do.
     */
    const EDIT_REGISTRATION_TO = 'edit_registration_to';

    /**
     * Bankovní účet pro platbu.
     */
    const BANK_ACCOUNT = 'bank_account';

    /**
     * Odkaz pro potvrzení změny e-mailu.
     */
    const EMAIL_VERIFICATION_LINK = 'email_verification_link';



    use Identifier;

    /**
     * Název proměnné.
     * @ORM\Column(type="string")
     * @var string
     */
    protected $name;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
