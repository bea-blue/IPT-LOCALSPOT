<?php
namespace App\Support;

/**
 * Turns the explore page's query string (?q=&category=&max_budget=) into safe,
 * typed filters. Invalid values are dropped and explained to the visitor
 * instead of being passed to SQL.
 */
final class PlaceFilters
{
    public ?string $keyword = null;
    public ?int $categoryId = null;
    public ?float $maxBudget = null;
    /** @var string[] */
    public array $errors = [];

    /** @param int[] $validCategoryIds */
    public static function fromQuery(array $query, array $validCategoryIds): self
    {
        $f = new self();

        $keyword = is_string($query['q'] ?? null) ? trim($query['q']) : '';
        if ($keyword !== '') {
            if (mb_strlen($keyword) > 100) {
                $f->errors[] = 'Search text must be 100 characters or fewer.';
            } else {
                $f->keyword = $keyword;
            }
        }

        $category = is_string($query['category'] ?? null) ? trim($query['category']) : '';
        if ($category !== '') {
            if (ctype_digit($category) && in_array((int) $category, $validCategoryIds, true)) {
                $f->categoryId = (int) $category;
            } else {
                $f->errors[] = 'That category does not exist, so it was ignored.';
            }
        }

        $budget = is_string($query['max_budget'] ?? null) ? trim($query['max_budget']) : '';
        if ($budget !== '') {
            if (is_numeric($budget) && (float) $budget >= 0 && (float) $budget <= 100000) {
                $f->maxBudget = (float) $budget;
            } else {
                $f->errors[] = 'Budget must be a number from 0 to 100,000, so it was ignored.';
            }
        }

        return $f;
    }

    public function toArray(): array
    {
        return ['keyword' => $this->keyword, 'category_id' => $this->categoryId, 'max_budget' => $this->maxBudget];
    }

    public function isActive(): bool
    {
        return $this->keyword !== null || $this->categoryId !== null || $this->maxBudget !== null;
    }
}
