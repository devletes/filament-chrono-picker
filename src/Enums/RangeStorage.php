<?php

namespace Devletes\ChronoPicker\Enums;

/** Where a range field keeps its two ends. */
enum RangeStorage
{
    /** Two attributes beside the field, named by the developer: `storeIn(start: 'starts_on', end: 'ends_on')`. */
    case Columns;

    /** One array/JSON attribute under the field's own name, with named keys: `storeAsJson(start: 'from', end: 'to')`. */
    case Json;
}
