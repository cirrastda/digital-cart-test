<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\DB;

class UserService
{
    /**
     * Create a new user.
     *
     * @param string $nome
     * @param string $email
     * @param string $password
     * @return User
     * @throws \Exception Se usuário já existir
     */
    public function createUser($nome, $email, $password): User
    {
        DB::beginTransaction();
        try {
            if ($this->userExistsByEmail($email)) {
                throw new \Exception('Usuário já existe com este email.');
            }

            $user = new User();
            $user->name = $nome;
            $user->email = $email;
            $user->password = Hash::make($password);
            $user->token = '';
            $user->balance = 0;

            $user->save();

            $token = $this->createToken($user);

            DB::commit();
            return $user;
        } catch (\Throwable $te) {
            DB::rollBack();
            throw $te;
        }
    }

    /**
     * Check if user exists by email.
     *
     * @param string $email
     * @return bool
     */
    public function userExistsByEmail(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    /**
     * Create a new personal access token for the user.
     *
     * @param User $user
     * @return string
     */
    public function createToken(User $user): string
    {
        try {
            $freshUser = \App\Models\User::where('email', $user->email)->first();
            if ($freshUser && $freshUser->getKey()) {
                $token = $freshUser->createToken('api')->plainTextToken;
                $freshUser->token = $token;
                $freshUser->save();
            } else {
                throw new \Exception('Usuário recém-criado não possui ID carregado para Sanctum.');
            }
        } catch (\Throwable $te) {
            throw new \Exception('Falha ao gerar token Sanctum: '.$te->getMessage());
        }
        return $token;
    }

    /**
     * Get the authenticated user.
     *
     * @return User
     * @throws AuthenticationException
     */
    public function getAuthUser(): User
    {
        $user = auth()->user();
        if (!$user) {
            throw new AuthenticationException('Usuário não autenticado.');
        }
        return $user;
    }

    /**
     * Find a user by ID.
     *
     * @param int $id
     * @return User
     * @throws \Exception Se usuário não for encontrado
     */
    public function findUserById(int $id): User
    {
        $user = User::find($id);
        if (!$user) {
            throw new \Exception('Usuário não encontrado.');
        }
        return $user;
    }

    /**
     * Find a user by email.
     *
     * @param string $email
     * @return User
     * @throws \Exception Se usuário não for encontrado
     */
    public function findUserByEmail(string $email): User
    {
        $user = User::where('email', $email)->first();
        if (!$user) {
            throw new \Exception('Usuário não encontrado.');
        }
        return $user;
    }
}
