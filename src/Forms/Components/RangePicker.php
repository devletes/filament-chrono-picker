<?php

namespace Devletes\ChronoPicker\Forms\Components;

use Carbon\CarbonInterface;
use Closure;
use Devletes\ChronoPicker\ChronoPickerServiceProvider;
use Devletes\ChronoPicker\Concerns\HasPanelOptions;
use Devletes\ChronoPicker\Enums\PickerMode;
use Devletes\ChronoPicker\Enums\RangeLayout;
use Devletes\ChronoPicker\Enums\RangeStorage;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\DateTimePicker as BaseDateTimePicker;
use Filament\Forms\Components\Field;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use LogicException;

/**
 * Two ends of one kind (dates, months, years or times) in a single field.
 *
 * Each end is a single-value picker of this package, built on demand and never added to the schema: it supplies the
 * panel's config, the state casts and the bounds, so a range reads, stores and validates each end exactly as the
 * single picker would. The range itself only says where the two ends are stored, how the panel lays them out, and
 * that the end is not before the start.
 *
 * Where the ends are stored is always the developer's call, never a default: `storeIn()` for two columns,
 * `storeAsJson()` for one array column. Leaving both out throws as soon as the field is filled or rendered.
 *
 * In Livewire the field's state is `[startKey => internal, endKey => internal]`, each end in Filament's internal
 * `Y-m-d H:i:s`; the keys are the JSON keys when stored as JSON, `start` and `end` otherwise.
 */
abstract class RangePicker extends Field
{
    use CanBeReadOnly;
    use HasPanelOptions;
    use HasPlaceholder;

    protected string $view = 'devletes-chrono-picker::range';

    protected ?RangeStorage $storage = null;

    protected string|Closure|null $startTarget = null;

    protected string|Closure|null $endTarget = null;

    protected RangeLayout|string|Closure $layout = RangeLayout::SideBySide;

    protected string|Closure $rangeSeparator = ' – ';

    protected string|Closure|null $startLabel = null;

    protected string|Closure|null $endLabel = null;

    /** @var array<int, array{0: string, 1: array<int, mixed>}> */
    protected array $endPickerCalls = [];

    /** @var array<int, Closure> */
    protected array $endPickerModifiers = [];

    /** The single picker each end is: `DatePicker`, `MonthPicker`, `YearPicker` or `TimePicker`. */
    abstract protected function makeEndPicker(string $name): BaseDateTimePicker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule(static fn (self $component): Closure => static function (string $attribute, mixed $value, Closure $fail) use ($component): void {
            $component->validateRange($value, $fail);
        });
    }

    // Storage

    /** Two attributes beside the field, e.g. `storeIn(start: 'starts_on', end: 'ends_on')`. */
    public function storeIn(string|Closure $start, string|Closure $end): static
    {
        $this->storage = RangeStorage::Columns;
        $this->startTarget = $start;
        $this->endTarget = $end;

        return $this;
    }

    /** One array/JSON attribute under the field's own name, with named keys, e.g. `storeAsJson(start: 'from', end: 'to')`. */
    public function storeAsJson(string|Closure $start = 'start', string|Closure $end = 'end'): static
    {
        $this->storage = RangeStorage::Json;
        $this->startTarget = $start;
        $this->endTarget = $end;

        return $this;
    }

    public function getStorage(): RangeStorage
    {
        if ($this->storage === null) {
            $class = class_basename(static::class);

            throw new LogicException(
                "{$class} [{$this->getName()}] does not say where to store its range. Call ->storeIn(start: 'starts_on', end: 'ends_on') "
                ."to store the ends in two columns, or ->storeAsJson(start: 'from', end: 'to') to store them in one array column."
            );
        }

        return $this->storage;
    }

    /** The two keys of the field's own state, which are the JSON keys when stored as JSON. */
    public function getStateKeys(): array
    {
        if ($this->getStorage() === RangeStorage::Json) {
            return ['start' => (string) $this->evaluate($this->startTarget), 'end' => (string) $this->evaluate($this->endTarget)];
        }

        return ['start' => 'start', 'end' => 'end'];
    }

    /** The absolute state paths of the two columns, beside the field in its own container. */
    public function getColumnStatePaths(): array
    {
        $container = $this->getContainer()->getStatePath();
        $path = static fn (string $column): string => filled($container) ? "{$container}.{$column}" : $column;

        return [
            'start' => $path((string) $this->evaluate($this->startTarget)),
            'end' => $path((string) $this->evaluate($this->endTarget)),
        ];
    }

    // Layout

    public function layout(RangeLayout|string|Closure $layout): static
    {
        $this->layout = $layout;

        return $this;
    }

    public function sideBySide(): static
    {
        return $this->layout(RangeLayout::SideBySide);
    }

    public function oneAtATime(): static
    {
        return $this->layout(RangeLayout::OneAtATime);
    }

    public function getLayout(): RangeLayout
    {
        $layout = $this->evaluate($this->layout);

        return $layout instanceof RangeLayout ? $layout : RangeLayout::from($layout);
    }

    public function rangeSeparator(string|Closure $separator): static
    {
        $this->rangeSeparator = $separator;

        return $this;
    }

    public function getRangeSeparator(): string
    {
        return $this->evaluate($this->rangeSeparator);
    }

    public function startLabel(string|Closure|null $label): static
    {
        $this->startLabel = $label;

        return $this;
    }

    public function endLabel(string|Closure|null $label): static
    {
        $this->endLabel = $label;

        return $this;
    }

    public function getStartLabel(): string
    {
        return $this->evaluate($this->startLabel) ?? __('devletes-chrono-picker::chrono-picker.range.start');
    }

    public function getEndLabel(): string
    {
        return $this->evaluate($this->endLabel) ?? __('devletes-chrono-picker::chrono-picker.range.end');
    }

    // Options of the ends: each is passed on to both single pickers.

    public function format(string|Closure|null $format): static
    {
        return $this->forEachEnd('format', $format);
    }

    public function displayFormat(string|Closure|null $format): static
    {
        return $this->forEachEnd('displayFormat', $format);
    }

    public function minDate(CarbonInterface|string|int|Closure|null $date): static
    {
        return $this->forEachEnd('minDate', $date);
    }

    public function maxDate(CarbonInterface|string|int|Closure|null $date): static
    {
        return $this->forEachEnd('maxDate', $date);
    }

    public function minTime(CarbonInterface|string|Closure|null $time): static
    {
        return $this->forEachEnd('minTime', $time);
    }

    public function maxTime(CarbonInterface|string|Closure|null $time): static
    {
        return $this->forEachEnd('maxTime', $time);
    }

    /**
     * @param  array<mixed>|Closure  $dates
     */
    public function disabledDates(array|Closure $dates): static
    {
        return $this->forEachEnd('disabledDates', $dates);
    }

    public function defaultFocusedDate(CarbonInterface|string|Closure|null $date): static
    {
        return $this->forEachEnd('defaultFocusedDate', $date);
    }

    public function firstDayOfWeek(?int $day): static
    {
        return $this->forEachEnd('firstDayOfWeek', $day);
    }

    public function weekStartsOnMonday(): static
    {
        return $this->firstDayOfWeek(1);
    }

    public function weekStartsOnSunday(): static
    {
        return $this->firstDayOfWeek(7);
    }

    public function timezone(string|Closure|null $timezone): static
    {
        return $this->forEachEnd('timezone', $timezone);
    }

    public function locale(string|Closure|null $locale): static
    {
        return $this->forEachEnd('locale', $locale);
    }

    public function hoursCycle(int|Closure|null $cycle): static
    {
        return $this->forEachEnd('hoursCycle', $cycle);
    }

    public function seconds(bool|Closure $condition = true): static
    {
        return $this->forEachEnd('seconds', $condition);
    }

    public function hoursStep(int|Closure|null $step): static
    {
        return $this->forEachEnd('hoursStep', $step);
    }

    public function minutesStep(int|Closure|null $step): static
    {
        return $this->forEachEnd('minutesStep', $step);
    }

    public function secondsStep(int|Closure|null $step): static
    {
        return $this->forEachEnd('secondsStep', $step);
    }

    /** Anything else a single picker offers: `configureEndsUsing(fn (DatePicker $end, string $which) => ...)`. */
    public function configureEndsUsing(Closure $callback): static
    {
        $this->endPickerModifiers[] = $callback;

        return $this;
    }

    protected function forEachEnd(string $method, mixed ...$arguments): static
    {
        $this->endPickerCalls[] = [$method, $arguments];

        return $this;
    }

    /** The single picker standing for one end, configured as the range was. */
    public function getEndPicker(string $which): BaseDateTimePicker
    {
        $picker = $this->makeEndPicker("{$this->getName()}_{$which}")
            ->container($this->getContainer())
            ->label($which === 'start' ? $this->getStartLabel() : $this->getEndLabel());

        foreach ($this->endPickerCalls as [$method, $arguments]) {
            $picker->{$method}(...$arguments);
        }

        foreach ($this->endPickerModifiers as $modifier) {
            $modifier($picker, $which);
        }

        return $picker;
    }

    public function getPickerMode(): PickerMode
    {
        return $this->getEndPicker('start')->getPickerMode();
    }

    // State

    public function callAfterStateHydrated(): static
    {
        $this->hydrateRange();

        return parent::callAfterStateHydrated();
    }

    /** Reads both ends from where they are stored into the field's own state, each through its picker's casts. */
    protected function hydrateRange(): void
    {
        $keys = $this->getStateKeys();

        if ($this->getStorage() === RangeStorage::Columns) {
            $livewire = $this->getLivewire();
            $paths = $this->getColumnStatePaths();
            $stored = ['start' => data_get($livewire, $paths['start']), 'end' => data_get($livewire, $paths['end'])];
        } else {
            $raw = $this->getRawState();

            if (is_string($raw)) {
                $raw = json_decode($raw, associative: true);
            }

            $raw = is_array($raw) ? $raw : [];
            $stored = ['start' => $raw[$keys['start']] ?? null, 'end' => $raw[$keys['end']] ?? null];
        }

        $state = [];

        foreach (['start', 'end'] as $which) {
            $value = $stored[$which];

            foreach ($this->getEndPicker($which)->getStateCasts() as $cast) {
                $value = $cast->set($value);
            }

            $state[$keys[$which]] = $value;
        }

        $this->rawState($state);
    }

    /**
     * @return array<string, mixed>
     */
    public function getStateToDehydrate(mixed $state): array
    {
        $keys = $this->getStateKeys();
        $state = is_array($state) ? $state : [];
        $values = [];

        foreach (['start', 'end'] as $which) {
            $value = $state[$keys[$which]] ?? null;

            foreach ($this->getEndPicker($which)->getStateCasts() as $cast) {
                $value = $cast->get($value === '' ? null : $value);
            }

            $values[$which] = $value;
        }

        if ($this->getStorage() === RangeStorage::Columns) {
            $paths = $this->getColumnStatePaths();

            return [$paths['start'] => $values['start'], $paths['end'] => $values['end']];
        }

        $empty = blank($values['start']) && blank($values['end']);

        return [$this->getStatePath() => $empty ? null : [$keys['start'] => $values['start'], $keys['end'] => $values['end']]];
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public function dehydrateState(array &$state, bool $isDehydrated = true): void
    {
        parent::dehydrateState($state, $isDehydrated);

        // Stored in two columns, the field's own key is a working copy and nothing to save.
        if ($isDehydrated && $this->isDehydrated() && $this->getStorage() === RangeStorage::Columns) {
            Arr::forget($state, $this->getStatePath());
        }
    }

    // Validation

    /** Both ends or neither, the end not before the start, and each end inside the bounds. */
    public function validateRange(mixed $value, Closure $fail): void
    {
        $keys = $this->getStateKeys();
        $value = is_array($value) ? $value : [];
        $start = filled($value[$keys['start']] ?? null) ? (string) $value[$keys['start']] : null;
        $end = filled($value[$keys['end']] ?? null) ? (string) $value[$keys['end']] : null;

        if ($start === null && $end === null) {
            if ($this->isRequired()) {
                $fail(__('devletes-chrono-picker::chrono-picker.range.required'));
            }

            return;
        }

        if ($start === null || $end === null) {
            $fail(__('devletes-chrono-picker::chrono-picker.range.incomplete'));

            return;
        }

        // A time range compares the time of day; Filament keeps a date in front of it that means nothing.
        $comparable = fn (string $internal): string => $this->getPickerMode() === PickerMode::Time ? substr($internal, -8) : $internal;

        if ($comparable($end) < $comparable($start)) {
            $fail(__('devletes-chrono-picker::chrono-picker.range.order', [
                'end' => $this->getEndLabel(),
                'start' => $this->getStartLabel(),
            ]));
        }

        foreach (['start' => $start, 'end' => $end] as $which => $internal) {
            $picker = $this->getEndPicker($which);
            $label = $which === 'start' ? $this->getStartLabel() : $this->getEndLabel();

            if ($picker->hasTime() && ! $picker->hasDate()) {
                $time = substr($internal, -8);

                if (filled($min = $picker->getMinTime()) && $time < $min) {
                    $fail(__('devletes-chrono-picker::chrono-picker.range.min', ['end' => $label, 'bound' => $picker->formatForHumans($min)]));
                }

                if (filled($max = $picker->getMaxTime()) && $time > $max) {
                    $fail(__('devletes-chrono-picker::chrono-picker.range.max', ['end' => $label, 'bound' => $picker->formatForHumans($max)]));
                }

                continue;
            }

            $display = fn (string $bound): string => Carbon::parse($bound)->translatedFormat($picker->getDisplayFormat());

            if (filled($min = $picker->getMinDate()) && $internal < Carbon::parse($min)->format('Y-m-d H:i:s')) {
                $fail(__('devletes-chrono-picker::chrono-picker.range.min', ['end' => $label, 'bound' => $display($min)]));
            }

            if (filled($max = $picker->getMaxDate()) && $internal > Carbon::parse($max)->format('Y-m-d H:i:s')) {
                $fail(__('devletes-chrono-picker::chrono-picker.range.max', ['end' => $label, 'bound' => $display($max)]));
            }
        }
    }

    // The panel

    public function getAlpineComponentSrc(): string
    {
        return FilamentAsset::getAlpineComponentSrc(ChronoPickerServiceProvider::ASSET, ChronoPickerServiceProvider::PACKAGE);
    }

    public function getStylesheetHref(): string
    {
        return FilamentAsset::getStyleHref(ChronoPickerServiceProvider::ASSET, ChronoPickerServiceProvider::PACKAGE);
    }

    /**
     * @return array<string, mixed>
     */
    public function getRangeConfig(): array
    {
        $start = $this->getEndPicker('start');

        return [
            'range' => true,
            'mode' => $start->getPickerMode()->value,
            'layout' => $this->getLayout()->value,
            'keys' => $this->getStateKeys(),
            'displayFormat' => $start->getDisplayFormat(),
            'locale' => $start->getLocale(),
            'separator' => $this->getRangeSeparator(),
            'closeOnComplete' => $this->shouldCloseOnComplete(),
            'isDisabled' => $this->isDisabled(),
            'labels' => [
                'start' => $this->getStartLabel(),
                'end' => $this->getEndLabel(),
            ],
        ];
    }

    /**
     * Each end's panel: the single picker's config, set to hand every pick straight to the range.
     *
     * @return array<string, mixed>
     */
    public function getEndConfig(string $which): array
    {
        return [
            ...$this->getEndPicker($which)->getPickerConfig(),
            'closeOnComplete' => true,
            'closeOnDateSelection' => false,
            'isAutofocused' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getEndClockFace(): array
    {
        return $this->getEndPicker('start')->getClockFace();
    }
}
