<?php

namespace O21\LaravelWallet\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use O21\LaravelWallet\Contracts\Custodian as CustodianContract;
use O21\LaravelWallet\Models\Concerns\HasBalance;
use O21\LaravelWallet\Models\Concerns\HasMetaColumn;

/**
 * @property int $id
 * @property string $name
 * @property array $meta
 * @property \Carbon\Carbon $created_at
 */
class Custodian extends Model implements CustodianContract
{
    use HasBalance;
    use HasMetaColumn;
    use HasUuids;

    public const UPDATED_AT = null;

    protected $fillable = [
        'name',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setTable(config('wallet.table_names.custodians', 'custodians'));
    }

    public static function of(?string $name = null, array $meta = []): self
    {
        if ($name === null) {
            return self::create(compact('meta'));
        }

        $shadow = self::firstOrCreate(compact('name'), compact('meta'));

        if (! empty($meta)) {
            $shadow->updateMeta($meta);
        }

        return $shadow;
    }

    public function uniqueIds(): array
    {
        return ['name'];
    }
}
