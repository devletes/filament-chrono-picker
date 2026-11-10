<?php

namespace Devletes\ChronoPicker\Forms\Components;

use Carbon\CarbonInterface;
use Carbon\Exceptions\InvalidFormatException;
use Devletes\ChronoPicker\Concerns\InteractsWithChronoPanel;
use Devletes\ChronoPicker\StateCasts\PeriodStateCast;
use Filament\Forms\Components\DatePicker as BaseDatePicker;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use Filament\Schemas\Components\StateCasts\DateTimeStateCast;
use Illuminate\Support\Carbon;

/** A whole month or year; bounds widen to their period, so `minDate('2026-03')` allows all of March. */
abstract class PeriodPicker extends BaseDatePicker
{
    use InteractsWithChronoPanel;

    /** `month` or `year`, as Carbon's startOf() names them. */
    abstract protected function periodUnit(): string;

    abstract protected function defaultStorageFormat(): string;

    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpChronoPicker();
    }

    public function getFormat(): string
    {
        return $this->evaluate($this->format) ?: $this->defaultStorageFormat();
    }

    public function getMinDate(): ?string
    {
        return $this->periodBound($this->evaluate($this->minDate), isMaximum: false);
    }

    public function getMaxDate(): ?string
    {
        return $this->periodBound($this->evaluate($this->maxDate), isMaximum: true);
    }

    public function getDefaultFocusedDate(): ?string
    {
        return $this->periodBound($this->evaluate($this->defaultFocusedDate), isMaximum: false);
    }

    /**
     * @return array<StateCast>
     */
    public function getDefaultStateCasts(): array
    {
        return [
            ...array_filter(
                parent::getDefaultStateCasts(),
                static fn (StateCast $cast): bool => ! $cast instanceof DateTimeStateCast,
            ),
            app(PeriodStateCast::class, [
                'format' => $this->getFormat(),
                'internalFormat' => $this->getInternalFormat(),
                'timezone' => config('app.timezone'),
            ]),
        ];
    }

    protected function periodBound(CarbonInterface|string|int|null $value, bool $isMaximum): ?string
    {
        $date = $this->parsePeriod($value);

        if ($date === null) {
            return null;
        }

        $unit = $this->periodUnit();

        return ($isMaximum ? $date->endOf($unit) : $date->startOf($unit))->format('Y-m-d H:i:s');
    }

    protected function parsePeriod(CarbonInterface|string|int|null $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        if ($value instanceof CarbonInterface) {
            return Carbon::instance($value);
        }

        foreach ([$this->getFormat(), 'Y-m', 'Y'] as $format) {
            if ($date = PeriodStateCast::parse($value, $format)) {
                return Carbon::instance($date);
            }
        }

        try {
            return Carbon::parse((string) $value);
        } catch (InvalidFormatException) {
            return null;
        }
    }
}
