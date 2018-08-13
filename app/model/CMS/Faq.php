<?php
declare(strict_types=1);

namespace App\Model\CMS;

use App\Model\User\User;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;


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
    use Identifier;

    /**
     * Otázka.
     * @ORM\Column(type="text")
     * @var string
     */
    protected $question;

    /**
     * Autor otázky.
     * @ORM\ManyToOne(targetEntity="\App\Model\User\User")
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
    protected $public = FALSE;

    /**
     * Pozice otázky.
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $position = 0;


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
    public function getQuestion(): string
    {
        return $this->question;
    }

    /**
     * @param string $question
     */
    public function setQuestion(string $question): void
    {
        $this->question = $question;
    }

    /**
     * @return User
     */
    public function getAuthor(): User
    {
        return $this->author;
    }

    /**
     * @param User $author
     */
    public function setAuthor(User $author): void
    {
        $this->author = $author;
    }

    /**
     * @return string
     */
    public function getAnswer(): ?string
    {
        return $this->answer;
    }

    /**
     * @param string $answer
     */
    public function setAnswer(?string $answer): void
    {
        $this->answer = $answer;
    }

    /**
     * @return bool
     */
    public function isPublic(): bool
    {
        return $this->public;
    }

    /**
     * @param bool $public
     */
    public function setPublic(bool $public): void
    {
        $this->public = $public;
    }

    /**
     * @return int
     */
    public function getPosition(): int
    {
        return $this->position;
    }

    /**
     * @param int $position
     */
    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    /**
     * Je zodpovězena?
     * @return bool
     */
    public function isAnswered(): bool
    {
        return $this->answer != '';
    }
}
