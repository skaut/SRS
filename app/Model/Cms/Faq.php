<?php

declare(strict_types=1);

namespace App\Model\Cms;

use App\Model\User\User;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entita FAQ.
 *
 * @ORM\Entity
 * @ORM\Table(name="faq")
 */
class Faq
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer", nullable=FALSE)
     */
    private ?int $id = null;

    /**
     * Otázka.
     *
     * @ORM\Column(type="text")
     */
    protected string $question;

    /**
     * Autor otázky.
     *
     * @ORM\ManyToOne(targetEntity="\App\Model\User\User", cascade={"persist"})
     */
    protected User $author;

    /**
     * Odpověď.
     *
     * @ORM\Column(type="text", nullable=true)
     */
    protected ?string $answer = null;

    /**
     * Otázka zveřejněna všem.
     *
     * @ORM\Column(type="boolean")
     */
    protected bool $public = false;

    /**
     * Pozice otázky.
     *
     * @ORM\Column(type="integer")
     */
    protected int $position = 0;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getQuestion(): string
    {
        return $this->question;
    }

    public function setQuestion(string $question): void
    {
        $this->question = $question;
    }

    public function getAuthor(): User
    {
        return $this->author;
    }

    public function setAuthor(User $author): void
    {
        $this->author = $author;
    }

    public function getAnswer(): ?string
    {
        return $this->answer;
    }

    public function setAnswer(?string $answer): void
    {
        $this->answer = $answer;
    }

    public function isPublic(): bool
    {
        return $this->public;
    }

    public function setPublic(bool $public): void
    {
        $this->public = $public;
    }

    public function getPosition(): int
    {
        return $this->position;
    }

    public function setPosition(int $position): void
    {
        $this->position = $position;
    }

    /**
     * Je zodpovězena?
     */
    public function isAnswered(): bool
    {
        return $this->answer !== '';
    }
}
