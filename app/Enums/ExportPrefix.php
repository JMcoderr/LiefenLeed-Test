<?php

namespace App\Enums;

enum ExportPrefix: string
{
    case MSG = 'MSG';
    case PMNTINFO = 'PMT';
    case ENDTOEND = 'E2E';
}
