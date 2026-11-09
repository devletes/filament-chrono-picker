<?php

namespace Devletes\ChronoPicker\Forms\Components;

use Filament\Forms\Components\DateTimePicker as BaseDateTimePicker;

/** Two times of day on two clocks. The end may not be before the start: an overnight span is two fields. */
class TimeRangePicker extends RangePicker
{
    protected function makeEndPicker(string $name): BaseDateTimePicker
    {
        return TimePicker::make($name);
    }
}
