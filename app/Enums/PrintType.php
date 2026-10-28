<?php

namespace App\Enums;

enum PrintType: string
{
    case Invoice = 'invoice';
    case Receipt = 'receipt';
    case Kitchen = 'kitchen';
}
