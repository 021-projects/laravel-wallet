<?php

namespace O21\LaravelWallet\Enums;

class TransactionStatus
{
    public const PENDING = 'pending';

    public const SUCCESS = 'success';

    public const ON_HOLD = 'on_hold';

    public const CANCELED = 'canceled';

    public const FAILED = 'failed';

    public const REFUNDED = 'refunded';

    public const EXPIRED = 'expired';

    public const AWAITING_APPROVAL = 'awaiting_approval';

    public const AWAITING_PAYMENT = 'awaiting_payment';

    public const IN_PROGRESS = 'in_progress';

    private static array $known = [
        self::PENDING,
        self::SUCCESS,
        self::ON_HOLD,
        self::CANCELED,
        self::FAILED,
        self::REFUNDED,
        self::EXPIRED,
        self::AWAITING_APPROVAL,
        self::AWAITING_PAYMENT,
        self::IN_PROGRESS,
    ];

    public static function known(?array $statuses = null, bool $merge = false): array
    {
        if ($statuses === null) {
            return self::$known;
        }

        if ($merge) {
            self::$known = array_merge(self::$known, $statuses);

            return self::$known;
        }

        self::$known = $statuses;

        return self::$known;
    }
}
