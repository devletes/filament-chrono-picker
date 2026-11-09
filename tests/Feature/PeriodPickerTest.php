<?php

namespace Devletes\ChronoPicker\Tests\Feature;

use Devletes\ChronoPicker\Forms\Components\MonthPicker;
use Devletes\ChronoPicker\Forms\Components\YearPicker;
use Devletes\ChronoPicker\Tests\Fixtures\PeriodPickerComponent;
use Devletes\ChronoPicker\Tests\TestCase;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

class PeriodPickerTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_a_month_is_stored_as_year_and_month(): void
    {
        Livewire::test(PeriodPickerComponent::class, ['state' => ['billing_month' => '2026-02']])
            ->assertSet('data.billing_month', '2026-02-01 00:00:00')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('saved.billing_month', '2026-02');
    }

    public function test_a_short_month_read_on_the_31st_stays_itself(): void
    {
        // Filament's own cast fills the day from today, which would roll February over into March.
        Carbon::setTestNow('2026-03-31 12:00:00');

        Livewire::test(PeriodPickerComponent::class, ['state' => ['billing_month' => '2026-02']])
            ->assertSet('data.billing_month', '2026-02-01 00:00:00')
            ->call('save')
            ->assertSet('saved.billing_month', '2026-02');
    }

    public function test_a_year_is_stored_as_a_year_from_a_string_or_an_integer(): void
    {
        Livewire::test(PeriodPickerComponent::class, ['state' => ['fiscal_year' => 2026]])
            ->assertSet('data.fiscal_year', '2026-01-01 00:00:00')
            ->call('save')
            ->assertSet('saved.fiscal_year', '2026');

        Livewire::test(PeriodPickerComponent::class, ['state' => ['fiscal_year' => '1999']])
            ->call('save')
            ->assertSet('saved.fiscal_year', '1999');
    }

    public function test_a_picked_month_and_year_are_saved(): void
    {
        Livewire::test(PeriodPickerComponent::class)
            ->set('data.billing_month', '2027-11-01 00:00:00')
            ->set('data.fiscal_year', '2030-01-01 00:00:00')
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('saved.billing_month', '2027-11')
            ->assertSet('saved.fiscal_year', '2030');
    }

    public function test_empty_stays_empty(): void
    {
        Livewire::test(PeriodPickerComponent::class)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSet('saved.billing_month', null)
            ->assertSet('saved.fiscal_year', null);
    }

    public function test_month_bounds_cover_the_whole_month(): void
    {
        $component = fn () => Livewire::test(PeriodPickerComponent::class)
            ->set('minMonth', '2026-03')
            ->set('maxMonth', '2026-06');

        $component()->set('data.billing_month', '2026-02-01 00:00:00')->call('save')->assertHasErrors('data.billing_month');
        $component()->set('data.billing_month', '2026-03-01 00:00:00')->call('save')->assertHasNoErrors();
        $component()->set('data.billing_month', '2026-06-01 00:00:00')->call('save')->assertHasNoErrors();
        $component()->set('data.billing_month', '2026-07-01 00:00:00')->call('save')->assertHasErrors('data.billing_month');
    }

    public function test_year_bounds_cover_the_whole_year(): void
    {
        $component = fn () => Livewire::test(PeriodPickerComponent::class)
            ->set('minYear', '2020')
            ->set('maxYear', '2027');

        $component()->set('data.fiscal_year', '2019-01-01 00:00:00')->call('save')->assertHasErrors('data.fiscal_year');
        $component()->set('data.fiscal_year', '2020-01-01 00:00:00')->call('save')->assertHasNoErrors();
        $component()->set('data.fiscal_year', '2027-01-01 00:00:00')->call('save')->assertHasNoErrors();
        $component()->set('data.fiscal_year', '2028-01-01 00:00:00')->call('save')->assertHasErrors('data.fiscal_year');
    }

    public function test_the_panel_opens_on_the_months_or_the_years(): void
    {
        $month = $this->attach(MonthPicker::make('m'))->getPickerConfig();
        $year = $this->attach(YearPicker::make('y'))->getPickerConfig();

        $this->assertSame(['month', 'months', 'M Y', 'Y-m-d H:i:s'], [$month['mode'], $month['initialView'], $month['displayFormat'], $month['internalFormat']]);
        $this->assertSame(['year', 'years', 'Y'], [$year['mode'], $year['initialView'], $year['displayFormat']]);

        $this->assertFalse($month['hasCalendar']);
        $this->assertFalse($month['hasTime']);
        $this->assertTrue($month['hasDate']);
    }

    public function test_bounds_reach_the_panel_as_whole_periods(): void
    {
        $month = $this->attach(MonthPicker::make('m')->minDate('2026-03')->maxDate('2026-06'))->getPickerConfig();
        $year = $this->attach(YearPicker::make('y')->minDate(2020)->maxDate(Carbon::parse('2027-05-05')))->getPickerConfig();

        $this->assertSame('2026-03-01 00:00:00', $month['minDate']);
        $this->assertSame('2026-06-30 23:59:59', $month['maxDate']);
        $this->assertSame('2020-01-01 00:00:00', $year['minDate']);
        $this->assertSame('2027-12-31 23:59:59', $year['maxDate']);
    }

    public function test_the_storage_and_display_formats_can_be_changed(): void
    {
        $picker = MonthPicker::make('m')->format('m/Y')->displayFormat('F Y');

        $this->assertSame('m/Y', $picker->getFormat());
        $this->assertSame('F Y', $picker->getDisplayFormat());
    }

    public function test_the_shortcut_is_named_for_the_period(): void
    {
        $this->assertSame('This month', MonthPicker::make('m')->getNowLabel());
        $this->assertSame('This year', YearPicker::make('y')->getNowLabel());
    }

    public function test_it_renders_the_grids_without_days_or_a_clock(): void
    {
        Livewire::test(PeriodPickerComponent::class, ['state' => ['billing_month' => '2026-02']])
            ->assertSee('pickMonth(index)', escape: false)
            ->assertSee('pickYear(pageYear)', escape: false)
            ->assertDontSee('fi-chrono-calendar-days', escape: false)
            ->assertDontSee('fi-chrono-face', escape: false);
    }

    protected function attach(mixed $picker): mixed
    {
        return $picker->container(Schema::make(new PeriodPickerComponent));
    }
}
