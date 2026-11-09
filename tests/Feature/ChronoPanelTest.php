<?php

namespace Devletes\ChronoPicker\Tests\Feature;

use Devletes\ChronoPicker\ChronoPickerServiceProvider;
use Devletes\ChronoPicker\Enums\PickerMode;
use Devletes\ChronoPicker\Forms\Components\DatePicker;
use Devletes\ChronoPicker\Forms\Components\DateTimePicker;
use Devletes\ChronoPicker\Forms\Components\TimePicker;
use Devletes\ChronoPicker\Tests\Fixtures\DateTimePickerComponent;
use Devletes\ChronoPicker\Tests\Fixtures\PickerComponent;
use Devletes\ChronoPicker\Tests\TestCase;
use Filament\Schemas\Schema;
use Filament\Support\Facades\FilamentAsset;
use Livewire\Livewire;

class ChronoPanelTest extends TestCase
{
    public function test_each_picker_names_its_mode_and_first_view(): void
    {
        $time = $this->attach(TimePicker::make('t'))->getPickerConfig();
        $date = $this->attach(DatePicker::make('d'))->getPickerConfig();
        $dateTime = $this->attach(DateTimePicker::make('dt'))->getPickerConfig();

        $this->assertSame(['time', 'time'], [$time['mode'], $time['initialView']]);
        $this->assertSame(['date', 'date'], [$date['mode'], $date['initialView']]);
        $this->assertSame(['datetime', 'date'], [$dateTime['mode'], $dateTime['initialView']]);

        $this->assertFalse($date['hasTime']);
        $this->assertTrue($date['hasCalendar']);
        $this->assertFalse($time['hasCalendar']);
    }

    public function test_a_date_time_picker_without_time_is_a_date_picker(): void
    {
        $this->assertSame(PickerMode::Date, DateTimePicker::make('dt')->time(false)->getPickerMode());
    }

    public function test_the_date_picker_stores_a_date(): void
    {
        $picker = DatePicker::make('d');

        $this->assertSame('Y-m-d', $picker->getFormat());
        $this->assertSame('M j, Y', $picker->getDisplayFormat());
        $this->assertFalse($picker->isNative());
    }

    public function test_the_calendar_carries_a_month_grid_and_a_year_grid(): void
    {
        Livewire::test(DateTimePickerComponent::class, ['startsAt' => '2026-11-03 09:30:00'])
            ->assertSee('showMonths()', escape: false)
            ->assertSee('showYears()', escape: false)
            ->assertSee('pickMonth(index)', escape: false)
            ->assertSee('pickYear(pageYear)', escape: false)
            ->assertSee('previousYears()', escape: false)
            ->assertDontSee('fi-chrono-calendar-month-dropdown', escape: false)
            ->assertDontSee('type="number"', escape: false);
    }

    public function test_a_time_picker_carries_no_calendar_grids(): void
    {
        Livewire::test(PickerComponent::class, ['time' => '09:30'])
            ->assertDontSee('pickMonth(index)', escape: false)
            ->assertDontSee('pickYear(pageYear)', escape: false);
    }

    public function test_the_year_grid_pages_by_a_dozen(): void
    {
        $this->assertSame(12, $this->attach(DatePicker::make('d'))->getPickerConfig()['yearsPerPage']);
    }

    public function test_the_script_and_stylesheet_load_on_request(): void
    {
        $src = FilamentAsset::getAlpineComponentSrc(ChronoPickerServiceProvider::ASSET, ChronoPickerServiceProvider::PACKAGE);
        $href = FilamentAsset::getStyleHref(ChronoPickerServiceProvider::ASSET, ChronoPickerServiceProvider::PACKAGE);

        $this->assertStringContainsString('js/devletes/filament-chrono-picker/components/chrono-picker.js?v=', $src);
        $this->assertStringContainsString('css/devletes/filament-chrono-picker/chrono-picker.css?v=', $href);

        // Nothing is in the panel's <head>; a field requests both.
        $this->assertSame([], FilamentAsset::getScripts([ChronoPickerServiceProvider::PACKAGE]));
        $this->assertStringNotContainsString('chrono-picker.css', FilamentAsset::renderStyles([ChronoPickerServiceProvider::PACKAGE]) ?? '');

        Livewire::test(PickerComponent::class, ['time' => '09:30'])
            ->assertSee('x-load-src="'.e($src).'"', escape: false)
            ->assertSee('x-load-css', escape: false);
    }

    public function test_the_now_button_is_named_for_what_it_picks(): void
    {
        $this->assertSame('Now', TimePicker::make('t')->getNowLabel());
        $this->assertSame('Now', DateTimePicker::make('t')->getNowLabel());
        $this->assertSame('Today', DatePicker::make('t')->getNowLabel());
        $this->assertSame('Pick now', DatePicker::make('t')->nowLabel('Pick now')->getNowLabel());
    }

    public function test_the_1x_config_getter_still_answers(): void
    {
        $picker = $this->attach(TimePicker::make('t'));

        $this->assertSame($picker->getPickerConfig(), $picker->getClockConfig());
    }

    public function test_time_bounds_are_not_enforced_on_a_date_picker(): void
    {
        $config = $this->attach(DatePicker::make('d')->minTime('09:00'))->getPickerConfig();

        $this->assertNull($config['minTime']);
    }

    /**
     * @template T of \Filament\Forms\Components\Field
     *
     * @param  T  $picker
     * @return T
     */
    protected function attach(mixed $picker): mixed
    {
        return $picker->container(Schema::make(new DateTimePickerComponent));
    }
}
