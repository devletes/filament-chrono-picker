<?php

namespace Devletes\ChronoPicker;

use Devletes\ChronoPicker\Assets\HashedAlpineComponent;
use Devletes\ChronoPicker\Assets\HashedCss;
use Filament\Support\Facades\FilamentAsset;
use Illuminate\Support\ServiceProvider;

class ChronoPickerServiceProvider extends ServiceProvider
{
    public const PACKAGE = 'devletes/filament-chrono-picker';

    public const ASSET = 'chrono-picker';

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/chrono-picker.php', 'chrono-picker');

        $this->mergeLegacyConfig();
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'devletes-chrono-picker');
        $this->loadTranslationsFrom(__DIR__.'/../resources/lang', 'devletes-chrono-picker');

        $this->publishes([
            __DIR__.'/../config/chrono-picker.php' => config_path('chrono-picker.php'),
        ], 'devletes-chrono-picker-config');

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/devletes-chrono-picker'),
        ], 'devletes-chrono-picker-views');

        $this->publishes([
            __DIR__.'/../resources/lang' => lang_path('vendor/devletes-chrono-picker'),
        ], 'devletes-chrono-picker-translations');

        // Both load on request: a field asks for them (x-load / x-load-css), so a page without one fetches nothing.
        FilamentAsset::register([
            HashedAlpineComponent::make(self::ASSET, __DIR__.'/../resources/js/chrono-picker.js'),
            HashedCss::make(self::ASSET, __DIR__.'/../resources/css/chrono-picker.css')->loadedOnRequest(),
        ], package: self::PACKAGE);
    }

    /** A config/time-picker.php published under the time picker's name keeps applying until it is renamed. Remove in 1.0. */
    protected function mergeLegacyConfig(): void
    {
        $legacy = $this->app['config']->get('time-picker');

        if (! is_array($legacy) || file_exists($this->app->configPath('chrono-picker.php'))) {
            return;
        }

        $this->app['config']->set('chrono-picker', array_merge(
            $this->app['config']->get('chrono-picker', []),
            $legacy,
        ));
    }
}
