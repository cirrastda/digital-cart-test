<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Services\TransactionService;
use Laravel\Sanctum\Sanctum;

class TransactionControllerErrorServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_deposit_service_error_returns_500(): void
    {
        $user = User::factory()->create(['balance' => 0]);
        Sanctum::actingAs($user, ['*']);

        $mock = $this->mock(TransactionService::class);
        $mock->shouldReceive('depositExceedsDailyLimit')->andReturn(false);
        $mock->shouldReceive('depositMoney')->andThrow(new \Exception('Falha no serviço'));

        $resp = $this->postJson('/deposit', ['amount' => 25]);
        $resp->assertStatus(500);
        $resp->assertJson(['message' => 'Erro ao realizar depósito: Falha no serviço']);
    }

    public function test_withdraw_service_error_returns_500(): void
    {
        $user = User::factory()->create(['balance' => 100]);
        Sanctum::actingAs($user, ['*']);

        $mock = $this->mock(TransactionService::class);
        $mock->shouldReceive('withdrawExceedsDailyLimit')->andReturn(false);
        $mock->shouldReceive('withdrawMoney')->andThrow(new \Exception('Falha no serviço'));

        $resp = $this->postJson('/withdraw', ['amount' => 40]);
        $resp->assertStatus(500);
        $resp->assertJson(['message' => 'Erro ao realizar saque: Falha no serviço']);
    }

    public function test_transfer_service_error_returns_500(): void
    {
        $sender = User::factory()->create(['balance' => 80]);
        $recipient = User::factory()->create(['balance' => 5]);
        Sanctum::actingAs($sender, ['*']);

        $mock = $this->mock(TransactionService::class);
        $mock->shouldReceive('transferMoney')->andThrow(new \Exception('Falha no serviço'));

        $resp = $this->postJson('/transfer', ['amount' => 30, 'recipient' => $recipient->email]);
        $resp->assertStatus(500);
        $resp->assertJson(['message' => 'Erro ao realizar transferência: Falha no serviço']);
    }
}
