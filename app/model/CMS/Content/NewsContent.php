<?php

namespace App\Model\CMS\Content;

use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;

/**
 * @ORM\Entity
 * @ORM\Table(name="news_content")
 */
class NewsContent extends Content
{
    /**
     * @ORM\Column(type="integer", nullable=true)
     * @var int
     */
    protected $count;
}