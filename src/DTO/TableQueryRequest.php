<?php

declare(strict_types=1);

namespace Letkode\EntityTraitsBundle\DTO;

final readonly class TableQueryRequest
{
    public function __construct(
        public int $page = 1,
        public int $perPage = 20,
        public string|null $q = null,
        public string|null $sort = null,
        public string $dir = 'asc',
    ) {
    }

    public static function fromArray(array $params): self
    {
        $dir = strtolower((string) ($params['dir'] ?? 'asc'));

        return new self(
            page: max(1, (int) ($params['page'] ?? 1)),
            perPage: min(100, max(1, (int) ($params['per_page'] ?? 20))),
            q: isset($params['q']) && '' !== $params['q'] ? (string) $params['q'] : null,
            sort: isset($params['sort']) && '' !== $params['sort'] ? (string) $params['sort'] : null,
            dir: \in_array($dir, ['asc', 'desc'], true) ? $dir : 'asc',
        );
    }
}
