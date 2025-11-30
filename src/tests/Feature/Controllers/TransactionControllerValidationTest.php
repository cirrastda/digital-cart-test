<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

class TransactionControllerValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_invalid_amount_returns_422(): void
    {
        $user = User::factory()->create(['balance' => 0]);
        Sanctum::actingAs($user, ['*']);

        $resp = $this->postJson('/deposit', ['amount' => -10]);
        $resp->assertStatus(422);
        $resp->assertJsonValidationErrors(['amount']);
    }

    public function test_withdraw_insufficient_funds_returns_422(): void
    {
        $user = User::factory()->create(['balance' => 50]);
        Sanctum::actingAs($user, ['*']);

        $resp = $this->postJson('/withdraw', ['amount' => 60]);
        $resp->assertStatus(422);
        $resp->assertJsonValidationErrors(['amount']);
    }

    public function test_transfer_invalid_recipient_returns_422(): void
    {
        $sender = User::factory()->create(['balance' => 100]);
        Sanctum::actingAs($sender, ['*']);

        $resp = $this->postJson('/transfer', ['amount' => 10, 'recipient' => 'missing@example.com']);
        $resp->assertStatus(422);
        $resp->assertJsonValidationErrors(['recipient']);
    }

    public function test_transfer_insufficient_funds_returns_422(): void
    {
        $sender = User::factory()->create(['balance' => 20]);
        $recipient = User::factory()->create(['balance' => 0]);
        Sanctum::actingAs($sender, ['*']);

        $resp = $this->postJson('/transfer', ['amount' => 50, 'recipient' => $recipient->email]);
        $resp->assertStatus(422);
        $resp->assertJsonValidationErrors(['amount']);
    }
}