<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_user_success_returns_token_and_user(): void
    {
        $payload = [
            'name' => 'Alice',
            'email' => 'alice@example.com',
            'password' => 'password123',
        ];

        $resp = $this->postJson('/users', $payload);
        $resp->assertStatus(201);
        $resp->assertJson([
            'success' => true,
            'code' => 201,
        ]);

        $data = $resp->json('data');
        $this->assertArrayHasKey('token', $data);
        $this->assertArrayHasKey('user', $data);
        $this->assertSame('alice@example.com', $data['user']['email']);
    }

    public function test_create_user_validation_errors_invalid_email_and_unique(): void
    {
        User::factory()->create(['email' => 'exists@example.com']);

        $respInvalid = $this->postJson('/users', [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
        ]);
        $respInvalid->assertStatus(422);
        $respInvalid->assertJson([
            'success' => false,
            'code' => 422,
        ]);
        $this->assertNotEmpty($respInvalid->json('error.errors'));

        $respUnique = $this->postJson('/users', [
            'name' => 'Bob',
            'email' => 'exists@example.com',
            'password' => 'password123',
        ]);
        $respUnique->assertStatus(422);
        $respUnique->assertJson([
            'success' => false,
            'code' => 422,
        ]);
        $this->assertNotEmpty($respUnique->json('error.errors'));
    }
}