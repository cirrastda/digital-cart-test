<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Transaction;
use Laravel\Sanctum\Sanctum;

class TransactionControllerLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_exceeds_daily_limit_returns_422(): void
    {
        $user = User::factory()->create(['balance' => 0]);
        Sanctum::actingAs($user, ['*']);

        $amount = Transaction::DEPOSIT_LIMIT + 1;
        $resp = $this->postJson('/deposit', ['amount' => $amount]);

        $resp->assertStatus(422);
        $json = $resp->json();
        $this->assertSame('Limite diário de depósito excedido.', $json['errors']['amount'][0] ?? null);
    }

    public function test_withdraw_exceeds_daily_limit_returns_422(): void
    {
        $user = User::factory()->create(['balance' => Transaction::WITHDRAW_LIMIT + 1000]);
        Sanctum::actingAs($user, ['*']);

        $amount = Transaction::WITHDRAW_LIMIT + 1;
        $resp = $this->postJson('/withdraw', ['amount' => $amount]);

        $resp->assertStatus(422);
        $json = $resp->json();
        $this->assertSame('Limite diário de saque excedido.', $json['errors']['amount'][0] ?? null);
    }
}