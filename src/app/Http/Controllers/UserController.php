<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    /**
     * Cria um novo usuário.
     *
     * @param StoreUserRequest $request
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Post(
     *   path="/users",
     *   summary="Criar usuário",
     *   tags={"Usuários"},
     *   requestBody=@OA\RequestBody(
     *     required=true,
     *     content={
     *       "application/json"=@OA\JsonContent(
     *         required={"name","email","password"},
     *         @OA\Property(property="name", type="string"),
     *         @OA\Property(property="email", type="string", format="email"),
     *         @OA\Property(property="password", type="string", format="password")
     *       )
     *     }
     *   ),
     *   @OA\Response(response=201, description="Usuário criado"),
     *   @OA\Response(response=422, description="Erro de validação"),
     *   @OA\Response(response=500, description="Erro interno")
     * )
     */
    public function store(StoreUserRequest $request)
    {
        try {
            $user = $this->userService->createUser(
                $request->name,
                $request->email,
                $request->password
            );

            $token = $this->userService->createToken($user);

            return response()->json([
                'message' => 'Usuário criado com sucesso',
                'user' => $user,
                'token' => $token,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Retorna o saldo do usuário autenticado.
     *
     * @return \Illuminate\Http\JsonResponse
     *
     * @OA\Get(
     *   path="/users/balance",
     *   summary="Consultar saldo",
     *   tags={"Usuários"},
     *   security={{"sanctum":{}}},
     *   @OA\Response(
     *     response=200,
     *     description="Saldo atual",
     *     content={
     *       "application/json"=@OA\JsonContent(
     *         @OA\Property(property="balance", type="number", format="float")
     *       )
     *     }
     *   ),
     *   @OA\Response(response=401, description="Não autenticado"),
     *   @OA\Response(response=500, description="Erro interno")
     * )
     */
    public function get_balance()
    {
        try {
            $authUser = $this->userService->getAuthUser();

            return response()->json([
                'balance' => $authUser->balance,
            ]);
        } catch (\BadMethodCallException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao buscar saldo do usuário: ' . $e->getMessage()
            ], 500);
        }
    }
}
