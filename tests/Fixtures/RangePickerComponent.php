<?php

namespace Devletes\ChronoPicker\Tests\Fixtures;

use Devletes\ChronoPicker\Forms\Components\DateRangePicker;
use Devletes\ChronoPicker\Forms\Components\MonthRangePicker;
use Devletes\ChronoPicker\Forms\Components\TimeRangePicker;
use Devletes\ChronoPicker\Forms\Components\YearRangePicker;
use Devletes\ChronoPicker\Tests\Fixtures\Models\Booking;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Livewire\Component;

/** Every storage mode on one form: columns, JSON, a relationship repeater, and month, year and time ranges. */
class RangePickerComponent extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    /** @var array<string, mixed> */
    public ?array $data = [];

    public ?Booking $record = null;

    public ?string $minDate = null;

    public ?string $maxDate = null;

    public bool $required = false;

    public bool $withRepeater = false;

    /** @var array<string, mixed>|null */
    public ?array $saved = null;

    /**
     * @param  array<string, mixed>  $state
     */
    public function mount(?Booking $record = null, array $state = [], bool $withRepeater = false): void
    {
        // Laravel 12's container injects an unsaved model when none is passed.
        $this->record = $record?->exists ? $record : null;
        $this->withRepeater = $withRepeater;

        $this->form->fill($this->record?->attributesToArray() ?? $state);
    }

    public function save(): void
    {
        $this->saved = $this->form->getState();

        if ($this->record) {
            $this->record->update(collect($this->saved)->except(['legs', 'billing'])->all());
        }
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->model($this->record ?? Booking::class)
            ->components([
                DateRangePicker::make('dates')
                    ->storeIn(start: 'starts_on', end: 'ends_on')
                    ->required(fn (): bool => $this->required)
                    ->minDate(fn (): ?string => $this->minDate)
                    ->maxDate(fn (): ?string => $this->maxDate),
                DateRangePicker::make('stay')
                    ->storeAsJson(start: 'from', end: 'to')
                    ->oneAtATime(),
                MonthRangePicker::make('billing')
                    ->storeAsJson(start: 'from', end: 'to'),
                YearRangePicker::make('fiscal')
                    ->storeIn(start: 'first_year', end: 'last_year'),
                TimeRangePicker::make('shift')
                    ->storeIn(start: 'shift_starts', end: 'shift_ends')
                    ->seconds(false)
                    ->minTime('06:00')
                    ->maxTime('22:00'),
                Repeater::make('legs')
                    ->relationship()
                    ->visible(fn (): bool => $this->withRepeater)
                    ->schema([
                        DateRangePicker::make('dates')->storeIn(start: 'starts_on', end: 'ends_on'),
                    ]),
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
