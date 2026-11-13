<?php

namespace Devletes\ChronoPicker\Tests\Feature;

use Devletes\ChronoPicker\Infolists\Components\RangeEntry;
use Devletes\ChronoPicker\Tests\Fixtures\Models\Booking;
use Devletes\ChronoPicker\Tests\Fixtures\PickerComponent;
use Devletes\ChronoPicker\Tests\TestCase;
use Filament\Schemas\Schema;
use LogicException;

class RangeEntryTest extends TestCase
{
    /** The entry inside a schema whose state is the given record or array. */
    protected function entry(RangeEntry $entry, mixed $state): RangeEntry
    {
        $schema = Schema::make(new PickerComponent)->components([$entry]);
        $entry->container($schema);

        $state instanceof Booking ? $schema->record($state) : $schema->constantState($state);

        return $entry;
    }

    public function test_two_columns_are_shown_as_one_line(): void
    {
        $booking = new Booking(['starts_on' => '2026-01-01', 'ends_on' => '2026-01-20']);

        $entry = $this->entry(RangeEntry::make('dates')->fromColumns(start: 'starts_on', end: 'ends_on'), $booking);

        $this->assertSame('Jan 1, 2026 – Jan 20, 2026', $entry->getConstantState());
    }

    public function test_a_json_column_is_read_by_its_keys(): void
    {
        $booking = new Booking(['stay' => ['from' => '2026-12-24', 'to' => '2027-01-02']]);

        $entry = $this->entry(RangeEntry::make('stay')->fromJson(start: 'from', end: 'to'), $booking);

        $this->assertSame('Dec 24, 2026 – Jan 2, 2027', $entry->getConstantState());
    }

    public function test_a_json_string_is_read_too(): void
    {
        $entry = $this->entry(
            RangeEntry::make('stay')->fromJson(),
            ['stay' => json_encode(['start' => '2026-03', 'end' => '2026-06'])],
        )->displayFormat('M Y');

        $this->assertSame('Mar 2026 – Jun 2026', $entry->getConstantState());
    }

    public function test_months_years_and_times_take_the_display_format(): void
    {
        $years = $this->entry(RangeEntry::make('service')->fromColumns('first', 'last')->displayFormat('Y'), ['first' => '2018', 'last' => '2026']);
        $times = $this->entry(RangeEntry::make('shift')->fromColumns('in', 'out')->displayFormat('g:i A'), ['in' => '07:30', 'out' => '16:00:00']);

        $this->assertSame('2018 – 2026', $years->getConstantState());
        $this->assertSame('7:30 AM – 4:00 PM', $times->getConstantState());
    }

    public function test_the_separator_can_be_changed(): void
    {
        $entry = $this->entry(
            RangeEntry::make('dates')->fromColumns('a', 'b')->displayFormat('M j')->rangeSeparator(' to '),
            ['a' => '2026-01-01', 'b' => '2026-01-20'],
        );

        $this->assertSame('Jan 1 to Jan 20', $entry->getConstantState());
    }

    public function test_an_open_end_keeps_the_separator_and_an_empty_range_is_null(): void
    {
        $make = fn (array $state): mixed => $this->entry(RangeEntry::make('dates')->fromColumns('a', 'b')->displayFormat('M Y'), $state)->getConstantState();

        $this->assertSame('Jan 2026 –', $make(['a' => '2026-01', 'b' => null]));
        $this->assertSame('– Jun 2026', $make(['a' => null, 'b' => '2026-06']));
        $this->assertNull($make(['a' => null, 'b' => null]));
    }

    public function test_an_entry_that_says_nothing_about_storage_throws(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('fromColumns');

        $this->entry(RangeEntry::make('dates'), [])->getConstantState();
    }
}
