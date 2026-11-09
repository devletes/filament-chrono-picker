<?php

namespace Devletes\ChronoPicker\Assets;

use Filament\Support\Assets\Css;

class HashedCss extends Css
{
    use VersionsByContentHash;
}
