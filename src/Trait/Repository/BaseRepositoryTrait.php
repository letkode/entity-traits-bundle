<?php

declare(strict_types=1);

namespace Letkode\EntityTraitsBundle\Trait\Repository;

use Doctrine\ORM\QueryBuilder;
use Letkode\CommonBundle\Exception\EntityNotFoundException;
use Letkode\EntityTraitsBundle\DTO\PaginatedResult;
use Letkode\EntityTraitsBundle\DTO\TableQueryRequest;
use Symfony\Component\Uid\Uuid;

/** @template T of object */
trait BaseRepositoryTrait
{
    /** @param T $entity */
    public function save(object $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /** @param T $entity */
    public function remove(object $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param string[] $sortable   Allowed field names for sorting (entity property names)
     * @param string[] $searchable Fields to apply LIKE search on
     */
    public function paginate(
        QueryBuilder $qb,
        TableQueryRequest $query,
        array $sortable = [],
        array $searchable = [],
        int $minSearchLength = 3,
    ): PaginatedResult {
        $alias = $qb->getRootAliases()[0];

        $this->applySearch($qb, $alias, $query->q, $searchable, $minSearchLength);
        $this->applySort($qb, $alias, $query->sort, $query->dir, $sortable);

        $total = (int) (clone $qb)
            ->select('COUNT(DISTINCT ' . $alias . '.id)')
            ->resetDQLPart('orderBy')
            ->getQuery()
            ->getSingleScalarResult();

        $data = $qb
            ->setFirstResult(($query->page - 1) * $query->perPage)
            ->setMaxResults($query->perPage)
            ->getQuery()
            ->getResult();

        return new PaginatedResult($data, $total, $query->page, $query->perPage);
    }

    private function applySearch(QueryBuilder $qb, string $alias, string|null $q, array $searchable, int $minSearchLength): void
    {
        if (null === $q || mb_strlen($q) < $minSearchLength || [] === $searchable) {
            return;
        }

        $conditions = array_map(
            static fn (string $field) => 'ILIKE(' . $alias . '.' . $field . ', :q) = TRUE',
            $searchable,
        );

        $qb->andWhere(implode(' OR ', $conditions))
            ->setParameter('q', '%' . $q . '%');
    }

    private function applySort(QueryBuilder $qb, string $alias, string|null $sort, string $dir, array $sortable): void
    {
        if (null === $sort || !\in_array($sort, $sortable, true)) {
            return;
        }

        $qb->resetDQLPart('orderBy')
            ->orderBy($alias . '.' . $sort, strtoupper($dir));
    }

    /** @return T|null */
    public function findByUuid(Uuid $uuid): object|null
    {
        return $this->findOneBy(['uuid' => $uuid]);
    }

    /** @return T */
    public function findOrFailByUuid(Uuid $uuid, string $message = 'Not found.'): object
    {
        $entity = $this->findByUuid($uuid);

        if (null === $entity) {
            throw new EntityNotFoundException($message);
        }

        return $entity;
    }
}
