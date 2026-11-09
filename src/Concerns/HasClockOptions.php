<?php

namespace Devletes\ChronoPicker\Concerns;

use Carbon\CarbonInterface;
use Carbon\Exceptions\InvalidFormatException;
use Closure;
use Illuminate\Support\Carbon;

trait HasClockOptions
{
    protected int|Closure|null $hoursCycle = null;

    protected CarbonInterface|string|Closure|null $minTime = null;

    protected CarbonInterface|string|Closure|null $maxTime = null;

    public function hoursCycle(int|Closure|null $cycle): static
    {
        $this->hoursCycle = $cycle;

        return $this;
    }

    public function minTime(CarbonInterface|string|Closure|null $time): static
    {
        $this->minTime = $time;

        return $this;
    }

    public function maxTime(CarbonInterface|string|Closure|null $time): static
    {
        $this->maxTime = $time;

        return $this;
    }

    public function getHoursCycle(): int
    {
        $cycle = $this->evaluate($this->hoursCycle)
            ?? config('chrono-picker.hours_cycle');

        if (in_array($cycle, [12, 24], strict: true)) {
            return $cycle;
        }

        return $this->displayFormatIsTwelveHour() ? 12 : 24;
    }

    public function getMinTime(): ?string
    {
        return $this->normaliseTime($this->evaluate($this->minTime));
    }

    public function getMaxTime(): ?string
    {
        return $this->normaliseTime($this->evaluate($this->maxTime));
    }

    /** Whether the display format has an unescaped `g` or `h`. */
    protected function displayFormatIsTwelveHour(): bool
    {
        $unescaped = preg_replace('/\\\\./', '', $this->getDisplayFormat());

        return strpbrk($unescaped, 'gh') !== false;
    }

    protected function normaliseTime(CarbonInterface|string|null $time): ?string
    {
        if (blank($time)) {
            return null;
        }

        if ($time instanceof CarbonInterface) {
            return $time->format('H:i:s');
        }

        try {
            return Carbon::parse($time)->format('H:i:s');
        } catch (InvalidFormatException) {
            return null;
        }
    }
}
