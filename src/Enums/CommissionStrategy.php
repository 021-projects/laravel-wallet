<?php

namespace O21\LaravelWallet\Enums;

enum CommissionStrategy
{
    case FIXED;
    case PERCENT;
    case PERCENT_AND_FIXED;
}
