<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class TransactionService
{
    public function depositMoney(User $user, float $amount)
    {
        DB::beginTransaction();
        try {
            // Cria uma transação registrada para o usuário
            $transaction = $user->transactions()->create([
                'type' => 'deposit',
            ]);

            $deposit =

            $deposit = $transaction->deposits()->create([
                'amount' => $amount,
            ]);

            $user->balance += $amount;
            $user->save();

            DB::commit();
            return true;
        } catch (\Throwable $te) {
            DB::rollBack();
            throw $te;
        }
    }
    public function withdrawMoney(User $user, float $amount)
    {
        DB::beginTransaction();
        try {

            $transaction = $user->transactions()->create([
                'type' => 'withdraw',
            ]);

            $withdraw = $transaction->withdraws()->create([
                'amount' => $amount,
            ]);

            $user->balance -= $amount;
            $user->save();

            DB::commit();
            return true;
        } catch (\Throwable $te) {
            DB::rollBack();
            throw $te;
        }
    }

    public function transferMoney(User $sender, User $receiver, float $amount)
    {
        DB::beginTransaction();
        try {
            $transaction = $sender->transactions()->create([
                'type' => 'transfer',
            ]);

            $transfer = $transaction->transfers()->create([
                'amount' => -$amount,
                'recipient_user_id' => $receiver->id,
            ]);

            $sender->balance -= $amount;
            $sender->save();

            $receiver->balance += $amount;
            $receiver->save();

            DB::commit();
            return true;
        } catch (\Throwable $te) {
            DB::rollBack();
            throw $te;
        }
    }

    public function getTransactionHistory(User $user)
    {
        return $user->transactions()->with(['deposits', 'withdraws', 'transfers'])->get()->map->toList();
    }
}
