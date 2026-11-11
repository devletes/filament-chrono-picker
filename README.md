# Chrono Picker for Filament

[![Latest Version on Packagist](https://img.shields.io/packagist/v/devletes/filament-chrono-picker.svg?style=flat-square)](https://packagist.org/packages/devletes/filament-chrono-picker)
[![Total Downloads](https://img.shields.io/packagist/dt/devletes/filament-chrono-picker.svg?style=flat-square)](https://packagist.org/packages/devletes/filament-chrono-picker)
[![License](https://img.shields.io/packagist/l/devletes/filament-chrono-picker.svg?style=flat-square)](https://packagist.org/packages/devletes/filament-chrono-picker)
[![GitHub Stars](https://img.shields.io/github/stars/devletes/filament-chrono-picker?style=flat-square)](https://github.com/devletes/filament-chrono-picker/stargazers)

Time, date, date-time, month, year and range pickers for Filament that look like Filament's own: a clock face with hour and minute dials, a calendar whose month and year open as grids, and ranges that store into the columns you name.

| Single value | Range |
| --- | --- |
| `TimePicker` | `TimeRangePicker` |
| `DatePicker` | `DateRangePicker` |
| `DateTimePicker` | |
| `MonthPicker` | `MonthRangePicker` |
| `YearPicker` | `YearRangePicker` |

- **Drop-in replacements.** `TimePicker`, `DatePicker` and `DateTimePicker` extend Filament's own, so swapping the import is the whole migration; `MonthPicker` and `YearPicker` extend Filament's `DatePicker` too
- **Ranges stored your way.** Two columns you name (`check_in` / `check_out`), or one JSON column with keys you name, never a guess
- **No dependencies.** No jQuery, no date library, no build step: vanilla JavaScript on the Alpine you already have
- **Loaded on request.** The script and stylesheet are fetched by the first field that needs them, so a page without one loads nothing
- **The dial follows the field.** `g:i A` gets a 12-hour face with AM/PM, `H:i` gets a 24-hour face with an inner ring
- **Every minute is reachable**, not every fifth, and the steps still coarsen it when you want that
- **Bounds that hold.** `minTime()` / `maxTime()` dim the dial, clamp typing, and are re-checked on the server
- **A calendar to match**, with min and max dates, disabled dates and your locale's month and weekday names; click the month or the year for a grid of either
- Themed off Filament's palette, following your panel's colour, light and dark

<table><tr>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/layout_light.png" alt="The clock open on a form (light)"></td>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/layout_dark.png" alt="The clock open on a form (dark)"></td>
</tr></table>

## Requirements

- PHP `^8.2`
- Filament `^5.0` on Laravel 12 or 13

## Installation

```bash
composer require devletes/filament-chrono-picker
```

Then publish Filament's assets so the stylesheet and script are served on your panels:

```bash
php artisan filament:assets
```

The package registers its stylesheet and script with Filament's asset manager as **on-request** assets: neither is in the page's `<head>`, and the first picker on a page fetches both (Filament's `x-load` and `x-load-css`). There is nothing to add to your custom Tailwind theme. Run `filament:assets` again after every update of the package, as for any Filament plugin.

## Quick start

```php
use Devletes\ChronoPicker\Forms\Components\DatePicker;
use Devletes\ChronoPicker\Forms\Components\DateRangePicker;
use Devletes\ChronoPicker\Forms\Components\DateTimePicker;
use Devletes\ChronoPicker\Forms\Components\MonthPicker;
use Devletes\ChronoPicker\Forms\Components\TimePicker;
use Devletes\ChronoPicker\Forms\Components\YearPicker;

TimePicker::make('deliver_at')
    ->seconds(false)
    ->displayFormat('g:i A');

DatePicker::make('delivered_on');

DateTimePicker::make('ships_at')
    ->seconds(false)
    ->displayFormat('M j, Y g:i A');

MonthPicker::make('billing_month');          // stored as 2026-11
YearPicker::make('fiscal_year');             // stored as 2026

DateRangePicker::make('booking')
    ->storeIn(start: 'check_in', end: 'check_out');
```

Clicking anywhere on a field opens its panel.

## A drop-in replacement

Each component **extends** Filament's own, so swapping the import is the whole migration:

```diff
-use Filament\Forms\Components\TimePicker;
+use Devletes\ChronoPicker\Forms\Components\TimePicker;

-use Filament\Forms\Components\DatePicker;
+use Devletes\ChronoPicker\Forms\Components\DatePicker;

-use Filament\Forms\Components\DateTimePicker;
+use Devletes\ChronoPicker\Forms\Components\DateTimePicker;
```

Everything you already use still works and still means the same thing: `seconds()`, the steps, `format()`, `displayFormat()`, `timezone()`, `live()`, `disabled()`, the affixes and the validation. The state cast is Filament's own, so nothing about what is stored changes.

## The dial follows the field

Say nothing, and the face is read off the display format. A field that writes its hour as 1–12 gets the 12-hour face with an AM/PM switch; anything else gets the 24-hour face, with 13–23 and midnight on an inner ring.

```php
TimePicker::make('deliver_at')->displayFormat('g:i A');   // 12-hour face
TimePicker::make('cutoff')->displayFormat('H:i');         // 24-hour face
TimePicker::make('cutoff')->hoursCycle(24);               // say so yourself
```

<table><tr>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/twelve_hour_light.png" alt="The 12-hour face (light)"></td>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/twelve_hour_dark.png" alt="The 12-hour face (dark)"></td>
</tr></table>

<table><tr>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/twenty_four_hour_light.png" alt="The 24-hour face with its inner ring (light)"></td>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/twenty_four_hour_dark.png" alt="The 24-hour face with its inner ring (dark)"></td>
</tr></table>

Set `chrono-picker.hours_cycle` in the config to make one face the rule everywhere.

## Minutes and seconds

Picking an hour moves to the minute dial. The dial takes **every minute**: the numerals are drawn at the fives, but dragging between them lands on 9:07 the way you would expect. A small label above the pin says which unit you are setting.

```php
TimePicker::make('slot')->minutesStep(15);   // quarter hours only
TimePicker::make('slot')->hoursStep(2);
```

<table><tr>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/minutes_light.png" alt="The minute dial (light)"></td>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/minutes_dark.png" alt="The minute dial (dark)"></td>
</tr></table>

`seconds()` adds a third dial, and `secondsStep()` coarsens it the same way.

```php
TimePicker::make('loaded_at')
    ->seconds()
    ->displayFormat('H:i:s');
```

<table><tr>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/seconds_light.png" alt="The seconds dial (light)"></td>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/seconds_dark.png" alt="The seconds dial (dark)"></td>
</tr></table>

## Bounds

```php
TimePicker::make('pickup')
    ->minTime('09:00')
    ->maxTime('17:00');
```

Numerals outside the range are dimmed and refuse the pointer, typing is pulled back inside, and the same bound is checked again on the server, since the panel is not the only way a value arrives. The message names the bound in the field's own format ("must be no earlier than 9:00 AM"). Both accept a string, a `Carbon` instance, or a closure.

<table><tr>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/bounds_light.png" alt="Hours outside 9 AM–5 PM dimmed (light)"></td>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/bounds_dark.png" alt="Hours outside 9 AM–5 PM dimmed (dark)"></td>
</tr></table>

## Date and time

`DateTimePicker` pairs the same clock with a calendar. Picking a day moves straight to the clock, and the switch at the top of the panel moves between the two.

```php
DateTimePicker::make('ships_at')
    ->seconds(false)
    ->displayFormat('M j, Y g:i A')
    ->minDate(now()->startOfDay())
    ->maxDate(now()->addMonths(3))
    ->disabledDates(['2026-12-25', '2026-12-26'])
    ->minTime('08:00')
    ->maxTime('20:00');
```

<table><tr>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/date_time_calendar_light.png" alt="The calendar (light)"></td>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/date_time_calendar_dark.png" alt="The calendar (dark)"></td>
</tr></table>

<table><tr>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/date_time_clock_light.png" alt="The clock after picking a day (light)"></td>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/date_time_clock_dark.png" alt="The clock after picking a day (dark)"></td>
</tr></table>

`minDate()` and `maxDate()` disable the days outside them, and on the first and last day their times narrow the clock too. `minTime()` / `maxTime()` stay a daily window, applied on every day. Month and weekday names follow the field's locale.

Filament's other date options apply as they do there:

```php
DateTimePicker::make('ships_at')
    ->weekStartsOnSunday()                  // or firstDayOfWeek(0–7)
    ->defaultFocusedDate(now()->addWeek())  // the month an empty field opens on
    ->closeOnDateSelection();               // pick a day and close, without the clock
```

## Dates

`DatePicker` is the calendar alone: Filament's `DatePicker`, stored as `Y-m-d` and shown as `M j, Y` unless you say otherwise. Every date option above applies. (`DateTimePicker::make(...)->time(false)` is the same field.)

```php
DatePicker::make('delivered_on')
    ->minDate(now()->startOfDay())
    ->maxDate(now()->addYears(2)->endOfYear());
```

<table><tr>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/date_calendar_light.png" alt="The date picker (light)"></td>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/date_calendar_dark.png" alt="The date picker (dark)"></td>
</tr></table>

## Month and year grids

Every calendar's header is the month and the year between two arrows. Click the **month** for a grid of the twelve months of that year, whose arrows step a year at a time. Click the **year** (on either view) for a grid of twelve years, whose arrows page through earlier and later dozens. Picking in a grid goes back to the view it was opened from, so a date three years away is three clicks. Months and years wholly outside `minDate()` / `maxDate()` are dimmed and refuse the pointer; the current month and year are marked in the accent colour.

<table><tr>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/date_months_light.png" alt="The month grid (light)"></td>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/date_years_dark.png" alt="The year grid (dark)"></td>
</tr></table>

## Months and years

`MonthPicker` picks a month of a year and `YearPicker` a year. Both are Filament's `DatePicker` underneath, so `required()`, `minDate()`, `maxDate()`, `format()`, `displayFormat()`, `default()`, `live()` and validation behave as they do there.

| | Stored as | Shown as | Opens on |
| --- | --- | --- | --- |
| `MonthPicker` | `Y-m` (`2026-11`) | `M Y` (`Nov 2026`) | the twelve months of the year, under a year that opens the year grid |
| `YearPicker` | `Y` (`2026`) | `Y` | a page of twelve years, with arrows to page |

```php
MonthPicker::make('billing_month')
    ->maxDate(now()->addMonths(6));

YearPicker::make('fiscal_year')
    ->minDate(2015)
    ->maxDate(now()->addYear());

MonthPicker::make('period')->format('m/Y')->displayFormat('F Y');   // store and show as you like
```

Bounds are whole periods: `minDate('2026-03')` allows March onwards and `maxDate(2027)` allows all of 2027, on the server as in the panel. A bound may be a `Y-m` or `Y` string, an integer year, a `Carbon` instance or a closure. Reading a stored `2026-02` on the 31st of a month stays February (Filament's own cast would roll it into March), and a year stored as an integer column reads as well as a string.

<table><tr>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/month_light.png" alt="The month picker (light)"></td>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/month_years_dark.png" alt="The month picker's year grid (dark)"></td>
</tr></table>

<table><tr>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/year_light.png" alt="The year picker (light)"></td>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/year_dark.png" alt="The year picker (dark)"></td>
</tr></table>

## Ranges

`DateRangePicker`, `MonthRangePicker`, `YearRangePicker` and `TimeRangePicker` put both ends of a range in one field: "Jan 1, 2026 – Jan 20, 2026", "Jan 2026 – Jun 2026", "2018 – 2026", "7:30 AM – 4:00 PM". Each end is the matching single picker, so it reads, stores and is bounded exactly as that picker would be.

```php
use Devletes\ChronoPicker\Forms\Components\DateRangePicker;
use Devletes\ChronoPicker\Forms\Components\MonthRangePicker;
use Devletes\ChronoPicker\Forms\Components\TimeRangePicker;
use Devletes\ChronoPicker\Forms\Components\YearRangePicker;

DateRangePicker::make('booking')
    ->storeIn(start: 'check_in', end: 'check_out');            // 2026-01-01 / 2026-01-20

MonthRangePicker::make('contract')
    ->storeIn(start: 'contract_starts', end: 'contract_ends'); // 2026-01 / 2026-06

YearRangePicker::make('service')
    ->storeAsJson(start: 'from', end: 'to');                   // ['from' => '2018', 'to' => '2026']

TimeRangePicker::make('shift')
    ->storeIn(start: 'shift_starts', end: 'shift_ends')
    ->seconds(false)
    ->displayFormat('g:i A');                                  // 07:30 / 16:00
```

### Where the range is stored

You say where, every time; there is no default.

**Two columns** (the common case): the field reads both ends from the attributes you name and writes both back. Its own name is only the field's, and is not saved.

```php
DateRangePicker::make('booking')
    ->storeIn(start: 'check_in', end: 'check_out');
```

On an Eloquent record, `check_in` and `check_out` are the record's columns; inside a `Repeater` (with or without `->relationship()`), each item's own; on a Livewire component with no model, the keys beside the field in its state. The form's state comes back as `['check_in' => '2026-01-01', 'check_out' => '2026-01-20']`.

**One JSON column**: both ends in an array under the field's own name, with the keys you name. Give the model an `array` (or `json`) cast; a JSON string is read too.

```php
DateRangePicker::make('vacation')
    ->storeAsJson(start: 'from', end: 'to');   // ['from' => '2026-12-24', 'to' => '2027-01-02']
```

An empty range saves two nulls in columns, or `null` in JSON. A field that says neither throws a `LogicException` naming both methods as soon as it is filled or rendered.

Each end is stored in its picker's format: `Y-m-d` for dates, `Y-m` for months, `Y` for years, `H:i` (or `H:i:s` with `seconds()`) for times. `format()` changes it for both ends.

### Layout

**Expanded** (the default): From on the left, To on the right, both open at once, each headed by its label.

```php
DateRangePicker::make('booking')->storeIn(start: 'check_in', end: 'check_out');
```

<table><tr>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/date_range_light.png" alt="An expanded date range (light)"></td>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/date_range_dark.png" alt="An expanded date range (dark)"></td>
</tr></table>

**Compact**: one panel with a From / To switch over it. Picking From moves the panel on to To. Clicking the From or To half of the field's text opens that end.

```php
DateRangePicker::make('vacation')
    ->storeAsJson(start: 'from', end: 'to')
    ->compact();               // or ->layout(RangeLayout::Compact) / ->layout('compact')
```

<table><tr>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/date_range_compact_light.png" alt="A compact date range, on To (light)"></td>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/date_range_compact_dark.png" alt="A compact date range, on To (dark)"></td>
</tr></table>

Either way:

- each end is saved as soon as it is picked, so the field updates as you go and **OK** or a click outside just closes the panel;
- **Cancel** puts back the range the panel opened on, and **Clear** empties both ends;
- the To panel opens where From is and dims what comes before it, and a From picked after the current To clears To;
- `closeOnComplete()` closes the panel as soon as To is picked.

### Labels

The ends are called **From** and **To** unless you name them. The names head each end in the panel (or label the compact switch), stand in for an empty half of the field, and name the end in validation messages.

```php
DateRangePicker::make('booking')
    ->storeIn(start: 'check_in', end: 'check_out')
    ->startLabel('Check-in')
    ->endLabel('Check-out')
    ->placeholder('Arriving')          // the empty start half's text, in place of its label
    ->rangeSeparator(' to ');          // between the two halves of the field (default ' – ')
```

Both labels accept a closure, and the defaults are translatable (`devletes-chrono-picker::chrono-picker.range.start` / `.end`).

### Months, years and times

```php
MonthRangePicker::make('contract')
    ->storeIn(start: 'contract_starts', end: 'contract_ends');      // 2026-01 / 2026-06

YearRangePicker::make('service')
    ->storeIn(start: 'service_from', end: 'service_to')
    ->compact();                                                // 2018 / 2026

TimeRangePicker::make('shift')
    ->storeIn(start: 'shift_starts', end: 'shift_ends')
    ->seconds(false)
    ->displayFormat('g:i A')
    ->minTime('06:00')
    ->maxTime('22:00');                                            // 07:30 / 16:00
```

<table><tr>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/month_range_light.png" alt="A month range (light)"></td>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/year_range_dark.png" alt="A compact year range (dark)"></td>
</tr></table>

<table><tr>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/time_range_light.png" alt="A time range on two clocks (light)"></td>
<td width="50%"><img src="https://raw.githubusercontent.com/devletes/filament-chrono-picker/main/docs/images/time_range_dark.png" alt="A time range on two clocks (dark)"></td>
</tr></table>

A time range is within one day: the end may not be before the start, so a shift past midnight is two fields.

### Validation

On the server, whatever arrived:

- both ends or neither, unless one end is required on its own (below);
- the end not before the start (the same moment is allowed);
- `minDate()` / `maxDate()` (or `minTime()` / `maxTime()`) on each end, as whole periods for months and years.

`required()` asks for both ends. To require one and leave the other open, say which:

```php
DateRangePicker::make('employment')
    ->storeIn(start: 'hired_on', end: 'left_on')
    ->requireStart();     // a start is required; the end may stay open (still employed)

DateRangePicker::make('offer')
    ->storeIn(start: 'opens_on', end: 'closes_on')
    ->requireEnd();       // an end is required; the start may stay open (open now)
```

Either marks the field as required, accepts a closure, and names the missing end in its message: "From is required."

Messages name each end by its [label](#labels): "To must not be before From."

### Options

These single-picker options can be set on the range, and apply to both ends: `format()`, `displayFormat()`, `minDate()`, `maxDate()`, `minTime()`, `maxTime()`, `disabledDates()`, `defaultFocusedDate()`, `firstDayOfWeek()`, `weekStartsOnMonday()`, `weekStartsOnSunday()`, `timezone()`, `locale()`, `hoursCycle()`, `seconds()`, `hoursStep()`, `minutesStep()`, `secondsStep()`.

To set something on one end only, or an option the range does not pass on, configure that end's picker. Each end is a single picker (a `DatePicker` for a date range):

```php
DateRangePicker::make('booking')
    ->storeIn(start: 'check_in', end: 'check_out')
    ->startPicker(fn (DatePicker $picker) => $picker->minDate(today()))               // no check-in in the past
    ->endPicker(fn (DatePicker $picker) => $picker->maxDate(today()->addYear()));    // nor a check-out a year out
```

Whatever you set there applies in the panel and in the server-side validation alike. `configureEndsUsing(fn (DatePicker $picker, string $which) => ...)` configures both from one closure.

## Typing and the keyboard

The big digits in the panel are inputs. Click one and type; it moves to the minute on its own when the hour cannot be extended. The arrow keys nudge by the field's own step, Escape closes the panel, and Backspace on the closed field clears it.

## Closing and the footer

By default a single picker waits for **OK**. To save and close as soon as the last dial is set (the minute, or the second when seconds are on), and hide OK:

```php
TimePicker::make('call_back_at')->closeOnComplete();
```

The footer's buttons are Filament's own. Relabel them, or drop the "Now" shortcut:

```php
TimePicker::make('deliver_at')
    ->okLabel('Set time')
    ->cancelLabel('Back')
    ->nowLabel('Right now')
    ->nowButton(false);
```

## On phones

`native()` hands rendering back to Filament, which gives the browser's own control. On a phone the platform's picker is the better choice:

```php
TimePicker::make('deliver_at')
    ->native(fn (): bool => str_contains((string) request()->userAgent(), 'Mobile'));
```

## Configuration

`config/chrono-picker.php` sets the defaults every field starts from:

```bash
php artisan vendor:publish --tag=devletes-chrono-picker-config
```

```php
return [
    'hours_cycle' => null,         // 12, 24, or null to read it off each field's display format
    'close_on_complete' => false,  // save and close once the last dial, day, month or year is picked
    'now_button' => true,          // show the "Now" / "Today" shortcut
];
```

## Theming

Every colour resolves through Filament's palette variables, so the picker follows your panel's primary colour and its light and dark themes without being told. To change it further, override the `--fi-chrono-*` variables in your theme stylesheet:

```css
html .fi-chrono {
    --fi-chrono-accent: var(--success-600);
    --fi-chrono-plate: var(--gray-50);
}

html.dark .fi-chrono {
    --fi-chrono-accent: var(--success-400);
}
```

The variables are `surface`, `plate` (the dial), `border`, `text`, `muted`, `faded`, `accent`, `on-accent` and `hover`.

Or publish the views to change the markup:

```bash
php artisan vendor:publish --tag=devletes-chrono-picker-views
```

## Translations

Every user-facing string goes through `devletes-chrono-picker::chrono-picker.*`. Publish the language files to reword them or add a locale:

```bash
php artisan vendor:publish --tag=devletes-chrono-picker-translations
```

Then edit or add files under `lang/vendor/devletes-chrono-picker/`.

## API reference

Everything Filament's `TimePicker`, `DatePicker` and `DateTimePicker` offer, plus:

| Method | What it does |
| --- | --- |
| `hoursCycle(12\|24)` | Fixes the face rather than reading it off the display format |
| `minTime($time)` | The earliest time of day the field will take. String, `Carbon`, or closure |
| `maxTime($time)` | The latest time of day the field will take. String, `Carbon`, or closure |
| `closeOnComplete()` | Save and close once the last dial is set, and hide OK |
| `nowButton(false)` | Drops the "Now" shortcut from the footer |
| `okLabel($label)` | Relabels the confirm button |
| `cancelLabel($label)` | Relabels the cancel button |
| `nowLabel($label)` | Relabels the "Now" shortcut |
| `native()` | Hands rendering back to Filament for the browser's own control |

On `DatePicker` and `DateTimePicker`, Filament's `minDate()`, `maxDate()`, `disabledDates()`, `firstDayOfWeek()`, `weekStartsOnMonday()`, `weekStartsOnSunday()`, `defaultFocusedDate()`, `closeOnDateSelection()`, `time()` and `seconds()` all apply to the calendar and the clock. On `MonthPicker` and `YearPicker`, `minDate()`, `maxDate()` and `defaultFocusedDate()` take whole periods.

The range classes (`DateRangePicker`, `MonthRangePicker`, `YearRangePicker`, `TimeRangePicker`) are Filament fields of their own, with everything a field has (`label()`, `required()`, `disabled()`, `readOnly()`, `placeholder()`, `helperText()`, `live()`, `columnSpan()`...) and:

| Method | What it does |
| --- | --- |
| `storeIn(start: 'a', end: 'b')` | Stores the ends in two attributes beside the field |
| `storeAsJson(start: 'from', end: 'to')` | Stores the ends in one array under the field's name, with these keys |
| `expanded()` / `compact()` / `layout($layout)` | How the two ends share the panel (`RangeLayout`) |
| `startLabel($label)` / `endLabel($label)` | Names the ends (default From / To) in the panel and in messages |
| `requireStart()` / `requireEnd()` | Requires one end and lets the other stay open (`required()` requires both) |
| `rangeSeparator($separator)` | The text between the two halves of the field (default ` – `) |
| `startPicker(fn ($picker) => ...)` / `endPicker(...)` | Configures one end's single picker directly |
| `configureEndsUsing(fn ($picker, $which) => ...)` | Configures both ends' pickers from one closure |
| `closeOnComplete()`, `okLabel()`, `cancelLabel()` | As on the single pickers |
| The single pickers' bounds, formats and steps | Passed on to both ends (see Ranges → Options) |

## Testing

```bash
composer install
composer test
```

The suite runs on Testbench with the real Filament stack, against PHP 8.2–8.4 on Laravel 12 and 13: every picker's stored shape and bounds, and the ranges on an Eloquent record, in a JSON column, inside a relationship repeater and on plain Livewire state.

## Need something custom?

We build production Filament panels and plugins for teams that want to ship fast without compromising on polish. If you need a custom feature, an extended variant of this package, or a fully bespoke component built for your stack, we can help.

- **Browse the rest of our Filament work:** [filament.devletes.com](https://filament.devletes.com)
- **Get in touch:** [salman@devletes.com](mailto:salman@devletes.com)

Typical engagements: new Filament plugins, custom resources/widgets/actions, theme + UX work, integrations with your existing services, and one-off tailored forks of our open-source packages.

## Have you seen our Orbit theme?

Orbit is our dark-first theme for Filament 5. Every panel surface is restyled: sidebar, topbar, forms, tables, modals, notifications.

<table><tr>
<td width="50%"><img src="https://filament.devletes.com/screenshots/orbit/resource_detail_with_hero_card_light.png" alt="Orbit theme, light mode"></td>
<td width="50%"><img src="https://filament.devletes.com/screenshots/orbit/resource_detail_with_hero_card.png" alt="Orbit theme, dark mode"></td>
</tr></table>

**[Check it out here](https://filament.devletes.com/orbit) →**

## Credits

- [Salman Hijazi](https://www.linkedin.com/in/syedsalmanhijazi/)

## License

MIT. See [LICENSE.md](LICENSE.md).
