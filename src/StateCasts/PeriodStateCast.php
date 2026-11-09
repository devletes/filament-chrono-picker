<?php

namespace Devletes\ChronoPicker\StateCasts;

use Carbon\CarbonInterface;
use Filament\Schemas\Components\StateCasts\DateTimeStateCast;
use Illuminate\Support\Carbon;

/**
 * Filament's date cast for a value coarser than a day: `Y-m` or `Y`, stored as written.
 *
 * Filament's own cast fills the missing parts from today, so a `Y-m` read on the 31st of a month lands in the next
 * month whenever the stored month is shorter, and a bare `2026` read by Carbon::parse() is twenty past eight tonight.
 * Here the missing parts are the period's first moment instead (`!` in the format).
 */
class PeriodStateCast extends DateTimeStateCast
{
    public function get(mixed $state): ?string
    {
        if (blank($state)) {
            return null;
        }

        $date = static::parse($state, $this->internalFormat) ?? static::parse($state, $this->format);

        return $date?->format($this->format) ?? parent::get($state);
    }

    public function set(mixed $state): ?string
    {
        if (blank($state)) {
            return null;
        }

        $date = static::parse($state, $this->format) ?? static::parse($state, $this->internalFormat);

        return $date?->format($this->internalFormat) ?? parent::set($state);
    }

    public static function parse(mixed $state, string $format): ?CarbonInterface
    {
        if ($state instanceof CarbonInterface) {
            return $state;
        }

        if (! is_string($state) && ! is_int($state)) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('!'.$format, (string) $state, config('app.timezone'));
        } catch (\Throwable) {
            return null;
        }

        // createFromFormat() rolls an impossible date over; only a value that reads back the same is that format.
        return ($date !== false && $date !== null && $date->format($format) === (string) $state) ? $date : null;
    }
}
