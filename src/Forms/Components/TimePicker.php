<?php

namespace Devletes\ChronoPicker\Forms\Components;

use Devletes\ChronoPicker\Concerns\InteractsWithChronoPanel;
use Filament\Forms\Components\TimePicker as BaseTimePicker;

/** A clock face: hour and minute dials, drag or type. */
class TimePicker extends BaseTimePicker
{
    use InteractsWithChronoPanel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpChronoPicker();
    }
}
