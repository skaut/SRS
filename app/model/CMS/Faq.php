<?php

declare(strict_types=1);

namespace App\Model\CMS;

use App\Model\User\User;
use Doctrine\ORM\Mapping as ORM;
use Nettrine\ORM\Entity\Attributes\Id;

/**
 * Entita FAQ.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity(repositoryClass="FaqRepository")
 * @ORM\Table(name="faq")
 */
class Faq
{
    use Id;

    /**
     * Otázka.
     * @ORM\Column(type="text")
     * @var string
     */
    protected $question;

    /**
     * Autor otázky.
     * @ORM\ManyToOne(targetEntity="\App\Model\User\User", cascade={"persist"})
     * @var User
     */
    protected $author;

    /**
     * Odpověď.
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    protected $answer;

    /**
     * Otázka zveřejněna všem.
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $public = false;

    /**
     * Pozice otázky.
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $position = 0;


    public function getId() : int
    {
        return $this->id;
    }

    public function getQuestion() : string
    {
        return $this->question;
    }

    public function setQuestion(string $question) : void
    {
        $this->question = $question;
    }

    public function getAuthor() : User
    {
        return $this->author;
    }

    public function setAuthor(User $author) : void
    {
        $this->author = $author;
    }

    public function getAnswer() : ?string
    {
        return $this->answer;
    }

    public function setAnswer(?string $answer) : void
    {
        $this->answer = $answer;
    }

    public function isPublic() : bool
    {
        return $this->public;
    }

    public function setPublic(bool $public) : void
    {
        $this->public = $public;
    }

    public function getPosition() : int
    {
        return $this->position;
    }

    public function setPosition(int $position) : void
    {
        $this->position = $position;
    }

    /**
     * Je zodpovězena?
     */
    public function isAnswered() : bool
    {
        return $this->answer !== '';
    }
}
