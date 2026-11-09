<?php

namespace Devletes\ChronoPicker\Assets;

use Filament\Support\Assets\AlpineComponent;

class HashedAlpineComponent extends AlpineComponent
{
    use VersionsByContentHash;
}
