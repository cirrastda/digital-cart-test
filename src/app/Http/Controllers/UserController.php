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
     * Store a newly created resource in storage.
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
            ], 400);
        }
    }


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
