<?php

namespace App\Model\CMS\Content;


use App\Model\CMS\Document\TagRepository;
use App\Model\CMS\Page;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Nette\Application\UI\Form;

/**
 * @ORM\Entity
 * @ORM\Table(name="place_content")
 */
class PlaceContent extends Content implements IContent
{
    protected $type = Content::PLACE;
}