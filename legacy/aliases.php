<?php

/*
 * The time picker shipped as devletes/filament-time-picker under Devletes\TimePicker. Its class names keep resolving,
 * lazily and only when asked for, to the classes that replaced them. Remove in Chrono Picker 1.0.
 */

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
