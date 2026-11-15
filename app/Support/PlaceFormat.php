<?php
namespace App\Support;

use App\Models\Place;

/** Turns stored place values into display text. Unknown values stay unknown. */
final class PlaceFormat
{
    /**
     * Price text for a place, or null when there is no budget figure.
     * "estimate" is true for planning estimates, which are shown with "Est.".
     * @return array{amount:string, unit:?string, estimate:bool}|null
     */
    public static function budget(array $place): ?array
    {
        if ($place['budget_min'] === null || $place['budget_min'] === '') {
            return null;
        }
        $min = (float) $place['budget_min'];
        $max = ($place['budget_max'] === null || $place['budget_max'] === '') ? $min : (float) $place['budget_max'];
        $amount = $min === $max ? peso($min) : peso($min) . '–' . peso($max);

        $unit = Place::PRICE_UNITS[$place['price_unit'] ?? ''] ?? null;
        return ['amount' => $amount, 'unit' => $unit, 'estimate' => !empty($place['budget_is_estimate'])];
    }

    /** Text for a place with no budget figure. */
    public static function noBudgetLabel(array $place): string
    {
        return !empty($place['budget_note']) && stripos((string) $place['budget_note'], 'awaiting') === 0
            ? 'Budget awaiting verification'
            : 'Budget not confirmed';
    }

    /** "Indoor", "Outdoor", "Indoor & outdoor", or null when not confirmed. */
    public static function setting(?string $setting): ?string
    {
        return Place::SETTINGS[$setting ?? ''] ?? null;
    }

    /** "Nov 15, 2026" from a YYYY-MM-DD value, or null. */
    public static function checkedOn(?string $date): ?string
    {
        if ($date === null || $date === '') {
            return null;
        }
        $d = \DateTime::createFromFormat('!Y-m-d', substr($date, 0, 10));
        return $d ? $d->format('M j, Y') : null;
    }

    /** Short hostname for a source link, e.g. "workspace45.com". */
    public static function sourceHost(?string $url): ?string
    {
        if ($url === null || $url === '') {
            return null;
        }
        $host = parse_url($url, PHP_URL_HOST);
        return is_string($host) ? preg_replace('/^www\./', '', $host) : null;
    }
}
