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
     * Deposita valor na conta do usuário autenticado.
     *
     * @param DepositRequest $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Post(
     *   path="/deposit",
     *   summary="Depositar",
     *   tags={"Transações"},
     *   security={{"sanctum":{}}},
     *   requestBody=@OA\RequestBody(
     *     required=true,
     *     content={
     *       "application/json"=@OA\JsonContent(
     *         required={"amount"},
     *         @OA\Property(property="amount", type="number", format="float", minimum=0.01)
     *       )
     *     }
     *   ),
     *   @OA\Response(response=200, description="Depósito realizado"),
     *   @OA\Response(response=422, description="Erro de validação"),
     *   @OA\Response(response=401, description="Não autenticado"),
     *   @OA\Response(response=500, description="Erro interno")
     * )
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
     * Realiza saque na conta do usuário autenticado.
     *
     * @param WithdrawRequest $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Post(
     *   path="/withdraw",
     *   summary="Sacar",
     *   tags={"Transações"},
     *   security={{"sanctum":{}}},
     *   requestBody=@OA\RequestBody(
     *     required=true,
     *     content={
     *       "application/json"=@OA\JsonContent(
     *         required={"amount"},
     *         @OA\Property(property="amount", type="number", format="float", minimum=0.01)
     *       )
     *     }
     *   ),
     *   @OA\Response(response=200, description="Saque realizado"),
     *   @OA\Response(response=422, description="Erro de validação"),
     *   @OA\Response(response=401, description="Não autenticado"),
     *   @OA\Response(response=500, description="Erro interno")
     * )
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
     * Transfere valor para outro usuário.
     *
     * @param TransferRequest $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Post(
     *   path="/transfer",
     *   summary="Transferir",
     *   tags={"Transações"},
     *   security={{"sanctum":{}}},
     *   requestBody=@OA\RequestBody(
     *     required=true,
     *     content={
     *       "application/json"=@OA\JsonContent(
     *         required={"amount","recipient"},
     *         @OA\Property(property="amount", type="number", format="float", minimum=0.01),
     *         @OA\Property(property="recipient", type="string", format="email")
     *       )
     *     }
     *   ),
     *   @OA\Response(response=200, description="Transferência realizada"),
     *   @OA\Response(response=422, description="Erro de validação"),
     *   @OA\Response(response=401, description="Não autenticado"),
     *   @OA\Response(response=500, description="Erro interno")
     * )
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

    /**
     * Retorna o histórico de transações do usuário.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Get(
     *   path="/history",
     *   summary="Histórico de transações",
     *   tags={"Transações"},
     *   security={{"sanctum":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Lista de transações",
     *     content={
     *       "application/json"=@OA\JsonContent(
     *         @OA\Property(
     *           property="transactions",
     *           type="array",
     *           @OA\Items(
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="type", type="string"),
     *             @OA\Property(property="created_at", type="string", format="date-time"),
     *             @OA\Property(property="amount", type="number", format="float"),
     *             @OA\Property(property="recipient", type="string", nullable=true),
     *             @OA\Property(property="sender", type="string", nullable=true)
     *           )
     *         )
     *       )
     *     }
     *   ),
     *   @OA\Response(response=401, description="Não autenticado"),
     *   @OA\Response(response=500, description="Erro interno")
     * )
     */
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
