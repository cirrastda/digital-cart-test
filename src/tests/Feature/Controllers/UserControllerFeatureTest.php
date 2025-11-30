<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class UserControllerFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_user_success_returns_token(): void
    {
        $payload = [
            'name' => 'Nome',
            'email' => 'user@example.com',
            'password' => 'password123',
        ];

        $resp = $this->postJson('/users', $payload);
        $resp->assertStatus(201);
        $resp->assertJson([
            'success' => true,
            'code' => 201,
        ]);
        $resp->assertJsonStructure([
            'success', 'code', 'data' => [
                'user' => ['id', 'name', 'email'],
                'token'
            ],
            'error'
        ]);
    }

    public function test_register_user_duplicate_email_returns_422(): void
    {
        User::factory()->create(['email' => 'dup@example.com']);

        $payload = [
            'name' => 'Outro Nome',
            'email' => 'dup@example.com',
            'password' => 'password456',
        ];

        $resp = $this->postJson('/users', $payload);
        $resp->assertStatus(422);
        $resp->assertJson([
            'success' => false,
            'code' => 422,
        ]);
        $this->assertNotEmpty($resp->json('error.errors.email'));
    }

    public function test_get_balance_success_returns_value(): void
    {
        $user = User::factory()->create(['balance' => 123.45]);
        Sanctum::actingAs($user, ['*']);

        $resp = $this->getJson('/users/balance');
        $resp->assertStatus(200);
        $resp->assertJson([
            'success' => true,
            'code' => 200,
        ]);
        $this->assertSame('123.45', $resp->json('data.balance'));
    }

    public function test_get_balance_unauthenticated_returns_error(): void
    {
        $resp = $this->getJson('/users/balance');
        $resp->assertStatus(401);
        $resp->assertJson([
            'success' => false,
            'code' => 401,
        ]);
        $this->assertSame('Não autenticado', $resp->json('error'));
    }
}