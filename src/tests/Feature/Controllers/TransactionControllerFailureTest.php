<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class TransactionControllerFailureTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_exceeds_daily_limit_returns_422(): void
    {
        $user = User::factory()->create(['balance' => 0]);
        Sanctum::actingAs($user, ['*']);

        // Primeiro depósito dentro do limite
        $this->postJson('/deposit', ['amount' => 800])->assertStatus(200);
        // Segundo depósito deve estourar limite diário (1000)
        $resp = $this->postJson('/deposit', ['amount' => 300]);
        $resp->assertStatus(422);
        $resp->assertJson(['success' => false, 'code' => 422]);
        $this->assertNotEmpty($resp->json('error.errors.amount'));
    }

    public function test_withdraw_insufficient_balance_returns_422(): void
    {
        $user = User::factory()->create(['balance' => 50]);
        Sanctum::actingAs($user, ['*']);

        $resp = $this->postJson('/withdraw', ['amount' => 60]);
        $resp->assertStatus(422);
        $resp->assertJson(['success' => false, 'code' => 422]);
        $this->assertNotEmpty($resp->json('error.errors.amount'));
    }

    public function test_transfer_recipient_not_exists_returns_422(): void
    {
        $sender = User::factory()->create(['balance' => 100]);
        Sanctum::actingAs($sender, ['*']);

        $resp = $this->postJson('/transfer', ['amount' => 10, 'recipient' => 'unknown@example.com']);
        $resp->assertStatus(422);
        $resp->assertJson(['success' => false, 'code' => 422]);
        $this->assertNotEmpty($resp->json('error.errors.recipient'));
    }

    public function test_transfer_insufficient_balance_returns_422(): void
    {
        $sender = User::factory()->create(['balance' => 10]);
        $recipient = User::factory()->create(['balance' => 0]);
        Sanctum::actingAs($sender, ['*']);

        $resp = $this->postJson('/transfer', ['amount' => 20, 'recipient' => $recipient->email]);
        $resp->assertStatus(422);
        $resp->assertJson(['success' => false, 'code' => 422]);
        $this->assertNotEmpty($resp->json('error.errors.amount'));
    }
}