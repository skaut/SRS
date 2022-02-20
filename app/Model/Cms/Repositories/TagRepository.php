<?php

declare(strict_types=1);

namespace App\Model\Cms\Repositories;

use App\Model\Cms\Tag;
use App\Model\Infrastructure\Repositories\AbstractRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMException;

use function array_map;

/**
 * Třída spravující tagy dokumentů.
 */
class TagRepository extends AbstractRepository
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, Tag::class);
    }

    /**
     * Vrátí tag podle id.
     */
    public function findById(?int $id): ?Tag
    {
        return $this->getRepository()->findOneBy(['id' => $id]);
    }

    /**
     * Vrátí tagy podle id.
     *
     * @param int[] $ids
     *
     * @return Collection<int, Tag>
     */
    public function findTagsByIds(array $ids): Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->in('id', $ids))
            ->orderBy(['name' => 'ASC']);

        return $this->getRepository()->matching($criteria);
    }

    /**
     * Vrátí id tagů.
     *
     * @param Collection<int, Tag> $tags
     *
     * @return int[]
     */
    public function findTagsIds(Collection $tags): array
    {
        return array_map(static function (Tag $o) {
            return $o->getId();
        }, $tags->toArray());
    }

    /**
     * Vrátí všechny názvy tagů.
     *
     * @return string[]
     */
    public function findAllNames(): array
    {
        $names = $this->createQueryBuilder('t')
            ->select('t.name')
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $names);
    }

    /**
     * Vrátí názvy tagů, kromě tagu s id.
     *
     * @return string[]
     */
    public function findOthersNames(int $id): array
    {
        $names = $this->createQueryBuilder('t')
            ->select('t.name')
            ->where('t.id != :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getScalarResult();

        return array_map('current', $names);
    }

    /**
     * Uloží tag.
     *
     */
    public function save(Tag $tag): void
    {
        $this->em->persist($tag);
        $this->em->flush();
    }

    /**
     * Odstraní tag.
     *
     */
    public function remove(Tag $tag): void
    {
        $this->em->remove($tag);
        $this->em->flush();
    }

    /**
     * Vrátí seznam tagů jako možnosti pro select.
     *
     * @return string[]
     */
    public function getTagsOptions(): array
    {
        $tags = $this->createQueryBuilder('t')
            ->select('t.id, t.name')
            ->orderBy('t.name')
            ->getQuery()
            ->getResult();

        $options = [];
        foreach ($tags as $tag) {
            $options[$tag['id']] = $tag['name'];
        }

        return $options;
    }
}
