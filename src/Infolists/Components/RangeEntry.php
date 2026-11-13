<?php

namespace Devletes\ChronoPicker\Infolists\Components;

use Carbon\CarbonInterface;
use Carbon\Exceptions\InvalidFormatException;
use Closure;
use Devletes\ChronoPicker\Enums\RangeStorage;
use Devletes\ChronoPicker\StateCasts\PeriodStateCast;
use Filament\Infolists\Components\TextEntry;
use Illuminate\Support\Carbon;
use LogicException;

/** A range read where its picker stored it, shown as one line: "Jan 2026 – Jun 2026". */
class RangeEntry extends TextEntry
{
    /** Stored shapes, tried in order before Carbon's own parser. */
    protected const FORMATS = ['Y-m-d H:i:s', 'Y-m-d', 'Y-m', 'Y', 'H:i:s', 'H:i'];

    protected ?RangeStorage $storage = null;

    protected string|Closure|null $startTarget = null;

    protected string|Closure|null $endTarget = null;

    protected string|Closure|null $rangeDisplayFormat = null;

    protected string|Closure $rangeSeparator = ' – ';

    protected function setUp(): void
    {
        parent::setUp();

        $this->getConstantStateUsing(static fn (RangeEntry $component): ?string => $component->getRangeText());
    }

    /** Reads the ends from two attributes, where the picker's `storeIn()` writes them. */
    public function fromColumns(string|Closure $start, string|Closure $end): static
    {
        $this->storage = RangeStorage::Columns;
        $this->startTarget = $start;
        $this->endTarget = $end;

        return $this;
    }

    /** Reads the ends from one array under the entry's name, where the picker's `storeAsJson()` writes them. */
    public function fromJson(string|Closure $start = 'start', string|Closure $end = 'end'): static
    {
        $this->storage = RangeStorage::Json;
        $this->startTarget = $start;
        $this->endTarget = $end;

        return $this;
    }

    /** How each end is written, as a PHP date format: `M j, Y`, `M Y`, `Y`, `g:i A`. */
    public function displayFormat(string|Closure|null $format): static
    {
        $this->rangeDisplayFormat = $format;

        return $this;
    }

    /** The text between the two ends (default ` – `). */
    public function rangeSeparator(string|Closure $separator): static
    {
        $this->rangeSeparator = $separator;

        return $this;
    }

    public function getDisplayFormat(): string
    {
        return $this->evaluate($this->rangeDisplayFormat) ?? $this->getContainer()->getDefaultDateDisplayFormat();
    }

    public function getRangeSeparator(): string
    {
        return $this->evaluate($this->rangeSeparator);
    }

    public function getStorage(): RangeStorage
    {
        if ($this->storage === null) {
            throw new LogicException(
                "RangeEntry [{$this->getName()}] does not say where its range is stored. Call ->fromColumns(start: 'starts_on', end: 'ends_on') "
                ."for two columns, or ->fromJson(start: 'from', end: 'to') for one array column."
            );
        }

        return $this->storage;
    }

    /** "start – end", "start –" or "– end" for an open end, and null when there is neither. */
    public function getRangeText(): ?string
    {
        $storage = $this->getStorage();
        $container = $this->getContainer()->getConstantState();
        $start = (string) $this->evaluate($this->startTarget);
        $end = (string) $this->evaluate($this->endTarget);

        if ($storage === RangeStorage::Columns) {
            $values = [data_get($container, $start), data_get($container, $end)];
        } else {
            $stored = data_get($container, $this->getName());
            $stored = is_string($stored) ? json_decode($stored, associative: true) : $stored;
            $stored = is_array($stored) ? $stored : [];
            $values = [$stored[$start] ?? null, $stored[$end] ?? null];
        }

        [$start, $end] = array_map(fn (mixed $value): ?string => $this->formatEnd($value), $values);

        if ($start === null && $end === null) {
            return null;
        }

        return trim(($start ?? '').$this->getRangeSeparator().($end ?? ''));
    }

    protected function formatEnd(mixed $value): ?string
    {
        if (blank($value)) {
            return null;
        }

        $date = $value instanceof CarbonInterface ? $value : null;

        foreach (self::FORMATS as $format) {
            $date ??= PeriodStateCast::parse($value, $format);
        }

        try {
            $date ??= Carbon::parse((string) $value);
        } catch (InvalidFormatException) {
            return (string) $value;
        }

        return $date->translatedFormat($this->getDisplayFormat());
    }
}
