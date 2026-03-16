<?php

declare(strict_types=1);

namespace OpenSpout\Writer\XLSX\Validation;

enum ErrorStyle: string
{
    case Stop = 'stop';
    case Warning = 'warning';
    case Information = 'information';
}
