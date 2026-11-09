<?php

namespace Devletes\ChronoPicker\Concerns;

use Devletes\ChronoPicker\ChronoPickerServiceProvider;
use Filament\Support\Facades\FilamentAsset;

trait LoadsAssets
{
    public function getAlpineComponentSrc(): string
    {
        return FilamentAsset::getAlpineComponentSrc(ChronoPickerServiceProvider::ASSET, ChronoPickerServiceProvider::PACKAGE);
    }

    public function getStylesheetHref(): string
    {
        return FilamentAsset::getStyleHref(ChronoPickerServiceProvider::ASSET, ChronoPickerServiceProvider::PACKAGE);
    }
}
