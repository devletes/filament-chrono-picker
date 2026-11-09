<?php

namespace Devletes\ChronoPicker\Forms\Components;

use Closure;
use Devletes\ChronoPicker\Enums\PickerMode;

/** A month of a year: stored as `Y-m`, shown as `M Y`, picked from a month grid under a year that opens a year grid. */
class MonthPicker extends PeriodPicker
{
    protected string|Closure $defaultDateDisplayFormat = 'M Y';

    public function getPickerMode(): PickerMode
    {
        return PickerMode::Month;
    }

    protected function periodUnit(): string
    {
        return 'month';
    }

    protected function defaultStorageFormat(): string
    {
        return 'Y-m';
    }
}
