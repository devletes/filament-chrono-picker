<?php

// Resolves devletes/filament-time-picker's Devletes\TimePicker classes to their replacements. Remove in the next major release.

spl_autoload_register(static function (string $class): void {
    $legacy = 'Devletes\\TimePicker\\';

    if (! str_starts_with($class, $legacy)) {
        return;
    }

    $renamed = [
        'TimePickerServiceProvider' => 'ChronoPickerServiceProvider',
        'Concerns\\InteractsWithClock' => 'Concerns\\InteractsWithChronoPanel',
    ];

    $relative = substr($class, strlen($legacy));
    $target = 'Devletes\\ChronoPicker\\'.($renamed[$relative] ?? $relative);

    if (! class_exists($target) && ! trait_exists($target) && ! interface_exists($target)) {
        return;
    }

    @trigger_error("{$class} is deprecated; use {$target} (devletes/filament-chrono-picker).", E_USER_DEPRECATED);

    class_alias($target, $class);
});
