<?php

use App\Support\PlaceFormat;
use SpotCore\Testing\TestCase;

final class PlaceFormatTest extends TestCase
{
    public function testUnknownBudgetIsNotTurnedIntoZero(): void
    {
        $this->assertTrue(PlaceFormat::budget(['budget_min' => null, 'budget_max' => null, 'price_unit' => null]) === null);
    }

    public function testFreeIsAKnownBudget(): void
    {
        $b = PlaceFormat::budget(['budget_min' => '0.00', 'budget_max' => '0.00', 'price_unit' => 'person']);
        $this->assertSame('₱0', $b['amount']);
        $this->assertSame('per person', $b['unit']);
    }

    public function testRangeKeepsItsBillingUnit(): void
    {
        $b = PlaceFormat::budget(['budget_min' => '150.00', 'budget_max' => '300.00', 'price_unit' => 'day_pass']);
        $this->assertSame('₱150–₱300', $b['amount']);
        $this->assertSame('per day pass', $b['unit']);
    }

    public function testUnknownSettingStaysUnknown(): void
    {
        $this->assertTrue(PlaceFormat::setting(null) === null);
        $this->assertSame('Indoor', PlaceFormat::setting('indoor'));
    }
}
