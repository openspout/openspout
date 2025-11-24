<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Style;

enum BorderName: string
{
    case LEFT = 'left';
    case RIGHT = 'right';
    case TOP = 'top';
    case BOTTOM = 'bottom';
}
