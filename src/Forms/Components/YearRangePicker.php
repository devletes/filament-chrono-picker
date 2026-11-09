<?php

namespace Devletes\ChronoPicker\Forms\Components;

use Filament\Forms\Components\DateTimePicker as BaseDateTimePicker;

/** Two years, e.g. 2000 – 2025. */
class YearRangePicker extends RangePicker
{
    protected function makeEndPicker(string $name): BaseDateTimePicker
    {
        return YearPicker::make($name);
    }
}
