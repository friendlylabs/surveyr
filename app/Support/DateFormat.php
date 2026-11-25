<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

/**
 * DateFormat
 *
 * Every date and time the app shows or takes in, in one place. Named formats keep a moment looking the same on every
 * page (x-form.date shows them in its field and pc-date.js mirrors the PHP characters), and moments are shown in the
 * display zone (DateFormat::zone(); every method also takes a $zone to show them in another).
 *   DateFormat::format($invoice->paid_at)                           12 Oct 2026
 *   DateFormat::format($invoice->paid_at, 'datetime')               12 Oct 2026, 09:30
 *   DateFormat::range($from, $to)                                   12 – 18 Oct 2026
 *   DateFormat::parse($request->input('starts_at'), 'datetime')     x-form.date's local value, in the app's zone
 */
class DateFormat
{
    /**
     * Named display formats (PHP date() characters). Any other format string passed in is used as it is.
     *
     * @var array<string, string>
     */
    public const FORMATS = [
        'date' => 'j M Y',              // 12 Oct 2026
        'time' => 'H:i',                // 09:30
        'datetime' => 'j M Y, H:i',     // 12 Oct 2026, 09:30
        'seconds' => 'j M Y, H:i:s',    // 12 Oct 2026, 09:30:15
        'day' => 'D j M Y',             // Mon 12 Oct 2026
        'long' => 'l, j F Y',           // Monday, 12 October 2026
        'full' => 'l, j F Y, H:i T',    // Monday, 12 October 2026, 09:30 EAT
        'short' => 'j M',               // 12 Oct
        'month' => 'F Y',               // October 2026
    ];

    /**
     * How x-form.date posts each of its modes (and how DateFormat::parse() reads them back).
     *
     * @var array{date: string, time: string, datetime: string}
     */
    public const INPUT = ['date' => 'Y-m-d', 'time' => 'H:i', 'datetime' => 'Y-m-d H:i'];

    /** PHP date() characters that carry a time of day. */
    private const TIME_CHARACTERS = '/[GHghisAauvT]/';

    /**
     * The zone moments are shown and entered in; change this to read a user's or tenant's zone where the app has one.
     */
    public static function zone(): string
    {
        return config('app.timezone');
    }

    /** A named format's PHP pattern; anything that isn't a name is taken to be a pattern already. */
    public static function pattern(string $format): string
    {
        return self::FORMATS[$format] ?? $format;
    }

    /**
     * The value as a moment in the display zone. A bare Y-m-d string is a calendar day, not a moment, so it is taken
     * as that day in the zone rather than converted (a `date` cast is a Carbon: pass the app's zone to show it as is).
     */
    public static function local(CarbonInterface|string|null $value, ?string $zone = null): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        $zone ??= self::zone();

        if (is_string($value) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return CarbonImmutable::createFromFormat('!Y-m-d', $value, $zone);
        }

        return CarbonImmutable::parse($value)->setTimezone($zone);
    }

    /** The value as text in a named format or PHP pattern, in the display zone. */
    public static function format(CarbonInterface|string|null $value, string $format = 'date', ?string $zone = null): ?string
    {
        return self::local($value, $zone)?->translatedFormat(self::pattern($format));
    }

    /**
     * A span between two moments, shortened where both ends share a part:
     *   12 Oct 2026, 09:00 – 17:30     12 – 18 Oct 2026     28 Sep – 4 Oct 2026     12 Oct 2026 – 3 Jan 2027
     * With only one end it reads "From …" or "Until …".
     */
    public static function range(CarbonInterface|string|null $start, CarbonInterface|string|null $end, string $format = 'date', ?string $zone = null, string $separator = ' – '): ?string
    {
        [$start, $end] = [self::local($start, $zone), self::local($end, $zone)];
        $pattern = self::pattern($format);

        if (! $start || ! $end) {
            return match (true) {
                (bool) $start => __('From :date', ['date' => $start->translatedFormat($pattern)]),
                (bool) $end => __('Until :date', ['date' => $end->translatedFormat($pattern)]),
                default => null,
            };
        }

        $full = $end->translatedFormat($pattern);

        if ($start->translatedFormat($pattern) === $full) {
            return $full;
        }

        preg_match(self::TIME_CHARACTERS, $pattern, $match, PREG_OFFSET_CAPTURE);
        $timeAt = $match[0][1] ?? null;

        // same day: the date once, then both times
        if ($timeAt > 0 && $start->isSameDay($end)) {
            $time = substr($pattern, $timeAt);

            return $start->translatedFormat($pattern).$separator.$end->translatedFormat($time);
        }

        if ($timeAt === null && $start->isSameMonth($end) && preg_match('/^([dj])\W/', $pattern, $day)) {
            return $start->translatedFormat($day[1]).$separator.$full;
        }

        if ($timeAt === null && $start->isSameYear($end) && preg_match('/[\s,]*Y$/', $pattern)) {
            return $start->translatedFormat(preg_replace('/[\s,]*Y$/', '', $pattern)).$separator.$full;
        }

        return $start->translatedFormat($pattern).$separator.$full;
    }

    /** A value as x-form.date posts it; a string already in that shape is a form value and is kept as it is. */
    public static function input(CarbonInterface|string|null $value, string $mode = 'date', ?string $zone = null): ?string
    {
        $format = self::INPUT[$mode];

        if (is_string($value) && CarbonImmutable::canBeCreatedFromFormat($value, $format)) {
            return $value;
        }

        return self::local($value, $zone)?->format($format);
    }

    /**
     * Reads x-form.date's value (validate it with `date_format:` first) into the app's zone, ready to store. A date is
     * the start of that day in the display zone; a time of day has no date to convert, so it keeps its clock time.
     */
    public static function parse(?string $value, string $mode = 'date', ?string $zone = null): ?CarbonImmutable
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($mode === 'time') {
            return CarbonImmutable::createFromFormat('!'.self::INPUT['time'], $value, config('app.timezone'));
        }

        return CarbonImmutable::createFromFormat('!'.self::INPUT[$mode], $value, $zone ?? self::zone())->setTimezone(config('app.timezone'));
    }

    /**
     * The shortcuts offered beside a date range picker, as label => [first day, last day] in the display zone.
     *
     * @return array<string, array{0: string, 1: string}>
     */
    public static function rangePresets(?string $zone = null): array
    {
        $today = CarbonImmutable::now($zone ?? self::zone())->startOfDay();
        $day = fn (CarbonInterface $date): string => $date->format(self::INPUT['date']);

        return [
            __('Today') => [$day($today), $day($today)],
            __('Yesterday') => [$day($today->subDay()), $day($today->subDay())],
            __('Last 7 days') => [$day($today->subDays(6)), $day($today)],
            __('Last 30 days') => [$day($today->subDays(29)), $day($today)],
            __('This month') => [$day($today->startOfMonth()), $day($today)],
            __('Last month') => [$day($today->subMonthNoOverflow()->startOfMonth()), $day($today->subMonthNoOverflow()->endOfMonth())],
            __('This year') => [$day($today->startOfYear()), $day($today)],
        ];
    }
}
