<?php

namespace Tests\Extensions;

use PHPUnit\Framework\TestCase;
use Cocoon\View\Extensions\ArrayExtension;

class ArrayExtensionTest extends TestCase
{
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new ArrayExtension();
    }

    public function testSortFilter()
    {
        $filters = $this->extension->getFilters();
        $this->assertArrayHasKey('sort', $filters);

        $array = [3, 1, 4, 1, 5];
        $result = $filters['sort']($array);
        $this->assertEquals([1, 1, 3, 4, 5], $result);

        $array = [
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25],
            ['name' => 'Bob', 'age' => 35]
        ];
        $result = $filters['sort']($array, 'age');
        $this->assertEquals([
            ['name' => 'Jane', 'age' => 25],
            ['name' => 'John', 'age' => 30],
            ['name' => 'Bob', 'age' => 35]
        ], $result);
    }

    public function testFilterFilter()
    {
        $filters = $this->extension->getFilters();
        $this->assertArrayHasKey('filter', $filters);

        $array = [
            ['name' => 'John', 'status' => 'active'],
            ['name' => 'Jane', 'status' => 'inactive'],
            ['name' => 'Bob', 'status' => 'active']
        ];

        $result = $filters['filter']($array, 'status', 'active');
        $this->assertEquals([
            ['name' => 'John', 'status' => 'active'],
            ['name' => 'Bob', 'status' => 'active']
        ], array_values($result));
    }

    public function testMapFilter()
    {
        $filters = $this->extension->getFilters();
        $this->assertArrayHasKey('map', $filters);

        $array = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane'],
            ['id' => 3, 'name' => 'Bob']
        ];

        $result = $filters['map']($array, 'name');
        $this->assertEquals(['John', 'Jane', 'Bob'], $result);
    }

    public function testUniqueFilter()
    {
        $filters = $this->extension->getFilters();
        $this->assertArrayHasKey('unique', $filters);

        $array = [1, 2, 1, 3, 2, 4];
        $result = $filters['unique']($array);
        $this->assertEquals([1, 2, 3, 4], array_values($result));

        $array = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane']
        ];
        $result = $filters['unique']($array, 'id');
        $this->assertEquals([
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane']
        ], array_values($result));
    }

    public function testFirstFilter()
    {
        $filters = $this->extension->getFilters();
        $this->assertArrayHasKey('first', $filters);

        $array = [1, 2, 3, 4, 5];
        $result = $filters['first']($array);
        $this->assertEquals(1, $result);
    }

    public function testLastFilter()
    {
        $filters = $this->extension->getFilters();
        $this->assertArrayHasKey('last', $filters);

        $array = [1, 2, 3, 4, 5];
        $result = $filters['last']($array);
        $this->assertEquals(5, $result);
    }

    public function testArrayContainsFunction()
    {
        $functions = $this->extension->getFunctions();
        $this->assertArrayHasKey('array_contains', $functions);

        $array = [1, 2, 3, 4, 5];
        $result = $functions['array_contains']($array, 3);
        $this->assertTrue($result);

        $result = $functions['array_contains']($array, 6);
        $this->assertFalse($result);

        $array = [
            ['id' => 1, 'name' => 'John'],
            ['id' => 2, 'name' => 'Jane']
        ];
        $result = $functions['array_contains']($array, ['id' => 1]);
        $this->assertTrue($result);

        $result = $functions['array_contains']($array, ['id' => 3]);
        $this->assertFalse($result);
    }

    public function testArrayKeysFunction()
    {
        $functions = $this->extension->getFunctions();
        $this->assertArrayHasKey('array_keys', $functions);

        $array = ['a' => 1, 'b' => 2, 'c' => 3];
        $result = $functions['array_keys']($array);
        $this->assertEquals(['a', 'b', 'c'], $result);
    }

    public function testArrayValuesFunction()
    {
        $functions = $this->extension->getFunctions();
        $this->assertArrayHasKey('array_values', $functions);

        $array = ['a' => 1, 'b' => 2, 'c' => 3];
        $result = $functions['array_values']($array);
        $this->assertEquals([1, 2, 3], $result);
    }

    public function testArraySumFunction()
    {
        $functions = $this->extension->getFunctions();
        $this->assertArrayHasKey('array_sum', $functions);

        $array = [1, 2, 3, 4, 5];
        $result = $functions['array_sum']($array);
        $this->assertEquals(15, $result);

        $array = [
            ['id' => 1, 'value' => 10],
            ['id' => 2, 'value' => 20],
            ['id' => 3, 'value' => 30]
        ];
        $result = $functions['array_sum']($array, 'value');
        $this->assertEquals(60, $result);
    }

    public function testArrayAvgFunction()
    {
        $functions = $this->extension->getFunctions();
        $this->assertArrayHasKey('array_avg', $functions);

        $array = [1, 2, 3, 4, 5];
        $result = $functions['array_avg']($array);
        $this->assertEquals(3, $result);

        $array = [
            ['id' => 1, 'value' => 10],
            ['id' => 2, 'value' => 20],
            ['id' => 3, 'value' => 30]
        ];
        $result = $functions['array_avg']($array, 'value');
        $this->assertEquals(20, $result);

        $result = $functions['array_avg']([]);
        $this->assertEquals(0, $result);
    }
} 