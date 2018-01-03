<?php

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
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getQuestion()
    {
        return $this->question;
    }

    /**
     * @param string $question
     */
    public function setQuestion($question)
    {
        $this->question = $question;
    }

    /**
     * @return User
     */
    public function getAuthor()
    {
        return $this->author;
    }

    /**
     * @param User $author
     */
    public function setAuthor($author)
    {
        $this->author = $author;
    }

    /**
     * @return string
     */
    public function getAnswer()
    {
        return $this->answer;
    }

    /**
     * @param string $answer
     */
    public function setAnswer($answer)
    {
        $this->answer = $answer;
    }

    /**
     * @return bool
     */
    public function isPublic()
    {
        return $this->public;
    }

    /**
     * @param bool $public
     */
    public function setPublic($public)
    {
        $this->public = $public;
    }

    /**
     * @return int
     */
    public function getPosition()
    {
        return $this->position;
    }

    /**
     * @param int $position
     */
    public function setPosition($position)
    {
        $this->position = $position;
    }

    /**
     * Je zodpovězena?
     * @return bool
     */
    public function isAnswered()
    {
        return $this->answer != '';
    }
}
