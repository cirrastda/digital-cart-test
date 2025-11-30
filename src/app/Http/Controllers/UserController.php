<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Services\UserService;
use App\Http\Responses\ApiResponse;
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
     *   @OA\Response(
     *     response=201,
     *     description="Usuário criado",
     *     content={
     *       "application/json"=@OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="code", type="integer", example=201),
     *         @OA\Property(
     *           property="data",
     *           type="object",
     *           @OA\Property(property="user", type="object"),
     *           @OA\Property(property="token", type="string")
     *         ),
     *         @OA\Property(property="error", nullable=true)
     *       )
     *     }
     *   ),
     *   @OA\Response(
     *     response=422,
     *     description="Erro de validação",
     *     content={
     *       "application/json"=@OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=false),
     *         @OA\Property(property="code", type="integer", example=422),
     *         @OA\Property(property="data", type="object", nullable=true),
     *         @OA\Property(
     *           property="error",
     *           type="object",
     *           @OA\Property(property="message", type="string", example="Erro de validação"),
     *           @OA\Property(property="errors", type="object")
     *         )
     *       )
     *     }
     *   ),
     *   @OA\Response(
     *     response=500,
     *     description="Erro interno",
     *     content={
     *       "application/json"=@OA\JsonContent(
     *         @OA\Property(property="success", type="boolean", example=false),
     *         @OA\Property(property="code", type="integer", example=500),
     *         @OA\Property(property="data", type="object", nullable=true),
     *         @OA\Property(property="error", type="string", example="Erro ao criar usuário")
     *       )
     *     }
     *   )
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

            return ApiResponse::success([
                'user' => $user,
                'token' => $token,
            ], 201);

        } catch (\Exception $e) {
            return ApiResponse::error('Erro ao criar usuário: '.$e->getMessage().' ('.get_class($e).')', 500);
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
     *         @OA\Property(property="success", type="boolean", example=true),
     *         @OA\Property(property="code", type="integer", example=200),
     *         @OA\Property(
     *           property="data",
     *           type="object",
     *           @OA\Property(property="balance", type="number", format="float")
     *         ),
     *         @OA\Property(property="error", type="object", nullable=true)
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
            return ApiResponse::success([
                'balance' => $authUser->balance,
            ], 200);
        } catch (\Illuminate\Auth\AuthenticationException $e) {
            return ApiResponse::error('Não autenticado', 401);
        } catch (\Exception $e) {
            return ApiResponse::error('Erro ao buscar saldo do usuário: ' . $e->getMessage().' ('.get_class($e).')', 500);
        }
    }
}
