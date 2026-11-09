<?php

namespace Devletes\ChronoPicker\Concerns;

use Closure;

trait HasPanelOptions
{
    protected string|Closure|null $okLabel = null;

    protected string|Closure|null $cancelLabel = null;

    protected string|Closure|null $nowLabel = null;

    protected bool|Closure|null $hasNowButton = null;

    protected bool|Closure|null $shouldCloseOnComplete = null;

    public function okLabel(string|Closure|null $label): static
    {
        $this->okLabel = $label;

        return $this;
    }

    public function cancelLabel(string|Closure|null $label): static
    {
        $this->cancelLabel = $label;

        return $this;
    }

    public function nowLabel(string|Closure|null $label): static
    {
        $this->nowLabel = $label;

        return $this;
    }

    public function nowButton(bool|Closure|null $condition = true): static
    {
        $this->hasNowButton = $condition;

        return $this;
    }

    public function closeOnComplete(bool|Closure|null $condition = true): static
    {
        $this->shouldCloseOnComplete = $condition;

        return $this;
    }

    public function getOkLabel(): string
    {
        return $this->evaluate($this->okLabel) ?? __('devletes-chrono-picker::chrono-picker.ok');
    }

    public function getCancelLabel(): string
    {
        return $this->evaluate($this->cancelLabel) ?? __('devletes-chrono-picker::chrono-picker.cancel');
    }

    public function getNowLabel(): string
    {
        return $this->evaluate($this->nowLabel) ?? $this->getDefaultNowLabel();
    }

    /** "Now" for a clock, "Today" for a calendar, "This month" / "This year" for the coarser pickers. */
    protected function getDefaultNowLabel(): string
    {
        return __('devletes-chrono-picker::chrono-picker.now_labels.'.$this->getPickerMode()->value);
    }

    public function hasNowButton(): bool
    {
        return (bool) ($this->evaluate($this->hasNowButton) ?? config('chrono-picker.now_button', true));
    }

    public function shouldCloseOnComplete(): bool
    {
        return (bool) ($this->evaluate($this->shouldCloseOnComplete) ?? config('chrono-picker.close_on_complete', false));
    }
}
