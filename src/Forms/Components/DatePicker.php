<?php

namespace Devletes\ChronoPicker\Forms\Components;

use Devletes\ChronoPicker\Concerns\InteractsWithChronoPanel;
use Filament\Forms\Components\DatePicker as BaseDatePicker;

/** A calendar with a month grid and a year grid behind its header. */
class DatePicker extends BaseDatePicker
{
    use InteractsWithChronoPanel;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpChronoPicker();
    }
}
