<?php

namespace Workbench\App\Models;

use Illuminate\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use O21\LaravelWallet\Contracts\Payable;
use O21\LaravelWallet\Models\Concerns\HasBalance;
use Workbench\Database\Factories\UserFactory;

class User extends Model implements Payable
{
    use Authenticatable;
    use HasBalance;
    use HasFactory;

    protected $fillable = ['name'];

    public $timestamps = false;

    protected $table = 'users';

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return UserFactory::new();
    }
}
