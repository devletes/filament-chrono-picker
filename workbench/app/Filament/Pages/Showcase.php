<?php

namespace Workbench\App\Filament\Pages;

use BackedEnum;
use Devletes\ChronoPicker\Forms\Components\DatePicker;
use Devletes\ChronoPicker\Forms\Components\DateRangePicker;
use Devletes\ChronoPicker\Forms\Components\DateTimePicker;
use Devletes\ChronoPicker\Forms\Components\MonthPicker;
use Devletes\ChronoPicker\Forms\Components\MonthRangePicker;
use Devletes\ChronoPicker\Forms\Components\TimePicker;
use Devletes\ChronoPicker\Forms\Components\TimeRangePicker;
use Devletes\ChronoPicker\Forms\Components\YearPicker;
use Devletes\ChronoPicker\Forms\Components\YearRangePicker;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/** Every component on one form, each section tagged with data-demo for the screenshot script. */
class Showcase extends Page implements HasSchemas
{
    use InteractsWithSchemas;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

    protected static ?string $navigationLabel = 'Deliveries';

    protected static ?string $title = 'Schedule a delivery';

    protected static ?string $slug = 'deliveries';

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'deliver_at' => '14:30',
            'cutoff' => '17:00',
            'pickup' => '11:15',
            'ships_at' => now()->addDays(2)->setTime(10, 0)->format('Y-m-d H:i:s'),
            'loading_time' => '08:45:30',
            'call_back_at' => '16:00',
            'delivered_on' => now()->addWeek()->toDateString(),
            'billing_month' => now()->format('Y-m'),
            'fiscal_year' => now()->year,
            'window_starts_on' => now()->addDays(3)->toDateString(),
            'window_ends_on' => now()->addDays(9)->toDateString(),
            'closure' => ['from' => now()->setDate(now()->year, 12, 24)->toDateString(), 'to' => now()->setDate(now()->year + 1, 1, 2)->toDateString()],
            'contract_starts' => now()->startOfYear()->format('Y-m'),
            'contract_ends' => now()->startOfYear()->addMonths(5)->format('Y-m'),
            'service_from' => 2018,
            'service_to' => now()->year,
            'shift_starts' => '07:30',
            'shift_ends' => '16:00',
        ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedSchema::make('form')]);
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('save')
                ->action(function (): void {
                    $state = $this->form->getState();

                    Notification::make()
                        ->title('Saved')
                        ->body(json_encode(collect($state)->except(['deliver_at', 'cutoff', 'pickup', 'loading_time', 'call_back_at'])->all()))
                        ->success()
                        ->send();
                }),
        ];
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)->schema([
                    Section::make('Delivery slot')
                        ->extraAttributes(['data-demo' => 'twelve-hour'])
                        ->schema([
                            TimePicker::make('deliver_at')
                                ->label('Deliver at')
                                ->seconds(false)
                                ->displayFormat('g:i A'),
                        ]),

                    Section::make('Same-day cutoff')
                        ->extraAttributes(['data-demo' => 'twenty-four-hour'])
                        ->schema([
                            TimePicker::make('cutoff')
                                ->label('Orders close at')
                                ->seconds(false)
                                ->displayFormat('H:i'),
                        ]),

                    Section::make('Store pickup')
                        ->extraAttributes(['data-demo' => 'bounds'])
                        ->schema([
                            TimePicker::make('pickup')
                                ->label('Pickup time')
                                ->seconds(false)
                                ->displayFormat('g:i A')
                                ->minTime('09:00')
                                ->maxTime('17:00')
                                ->helperText('Open 9 AM to 5 PM'),
                        ]),

                    Section::make('Courier call-back')
                        ->extraAttributes(['data-demo' => 'close-on-complete'])
                        ->schema([
                            TimePicker::make('call_back_at')
                                ->label('Call the courier at')
                                ->seconds(false)
                                ->displayFormat('g:i A')
                                ->closeOnComplete()
                                ->helperText('Picking a minute saves and closes'),
                        ]),

                    Section::make('Loading dock')
                        ->extraAttributes(['data-demo' => 'seconds'])
                        ->schema([
                            TimePicker::make('loading_time')
                                ->label('Truck loaded at')
                                ->seconds()
                                ->displayFormat('H:i:s'),
                        ]),

                    Section::make('Dispatch')
                        ->extraAttributes(['data-demo' => 'date-time'])
                        ->schema([
                            DateTimePicker::make('ships_at')
                                ->label('Ships at')
                                ->seconds(false)
                                ->displayFormat('M j, Y g:i A')
                                ->minDate(now()->startOfDay())
                                ->minTime('08:00')
                                ->maxTime('20:00'),
                        ]),

                    Section::make('Delivery date')
                        ->extraAttributes(['data-demo' => 'date'])
                        ->schema([
                            DatePicker::make('delivered_on')
                                ->label('Deliver on')
                                ->minDate(now()->startOfDay())
                                ->maxDate(now()->addYears(2)->endOfYear())
                                ->helperText('Click the month or the year for a grid'),
                        ]),

                    Section::make('Invoicing')
                        ->extraAttributes(['data-demo' => 'month'])
                        ->schema([
                            MonthPicker::make('billing_month')
                                ->label('Bill with the month of')
                                ->maxDate(now()->addMonths(6))
                                ->helperText('Stored as Y-m'),
                        ]),

                    Section::make('Reporting')
                        ->extraAttributes(['data-demo' => 'year'])
                        ->schema([
                            YearPicker::make('fiscal_year')
                                ->label('Fiscal year')
                                ->minDate(2015)
                                ->maxDate(now()->addYear())
                                ->helperText('Stored as Y'),
                        ]),

                    Section::make('Delivery window')
                        ->extraAttributes(['data-demo' => 'date-range'])
                        ->schema([
                            DateRangePicker::make('window')
                                ->label('Deliver between')
                                ->storeIn(start: 'window_starts_on', end: 'window_ends_on')
                                ->minDate(now()->startOfDay())
                                ->helperText('Side by side, stored in two columns'),
                        ]),

                    Section::make('Holiday closure')
                        ->extraAttributes(['data-demo' => 'date-range-one-at-a-time'])
                        ->schema([
                            DateRangePicker::make('closure')
                                ->label('Depot closed')
                                ->storeAsJson(start: 'from', end: 'to')
                                ->oneAtATime()
                                ->helperText('One at a time, stored as JSON'),
                        ]),

                    Section::make('Contract term')
                        ->extraAttributes(['data-demo' => 'month-range'])
                        ->schema([
                            MonthRangePicker::make('contract')
                                ->label('Courier contract')
                                ->storeIn(start: 'contract_starts', end: 'contract_ends'),
                        ]),

                    Section::make('Years of service')
                        ->extraAttributes(['data-demo' => 'year-range'])
                        ->schema([
                            YearRangePicker::make('service')
                                ->label('Served the route')
                                ->storeIn(start: 'service_from', end: 'service_to')
                                ->oneAtATime(),
                        ]),

                    Section::make('Driver shift')
                        ->extraAttributes(['data-demo' => 'time-range'])
                        ->columnSpanFull()
                        ->schema([
                            TimeRangePicker::make('shift')
                                ->label('On the road')
                                ->storeIn(start: 'shift_starts', end: 'shift_ends')
                                ->seconds(false)
                                ->displayFormat('g:i A')
                                ->minTime('06:00')
                                ->maxTime('22:00'),
                        ]),
                ]),
            ])
            ->statePath('data');
    }
}
