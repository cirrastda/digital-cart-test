<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepositRequest;
use App\Http\Requests\WithdrawRequest;
use App\Http\Requests\TransferRequest;
use App\Models\User;
use App\Services\TransactionService;
use App\Services\UserService;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    protected $transactionService;
    protected $userService;

    public function __construct(TransactionService $transactionService, UserService $userService)
    {
        $this->transactionService = $transactionService;
        $this->userService = $userService;
    }

    /**
     * Deposit money into the user's account.
     */
    public function deposit(DepositRequest $request)
    {
        try {
            $user = $this->userService->getAuthUser();
            $validated = $request->validated();
            $amount = $validated['amount'];

            $this->transactionService->depositMoney($user, $amount);

            return response()->json([
                'message' => 'Depósito realizado com sucesso!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao realizar depósito: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Withdraw money from the user's account.
     */
    public function withdraw(WithdrawRequest $request)
    {
        try {
            $user = $this->userService->getAuthUser();
            $validated = $request->validated();
            $amount = $validated['amount'];

            $this->transactionService->withdrawMoney($user, $amount);

            return response()->json([
                'message' => 'Saque realizado com sucesso!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao realizar saque: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Transfer money between users.
     */
    public function transfer(TransferRequest $request)
    {
        try {
            $user = $this->userService->getAuthUser();
            $validated = $request->validated();
            $amount = $validated['amount'];
            $recipientEmail = $validated['recipient'];
            $recipient = $this->userService->findUserByEmail($recipientEmail);
            $this->transactionService->transferMoney($user, $recipient, $amount);

            return response()->json([
                'message' => 'Transferência realizada com sucesso!'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao realizar transferência: ' . $e->getMessage()
            ], 500);
        }
    }

    public function history()
    {
        try {
            $user = $this->userService->getAuthUser();
            $transactions = $this->transactionService->getTransactionHistory($user);

            return response()->json([
                'transactions' => $transactions
            ], 200, [], JSON_PRESERVE_ZERO_FRACTION);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao obter histórico de transações: ' . $e->getMessage()
            ], 500);
        }
    }
}
