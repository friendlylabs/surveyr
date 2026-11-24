<?php

namespace Tests\Feature;

use App\Support\DateFormat;
use Carbon\CarbonImmutable;
use Tests\TestCase;

class DateFormatTest extends TestCase
{
    private const NAIROBI = 'Africa/Nairobi';

    public function test_moments_show_in_the_display_zone_with_the_named_formats(): void
    {
        $moment = CarbonImmutable::parse('2026-10-12 21:30:00', 'UTC');

        $this->assertSame('UTC', DateFormat::zone());
        $this->assertSame('12 Oct 2026, 21:30', DateFormat::format($moment, 'datetime'));
        $this->assertSame('13 Oct 2026', DateFormat::format($moment, zone: self::NAIROBI));
        $this->assertSame('00:30', DateFormat::format($moment, 'time', self::NAIROBI));
        $this->assertSame('Tue 13 Oct 2026', DateFormat::format($moment, 'day', self::NAIROBI));
        $this->assertSame('Tuesday, 13 October 2026, 00:30 EAT', DateFormat::format($moment, 'full', self::NAIROBI));
        $this->assertSame('2026/10/13', DateFormat::format($moment, 'Y/m/d', self::NAIROBI));
        // a bare day is a calendar day, not a moment to convert
        $this->assertSame('12 Oct 2026', DateFormat::format('2026-10-12', zone: self::NAIROBI));
        $this->assertNull(DateFormat::format(null));
    }

    public function test_ranges_share_what_both_ends_have_in_common(): void
    {
        $this->assertSame('12 Oct 2026, 09:00 – 17:30', DateFormat::range('2026-10-12 09:00', '2026-10-12 17:30', 'datetime'));
        $this->assertSame('12 Oct 2026, 09:00 – 13 Oct 2026, 17:30', DateFormat::range('2026-10-12 09:00', '2026-10-13 17:30', 'datetime'));
        $this->assertSame('12 – 18 Oct 2026', DateFormat::range('2026-10-12', '2026-10-18'));
        $this->assertSame('28 Sep – 4 Oct 2026', DateFormat::range('2026-09-28', '2026-10-04'));
        $this->assertSame('28 Dec 2026 – 3 Jan 2027', DateFormat::range('2026-12-28', '2027-01-03'));
        $this->assertSame('Mon 12 Oct – Sun 18 Oct 2026', DateFormat::range('2026-10-12', '2026-10-18', 'day'));
        $this->assertSame('12 Oct 2026', DateFormat::range('2026-10-12', '2026-10-12'));
        $this->assertSame('22:00 – 06:00', DateFormat::range('2026-10-12 22:00', '2026-10-13 06:00', 'time'));
        $this->assertSame('From 12 Oct 2026', DateFormat::range('2026-10-12', null));
        $this->assertSame('Until 18 Oct 2026', DateFormat::range(null, '2026-10-18'));
        $this->assertNull(DateFormat::range(null, null));
    }

    public function test_form_values_go_out_in_the_display_zone_and_come_back_in_the_apps(): void
    {
        $moment = CarbonImmutable::parse('2026-10-12 21:30:00', 'UTC');
        $this->assertSame('2026-10-13 00:30', DateFormat::input($moment, 'datetime', self::NAIROBI));
        $this->assertSame('2026-10-13', DateFormat::input($moment, zone: self::NAIROBI));
        // strings already in the posted shape are form values and pass through
        $this->assertSame('09:30', DateFormat::input('09:30', 'time'));
        $this->assertSame('2026-10-12', DateFormat::input('2026-10-12'));

        $this->assertTrue(DateFormat::parse('2026-10-13 00:30', 'datetime', self::NAIROBI)->eq($moment));
        $this->assertSame('2026-10-11 21:00:00 UTC', DateFormat::parse('2026-10-12', zone: self::NAIROBI)->format('Y-m-d H:i:s T'));
        $this->assertSame('09:30', DateFormat::parse('09:30', 'time')->format('H:i'));
        $this->assertNull(DateFormat::parse(''));
    }

    public function test_range_presets_are_whole_days_in_the_display_zone(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-11 22:00:00', 'UTC'));

        $presets = DateFormat::rangePresets(self::NAIROBI);

        $this->assertSame(['2026-10-12', '2026-10-12'], $presets['Today']);
        $this->assertSame(['2026-10-11', '2026-10-11'], $presets['Yesterday']);
        $this->assertSame(['2026-10-06', '2026-10-12'], $presets['Last 7 days']);
        $this->assertSame(['2026-09-01', '2026-09-30'], $presets['Last month']);
        $this->assertSame(['2026-01-01', '2026-10-12'], $presets['This year']);
    }

    public function test_the_date_field_renders_its_inputs_for_the_picker(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-11 22:00:00', 'UTC'));

        $this->blade(
            '<x-form.date id="period" name="from" end-name="to" label="Period" range :value="$from" end-value="2026-10-18" zone="Africa/Nairobi" required />',
            ['from' => CarbonImmutable::parse('2026-10-11 22:00:00', 'UTC')],
        )
            ->assertSee('data-pc-date="date"', false)
            ->assertSee('data-range', false)
            ->assertSee('data-zone="Africa/Nairobi"', false)
            ->assertSee('data-display="j M Y"', false)
            ->assertSee('id="period" name="from" type="text" value="2026-10-12"', false)
            ->assertSee('id="periodEnd" name="to" type="text" value="2026-10-18"', false)
            ->assertSee('&quot;Last 7 days&quot;:[&quot;2026-10-06&quot;,&quot;2026-10-12&quot;]', false)
            ->assertSee('required', false)
            ->assertSee('id="periodError"', false);

        $this->blade('<x-form.date id="opens" name="opens_at" label="Opens" mode="time" value="09:30" format="g:i A" />')
            ->assertSee('data-pc-date="time"', false)
            ->assertSee('data-display="g:i A"', false)
            ->assertSee('value="09:30"', false)
            ->assertDontSee('data-presets', false);
    }
}
