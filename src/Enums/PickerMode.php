<?php

namespace Devletes\ChronoPicker\Enums;

/** What a single value picks, which decides the panel's first view and the views it can reach. */
enum PickerMode: string
{
    case Time = 'time';
    case Date = 'date';
    case DateTime = 'datetime';
    case Month = 'month';
    case Year = 'year';

    public function hasCalendar(): bool
    {
        return in_array($this, [self::Date, self::DateTime], strict: true);
    }

    public function hasClock(): bool
    {
        return in_array($this, [self::Time, self::DateTime], strict: true);
    }

    /** The view the panel opens on: `time`, `date` (the days), `months` or `years`. */
    public function initialView(): string
    {
        return match ($this) {
            self::Time => 'time',
            self::Date, self::DateTime => 'date',
            self::Month => 'months',
            self::Year => 'years',
        };
    }
}
