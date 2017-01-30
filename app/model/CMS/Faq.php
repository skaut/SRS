<?php

namespace App\Model\CMS;

use App\Model\User\User;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;

/**
 * @ORM\Entity
 * @ORM\Table(name="faq")
 */
class Faq
{
    use Identifier;

    /**
     * @ORM\Column(type="text")
     * @var string
     */
    protected $question;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Model\User\User", cascade={"persist"})
     * @var User
     */
    protected $author;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @var string
     */
    protected $answer;

    /**
     * @ORM\Column(type="integer")
     * @var int
     */
    protected $position = 0;

    /**
     * @ORM\Column(type="boolean")
     * @var bool
     */
    protected $public = false;
}