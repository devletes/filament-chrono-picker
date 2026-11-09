<?php

namespace Devletes\ChronoPicker\Concerns;

use Carbon\CarbonInterface;
use Carbon\Exceptions\InvalidFormatException;
use Closure;
use DateTimeInterface;
use Devletes\ChronoPicker\Enums\PickerMode;
use Illuminate\Support\Carbon;

/** Swaps a Filament date-time field's panel for Chrono Picker's; state, casts and validation stay Filament's. */
trait InteractsWithChronoPanel
{
    use HasClockOptions;
    use HasPanelOptions;
    use LoadsAssets;

    protected function setUpChronoPicker(): void
    {
        $this->view('devletes-chrono-picker::picker');
        $this->native(false);

        // The dial only dims out-of-range times, so the bounds are enforced here too.
        $this->rule(
            static function (self $component): Closure {
                return static function (string $attribute, mixed $value, Closure $fail) use ($component): void {
                    $time = $component->parseState($value);

                    if ($time === null) {
                        return;
                    }

                    $min = $component->getMinTime();
                    $max = $component->getMaxTime();

                    if (filled($min) && $time < $min) {
                        $fail(__('devletes-chrono-picker::chrono-picker.validation.min', [
                            'time' => $component->formatForHumans($min),
                        ]));
                    }

                    if (filled($max) && $time > $max) {
                        $fail(__('devletes-chrono-picker::chrono-picker.validation.max', [
                            'time' => $component->formatForHumans($max),
                        ]));
                    }
                };
            },
            static fn (self $component): bool => $component->hasTime() && (filled($component->getMinTime()) || filled($component->getMaxTime())),
        );
    }

    public function getPickerMode(): PickerMode
    {
        return match (true) {
            ! $this->hasDate() => PickerMode::Time,
            ! $this->hasTime() => PickerMode::Date,
            default => PickerMode::DateTime,
        };
    }

    /** A native field renders through Filament's own input instead of this view. */
    public function hasView(): bool
    {
        return (! $this->isNative()) && parent::hasView();
    }

    public function parseState(mixed $value): ?string
    {
        if (blank($value) || ! is_string($value)) {
            return null;
        }

        try {
            return Carbon::createFromFormat($this->getFormat(), $value)->format('H:i:s');
        } catch (InvalidFormatException) {
            try {
                return Carbon::parse($value)->format('H:i:s');
            } catch (InvalidFormatException) {
                return null;
            }
        }
    }

    public function formatForHumans(string $time): string
    {
        try {
            return Carbon::createFromFormat('H:i:s', $time)->format($this->getTimeDisplayFormat());
        } catch (InvalidFormatException) {
            return $time;
        }
    }

    public function getTimeDisplayFormat(): string
    {
        if (! $this->hasDate()) {
            return $this->getDisplayFormat();
        }

        $seconds = $this->hasSeconds() ? ':s' : '';

        return $this->getHoursCycle() === 12 ? "g:i{$seconds} A" : "H:i{$seconds}";
    }

    /**
     * @return array<string, mixed>
     */
    public function getPickerConfig(): array
    {
        $mode = $this->getPickerMode();
        $hasTime = $mode->hasClock();
        $hasDate = $mode !== PickerMode::Time;

        return [
            'mode' => $mode->value,
            'initialView' => $mode->initialView(),
            'internalFormat' => $this->getInternalFormat(),
            'displayFormat' => $this->getDisplayFormat(),
            'timeDisplayFormat' => $this->getTimeDisplayFormat(),
            'hasDate' => $hasDate,
            'hasCalendar' => $mode->hasCalendar(),
            'hasTime' => $hasTime,
            'hoursCycle' => $this->getHoursCycle(),
            'hasSeconds' => $hasTime && $this->hasSeconds(),
            'hoursStep' => max(1, $this->getHoursStep()),
            'minutesStep' => max(1, $this->getMinutesStep()),
            'secondsStep' => max(1, $this->getSecondsStep()),
            'minTime' => $hasTime ? $this->getMinTime() : null,
            'maxTime' => $hasTime ? $this->getMaxTime() : null,
            'minDate' => $hasDate ? $this->normaliseDateTime($this->getMinDate()) : null,
            'maxDate' => $hasDate ? $this->normaliseDateTime($this->getMaxDate()) : null,
            'disabledDates' => $mode->hasCalendar() ? $this->getDisabledDatesAsStrings() : [],
            'defaultFocusedDate' => $hasDate ? $this->normaliseDateTime($this->getDefaultFocusedDate()) : null,
            'firstDayOfWeek' => $this->getFirstDayOfWeek() % 7,
            'closeOnDateSelection' => $this->shouldCloseOnDateSelection(),
            'closeOnComplete' => $this->shouldCloseOnComplete(),
            'isDisabled' => $this->isDisabled(),
            'isReadOnly' => $this->isReadOnly(),
            'isAutofocused' => $this->isAutofocused(),
            'locale' => $this->getLocale(),
            'yearsPerPage' => 12,
            'labels' => $this->getPanelLabels(),
        ];
    }

    /**
     * @deprecated Use getPickerConfig(). Remove in 1.0.
     *
     * @return array<string, mixed>
     */
    public function getClockConfig(): array
    {
        return $this->getPickerConfig();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getPanelLabels(): array
    {
        $label = static fn (string $key): string => __("devletes-chrono-picker::chrono-picker.{$key}");

        return [
            'meridiem' => ['am' => $label('meridiem.am'), 'pm' => $label('meridiem.pm')],
            'date' => $label('date'),
            'time' => $label('time'),
            'month' => $label('month'),
            'year' => $label('year'),
            'hour' => $label('hour'),
            'minute' => $label('minute'),
            'second' => $label('second'),
            'dial' => ['hour' => $label('dial.hour'), 'minute' => $label('dial.minute'), 'second' => $label('dial.second')],
            'selectHour' => $label('select_hour'),
            'selectMinute' => $label('select_minute'),
            'selectSecond' => $label('select_second'),
            'selectMonth' => $label('select_month'),
            'selectYear' => $label('select_year'),
            'previousMonth' => $label('previous_month'),
            'nextMonth' => $label('next_month'),
            'previousYear' => $label('previous_year'),
            'nextYear' => $label('next_year'),
            'previousYears' => $label('previous_years'),
            'nextYears' => $label('next_years'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getClockFace(): array
    {
        return [
            'hour' => $this->getHoursCycle() === 12
                ? [$this->ring([12, ...range(1, 11)], outer: true, pad: false)]
                : [
                    $this->ring([12, ...range(1, 11)], outer: true, pad: false),
                    $this->ring([0, ...range(13, 23)], outer: false, pad: true),
                ],
            'minute' => [$this->ring(range(0, 55, 5), outer: true, pad: true)],
            'second' => [$this->ring(range(0, 55, 5), outer: true, pad: true)],
        ];
    }

    /**
     * @param  array<int, int>  $values
     * @return array<int, array<string, mixed>>
     */
    protected function ring(array $values, bool $outer, bool $pad): array
    {
        $centre = 112;
        $radius = $outer ? 84 : 54;

        return collect($values)
            ->values()
            ->map(fn (int $value, int $index): array => [
                'value' => $value,
                'label' => $pad ? str_pad((string) $value, 2, '0', STR_PAD_LEFT) : (string) $value,
                'x' => round($centre + $radius * sin(deg2rad($index * 30)), 2),
                'y' => round($centre - $radius * cos(deg2rad($index * 30)), 2),
                'outer' => $outer,
            ])
            ->all();
    }

    protected function normaliseDateTime(CarbonInterface|string|null $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        try {
            return ($value instanceof CarbonInterface ? $value : Carbon::parse($value))->format('Y-m-d H:i:s');
        } catch (InvalidFormatException) {
            return null;
        }
    }

    /**
     * @return array<int, string>
     */
    protected function getDisabledDatesAsStrings(): array
    {
        return collect($this->getDisabledDates())
            ->map(fn (mixed $date): ?string => $this->normaliseDateTime($date instanceof DateTimeInterface ? Carbon::instance($date) : (string) $date))
            ->filter()
            ->map(fn (string $date): string => substr($date, 0, 10))
            ->values()
            ->all();
    }
}
