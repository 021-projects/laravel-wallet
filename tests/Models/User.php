<?php

namespace Tests\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use O21\LaravelWallet\Contracts\UserContract;
use O21\LaravelWallet\Models\Concerns\HasBalance;

class User extends Model implements UserContract
{
    use HasFactory;
    use HasBalance;

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
