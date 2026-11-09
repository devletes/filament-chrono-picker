<?php

namespace Devletes\ChronoPicker\Enums;

/** How a range's two ends share the panel. */
enum RangeLayout: string
{
    /** From on the left, To on the right, both open at once. */
    case SideBySide = 'side-by-side';

    /** One end at a time: pick From and the panel moves on to To. */
    case OneAtATime = 'one-at-a-time';
}
