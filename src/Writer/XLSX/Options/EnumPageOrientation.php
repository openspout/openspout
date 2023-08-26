<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Options;

enum EnumPageOrientation: string
{
    case PORTRAIT = 'portrait';
    case LANDSCAPE = 'landscape';
}
