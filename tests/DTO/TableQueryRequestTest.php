<?php

declare(strict_types=1);

namespace Letkode\EntityTraitsBundle\Tests\DTO;

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
    }
}
