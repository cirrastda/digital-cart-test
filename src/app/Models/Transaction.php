<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Transaction extends Model
{
    use HasFactory;

    public const WITHDRAW_LIMIT = 1000.00;
    public const DEPOSIT_LIMIT = 1000.00;

    protected $fillable = [
        'user_id',
        'type',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }

    public function deposits()
    {
        return $this->hasMany(Deposit::class);
    }
    public function withdraws()
    {
        return $this->hasMany(Withdraw::class);
    }
    public function transfers()
    {
        return $this->hasMany(Transfer::class);
    }
}
