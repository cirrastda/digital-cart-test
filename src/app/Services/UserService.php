<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Firebase\JWT\JWT;

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
        // Validação de existência na camada de serviço (dupla proteção)
        if ($this->userExistsByEmail($email)) {
            throw new \Exception('Usuário já existe com este email.');
        }

        $user = new User();
        $user->name = $nome;
        $user->email = $email;
        $user->password = Hash::make($password);
        $user->token = JWT::encode([
            'email' => $email,
            'password' => $password,
        ], env('JWT_SECRET'), 'HS256');
        $user->balance = 0;

        $user->save();

        return $user;
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
}
