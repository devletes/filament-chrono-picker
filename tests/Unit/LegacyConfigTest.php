<?php

namespace Devletes\ChronoPicker\Tests\Unit;

use Devletes\ChronoPicker\ChronoPickerServiceProvider;
use Devletes\ChronoPicker\Forms\Components\TimePicker;
use Devletes\ChronoPicker\Tests\TestCase;

/** A config/time-picker.php published under the 1.x name still applies. */
class LegacyConfigTest extends TestCase
{
    public function test_the_1x_config_file_is_read(): void
    {
        // An app's config files are loaded before providers register; testbench registers first, so replay it.
        config()->set('time-picker', ['hours_cycle' => 12, 'now_button' => false]);
        config()->set('chrono-picker', []);

        (new ChronoPickerServiceProvider($this->app))->register();

        $this->assertSame(12, config('chrono-picker.hours_cycle'));
        $this->assertFalse(config('chrono-picker.now_button'));
        $this->assertFalse(config('chrono-picker.close_on_complete'), 'Keys the 1.x file lacks keep their defaults.');

        $this->assertSame(12, TimePicker::make('t')->displayFormat('H:i')->getHoursCycle());
    }

    public function test_without_a_1x_file_the_defaults_stand(): void
    {
        $this->assertNull(config('chrono-picker.hours_cycle'));
        $this->assertTrue(config('chrono-picker.now_button'));
    }
}
