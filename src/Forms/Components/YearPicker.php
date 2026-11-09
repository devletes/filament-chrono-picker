<?php

namespace Devletes\ChronoPicker\Forms\Components;

use Closure;
use Devletes\ChronoPicker\Enums\PickerMode;

/** A year: stored and shown as `Y`, picked from a grid of twelve years paged with the arrows. */
class YearPicker extends PeriodPicker
{
    protected string|Closure $defaultDateDisplayFormat = 'Y';

    public function getPickerMode(): PickerMode
    {
        return PickerMode::Year;
    }

    protected function periodUnit(): string
    {
        return 'year';
    }

    protected function defaultStorageFormat(): string
    {
        return 'Y';
    }
}
