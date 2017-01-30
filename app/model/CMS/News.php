<?php

namespace App\Model\CMS;

use App\Model\User\User;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;

/**
 * @ORM\Entity
 * @ORM\Table(name="news")
 */
class News
{
    use Identifier;

    /**
     * @ORM\Column(type="text")
     * @var string
     */
    protected $text;

    /**
     * @ORM\Column(type="date")
     * @var \DateTime
     */
    protected $published;

    /**
     * @ORM\ManyToOne(targetEntity="\App\Model\User\User", cascade={"persist"})
     * @var User
     */
    protected $author;
}