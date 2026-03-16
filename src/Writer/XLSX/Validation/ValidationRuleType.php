<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Validation;

enum ValidationRuleType: string
{
    case Whole = 'whole';
    case Decimal = 'decimal';
    case Date = 'date';
    case Time = 'time';
    case TextLength = 'textLength';
    case List = 'list';
    case Custom = 'custom';
}
