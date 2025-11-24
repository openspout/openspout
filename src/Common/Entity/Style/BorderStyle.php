<?php

declare(strict_types=1);

namespace OpenSpout\Common\Entity\Style;

enum BorderStyle: string
{
    case NONE = 'none';
    case SOLID = 'solid';
    case DASHED = 'dashed';
    case DOTTED = 'dotted';
    case DOUBLE = 'double';
}
