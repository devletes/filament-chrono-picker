<?php

namespace Devletes\ChronoPicker\Tests\Unit;

use Devletes\ChronoPicker\ChronoPickerServiceProvider;
use Devletes\ChronoPicker\Concerns\InteractsWithChronoPanel;
use Devletes\ChronoPicker\Forms\Components\DateTimePicker;
use Devletes\ChronoPicker\Forms\Components\TimePicker;
use Devletes\ChronoPicker\Tests\TestCase;

/** The time picker shipped as devletes/filament-time-picker; its names keep working until Chrono Picker 1.0. */
class LegacyNamesTest extends TestCase
{
    public function test_the_1x_component_classes_resolve_to_the_new_ones(): void
    {
        $legacyTime = 'Devletes\\TimePicker\\Forms\\Components\\TimePicker';
        $legacyDateTime = 'Devletes\\TimePicker\\Forms\\Components\\DateTimePicker';

        $this->assertInstanceOf(TimePicker::class, $legacyTime::make('t'));
        $this->assertInstanceOf(DateTimePicker::class, $legacyDateTime::make('t'));
        $this->assertSame(TimePicker::class, get_class($legacyTime::make('t')));
    }

    public function test_renamed_classes_resolve_to_their_new_names(): void
    {
        $this->assertTrue(class_exists('Devletes\\TimePicker\\TimePickerServiceProvider'));
        $this->assertTrue(is_a('Devletes\\TimePicker\\TimePickerServiceProvider', ChronoPickerServiceProvider::class, allow_string: true));
        $this->assertTrue(trait_exists('Devletes\\TimePicker\\Concerns\\InteractsWithClock'));
        $this->assertContains(InteractsWithChronoPanel::class, class_uses(TimePicker::class));
    }

    public function test_an_unknown_legacy_name_is_left_alone(): void
    {
        $this->assertFalse(class_exists('Devletes\\TimePicker\\Nothing\\Here'));
    }
}
