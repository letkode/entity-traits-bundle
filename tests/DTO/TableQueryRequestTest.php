<?php

declare(strict_types=1);

namespace Letkode\EntityTraitsBundle\Tests\DTO;

use Letkode\EntityTraitsBundle\DTO\FilterCriteria;
use Letkode\EntityTraitsBundle\DTO\TableQueryRequest;
use PHPUnit\Framework\TestCase;

final class TableQueryRequestTest extends TestCase
{
    public function testDefaultValues(): void
    {
        $request = new TableQueryRequest();

        self::assertSame(1, $request->page);
        self::assertSame(20, $request->perPage);
        self::assertNull($request->q);
        self::assertNull($request->sort);
        self::assertSame('asc', $request->dir);
        self::assertSame([], $request->filters);
    }

    public function testFromArrayWithAllParams(): void
    {
        $request = TableQueryRequest::fromArray([
            'page' => '3',
            'per_page' => '50',
            'q' => 'search term',
            'sort' => 'name',
            'dir' => 'DESC',
        ]);

        self::assertSame(3, $request->page);
        self::assertSame(50, $request->perPage);
        self::assertSame('search term', $request->q);
        self::assertSame('name', $request->sort);
        self::assertSame('desc', $request->dir);
    }

    public function testFromArrayClampsPageToMinimumOne(): void
    {
        $request = TableQueryRequest::fromArray(['page' => '-5']);

        self::assertSame(1, $request->page);
    }

    public function testFromArrayClampsPerPageToMaximum100(): void
    {
        $request = TableQueryRequest::fromArray(['per_page' => '999']);

        self::assertSame(100, $request->perPage);
    }

    public function testFromArrayClampsPerPageToMinimumOne(): void
    {
        $request = TableQueryRequest::fromArray(['per_page' => '0']);

        self::assertSame(1, $request->perPage);
    }

    public function testFromArrayReturnsNullQForEmptyString(): void
    {
        $request = TableQueryRequest::fromArray(['q' => '']);

        self::assertNull($request->q);
    }

    public function testFromArrayReturnsNullSortForEmptyString(): void
    {
        $request = TableQueryRequest::fromArray(['sort' => '']);

        self::assertNull($request->sort);
    }

    public function testFromArrayDefaultsInvalidDirToAsc(): void
    {
        $request = TableQueryRequest::fromArray(['dir' => 'random']);

        self::assertSame('asc', $request->dir);
    }

    public function testFromArrayAllowsDesc(): void
    {
        $request = TableQueryRequest::fromArray(['dir' => 'desc']);

        self::assertSame('desc', $request->dir);
    }

    public function testFromArrayWithEmptyParams(): void
    {
        $request = TableQueryRequest::fromArray([]);

        self::assertSame(1, $request->page);
        self::assertSame(20, $request->perPage);
        self::assertNull($request->q);
        self::assertNull($request->sort);
        self::assertSame('asc', $request->dir);
        self::assertSame([], $request->filters);
    }

    public function testFromArrayParsesPerPageCamelCase(): void
    {
        $request = TableQueryRequest::fromArray(['perPage' => '50']);

        self::assertSame(50, $request->perPage);
    }

    public function testFromArrayParsesFiltersWithSingleValue(): void
    {
        $request = TableQueryRequest::fromArray([
            'filters' => [
                'firstName' => ['op' => 'is', 'value' => ['PRUEBA']],
            ],
        ]);

        self::assertCount(1, $request->filters);
        self::assertInstanceOf(FilterCriteria::class, $request->filters[0]);
        self::assertSame('firstName', $request->filters[0]->field);
        self::assertSame('is', $request->filters[0]->operator);
        self::assertSame(['PRUEBA'], $request->filters[0]->values);
    }

    public function testFromArrayParsesFiltersWithMultipleValues(): void
    {
        $request = TableQueryRequest::fromArray([
            'filters' => [
                'rolePolicy' => ['op' => 'is_any_of', 'value' => ['uuid-1', 'uuid-2']],
            ],
        ]);

        self::assertCount(1, $request->filters);
        self::assertSame('is_any_of', $request->filters[0]->operator);
        self::assertSame(['uuid-1', 'uuid-2'], $request->filters[0]->values);
    }

    public function testFromArrayParsesValuelessOperator(): void
    {
        $request = TableQueryRequest::fromArray([
            'filters' => [
                'email' => ['op' => 'empty'],
            ],
        ]);

        self::assertCount(1, $request->filters);
        self::assertSame('empty', $request->filters[0]->operator);
        self::assertSame([], $request->filters[0]->values);
    }

    public function testFromArrayIgnoresFiltersWithoutOp(): void
    {
        $request = TableQueryRequest::fromArray([
            'filters' => [
                'firstName' => ['value' => ['foo']],
            ],
        ]);

        self::assertSame([], $request->filters);
    }

    public function testFromArrayHandlesMultipleFilters(): void
    {
        $request = TableQueryRequest::fromArray([
            'filters' => [
                'firstName' => ['op' => 'starts_with', 'value' => ['An']],
                'enabled'   => ['op' => 'is', 'value' => ['true']],
            ],
        ]);

        self::assertCount(2, $request->filters);
    }

    public function testFromArrayHandlesAbsentFiltersKey(): void
    {
        $request = TableQueryRequest::fromArray(['page' => '1']);

        self::assertSame([], $request->filters);
    }
}
