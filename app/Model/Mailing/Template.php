<?php

declare(strict_types=1);

namespace App\Model\Mailing;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nettrine\ORM\Entity\Attributes\Id;

/**
 * Entita šablona automatického e-mailu.
 *
 * @ORM\Entity(repositoryClass="TemplateRepository")
 * @ORM\Table(name="mail_template")
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class Template
{
    /**
     * Přihlášení přes skautIS.
     */
    public const SIGN_IN = 'sign_in';

    /**
     * Potvrzení registrace.
     */
    public const REGISTRATION = 'registration';

    /**
     * Odhlášení ze semináře.
     */
    public const REGISTRATION_CANCELED = 'registration_canceled';

    /**
     * Potvrzení změny rolí.
     */
    public const ROLES_CHANGED = 'roles_changed';

    /**
     * Potvrzení změny podakcí.
     */
    public const SUBEVENTS_CHANGED = 'subevents_changed';

    /**
     * Potvrzení přijetí platby.
     */
    public const PAYMENT_CONFIRMED = 'payment_confirmed';

    /**
     * Upozornění na splatnost.
     */
    public const MATURITY_REMINDER = 'maturity_reminder';

    /**
     * Účastníkovi byl zapsán program.
     */
    public const PROGRAM_REGISTERED = 'program_registered';

    /**
     * Účastníkovi byl odhlášen program.
     */
    public const PROGRAM_UNREGISTERED = 'program_unregistered';

    /**
     * Ověření e-mailu pro mailing.
     */
    public const EMAIL_VERIFICATION = 'email_verification';

    /**
     * Potvrzení změny vlastního pole.
     */
    public const CUSTOM_INPUT_VALUE_CHANGED = 'custom_input_value_changed';

    /**
     * Zpráva z kontaktního formuláře.
     */
    public const CONTACT_FORM = 'contact_form';
    use Id;

    /**
     * Typ e-mailu.
     *
     * @ORM\Column(type="string", unique=true)
     */
    protected string $type;

    /**
     * Předmět e-mailu.
     *
     * @ORM\Column(type="string")
     */
    protected string $subject;

    /**
     * Text e-mailu.
     *
     * @ORM\Column(type="text")
     */
    protected string $text;

    /**
     * Aktivní.
     *
     * @ORM\Column(type="boolean")
     */
    protected bool $active;

    /**
     * Proměnné použitelné v šabloně.
     *
     * @ORM\ManyToMany(targetEntity="\App\Model\Mailing\TemplateVariable")
     *
     * @var Collection|TemplateVariable[]
     */
    protected Collection $variables;

    /**
     * Systémový e-mail. Nelze jej deaktivovat.
     *
     * @ORM\Column(type="boolean")
     */
    protected bool $systemTemplate;

    public function __construct()
    {
        $this->variables = new ArrayCollection();
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getType() : string
    {
        return $this->type;
    }

    public function setType(string $type) : void
    {
        $this->type = $type;
    }

    public function getSubject() : string
    {
        return $this->subject;
    }

    public function setSubject(string $subject) : void
    {
        $this->subject = $subject;
    }

    public function getText() : string
    {
        return $this->text;
    }

    public function setText(string $text) : void
    {
        $this->text = $text;
    }

    public function isActive() : bool
    {
        return $this->active;
    }

    public function setActive(bool $active) : void
    {
        $this->active = $active;
    }

    /**
     * @return Collection|TemplateVariable[]
     */
    public function getVariables() : Collection
    {
        return $this->variables;
    }

    public function isSystemTemplate() : bool
    {
        return $this->systemTemplate;
    }
}
