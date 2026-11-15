<?php

use SpotCore\Validator;
use SpotCore\Testing\TestCase;

final class ValidatorTest extends TestCase
{
    private array $rules = [
        'name'        => 'required|max:150',
        'category_id' => 'required|integer|exists:categories,id',
        'budget_min'  => 'required|numeric|min:0',
        'budget_max'  => 'required|numeric|min:0|gte:budget_min',
        'status'      => 'required|in:draft,published',
        'hours_note'  => 'nullable|max:255',
    ];

    private function valid(): array
    {
        return ['name' => 'Test Café', 'category_id' => '1', 'budget_min' => '0', 'budget_max' => '150', 'status' => 'draft', 'hours_note' => ''];
    }

    public function testValidDataPassesAndZeroBudgetIsAllowed(): void
    {
        $v = new Validator($this->valid(), $this->rules);
        $this->assertTrue($v->passes(), json_encode($v->errors()));
    }

    public function testMissingFieldsFail(): void
    {
        $v = new Validator([], $this->rules);
        foreach (['name', 'category_id', 'budget_min', 'budget_max', 'status'] as $field) {
            $this->assertTrue(isset($v->errors()[$field]), "missing error for {$field}");
        }
        $this->assertFalse(isset($v->errors()['hours_note']), 'nullable field should not error');
    }

    public function testNegativeBudgetAndReversedRangeFail(): void
    {
        $v = new Validator(['budget_min' => '-5', 'budget_max' => '10'] + $this->valid(), $this->rules);
        $this->assertTrue(isset($v->errors()['budget_min']));

        $v = new Validator(['budget_min' => '300', 'budget_max' => '100'] + $this->valid(), $this->rules);
        $this->assertStringContainsString('greater than or equal', $v->errors()['budget_max'] ?? '');
    }

    public function testCategoryMustExist(): void
    {
        $v = new Validator(['category_id' => '99'] + $this->valid(), $this->rules);
        $this->assertTrue(isset($v->errors()['category_id']));
    }

    public function testEnumAllowlist(): void
    {
        $v = new Validator(['status' => 'archived'] + $this->valid(), $this->rules);
        $this->assertTrue(isset($v->errors()['status']));
    }

    public function testTooLongName(): void
    {
        $v = new Validator(['name' => str_repeat('a', 151)] + $this->valid(), $this->rules);
        $this->assertTrue(isset($v->errors()['name']));
    }

    public function testUnknownBudgetMayBeEmptyButNotHalfFilled(): void
    {
        $rules = [
            'budget_min' => 'nullable|required_with:budget_max|numeric|min:0',
            'budget_max' => 'nullable|required_with:budget_min|numeric|min:0|gte:budget_min',
            'price_unit' => 'nullable|required_with:budget_min|in:person,meal,hour,day_pass',
        ];
        $empty = new Validator(['budget_min' => '', 'budget_max' => '', 'price_unit' => ''], $rules);
        $this->assertTrue($empty->passes(), 'all empty = budget not confirmed');

        $half = new Validator(['budget_min' => '100', 'budget_max' => '', 'price_unit' => ''], $rules);
        $this->assertTrue(isset($half->errors()['budget_max']), 'max required when min given');
        $this->assertTrue(isset($half->errors()['price_unit']), 'unit required when a price is given');

        $full = new Validator(['budget_min' => '100', 'budget_max' => '200', 'price_unit' => 'day_pass'], $rules);
        $this->assertTrue($full->passes(), json_encode($full->errors()));
    }

    public function testUrlRuleAcceptsOnlyHttpAndHttps(): void
    {
        $rules = ['source_url' => 'nullable|url'];
        $this->assertTrue((new Validator(['source_url' => 'https://workspace45.com/'], $rules))->passes());
        $this->assertFalse((new Validator(['source_url' => 'javascript:alert(1)'], $rules))->passes());
        $this->assertFalse((new Validator(['source_url' => 'ftp://example.com/file'], $rules))->passes());
        $this->assertTrue((new Validator(['source_url' => ''], $rules))->passes(), 'empty is allowed');
    }

    public function testDateRuleRejectsImpossibleDates(): void
    {
        $rules = ['checked_on' => 'nullable|date'];
        $this->assertTrue((new Validator(['checked_on' => '2026-11-15'], $rules))->passes());
        $this->assertFalse((new Validator(['checked_on' => '2026-02-30'], $rules))->passes());
        $this->assertFalse((new Validator(['checked_on' => '15/11/2026'], $rules))->passes());
    }

    public function testSameRuleChecksPasswordConfirmation(): void
    {
        $rules = ['password' => 'required|min:8', 'password_confirmation' => 'required|same:password'];
        $this->assertTrue((new Validator(['password' => 'secret123', 'password_confirmation' => 'secret123'], $rules))->passes());
        $v = new Validator(['password' => 'secret123', 'password_confirmation' => 'secret124'], $rules);
        $this->assertTrue(isset($v->errors()['password_confirmation']));
    }

    public function testUniqueRuleRejectsTakenEmailInAnyCase(): void
    {
        $rules = ['email' => 'required|email|unique:users,email'];
        $this->assertFalse((new Validator(['email' => 'ADMIN@localspot.test'], $rules))->passes(), 'taken email, different case');
        $this->assertTrue((new Validator(['email' => 'bea@example.com'], $rules))->passes());
        $this->assertThrows(\InvalidArgumentException::class, fn () => new Validator(['name' => 'x'], ['name' => 'unique:users,name']), 'not allowlisted');
    }
}
