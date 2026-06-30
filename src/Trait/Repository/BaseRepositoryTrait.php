<?php

declare(strict_types=1);

namespace Letkode\EntityTraitsBundle\Trait\Repository;

use Doctrine\ORM\QueryBuilder;
use Letkode\CommonBundle\Exception\EntityNotFoundException;
use Letkode\EntityTraitsBundle\DTO\FilterCriteria;
use Letkode\EntityTraitsBundle\DTO\FilterInput;
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
     * @param string[]                    $sortable   Allowed field names for sorting
     * @param string[]                    $searchable Fields to apply ILIKE search on
     * @param array<string, FilterInput>  $filterable Allowed filter fields and their definitions
     */
    public function paginate(
        QueryBuilder $qb,
        TableQueryRequest $query,
        array $sortable = [],
        array $searchable = [],
        int $minSearchLength = 3,
        array $filterable = [],
    ): PaginatedResult {
        $alias = $qb->getRootAliases()[0];

        $this->applySearch($qb, $alias, $query->q, $searchable, $minSearchLength);
        $this->applyFilters($qb, $alias, $query->filters, $filterable);
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

    /**
     * @param list<FilterCriteria>       $filters
     * @param array<string, FilterInput> $filterable
     */
    private function applyFilters(QueryBuilder $qb, string $alias, array $filters, array $filterable): void
    {
        foreach ($filters as $criteria) {
            if (!isset($filterable[$criteria->field])) {
                continue;
            }

            $field = $filterable[$criteria->field];
            $path = $field->path ?? $alias . '.' . $criteria->field;

            $this->applyFilterCondition($qb, $criteria, $field, $path);
        }
    }

    private function applyFilterCondition(QueryBuilder $qb, FilterCriteria $criteria, FilterInput $field, string $path): void
    {
        $op = $criteria->operator;
        $values = $criteria->values;
        $param = 'filter_' . preg_replace('/[^a-zA-Z0-9]/', '_', $criteria->field);

        match ($op) {
            'contains' => $qb
                ->andWhere('ILIKE(' . $path . ', :' . $param . ') = TRUE')
                ->setParameter($param, '%' . $values[0] . '%'),

            'not_contains' => $qb
                ->andWhere('ILIKE(' . $path . ', :' . $param . ') = FALSE')
                ->setParameter($param, '%' . $values[0] . '%'),

            'starts_with' => $qb
                ->andWhere('ILIKE(' . $path . ', :' . $param . ') = TRUE')
                ->setParameter($param, $values[0] . '%'),

            'ends_with' => $qb
                ->andWhere('ILIKE(' . $path . ', :' . $param . ') = TRUE')
                ->setParameter($param, '%' . $values[0]),

            'is' => $qb
                ->andWhere($path . ' = :' . $param)
                ->setParameter($param, $field->castValue($values[0])),

            'is_not' => $qb
                ->andWhere($path . ' != :' . $param)
                ->setParameter($param, $field->castValue($values[0])),

            'empty' => 'text' === $field->type
                ? $qb->andWhere($path . " IS NULL OR " . $path . " = ''")
                : $qb->andWhere($path . ' IS NULL'),

            'not_empty' => 'text' === $field->type
                ? $qb->andWhere($path . " IS NOT NULL AND " . $path . " != ''")
                : $qb->andWhere($path . ' IS NOT NULL'),

            'is_any_of' => $qb
                ->andWhere($path . ' IN (:' . $param . ')')
                ->setParameter($param, $field->castValues($values)),

            'is_not_any_of' => $qb
                ->andWhere($path . ' NOT IN (:' . $param . ')')
                ->setParameter($param, $field->castValues($values)),

            'includes_all' => $qb
                ->andWhere('CONTAINS(' . $path . ', :' . $param . ') = TRUE')
                ->setParameter($param, $field->castValues($values)),

            'excludes_all' => $qb
                ->andWhere('CONTAINS(' . $path . ', :' . $param . ') = FALSE')
                ->setParameter($param, $field->castValues($values)),

            default => null,
        };
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
