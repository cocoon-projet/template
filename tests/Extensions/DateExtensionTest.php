<?php

namespace Tests\Extensions;

use PHPUnit\Framework\TestCase;
use Cocoon\View\Extensions\DateExtension;
use Carbon\Carbon;

class DateExtensionTest extends TestCase
{
    private $extension;

    protected function setUp(): void
    {
        $this->extension = new DateExtension();
    }

    public function testTimeagoFilter()
    {
        $filters = $this->extension->getFilters();
        $this->assertArrayHasKey('timeago', $filters);

        $date = Carbon::now()->subDays(2);
        $result = $filters['timeago']($date);
        $this->assertIsString($result);
    }

    public function testAgeFilter()
    {
        $filters = $this->extension->getFilters();
        $this->assertArrayHasKey('age', $filters);

        $date = Carbon::now()->subYears(25);
        $result = $filters['age']($date);
        $this->assertEquals(25, $result);
    }

    public function testCalendarFilter()
    {
        $filters = $this->extension->getFilters();
        $this->assertArrayHasKey('calendar', $filters);

        $date = Carbon::create(2024, 1, 1);
        $result = $filters['calendar']($date);
        $this->assertIsString($result);
    }

    public function testDurationFilter()
    {
        $filters = $this->extension->getFilters();
        $this->assertArrayHasKey('duration', $filters);

        $result = $filters['duration'](7200);
        $this->assertIsString($result);

        $result = $filters['duration'](5400);
        $this->assertIsString($result);
    }

    public function testIsFutureFunction()
    {
        $functions = $this->extension->getFunctions();
        $this->assertArrayHasKey('is_future', $functions);

        $futureDate = Carbon::now()->addDays(1);
        $result = $functions['is_future']($futureDate);
        $this->assertTrue($result);

        $pastDate = Carbon::now()->subDays(1);
        $result = $functions['is_future']($pastDate);
        $this->assertFalse($result);
    }

    public function testIsPastFunction()
    {
        $functions = $this->extension->getFunctions();
        $this->assertArrayHasKey('is_past', $functions);

        $pastDate = Carbon::now()->subDays(1);
        $result = $functions['is_past']($pastDate);
        $this->assertTrue($result);

        $futureDate = Carbon::now()->addDays(1);
        $result = $functions['is_past']($futureDate);
        $this->assertFalse($result);
    }

    public function testIsTodayFunction()
    {
        $functions = $this->extension->getFunctions();
        $this->assertArrayHasKey('is_today', $functions);

        $today = Carbon::now();
        $result = $functions['is_today']($today);
        $this->assertTrue($result);

        $yesterday = Carbon::now()->subDays(1);
        $result = $functions['is_today']($yesterday);
        $this->assertFalse($result);
    }

    public function testIsWeekendFunction()
    {
        $functions = $this->extension->getFunctions();
        $this->assertArrayHasKey('is_weekend', $functions);

        $saturday = Carbon::create(2024, 1, 6); // Samedi
        $result = $functions['is_weekend']($saturday);
        $this->assertTrue($result);

        $monday = Carbon::create(2024, 1, 1); // Lundi
        $result = $functions['is_weekend']($monday);
        $this->assertFalse($result);
    }
} 