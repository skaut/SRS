<?php

declare(strict_types=1);

namespace App\Model\Cms;

use App\Model\Acl\Role;
use App\Model\Cms\Content\Content;
use App\Model\Page\PageException;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping as ORM;
use Nettrine\ORM\Entity\Attributes\Id;
use function array_map;
use function implode;
use function in_array;

/**
 * Entita stránky.
 *
 * @ORM\Entity(repositoryClass="PageRepository")
 * @ORM\Table(name="page")
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 */
class Page
{
    use Id;

    /**
     * Název stránky.
     *
     * @ORM\Column(type="string")
     *
     * @var string
     */
    protected $name;

    /**
     * Cesta stránky.
     *
     * @ORM\Column(type="string", unique=true)
     *
     * @var string
     */
    protected $slug;

    /**
     * Pořadí v menu.
     *
     * @ORM\Column(type="integer")
     *
     * @var int
     */
    protected $position = 0;

    /**
     * Viditelná.
     *
     * @ORM\Column(type="boolean")
     *
     * @var bool
     */
    protected $public = false;

    /**
     * Role, které mají na stránku přístup.
     *
     * @ORM\ManyToMany(targetEntity="\App\Model\Acl\Role", inversedBy="pages", cascade={"persist"})
     *
     * @var Collection|Role[]
     */
    protected $roles;

    /**
     * Obsahy na stránce.
     *
     * @ORM\OneToMany(targetEntity="\App\Model\Cms\Content\Content", mappedBy="page", cascade={"persist"})
     * @ORM\OrderBy({"position" = "ASC"})
     *
     * @var Collection|Content[]
     */
    protected $contents;

    public function __construct(string $name, string $slug)
    {
        $this->name     = $name;
        $this->slug     = $slug;
        $this->roles    = new ArrayCollection();
        $this->contents = new ArrayCollection();
    }

    public function getId() : int
    {
        return $this->id;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setName(string $name) : void
    {
        $this->name = $name;
    }

    public function getSlug() : string
    {
        return $this->slug;
    }

    public function setSlug(string $slug) : void
    {
        $this->slug = $slug;
    }

    public function getPosition() : int
    {
        return $this->position;
    }

    public function setPosition(int $position) : void
    {
        $this->position = $position;
    }

    public function isPublic() : bool
    {
        return $this->public;
    }

    public function setPublic(bool $public) : void
    {
        $this->public = $public;
    }

    /**
     * @return Collection|Role[]
     */
    public function getRoles() : Collection
    {
        return $this->roles;
    }

    public function getRolesText() : string
    {
        return implode(', ', $this->roles->map(static function (Role $role) {
            return $role->getName();
        })->toArray());
    }

    /**
     * @param Collection|Role[] $roles
     */
    public function setRoles(Collection $roles) : void
    {
        $this->roles->clear();
        foreach ($roles as $role) {
            $this->roles->add($role);
        }
    }

    public function addRole(Role $role) : void
    {
        $this->roles->add($role);
    }

    /**
     * Vrací obsahy v oblasti.
     *
     * @return Collection|Content[]
     *
     * @throws PageException
     */
    public function getContents(?string $area = null) : Collection
    {
        if ($area === null) {
            return $this->contents;
        }
        if (! in_array($area, Content::$areas)) {
            throw new PageException('Area ' . $area . ' not defined.');
        }
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('area', $area))
            ->orderBy(['position' => 'ASC']);

        return $this->contents->matching($criteria);
    }

    /**
     * @throws PageException
     */
    public function convertToDto() : PageDto
    {
        $allowedRoles = array_map(static function (Role $role) {
            return $role->getName();
        }, $this->roles->toArray());

        $mainContents = [];
        foreach ($this->getContents(Content::MAIN)->toArray() as $content) {
            $mainContents[] = $content->convertToDto();
        }

        $sidebarContents = [];
        foreach ($this->getContents(Content::SIDEBAR)->toArray() as $content) {
            $sidebarContents[] = $content->convertToDto();
        }

        return new PageDto($this->name, $this->slug, $allowedRoles, $mainContents, $sidebarContents, ! empty($sidebarContents));
    }
}
