<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Validation;

enum ValidationOperator: string
{
    case Between = 'between';
    case NotBetween = 'notBetween';
    case Equal = 'equal';
    case NotEqual = 'notEqual';
    case GreaterThan = 'greaterThan';
    case LessThan = 'lessThan';
    case GreaterThanOrEqual = 'greaterThanOrEqual';
    case LessThanOrEqual = 'lessThanOrEqual';
}
