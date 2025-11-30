<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use App\Models\Transaction;
use App\Models\Transfer;
use App\Services\TransactionHistoryFormatter;

class TransactionService
{
    public function depositMoney(User $user, float $amount)
    {
        DB::beginTransaction();
        try {

            $transaction = $user->transactions()->create([
                'type' => 'deposit',
            ]);

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
    public function depositExceedsDailyLimit(User $user, float $amount): bool
    {
        $depositLimit = Transaction::DEPOSIT_LIMIT;
        $currentDayDepositAmount = $this->getCurrentDayDepositAmount($user);
        if ($amount > $depositLimit || $amount + $currentDayDepositAmount > $depositLimit) {
            return true;
        }
        return false;
    }

    public function withdrawExceedsDailyLimit(User $user, float $amount): bool
    {
        $withdrawLimit = Transaction::WITHDRAW_LIMIT;
        $currentDayWithdrawAmount = $this->getCurrentDayWithdrawAmount($user);
        if ($amount > $withdrawLimit || $amount + $currentDayWithdrawAmount > $withdrawLimit) {
            return true;
        }
        return false;
    }

    public function getCurrentDayWithdrawAmount(User $user)
    {
        return $user->withdraws()
            ->whereDate('withdraws.created_at', now()->toDateString())
            ->sum('amount');
    }

    public function getCurrentDayDepositAmount(User $user)
    {
        return $user->deposits()
            ->whereDate('deposits.created_at', now()->toDateString())
            ->sum('amount');
    }

    public function getTransactionHistory(User $user)
    {
        $userId = $user->id;

        $deposits = DB::table('transactions as t')
            ->join('deposits as d', 'd.transaction_id', '=', 't.id')
            ->where('t.user_id', $userId)
            ->selectRaw("t.id as id, t.type as type, t.created_at as created_at, d.amount as amount, NULL as recipient, NULL as sender");

        $withdraws = DB::table('transactions as t')
            ->join('withdraws as w', 'w.transaction_id', '=', 't.id')
            ->where('t.user_id', $userId)
            ->selectRaw("t.id as id, t.type as type, t.created_at as created_at, w.amount as amount, NULL as recipient, NULL as sender");

        $transfersSent = DB::table('transactions as t')
            ->join('transfers as tr', 'tr.transaction_id', '=', 't.id')
            ->join('users as u_rec', 'u_rec.id', '=', 'tr.recipient_user_id')
            ->where('t.user_id', $userId)
            ->selectRaw("t.id as id, t.type as type, t.created_at as created_at, tr.amount as amount, u_rec.name as recipient, NULL as sender");

        $transfersReceived = DB::table('transactions as t')
            ->join('transfers as tr', 'tr.transaction_id', '=', 't.id')
            ->join('users as u_send', 'u_send.id', '=', 't.user_id')
            ->where('tr.recipient_user_id', $userId)
            ->selectRaw("t.id as id, 'transfer-received' as type, t.created_at as created_at, ABS(tr.amount) as amount, NULL as recipient, u_send.name as sender");

        $union = $deposits
            ->unionAll($withdraws)
            ->unionAll($transfersSent)
            ->unionAll($transfersReceived);

        $rows = DB::query()
            ->fromSub($union, 'history')
            ->orderBy('created_at')
            ->get();

        return TransactionHistoryFormatter::format($rows);
    }
}
