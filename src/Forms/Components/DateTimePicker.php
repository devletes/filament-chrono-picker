<?php

namespace Devletes\ChronoPicker\Forms\Components;

use Devletes\ChronoPicker\Concerns\InteractsWithChronoPanel;
use Filament\Forms\Components\DateTimePicker as BaseDateTimePicker;

/** A calendar that moves on to the clock once a day is picked. */
class DateTimePicker extends BaseDateTimePicker
{
    use InteractsWithChronoPanel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpChronoPicker();
    }
}
