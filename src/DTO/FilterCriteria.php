<?php

declare(strict_types=1);

namespace Letkode\EntityTraitsBundle\DTO;

final readonly class FilterCriteria
{
    /** @param list<string> $values */
    public function __construct(
        public string $field,
        public string $operator,
        public array $values = [],
    ) {
    }
}
