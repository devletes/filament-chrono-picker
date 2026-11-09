<?php

namespace Devletes\ChronoPicker\StateCasts;

use Carbon\CarbonInterface;
use Carbon\Exceptions\InvalidFormatException;
use Filament\Schemas\Components\StateCasts\DateTimeStateCast;
use Illuminate\Support\Carbon;

/** Reads `Y-m` and `Y` from the period's first moment, where Filament's cast fills the gaps from today. */
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
        } catch (InvalidFormatException) {
            return null;
        }

        // createFromFormat() rolls impossible dates over, so the value must read back unchanged.
        return ($date && $date->format($format) === (string) $state) ? $date : null;
    }
}
