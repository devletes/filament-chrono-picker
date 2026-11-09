# Changelog

All notable changes to `devletes/filament-chrono-picker` (formerly `devletes/filament-time-picker`) will be documented in this file.

## [0.1.1] - 2026-11-09

### Changed

- The panel is opened and positioned by Filament's floating panel (`x-float`): 8px from the field, flipping above it and shifting inside the window when there is no room.
- Assets are versioned by Filament's asset manager, like any Filament plugin's.

### Fixed

- The test suite on Laravel 12.

## [0.1.0] - 2026-11-09

First pre-release of Chrono Picker. Changes are against `devletes/filament-time-picker` 1.0.0, whose history this package continues.

### Changed

- Renamed to Chrono Picker: `devletes/filament-chrono-picker`, namespace `Devletes\ChronoPicker`, provider `ChronoPickerServiceProvider`, config `chrono-picker`, views and translations `devletes-chrono-picker`, CSS `fi-chrono-*`, Alpine component `devletesChronoPicker`. The time picker's class names and a published `config/time-picker.php` keep working until Chrono Picker 1.0.
- The script and stylesheet load on request (`x-load` / `x-load-css`) from the first field that needs them, instead of on every panel page. The script is now an ES module.
- The calendar's month dropdown and numeric year input are replaced by grids: click the month for the twelve months of the year, click the year for a page of twelve years, with arrows to page through either.
- The footer's shortcut reads "Today" on a date-only field.
- One shared core (`InteractsWithChronoPanel`) behind every picker; `getClockConfig()` is now `getPickerConfig()`, the old name kept.

### Added

- `DatePicker`, extending Filament's own.
- `MonthPicker` (stored `Y-m`, shown `M Y`) and `YearPicker` (stored `Y`), on Filament's `DatePicker`, opening on the month or year grid. Bounds are whole periods, and a stored month reads back as itself on any day of the month.
- `DateRangePicker`, `MonthRangePicker`, `YearRangePicker` and `TimeRangePicker`: both ends in one field ("Jan 2026 – Jun 2026"), each end the matching single picker. Stored where the developer says: `storeIn(start:, end:)` for two columns (on a record, in a repeater or relationship, or in plain Livewire state) or `storeAsJson(start:, end:)` for one array column with named keys; unconfigured throws. Two layouts: `sideBySide()` (default) and `oneAtATime()`, whose From and To halves of the field text each open their end. Validated on the server: both ends or neither, end not before start, bounds on each end.

## [time-picker 1.0.0] - 2026-10-25

First stable release.

### Added

- `TimePicker`: a clock face for Filament's time fields. It extends Filament's own `TimePicker`, so swapping the import is the whole migration, and the state cast, storage format, display format, steps, timezone and validation behave exactly as they do there.
- `DateTimePicker`: the same clock face paired with a calendar. It extends Filament's own `DateTimePicker`; picking a day moves straight to the clock, and a Date / Time switch at the top of the panel moves between them. `minDate()`, `maxDate()`, `disabledDates()`, `firstDayOfWeek()`, `weekStartsOnSunday()`, `defaultFocusedDate()`, `closeOnDateSelection()` and `time(false)` all apply, with month and weekday names in the field's locale.
- The dial follows the field: a display format that writes the hour as 1–12 gets a 12-hour face with AM/PM, anything else a 24-hour face with 13–23 and midnight on an inner ring. `->hoursCycle(12|24)` or `time-picker.hours_cycle` fixes it.
- Every minute is reachable on the dial; `->minutesStep()`, `->hoursStep()` and `->secondsStep()` still coarsen it. `->seconds()` adds a third dial, and a small translatable label above the pin names the unit being set.
- `->minTime()` / `->maxTime()`: a daily window that dims the dial, clamps typing and is enforced again on the server, with a message that names the bound in the field's own time format. On a `DateTimePicker` it narrows further on the days `minDate()` / `maxDate()` fall on.
- The panel's digits are inputs: type a time, or nudge it with the arrow keys by the field's own step.
- `->closeOnComplete()`, `->nowButton()`, `->okLabel()`, `->cancelLabel()` and `->nowLabel()`. The footer uses Filament's own buttons and the calendar's month menu Filament's own dropdown, so both follow the panel's theme.
- `->native()` hands rendering back to Filament for the browser's own control, the better choice on phones.
- No dependencies: vanilla JavaScript on the Alpine Filament already ships, themed off Filament's palette in light and dark. Translatable, with publishable config, views and language files.
- Supports PHP 8.2+, Filament 5, and Laravel 12 and 13, tested in CI on each.
