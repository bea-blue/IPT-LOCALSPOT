<?php
namespace SpotCore;

/**
 * Rule-based validation, e.g.
 *   new Validator($data, [
 *       'name'        => 'required|max:150',
 *       'category_id' => 'required|integer|exists:categories,id',
 *       'budget_max'  => 'required|numeric|min:0|gte:budget_min',
 *       'status'      => 'required|in:draft,published',
 *       'source_url'  => 'nullable|url',          // http(s) only
 *       'checked_on'  => 'nullable|date',         // YYYY-MM-DD
 *       'budget_max'  => 'nullable|required_with:budget_min|numeric',
 *       'email'       => 'required|email|unique:users,email',
 *       'password_confirmation' => 'required|same:password',
 *   ]);
 */
final class Validator
{
    private array $data;
    private array $rules;
    private array $labels;
    private array $errors = [];

    /** Tables/columns usable in exists: rules (identifiers can't be bound as SQL parameters). */
    private const EXISTS_ALLOWLIST = ['categories.id', 'users.id', 'places.id'];
    /** Tables/columns usable in unique: rules. */
    private const UNIQUE_ALLOWLIST = ['users.email'];

    public function __construct(array $data, array $rules, array $labels = [])
    {
        $this->data = $data;
        $this->rules = $rules;
        $this->labels = $labels;
        $this->run();
    }

    public function passes(): bool { return $this->errors === []; }
    public function fails(): bool { return !$this->passes(); }
    public function errors(): array { return $this->errors; }

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $value = $this->data[$field] ?? null;
            $rules = explode('|', $ruleString);
            $isNumeric = in_array('numeric', $rules, true) || in_array('integer', $rules, true);

            $isEmpty = $value === null || $value === '';
            if ($isEmpty && ($error = $this->requiredWith($field, $rules)) !== null) {
                $this->errors[$field] = $error;
                continue;
            }
            if (in_array('nullable', $rules, true) && $isEmpty) {
                continue;
            }

            foreach ($rules as $rule) {
                $parts = explode(':', $rule, 2);
                $error = $this->check($field, $parts[0], $parts[1] ?? null, $value, $isNumeric);
                if ($error !== null) {
                    $this->errors[$field] = $error;
                    break; // one message per field
                }
            }
        }
    }

    private function label(string $field): string
    {
        return $this->labels[$field] ?? ucfirst(str_replace('_', ' ', $field));
    }

    private function check(string $field, string $rule, ?string $arg, mixed $value, bool $isNumeric): ?string
    {
        $label = $this->label($field);
        $str = is_scalar($value) ? (string) $value : '';

        switch ($rule) {
            case 'required':
                return ($value === null || $str === '') ? "{$label} is required." : null;
            case 'nullable':
                return null;
            case 'email':
                return filter_var($str, FILTER_VALIDATE_EMAIL) ? null : "{$label} must be a valid email address.";
            case 'numeric':
                return is_numeric($str) ? null : "{$label} must be a number.";
            case 'integer':
                return preg_match('/^-?[0-9]+$/', $str) ? null : "{$label} must be a whole number.";
            case 'min':
                if ($isNumeric) {
                    return (is_numeric($str) && (float) $str >= (float) $arg) ? null : "{$label} must be at least {$arg}.";
                }
                return mb_strlen($str) >= (int) $arg ? null : "{$label} must be at least {$arg} characters.";
            case 'max':
                if ($isNumeric) {
                    return (is_numeric($str) && (float) $str <= (float) $arg) ? null : "{$label} must be at most " . number_format((float) $arg) . '.';
                }
                return mb_strlen($str) <= (int) $arg ? null : "{$label} must be at most {$arg} characters.";
            case 'in':
                return in_array($str, explode(',', (string) $arg), true) ? null : "{$label} has an invalid value.";
            case 'gte':
                $other = $this->data[$arg] ?? null;
                if (!is_numeric($str) || !is_numeric($other)) {
                    return null; // the numeric rule reports bad numbers
                }
                return (float) $str >= (float) $other ? null : "{$label} must be greater than or equal to " . strtolower($this->label($arg)) . '.';
            case 'required_with':
                return null; // handled before the other rules, see requiredWith()
            case 'url':
                $ok = filter_var($str, FILTER_VALIDATE_URL) !== false
                    && in_array(strtolower((string) parse_url($str, PHP_URL_SCHEME)), ['http', 'https'], true);
                return $ok ? null : "{$label} must be a full web address starting with http:// or https://.";
            case 'date':
                $d = \DateTime::createFromFormat('!Y-m-d', $str);
                return ($d !== false && $d->format('Y-m-d') === $str) ? null : "{$label} must be a date (YYYY-MM-DD).";
            case 'exists':
                return $this->exists((string) $arg, $str) ? null : "The selected {$label} does not exist.";
            case 'same':
                $other = $this->data[$arg] ?? null;
                return (is_scalar($other) && hash_equals((string) $other, $str)) ? null : "{$label} does not match " . strtolower($this->label((string) $arg)) . '.';
            case 'unique':
                return $this->unique((string) $arg, $str) ? null : "{$label} is already registered.";
            default:
                throw new \InvalidArgumentException("Unknown validation rule [{$rule}].");
        }
    }

    /** required_with:other — this field must be filled when the other field is filled. */
    private function requiredWith(string $field, array $rules): ?string
    {
        foreach ($rules as $rule) {
            if (strncmp($rule, 'required_with:', 14) === 0) {
                $other = substr($rule, 14);
                $otherValue = $this->data[$other] ?? null;
                if ($otherValue !== null && $otherValue !== '') {
                    return $this->label($field) . ' is required when ' . strtolower($this->label($other)) . ' is filled in.';
                }
            }
        }
        return null;
    }

    /** exists:table,column — checks a foreign key really points at a row. */
    private function exists(string $arg, string $value): bool
    {
        [$table, $column] = array_pad(explode(',', $arg), 2, 'id');
        if (!in_array("{$table}.{$column}", self::EXISTS_ALLOWLIST, true)) {
            throw new \InvalidArgumentException("exists:{$table},{$column} is not allowlisted.");
        }
        if ($value === '') {
            return false;
        }
        $count = Database::value("SELECT COUNT(*) FROM {$table} WHERE {$column} = :v", ['v' => $value]);
        return (int) $count > 0;
    }

    /** unique:table,column — no other row already has this value (compared in lower case). */
    private function unique(string $arg, string $value): bool
    {
        [$table, $column] = array_pad(explode(',', $arg), 2, 'id');
        if (!in_array("{$table}.{$column}", self::UNIQUE_ALLOWLIST, true)) {
            throw new \InvalidArgumentException("unique:{$table},{$column} is not allowlisted.");
        }
        $count = Database::value("SELECT COUNT(*) FROM {$table} WHERE LOWER({$column}) = :v", ['v' => strtolower($value)]);
        return (int) $count === 0;
    }
}
