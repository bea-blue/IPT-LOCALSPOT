<?php

use App\Support\PlaceFilters;
use SpotCore\Testing\TestCase;

final class PlaceFiltersTest extends TestCase
{
    public function testValidFiltersAreTyped(): void
    {
        $f = PlaceFilters::fromQuery(['q' => ' café ', 'category' => '2', 'max_budget' => '150'], [1, 2]);
        $this->assertSame(['keyword' => 'café', 'category_id' => 2, 'max_budget' => 150.0], $f->toArray());
        $this->assertSame([], $f->errors);
    }

    public function testInvalidValuesAreDroppedWithMessages(): void
    {
        $f = PlaceFilters::fromQuery(['category' => '1 OR 1=1', 'max_budget' => '-10'], [1, 2]);
        $this->assertSame(['keyword' => null, 'category_id' => null, 'max_budget' => null], $f->toArray());
        $this->assertSame(2, count($f->errors));
    }

    public function testUnknownCategoryIsRejected(): void
    {
        $f = PlaceFilters::fromQuery(['category' => '99'], [1, 2]);
        $this->assertSame(null, $f->categoryId);
    }

    public function testArrayInputIsIgnored(): void
    {
        $f = PlaceFilters::fromQuery(['q' => ['x'], 'category' => ['1']], [1]);
        $this->assertFalse($f->isActive());
    }
}
