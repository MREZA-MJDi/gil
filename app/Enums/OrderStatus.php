<?php

namespace App\Enums;

enum OrderStatus: string
{
    case New = 'new';
    case Confirmed = 'confirmed';
    case Preparing = 'preparing';
    case Ready = 'ready';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    /** @return list<self> */
    public function next(): array
    {
        return match ($this) {
            self::New => [self::Confirmed, self::Preparing],
            self::Confirmed => [self::Preparing],
            self::Preparing => [self::Ready],
            self::Ready => [self::Delivered],
            default => [],
        };
    }
}
