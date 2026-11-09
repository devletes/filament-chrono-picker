<?php

namespace Devletes\ChronoPicker\Forms\Components;

use Filament\Forms\Components\DateTimePicker as BaseDateTimePicker;

/** Two dates: From and To calendars, stored in two columns or one JSON column. */
class DateRangePicker extends RangePicker
{
    protected function makeEndPicker(string $name): BaseDateTimePicker
    {
        return DatePicker::make($name);
    }
}
