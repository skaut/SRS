<?php

namespace App\Model\CMS\Document;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Kdyby\Doctrine\Entities\Attributes\Identifier;


/**
 * Entita kategorie pro dokumenty.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @author Petr Parolek <petr.parolek@webnazakazku.cz>
 * @ORM\Entity(repositoryClass="CategoryDocumentRepository")
 * @ORM\Table(name="category_document")
 */
class CategoryDocument
{
    use Identifier;

    /**
     * Dokumenty s kategorií.
     * @ORM\ManyToMany(targetEntity="Document", mappedBy="documentCategories", cascade={"persist"})
     * @var Collection
     */
    protected $documents;

    /**
     * Název kategorie.
     * @ORM\Column(type="string", unique=true)
     * @var string
     */
    protected $name;


    /**
     * Kategorie constructor.
     */
    public function __construct()
    {
        $this->documents = new ArrayCollection();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return Collection
     */
    public function getDocuments()
    {
        return $this->documents;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }
}
