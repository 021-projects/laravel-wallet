<?php

namespace O21\LaravelWallet\Enums;

enum TransactionStatus: string
{
    case PENDING = 'pending';
    case SUCCESS = 'success';
    case ON_HOLD = 'on_hold';
    case CANCELED = 'canceled';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    case EXPIRED = 'expired';
}
