<?php

namespace Devletes\ChronoPicker\Forms\Components;

use Carbon\CarbonInterface;
use Closure;
use Devletes\ChronoPicker\Concerns\HasPanelOptions;
use Devletes\ChronoPicker\Concerns\LoadsAssets;
use Devletes\ChronoPicker\Enums\PickerMode;
use Devletes\ChronoPicker\Enums\RangeLayout;
use Devletes\ChronoPicker\Enums\RangeStorage;
use Filament\Forms\Components\Concerns\CanBeReadOnly;
use Filament\Forms\Components\Concerns\HasPlaceholder;
use Filament\Forms\Components\DateTimePicker as BaseDateTimePicker;
use Filament\Forms\Components\Field;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use LogicException;

/** Two ends in one field, each read, cast and bounded by an off-schema single picker. */
abstract class RangePicker extends Field
{
    use CanBeReadOnly;
    use HasPanelOptions;
    use HasPlaceholder;
    use LoadsAssets;

    protected string $view = 'devletes-chrono-picker::range';

    protected ?RangeStorage $storage = null;

    protected string|Closure|null $startTarget = null;

    protected string|Closure|null $endTarget = null;

    protected RangeLayout|string|Closure $layout = RangeLayout::Expanded;

    protected string|Closure $rangeSeparator = ' – ';

    protected string|Closure|null $startLabel = null;

    protected string|Closure|null $endLabel = null;

    protected bool|Closure $isStartRequired = false;

    protected bool|Closure $isEndRequired = false;

    /** @var array<int, array{0: string, 1: array<int, mixed>}> */
    protected array $endPickerCalls = [];

    /** @var array<int, array{0: string|null, 1: Closure}> */
    protected array $endPickerModifiers = [];

    abstract protected function makeEndPicker(string $name): BaseDateTimePicker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->rule(static fn (self $component): Closure => static function (string $attribute, mixed $value, Closure $fail) use ($component): void {
            $component->validateRange($value, $fail);
        });
    }

    /** Stores the ends in two attributes beside the field. */
    public function storeIn(string|Closure $start, string|Closure $end): static
    {
        $this->storage = RangeStorage::Columns;
        $this->startTarget = $start;
        $this->endTarget = $end;

        return $this;
    }

    /** Stores the ends under named keys in one array attribute. */
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

    /**
     * @return array{start: string, end: string}
     */
    public function getStateKeys(): array
    {
        if ($this->getStorage() === RangeStorage::Json) {
            return ['start' => (string) $this->evaluate($this->startTarget), 'end' => (string) $this->evaluate($this->endTarget)];
        }

        return ['start' => 'start', 'end' => 'end'];
    }

    /**
     * @return array{start: string, end: string}
     */
    public function getColumnStatePaths(): array
    {
        $container = $this->getContainer()->getStatePath();
        $path = static fn (string $column): string => filled($container) ? "{$container}.{$column}" : $column;

        return [
            'start' => $path((string) $this->evaluate($this->startTarget)),
            'end' => $path((string) $this->evaluate($this->endTarget)),
        ];
    }

    public function layout(RangeLayout|string|Closure $layout): static
    {
        $this->layout = $layout;

        return $this;
    }

    public function expanded(): static
    {
        return $this->layout(RangeLayout::Expanded);
    }

    public function compact(): static
    {
        return $this->layout(RangeLayout::Compact);
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

    /** Requires the start and lets the end stay open; `required()` requires both. */
    public function requireStart(bool|Closure $condition = true): static
    {
        $this->isStartRequired = $condition;

        return $this;
    }

    /** Requires the end and lets the start stay open; `required()` requires both. */
    public function requireEnd(bool|Closure $condition = true): static
    {
        $this->isEndRequired = $condition;

        return $this;
    }

    public function isStartRequired(): bool
    {
        return $this->isRequired() || (bool) $this->evaluate($this->isStartRequired);
    }

    public function isEndRequired(): bool
    {
        return $this->isRequired() || (bool) $this->evaluate($this->isEndRequired);
    }

    public function isMarkedAsRequired(): bool
    {
        return $this->evaluate($this->isMarkedAsRequired) ?? ($this->isStartRequired() || $this->isEndRequired());
    }

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

    /** Configures the start's picker: `fn (DatePicker $picker) => $picker->minDate(today())`. */
    public function startPicker(Closure $callback): static
    {
        $this->endPickerModifiers[] = ['start', $callback];

        return $this;
    }

    /** Configures the end's picker: `fn (DatePicker $picker) => $picker->maxDate(today()->addYear())`. */
    public function endPicker(Closure $callback): static
    {
        $this->endPickerModifiers[] = ['end', $callback];

        return $this;
    }

    /** Configures both ends' pickers: `fn (DatePicker $picker, string $which) => ...`. */
    public function configureEndsUsing(Closure $callback): static
    {
        $this->endPickerModifiers[] = [null, $callback];

        return $this;
    }

    protected function forEachEnd(string $method, mixed ...$arguments): static
    {
        $this->endPickerCalls[] = [$method, $arguments];

        return $this;
    }

    public function getEndPicker(string $which): BaseDateTimePicker
    {
        $picker = $this->makeEndPicker("{$this->getName()}_{$which}")
            ->container($this->getContainer())
            ->label($which === 'start' ? $this->getStartLabel() : $this->getEndLabel());

        foreach ($this->endPickerCalls as [$method, $arguments]) {
            $picker->{$method}(...$arguments);
        }

        foreach ($this->endPickerModifiers as [$for, $modifier]) {
            if ($for === null || $for === $which) {
                $modifier($picker, $which);
            }
        }

        return $picker;
    }

    public function getPickerMode(): PickerMode
    {
        return $this->getEndPicker('start')->getPickerMode();
    }

    public function callAfterStateHydrated(): static
    {
        $this->hydrateRange();

        return parent::callAfterStateHydrated();
    }

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

        // Stored in columns, the field's own key is only a working copy.
        if ($isDehydrated && $this->isDehydrated() && $this->getStorage() === RangeStorage::Columns) {
            Arr::forget($state, $this->getStatePath());
        }
    }

    public function validateRange(mixed $value, Closure $fail): void
    {
        $keys = $this->getStateKeys();
        $value = is_array($value) ? $value : [];
        $start = filled($value[$keys['start']] ?? null) ? (string) $value[$keys['start']] : null;
        $end = filled($value[$keys['end']] ?? null) ? (string) $value[$keys['end']] : null;

        $startRequired = $this->isStartRequired();
        $endRequired = $this->isEndRequired();

        if ($start === null && $end === null) {
            match (true) {
                $startRequired && $endRequired => $fail(__('devletes-chrono-picker::chrono-picker.range.required')),
                $startRequired => $fail(__('devletes-chrono-picker::chrono-picker.range.missing', ['end' => $this->getStartLabel()])),
                $endRequired => $fail(__('devletes-chrono-picker::chrono-picker.range.missing', ['end' => $this->getEndLabel()])),
                default => null,
            };

            return;
        }

        // One end may be left open only when the other is the one required.
        foreach (['start' => [$start, $endRequired && ! $startRequired], 'end' => [$end, $startRequired && ! $endRequired]] as $which => [$internal, $mayBeOpen]) {
            if ($internal === null && ! $mayBeOpen) {
                $label = $which === 'start' ? $this->getStartLabel() : $this->getEndLabel();

                $fail(($startRequired || $endRequired)
                    ? __('devletes-chrono-picker::chrono-picker.range.missing', ['end' => $label])
                    : __('devletes-chrono-picker::chrono-picker.range.incomplete'));

                return;
            }
        }

        // A time range compares the time of day only.
        $comparable = fn (string $internal): string => $this->getPickerMode() === PickerMode::Time ? substr($internal, -8) : $internal;

        if ($start !== null && $end !== null && $comparable($end) < $comparable($start)) {
            $fail(__('devletes-chrono-picker::chrono-picker.range.order', [
                'end' => $this->getEndLabel(),
                'start' => $this->getStartLabel(),
            ]));
        }

        foreach (array_filter(['start' => $start, 'end' => $end]) as $which => $internal) {
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
            'isReadOnly' => $this->isReadOnly(),
            'labels' => [
                'start' => $this->getStartLabel(),
                'end' => $this->getEndLabel(),
            ],
        ];
    }

    /**
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
