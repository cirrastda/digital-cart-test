<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class TransactionControllerSuccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_success_updates_balance(): void
    {
        $user = User::factory()->create(['balance' => 0]);
        Sanctum::actingAs($user, ['*']);

        $resp = $this->postJson('/deposit', ['amount' => 25]);
        $resp->assertStatus(200);
        $user->refresh();
        $this->assertSame('25.00', $user->balance);
    }

    public function test_withdraw_success_updates_balance(): void
    {
        $user = User::factory()->create(['balance' => 100]);
        Sanctum::actingAs($user, ['*']);

        $resp = $this->postJson('/withdraw', ['amount' => 40]);
        $resp->assertStatus(200);
        $user->refresh();
        $this->assertSame('60.00', $user->balance);
    }

    public function test_transfer_success_updates_both_balances(): void
    {
        $sender = User::factory()->create(['balance' => 80]);
        $recipient = User::factory()->create(['balance' => 5]);
        Sanctum::actingAs($sender, ['*']);

        $resp = $this->postJson('/transfer', ['amount' => 30, 'recipient' => $recipient->email]);
        $resp->assertStatus(200);

        $sender->refresh();
        $recipient->refresh();
        $this->assertSame('50.00', $sender->balance);
        $this->assertSame('35.00', $recipient->balance);
    }

    public function test_history_success_returns_transactions(): void
    {
        $user = User::factory()->create(['balance' => 0]);
        Sanctum::actingAs($user, ['*']);

        $this->postJson('/deposit', ['amount' => 25])->assertStatus(200);
        $this->postJson('/withdraw', ['amount' => 10])->assertStatus(200);

        $other = User::factory()->create(['balance' => 0]);
        $this->postJson('/transfer', ['amount' => 5, 'recipient' => $other->email])->assertStatus(200);

        $resp = $this->getJson('/history');
        $resp->assertStatus(200);
        $data = $resp->json('transactions');
        $this->assertIsArray($data);
        $this->assertCount(3, $data);
    }
}