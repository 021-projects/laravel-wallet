<?php

namespace O21\LaravelWallet\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use O21\LaravelWallet\Contracts\ShadowBalance as ShadowBalanceContract;
use O21\LaravelWallet\Models\Concerns\HasBalance;
use O21\LaravelWallet\Models\Concerns\HasMetaColumn;

/**
 * @property int $id
 * @property string $uuid
 * @property array $meta
 * @property \Carbon\Carbon $created_at
 */
class ShadowBalance extends Model implements ShadowBalanceContract
{
    use HasBalance;
    use HasMetaColumn;
    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'uuid',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('wallet.table_names.shadow_balances', 'shadow_balances'));
    }

    public static function of(?string $uuid = null, array $meta = []): self
    {
        if ($uuid === null) {
            return self::create(compact('meta'));
        }

        return self::firstOrCreate(compact('uuid'), compact('meta'));
    }

    public function uniqueIds(): array
    {
        return ['uuid'];
    }
}
