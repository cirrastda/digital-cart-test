<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'balance',
        'token',
    ];

    protected $hidden = [
        'password',
        'token',
    ];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'balance' => 'decimal:2',
        ];
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    public function deposits()
    {
        return $this->hasManyThrough(
            Deposit::class,
            Transaction::class,
            'user_id', // FK on transactions referencing users
            'transaction_id', // FK on deposits referencing transactions
            'id', // users.id
            'id' // transactions.id
        );
    }

    public function withdraws()
    {
        return $this->hasManyThrough(
            Withdraw::class,
            Transaction::class,
            'user_id',
            'transaction_id',
            'id',
            'id'
        );
    }

    public function transfers()
    {
        return $this->hasManyThrough(
            Transfer::class,
            Transaction::class,
            'user_id',
            'transaction_id',
            'id',
            'id'
        );
    }

}
