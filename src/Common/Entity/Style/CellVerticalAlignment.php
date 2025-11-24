<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Style;

enum CellVerticalAlignment: string
{
    case AUTO = 'auto';
    case BASELINE = 'baseline';
    case BOTTOM = 'bottom';
    case CENTER = 'center';
    case DISTRIBUTED = 'distributed';
    case JUSTIFY = 'justify';
    case TOP = 'top';
}
