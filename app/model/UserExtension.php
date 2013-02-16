<?php

namespace SRS\Model;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\Criteria;

/**
 * Rozsiruje uzivatele o dalsi volitelne parametry tybu boolean na bazi key-value pristupu
 *
 *
 * @ORM\Entity(repositoryClass="\SRS\Model\UserRepository")
 *
 * @property-read int $id
 * @property string $property
 * @property bool $value
 * @property User $user

 */
class UserExtension extends BaseEntity
{
    /**
     * @ORM\ManyToOne(targetEntity="\SRS\Model\User", inversedBy="extensions")
     */
    protected $user;

    /**
     * @ORM\Column
     * @var string
     */
    protected $property;

    /**
     *@ORM\Column(type="boolean")
     */
     protected $value = false;


}


class UserExtensionRepository extends \Nella\Doctrine\Repository
{

}