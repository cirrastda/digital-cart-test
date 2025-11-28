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
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $users = \App\Models\User::all();
            return response()->json($users);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao buscar usuários: ' . $e->getMessage()
            ], 500);
        }
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

            return response()->json([
                'message' => 'Usuário criado com sucesso',
                'user' => $user,
                'token' => $user->token,
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
            $authUser = auth()->user();

            return response()->json([
                'balance' => $authUser->balance,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao buscar saldo do usuário: ' . $e->getMessage()
            ], 500);
        }
    }
}
