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
     * Odhlášení ze semináře a změna rolí povolena do.
     */
    const EDIT_REGISTRATION_TO = 'edit_registration_to';

    /**
     * Role uživatele.
     */
    const USERS_ROLES = 'users_roles';

    /**
     * Podakce uživatele.
     */
    const USERS_SUBEVENTS = 'users_subevents';

    /**
     * Splatnost.
     */
    const MATURITY = 'maturity';

    /**
     * Název programu.
     */
    const PROGRAM_NAME = 'program_name';

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
