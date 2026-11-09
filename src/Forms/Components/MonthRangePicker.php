<?php

namespace Devletes\ChronoPicker\Forms\Components;

use Filament\Forms\Components\DateTimePicker as BaseDateTimePicker;

/** Two months, e.g. Jan 2026 – Jun 2026. */
class MonthRangePicker extends RangePicker
{
    protected function makeEndPicker(string $name): BaseDateTimePicker
    {
        return MonthPicker::make($name);
    }
}
