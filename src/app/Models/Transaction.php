<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Transaction extends Model
{
    use HasFactory;

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

    public function toList()
    {
        $result = [
            'id' => $this->id,
            'type' => $this->type,
            'created_at' => $this->created_at,
        ];
        if ($this->type === 'deposit') {
            $result['amount'] = $this->deposits->first()->amount;
        }
        if ($this->type === 'withdraw') {
            $result['amount'] = $this->withdraws->first()->amount;
        }
        if ($this->type === 'transfer') {
            $result['amount'] = $this->transfers->first()->amount;
            $result['recipient'] = $this->transfers->first()->recipient->name;
        }
        return $result;
    }
}
