<?php

namespace App\Helpers;

class OrderTypeConstants
{
    const REGULAR = 'regular';
    const ORDER_SHEET = 'order_sheet';
    const CART = 'cart';

    const ORDER_TYPES = [
        self::REGULAR,
        self::ORDER_SHEET,
        self::CART,
    ];
}
