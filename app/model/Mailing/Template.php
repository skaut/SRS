<?php

namespace App\Model\Mailing;

use Doctrine\Common\Collections\ArrayCollection;
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
     * Potvrzení automaticky registrace.
     */
    const REGISTRATION = 'registration';

    /**
     * Odhlášení ze semináře.
     */
    const REGISTRATION_CANCELED = 'registration_canceled';

    /**
     * Potvrzení změny rolí.
     */
    const ROLE_CHANGED = 'role_changed';

    /**
     * Potvrzení změny podakcí.
     */
    const SUBEVENT_CHANGED = 'subevent_changed';

    /**
     * Potvrzení přijetí platby.
     */
    const PAYMENT_CONFIRMED = 'payment_confirmed';

    /**
     * Upozornění na splatnost.
     */
    const MATURITY_NOTICE = 'maturity_notice';

    /**
     * Účastníkovi byl zapsán program.
     */
    const PROGRAM_REGISTERED = 'program_registered';

    /**
     * Účastníkovi byl odhlášen program.
     */
    const PROGRAM_UNREGISTERED = 'program_unregistered';

    /**
     * Ověření e-mailu.
     */
    const EMAIL_VERIFICATION = 'email_verification';


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
     * @var ArrayCollection
     */
    protected $variables;


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
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * @param string $subject
     */
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }

    /**
     * @return string
     */
    public function getText()
    {
        return $this->text;
    }

    /**
     * @param string $text
     */
    public function setText($text)
    {
        $this->text = $text;
    }

    /**
     * @return bool
     */
    public function isActive()
    {
        return $this->active;
    }

    /**
     * @param bool $active
     */
    public function setActive($active)
    {
        $this->active = $active;
    }

    /**
     * @return ArrayCollection
     */
    public function getVariables()
    {
        return $this->variables;
    }

    /**
     * @param ArrayCollection $variables
     */
    public function setVariables($variables)
    {
        $this->variables = $variables;
    }
}
