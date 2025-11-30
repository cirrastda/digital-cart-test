<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use Mockery;
use App\Http\Controllers\TransactionController;
use App\Services\TransactionService;
use App\Services\UserService;
use App\Models\User;
use App\Http\Requests\DepositRequest;
use App\Http\Requests\WithdrawRequest;
use App\Http\Requests\TransferRequest;
use Tests\Provider;

class TransactionControllerTest extends TestCase
{
    public static function provider_deposit(): array
    {
        $ok = new Provider();
        $ok->name = 'deposit sucesso';
        $ok->data = ['amount' => 100.0];
        $ok->success = true;
        $ok->expected = ['status' => 200, 'message' => 'Depósito realizado com sucesso!'];
        $ok->prepare = function() use ($ok) {
            $ts = Mockery::mock(TransactionService::class);
            $us = Mockery::mock(UserService::class);
            $c = new TransactionController($ts, $us);
            $u = new User();
            $us->shouldReceive('getAuthUser')->andReturn($u);
            $ts->shouldReceive('depositMoney')->andReturnTrue();
            $req = Mockery::mock(DepositRequest::class);
            $req->shouldReceive('validated')->andReturn(['amount' => $ok->data['amount']]);
            return [$c, $req];
        };

        $err = new Provider();
        $err->name = 'deposit erro serviço';
        $err->data = ['amount' => 100.0, 'error' => 'X'];
        $err->success = false;
        $err->expected = ['status' => 500, 'message' => 'Erro ao realizar depósito: X'];
        $err->prepare = function() use ($err) {
            $ts = Mockery::mock(TransactionService::class);
            $us = Mockery::mock(UserService::class);
            $c = new TransactionController($ts, $us);
            $u = new User();
            $us->shouldReceive('getAuthUser')->andReturn($u);
            $ts->shouldReceive('depositMoney')->andThrow(new \Exception($err->data['error']));
            $req = Mockery::mock(DepositRequest::class);
            $req->shouldReceive('validated')->andReturn(['amount' => $err->data['amount']]);
            return [$c, $req];
        };

        return [[$ok], [$err]];
    }

    /**
     * @dataProvider provider_deposit
     */
    public function test_deposit(Provider $p): void
    {
        [$c, $req] = ($p->prepare)();
        $res = $c->deposit($req);
        $payload = json_decode($res->getContent(), true);
        if ($p->success) {
            $this->assertSame(200, $payload['code'] ?? 0);
            $this->assertSame($p->expected['message'], $payload['data']['message'] ?? '');
        } else {
            $this->assertSame(500, $payload['code'] ?? 0);
            $this->assertStringContainsString($p->expected['message'], $payload['error'] ?? '');
        }
    }

    public static function provider_withdraw(): array
    {
        $ok = new Provider();
        $ok->name = 'withdraw sucesso';
        $ok->data = ['amount' => 50.0];
        $ok->success = true;
        $ok->expected = ['status' => 200, 'message' => 'Saque realizado com sucesso!'];
        $ok->prepare = function() use ($ok) {
            $ts = Mockery::mock(TransactionService::class);
            $us = Mockery::mock(UserService::class);
            $c = new TransactionController($ts, $us);
            $u = new User();
            $us->shouldReceive('getAuthUser')->andReturn($u);
            $ts->shouldReceive('withdrawMoney')->andReturnTrue();
            $req = Mockery::mock(WithdrawRequest::class);
            $req->shouldReceive('validated')->andReturn(['amount' => $ok->data['amount']]);
            return [$c, $req];
        };

        $err = new Provider();
        $err->name = 'withdraw erro serviço';
        $err->data = ['amount' => 50.0, 'error' => 'Y'];
        $err->success = false;
        $err->expected = ['status' => 500, 'message' => 'Erro ao realizar saque: Y'];
        $err->prepare = function() use ($err) {
            $ts = Mockery::mock(TransactionService::class);
            $us = Mockery::mock(UserService::class);
            $c = new TransactionController($ts, $us);
            $u = new User();
            $us->shouldReceive('getAuthUser')->andReturn($u);
            $ts->shouldReceive('withdrawMoney')->andThrow(new \Exception($err->data['error']));
            $req = Mockery::mock(WithdrawRequest::class);
            $req->shouldReceive('validated')->andReturn(['amount' => $err->data['amount']]);
            return [$c, $req];
        };

        return [[$ok], [$err]];
    }

    /**
     * @dataProvider provider_withdraw
     */
    public function test_withdraw(Provider $p): void
    {
        [$c, $req] = ($p->prepare)();
        $res = $c->withdraw($req);
        $payload = json_decode($res->getContent(), true);
        if ($p->success) {
            $this->assertSame(200, $payload['code'] ?? 0);
            $this->assertSame($p->expected['message'], $payload['data']['message'] ?? '');
        } else {
            $this->assertSame(500, $payload['code'] ?? 0);
            $this->assertStringContainsString($p->expected['message'], $payload['error'] ?? '');
        }
    }

    public static function provider_transfer(): array
    {
        $ok = new Provider();
        $ok->name = 'transfer sucesso';
        $ok->data = ['amount' => 30.0, 'recipient' => 'r@e.com'];
        $ok->success = true;
        $ok->expected = ['status' => 200, 'message' => 'Transferência realizada com sucesso!'];
        $ok->prepare = function() use ($ok) {
            $ts = Mockery::mock(TransactionService::class);
            $us = Mockery::mock(UserService::class);
            $c = new TransactionController($ts, $us);
            $sender = new User();
            $recipient = new User();
            $us->shouldReceive('getAuthUser')->andReturn($sender);
            $us->shouldReceive('findUserByEmail')->with($ok->data['recipient'])->andReturn($recipient);
            $ts->shouldReceive('transferMoney')->andReturnTrue();
            $req = Mockery::mock(TransferRequest::class);
            $req->shouldReceive('validated')->andReturn(['amount' => $ok->data['amount'], 'recipient' => $ok->data['recipient']]);
            return [$c, $req];
        };

        $err = new Provider();
        $err->name = 'transfer erro serviço';
        $err->data = ['amount' => 30.0, 'recipient' => 'r@e.com', 'error' => 'Z'];
        $err->success = false;
        $err->expected = ['status' => 500, 'message' => 'Erro ao realizar transferência: Z'];
        $err->prepare = function() use ($err) {
            $ts = Mockery::mock(TransactionService::class);
            $us = Mockery::mock(UserService::class);
            $c = new TransactionController($ts, $us);
            $sender = new User();
            $recipient = new User();
            $us->shouldReceive('getAuthUser')->andReturn($sender);
            $us->shouldReceive('findUserByEmail')->with($err->data['recipient'])->andReturn($recipient);
            $ts->shouldReceive('transferMoney')->andThrow(new \Exception($err->data['error']));
            $req = Mockery::mock(TransferRequest::class);
            $req->shouldReceive('validated')->andReturn(['amount' => $err->data['amount'], 'recipient' => $err->data['recipient']]);
            return [$c, $req];
        };

        return [[$ok], [$err]];
    }

    /**
     * @dataProvider provider_transfer
     */
    public function test_transfer(Provider $p): void
    {
        [$c, $req] = ($p->prepare)();
        $res = $c->transfer($req);
        $payload = json_decode($res->getContent(), true);
        if ($p->success) {
            $this->assertSame(200, $payload['code'] ?? 0);
            $this->assertSame($p->expected['message'], $payload['data']['message'] ?? '');
        } else {
            $this->assertSame(500, $payload['code'] ?? 0);
            $this->assertStringContainsString($p->expected['message'], $payload['error'] ?? '');
        }
    }

    public static function provider_history(): array
    {
        $ok = new Provider();
        $ok->name = 'history sucesso';
        $ok->success = true;
        $ok->expected = ['status' => 200];
        $ok->prepare = function() {
            $ts = Mockery::mock(TransactionService::class);
            $us = Mockery::mock(UserService::class);
            $c = new TransactionController($ts, $us);
            $u = new User();
            $us->shouldReceive('getAuthUser')->andReturn($u);
            $ts->shouldReceive('getTransactionHistory')->andReturn([['id' => 1]]);
            return [$c];
        };

        $err = new Provider();
        $err->name = 'history erro serviço';
        $err->success = false;
        $err->data = ['error' => 'H'];
        $err->expected = ['status' => 500, 'message' => 'Erro ao obter histórico de transações: H'];
        $err->prepare = function() use ($err) {
            $ts = Mockery::mock(TransactionService::class);
            $us = Mockery::mock(UserService::class);
            $c = new TransactionController($ts, $us);
            $u = new User();
            $us->shouldReceive('getAuthUser')->andReturn($u);
            $ts->shouldReceive('getTransactionHistory')->andThrow(new \Exception($err->data['error']));
            return [$c];
        };

        return [[$ok], [$err]];
    }

    /**
     * @dataProvider provider_history
     */
    public function test_history(Provider $p): void
    {
        [$c] = ($p->prepare)();
        $res = $c->history();
        $this->assertSame($p->expected['status'], $res->getStatusCode());
        if (!$p->success) {
            $payload = json_decode($res->getContent(), true);
            $this->assertFalse($payload['success'] ?? true);
            $this->assertSame(500, $payload['code'] ?? 0);
            $this->assertSame($p->expected['message'], $payload['error'] ?? '');
        } else {
            $payload = json_decode($res->getContent(), true);
            $this->assertTrue(($payload['success'] ?? false));
            $this->assertSame(200, $payload['code'] ?? 0);
            $this->assertArrayHasKey('transactions', $payload['data'] ?? []);
        }
    }
}