<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Cash = 'cash';
    case CardTerminal = 'card_terminal';
    case Online = 'online';
    case Other = 'other';
}
