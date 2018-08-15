<?php
declare(strict_types=1);

namespace App\Model\Mailing;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;


/**
 * Entita šablona automatického e-mailu.
 *
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity(repositoryClass="TemplateRepository")
 * @ORM\Table(name="mail_template")
 */
class Template
{
    /**
     * Přihlášení přes skautIS.
     */
    const SIGN_IN = 'sign_in';

    /**
     * Potvrzení registrace.
     */
    const REGISTRATION = 'registration';

    /**
     * Odhlášení ze semináře.
     */
    const REGISTRATION_CANCELED = 'registration_canceled';

    /**
     * Potvrzení změny rolí.
     */
    const ROLES_CHANGED = 'roles_changed';

    /**
     * Potvrzení změny podakcí.
     */
    const SUBEVENTS_CHANGED = 'subevents_changed';

    /**
     * Potvrzení přijetí platby.
     */
    const PAYMENT_CONFIRMED = 'payment_confirmed';

    /**
     * Upozornění na splatnost.
     */
    const MATURITY_REMINDER = 'maturity_reminder';

    /**
     * Účastníkovi byl zapsán program.
     */
    const PROGRAM_REGISTERED = 'program_registered';

    /**
     * Účastníkovi byl odhlášen program.
     */
    const PROGRAM_UNREGISTERED = 'program_unregistered';

    /**
     * Ověření e-mailu pro mailing.
     */
    const EMAIL_VERIFICATION = 'email_verification';

    /**
     * Potvrzení změny vlastního pole.
     */
    const CUSTOM_INPUT_VALUE_CHANGED = 'custom_input_value_changed';


    use Identifier;

    /**
     * Typ e-mailu.
     * @ORM\Column(type="string")
     * @var string
     */
    protected $type;

    /**
     * Předmět e-mailu.
     * @ORM\Column(type="string")
     * @var string
     */
    protected $subject;

    /**
     * Text e-mailu.
     * @ORM\Column(type="text")
     * @var string
     */
    protected $text;

    /**
     * Aktivní.
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $active;

    /**
     * Proměnné použitelné v šabloně.
     * @ORM\ManyToMany(targetEntity="\App\Model\Mailing\TemplateVariable")
     * @var Collection
     */
    protected $variables;

    /**
     * Zaslat uživateli.
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $sendToUser;

    /**
     * Zaslat pořadateli.
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $sendToOrganizer;

    /**
     * Systémový e-mail. Nelze u něj měnit příjemce.
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $system;


    /**
     * Template constructor.
     */
    public function __construct()
    {
        $this->variables = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType(string $type): void
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     */
    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    /**
     * @return string
     */
    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText(string $text): void
    {
        $this->text = $text;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * @return Collection
     */
    public function getVariables(): Collection
    {
        return $this->variables;
    }

    /**
     * @return bool
     */
    public function isSendToUser(): bool
    {
        return $this->sendToUser;
    }

    /**
     * @param bool $sendToUser
     */
    public function setSendToUser(bool $sendToUser): void
    {
        $this->sendToUser = $sendToUser;
    }

    /**
     * @return bool
     */
    public function isSendToOrganizer(): bool
    {
        return $this->sendToOrganizer;
    }

    /**
     * @param bool $sendToOrganizer
     */
    public function setSendToOrganizer(bool $sendToOrganizer): void
    {
        $this->sendToOrganizer = $sendToOrganizer;
    }

    /**
     * @return bool
     */
    public function isSystem(): bool
    {
        return $this->system;
    }

    /**
     * @param bool $system
     */
    public function setSystem(bool $system): void
    {
        $this->system = $system;
    }
}
