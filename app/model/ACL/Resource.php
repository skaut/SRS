<?php
declare(strict_types=1);

namespace App\Model\ACL;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Nettrine\ORM\Entity\Attributes\Id as Identifier;

/**
 * Entita prostředek.
 *
 * @author Michal Májský
 * @author Jan Staněk <jan.stanek@skaut.cz>
 * @ORM\Entity
 * @ORM\Table(name="resource")
 */
class Resource
{

	/**
	 * Administrace.
	 * @var string
	 */
	public const ADMIN = 'admin';

	/**
	 * Web.
	 * @var string
	 */
	public const CMS = 'cms';

	/**
	 * Role.
	 * @var string
	 */
	public const ACL = 'acl';

	/**
	 * Program.
	 * @var string
	 */
	public const PROGRAM = 'program';

	/**
	 * Nastavení.
	 * @var string
	 */
	public const CONFIGURATION = 'configuration';

	/**
	 * Uživatelé.
	 * @var string
	 */
	public const USERS = 'users';

	/**
	 * Mailing.
	 * @var string
	 */
	public const MAILING = 'mailing';

	/**
	 * Platby.
	 * @var string
	 */
	public const PAYMENTS = 'payments';

	/** @var string[] */
	public static $resources = [
		self::ADMIN,
		self::CMS,
		self::ACL,
		self::PROGRAM,
		self::CONFIGURATION,
		self::USERS,
		self::MAILING,
		self::PAYMENTS,
	];

	use Identifier;

	/**
	 * Název prostředku.
	 * @ORM\Column(type="string", unique=true)
	 * @var string
	 */
	protected $name;

	/**
	 * Oprávnění s tímto prostředkem.
	 * @ORM\OneToMany(targetEntity="\App\Model\ACL\Permission", mappedBy="resource", cascade={"persist"})
	 * @var Collection|Permission[]
	 */
	protected $permissions;

	public function __construct(string $name)
	{
		$this->name = $name;
		$this->permissions = new ArrayCollection();
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return Collection|Permission[]
	 */
	public function getPermissions(): Collection
	{
		return $this->permissions;
	}
}
