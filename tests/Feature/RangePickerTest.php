<?php

namespace Devletes\ChronoPicker\Tests\Feature;

use Devletes\ChronoPicker\Enums\RangeLayout;
use Devletes\ChronoPicker\Enums\RangeStorage;
use Devletes\ChronoPicker\Forms\Components\DateRangePicker;
use Devletes\ChronoPicker\Forms\Components\MonthRangePicker;
use Devletes\ChronoPicker\Forms\Components\TimeRangePicker;
use Devletes\ChronoPicker\Forms\Components\YearRangePicker;
use Devletes\ChronoPicker\Tests\Fixtures\Models\Booking;
use Devletes\ChronoPicker\Tests\Fixtures\RangePickerComponent;
use Devletes\ChronoPicker\Tests\TestCase;
use Filament\Schemas\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema as DatabaseSchema;
use Livewire\Livewire;
use LogicException;

class RangePickerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseSchema::create('bookings', function (Blueprint $table): void {
            $table->id();
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->json('stay')->nullable();
            $table->string('first_year')->nullable();
            $table->string('last_year')->nullable();
            $table->string('shift_starts')->nullable();
            $table->string('shift_ends')->nullable();
            $table->timestamps();
        });

        DatabaseSchema::create('legs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('booking_id');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->timestamps();
        });
    }

    // Two columns

    public function test_two_columns_are_read_into_the_field_and_written_back(): void
    {
        $booking = Booking::create(['starts_on' => '2026-01-01', 'ends_on' => '2026-01-20']);

        Livewire::test(RangePickerComponent::class, ['record' => $booking])
            ->assertSet('data.dates', ['start' => '2026-01-01 00:00:00', 'end' => '2026-01-20 00:00:00'])
            ->set('data.dates', ['start' => '2026-02-03 00:00:00', 'end' => '2026-02-10 00:00:00'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('saved.starts_on', '2026-02-03')
            ->assertSet('saved.ends_on', '2026-02-10');

        $booking->refresh();

        $this->assertSame('2026-02-03', substr((string) $booking->starts_on, 0, 10));
        $this->assertSame('2026-02-10', substr((string) $booking->ends_on, 0, 10));
    }

    public function test_the_field_itself_is_not_saved_when_stored_in_columns(): void
    {
        Livewire::test(RangePickerComponent::class, ['state' => ['starts_on' => '2026-01-01', 'ends_on' => '2026-01-02']])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('saved.starts_on', '2026-01-01')
            ->assertSet('saved.ends_on', '2026-01-02')
            ->assertNotSet('saved', fn (array $saved): bool => array_key_exists('dates', $saved));
    }

    public function test_two_columns_work_on_plain_livewire_state(): void
    {
        Livewire::test(RangePickerComponent::class, ['state' => ['starts_on' => '2026-05-01', 'ends_on' => '2026-05-31']])
            ->assertSet('data.dates', ['start' => '2026-05-01 00:00:00', 'end' => '2026-05-31 00:00:00'])
            ->call('save')
            ->assertSet('saved.starts_on', '2026-05-01')
            ->assertSet('saved.ends_on', '2026-05-31');
    }

    public function test_an_empty_range_saves_two_nulls(): void
    {
        Livewire::test(RangePickerComponent::class)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('saved.starts_on', null)
            ->assertSet('saved.ends_on', null)
            ->assertSet('saved.stay', null);
    }

    // One JSON column

    public function test_json_keys_are_the_developers(): void
    {
        $booking = Booking::create(['stay' => ['from' => '2026-07-01', 'to' => '2026-07-14']]);

        Livewire::test(RangePickerComponent::class, ['record' => $booking])
            ->assertSet('data.stay', ['from' => '2026-07-01 00:00:00', 'to' => '2026-07-14 00:00:00'])
            ->set('data.stay', ['from' => '2026-08-01 00:00:00', 'to' => '2026-08-02 00:00:00'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('saved.stay', ['from' => '2026-08-01', 'to' => '2026-08-02']);

        $this->assertSame(['from' => '2026-08-01', 'to' => '2026-08-02'], $booking->refresh()->stay);
    }

    public function test_json_stored_as_a_string_is_read_too(): void
    {
        Livewire::test(RangePickerComponent::class, ['state' => ['stay' => '{"from":"2026-07-01","to":"2026-07-14"}']])
            ->assertSet('data.stay', ['from' => '2026-07-01 00:00:00', 'to' => '2026-07-14 00:00:00']);
    }

    public function test_a_month_range_stores_year_and_month(): void
    {
        Livewire::test(RangePickerComponent::class, ['state' => ['billing' => ['from' => '2026-01', 'to' => '2026-11']]])
            ->assertSet('data.billing', ['from' => '2026-01-01 00:00:00', 'to' => '2026-11-01 00:00:00'])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('saved.billing', ['from' => '2026-01', 'to' => '2026-11']);
    }

    public function test_a_year_range_stores_years(): void
    {
        Livewire::test(RangePickerComponent::class, ['state' => ['first_year' => 2000, 'last_year' => '2025']])
            ->assertSet('data.fiscal', ['start' => '2000-01-01 00:00:00', 'end' => '2025-01-01 00:00:00'])
            ->call('save')
            ->assertSet('saved.first_year', '2000')
            ->assertSet('saved.last_year', '2025');
    }

    public function test_a_time_range_stores_times(): void
    {
        Livewire::test(RangePickerComponent::class, ['state' => ['shift_starts' => '09:00', 'shift_ends' => '17:30']])
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('saved.shift_starts', '09:00')
            ->assertSet('saved.shift_ends', '17:30');
    }

    // Relationships and repeaters

    public function test_each_repeater_item_reads_and_writes_its_own_columns(): void
    {
        $booking = Booking::create();
        $booking->legs()->create(['starts_on' => '2026-03-01', 'ends_on' => '2026-03-05']);
        $booking->legs()->create(['starts_on' => '2026-04-01', 'ends_on' => '2026-04-09']);

        $component = Livewire::test(RangePickerComponent::class, ['record' => $booking, 'withRepeater' => true]);

        $items = $component->get('data.legs');

        $this->assertCount(2, $items);
        $this->assertSame(
            [['start' => '2026-03-01 00:00:00', 'end' => '2026-03-05 00:00:00'], ['start' => '2026-04-01 00:00:00', 'end' => '2026-04-09 00:00:00']],
            array_values(array_map(fn (array $item): array => $item['dates'], $items)),
        );

        $firstKey = array_key_first($items);

        $component
            ->set("data.legs.{$firstKey}.dates", ['start' => '2026-03-02 00:00:00', 'end' => '2026-03-06 00:00:00'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(
            [['2026-03-02', '2026-03-06'], ['2026-04-01', '2026-04-09']],
            $booking->legs()->orderBy('id')->get()->map(fn ($leg): array => [substr((string) $leg->starts_on, 0, 10), substr((string) $leg->ends_on, 0, 10)])->all(),
        );
    }

    // Validation

    public function test_the_end_may_not_be_before_the_start(): void
    {
        Livewire::test(RangePickerComponent::class)
            ->set('data.dates', ['start' => '2026-02-10 00:00:00', 'end' => '2026-02-01 00:00:00'])
            ->call('save')
            ->assertHasErrors('data.dates')
            ->assertSee('To must not be before From.');

        Livewire::test(RangePickerComponent::class)
            ->set('data.dates', ['start' => '2026-02-10 00:00:00', 'end' => '2026-02-10 00:00:00'])
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_a_time_range_compares_the_time_of_day(): void
    {
        Livewire::test(RangePickerComponent::class)
            ->set('data.shift', ['start' => '2026-01-02 18:00:00', 'end' => '2026-01-01 09:00:00'])
            ->call('save')
            ->assertHasErrors('data.shift');

        // Different days in front of the times mean nothing: 09:00 to 18:00 is in order.
        Livewire::test(RangePickerComponent::class)
            ->set('data.shift', ['start' => '2026-01-09 09:00:00', 'end' => '2026-01-01 18:00:00'])
            ->call('save')
            ->assertHasNoErrors();
    }

    public function test_bounds_apply_to_both_ends(): void
    {
        $component = fn () => Livewire::test(RangePickerComponent::class)
            ->set('minDate', '2026-01-01')
            ->set('maxDate', '2026-12-31');

        $component()->set('data.dates', ['start' => '2025-12-31 00:00:00', 'end' => '2026-01-05 00:00:00'])->call('save')->assertHasErrors('data.dates');
        $component()->set('data.dates', ['start' => '2026-12-01 00:00:00', 'end' => '2027-01-01 00:00:00'])->call('save')->assertHasErrors('data.dates');
        $component()->set('data.dates', ['start' => '2026-01-01 00:00:00', 'end' => '2026-12-31 00:00:00'])->call('save')->assertHasNoErrors();
    }

    public function test_time_bounds_apply_to_both_ends(): void
    {
        Livewire::test(RangePickerComponent::class)
            ->set('data.shift', ['start' => '2026-01-01 05:30:00', 'end' => '2026-01-01 09:00:00'])
            ->call('save')
            ->assertHasErrors('data.shift');

        Livewire::test(RangePickerComponent::class)
            ->set('data.shift', ['start' => '2026-01-01 09:00:00', 'end' => '2026-01-01 22:30:00'])
            ->call('save')
            ->assertHasErrors('data.shift');
    }

    public function test_half_a_range_is_refused(): void
    {
        Livewire::test(RangePickerComponent::class)
            ->set('data.dates', ['start' => '2026-02-10 00:00:00', 'end' => null])
            ->call('save')
            ->assertHasErrors('data.dates');
    }

    public function test_a_required_range_needs_both_ends(): void
    {
        Livewire::test(RangePickerComponent::class)
            ->set('required', true)
            ->call('save')
            ->assertHasErrors('data.dates');

        Livewire::test(RangePickerComponent::class)
            ->call('save')
            ->assertHasNoErrors();
    }

    // Configuration

    public function test_storage_must_be_chosen(): void
    {
        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('->storeIn(start:');

        DateRangePicker::make('period')->getStorage();
    }

    public function test_the_storage_mode_is_what_was_asked_for(): void
    {
        $this->assertSame(RangeStorage::Columns, DateRangePicker::make('p')->storeIn('a', 'b')->getStorage());
        $this->assertSame(RangeStorage::Json, DateRangePicker::make('p')->storeAsJson('a', 'b')->getStorage());
        $this->assertSame(['start' => 'a', 'end' => 'b'], DateRangePicker::make('p')->storeAsJson('a', 'b')->getStateKeys());
        $this->assertSame(['start' => 'start', 'end' => 'end'], DateRangePicker::make('p')->storeIn('a', 'b')->getStateKeys());
    }

    public function test_the_layout_defaults_to_side_by_side(): void
    {
        $this->assertSame(RangeLayout::SideBySide, DateRangePicker::make('p')->getLayout());
        $this->assertSame(RangeLayout::OneAtATime, DateRangePicker::make('p')->oneAtATime()->getLayout());
        $this->assertSame(RangeLayout::OneAtATime, DateRangePicker::make('p')->layout('one-at-a-time')->getLayout());
    }

    public function test_each_end_is_the_matching_single_picker(): void
    {
        $this->assertSame('date', $this->attach(DateRangePicker::make('p')->storeIn('a', 'b'))->getRangeConfig()['mode']);
        $this->assertSame('month', $this->attach(MonthRangePicker::make('p')->storeIn('a', 'b'))->getRangeConfig()['mode']);
        $this->assertSame('year', $this->attach(YearRangePicker::make('p')->storeIn('a', 'b'))->getRangeConfig()['mode']);
        $this->assertSame('time', $this->attach(TimeRangePicker::make('p')->storeIn('a', 'b'))->getRangeConfig()['mode']);
    }

    public function test_options_reach_both_ends(): void
    {
        $range = $this->attach(MonthRangePicker::make('p')->storeIn('a', 'b')->minDate('2026-03')->displayFormat('F Y'));

        foreach (['start', 'end'] as $which) {
            $config = $range->getEndConfig($which);

            $this->assertSame('2026-03-01 00:00:00', $config['minDate']);
            $this->assertSame('F Y', $config['displayFormat']);
            $this->assertTrue($config['closeOnComplete'], 'An end hands every pick straight to the range.');
        }

        $this->assertSame('F Y', $range->getRangeConfig()['displayFormat']);
    }

    public function test_it_renders_both_ends_and_loads_on_request(): void
    {
        Livewire::test(RangePickerComponent::class, ['state' => ['starts_on' => '2026-01-01', 'ends_on' => '2026-01-20']])
            ->assertSee('data-chrono-range', escape: false)
            ->assertSee('data-chrono-end="start"', escape: false)
            ->assertSee('data-chrono-end="end"', escape: false)
            ->assertSee('fi-chrono-range-side-by-side', escape: false)
            ->assertSee('fi-chrono-range-one-at-a-time', escape: false)
            ->assertSee('x-load-src', escape: false);
    }

    protected function attach(mixed $picker): mixed
    {
        return $picker->container(Schema::make(new RangePickerComponent));
    }
}
