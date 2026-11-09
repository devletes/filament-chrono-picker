<?php

namespace Devletes\ChronoPicker\Tests\Fixtures;

use Devletes\ChronoPicker\Forms\Components\MonthPicker;
use Devletes\ChronoPicker\Forms\Components\YearPicker;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component;

class PeriodPickerComponent extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    /** @var array<string, mixed> */
    public ?array $data = [];

    public ?string $minMonth = null;

    public ?string $maxMonth = null;

    public ?string $minYear = null;

    public ?string $maxYear = null;

    /** @var array<string, mixed>|null */
    public ?array $saved = null;

    /**
     * @param  array<string, mixed>  $state
     */
    public function mount(array $state = []): void
    {
        $this->form->fill($state);
    }

    public function save(): void
    {
        $this->saved = $this->form->getState();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                MonthPicker::make('billing_month')
                    ->minDate(fn (): ?string => $this->minMonth)
                    ->maxDate(fn (): ?string => $this->maxMonth),
                YearPicker::make('fiscal_year')
                    ->minDate(fn (): ?string => $this->minYear)
                    ->maxDate(fn (): ?string => $this->maxYear),
            ])
            ->statePath('data');
    }

    public function render(): string
    {
        return <<<'BLADE'
            <div>
                {{ $this->form }}
            </div>
        BLADE;
    }
}
