<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use Mockery;
use App\Http\Controllers\UserController;
use App\Services\UserService;
use App\Models\User;
use App\Http\Requests\StoreUserRequest;
use Tests\Provider;

class UserControllerTest extends TestCase
{
    public static function provider_store(): array
    {
        $ok = new Provider();
        $ok->name = 'store sucesso';
        $ok->success = true;
        $ok->data = ['name' => 'Nome', 'email' => 'a@b.com', 'password' => 'pwd'];
        $ok->expected = ['status' => 201];
        $ok->prepare = function() use ($ok) {
            $us = Mockery::mock(UserService::class);
            $c = new UserController($us);
            $req = Mockery::mock(StoreUserRequest::class);
            $req->name = $ok->data['name'];
            $req->email = $ok->data['email'];
            $req->password = $ok->data['password'];
            $u = new User();
            $us->shouldReceive('createUser')->andReturn($u);
            $us->shouldReceive('createToken')->andReturn('tkn');
            return [$c, $req];
        };

        $err = new Provider();
        $err->name = 'store erro serviço';
        $err->success = false;
        $err->data = ['name' => 'Nome', 'email' => 'a@b.com', 'password' => 'pwd', 'error' => 'Falha ao criar'];
        $err->expected = ['status' => 500, 'message' => 'Falha ao criar'];
        $err->prepare = function() use ($err) {
            $us = Mockery::mock(UserService::class);
            $c = new UserController($us);
            $req = Mockery::mock(StoreUserRequest::class);
            $req->name = $err->data['name'];
            $req->email = $err->data['email'];
            $req->password = $err->data['password'];
            $us->shouldReceive('createUser')->andThrow(new \Exception($err->expected['message']));
            return [$c, $req];
        };

        return [[$ok], [$err]];
    }

    /**
     * @dataProvider provider_store
     */
    public function test_store(Provider $p): void
    {
        [$c, $req] = ($p->prepare)();
        $res = $c->store($req);
        $payload = json_decode($res->getContent(), true);
        if ($p->success) {
            $this->assertSame(201, $payload['code'] ?? 0);
            $this->assertArrayHasKey('user', $payload['data'] ?? []);
            $this->assertArrayHasKey('token', $payload['data'] ?? []);
        } else {
            $this->assertSame(500, $payload['code'] ?? 0);
            $this->assertStringContainsString($p->expected['message'], (string)($payload['error'] ?? ''));
        }
    }

    public static function provider_get_balance(): array
    {
        $ok = new Provider();
        $ok->name = 'get_balance sucesso';
        $ok->success = true;
        $ok->expected = ['status' => 200];
        $ok->prepare = function() {
            $us = Mockery::mock(UserService::class);
            $c = new UserController($us);
            $u = new User();
            $u->balance = 10;
            $us->shouldReceive('getAuthUser')->andReturn($u);
            return [$c];
        };

        $unauth = new Provider();
        $unauth->name = 'get_balance não autenticado';
        $unauth->success = false;
        $unauth->errors = ['exception_class' => \Illuminate\Auth\AuthenticationException::class, 'message' => 'Não autenticado'];
        $unauth->expected = ['status' => 401, 'message' => 'Não autenticado'];
        $unauth->prepare = function() use ($unauth) {
            $us = Mockery::mock(UserService::class);
            $c = new UserController($us);
            $us->shouldReceive('getAuthUser')->andThrow(new $unauth->errors['exception_class']($unauth->errors['message']));
            return [$c];
        };

        $err = new Provider();
        $err->name = 'get_balance erro genérico';
        $err->success = false;
        $err->errors = ['exception_class' => \Exception::class, 'message' => 'X'];
        $err->expected = ['status' => 500, 'message' => 'Erro ao buscar saldo do usuário: X'];
        $err->prepare = function() use ($err) {
            $us = Mockery::mock(UserService::class);
            $c = new UserController($us);
            $us->shouldReceive('getAuthUser')->andThrow(new $err->errors['exception_class']($err->errors['message']));
            return [$c];
        };

        return [[$ok], [$unauth], [$err]];
    }

    /**
     * @dataProvider provider_get_balance
     */
    public function test_get_balance(Provider $p): void
    {
        [$c] = ($p->prepare)();
        $res = $c->get_balance();
        // HTTP status may vary in unit context; assert envelope codes/messages
        if (!$p->success) {
            $payload = json_decode($res->getContent(), true);
            $this->assertFalse($payload['success'] ?? true);
            $this->assertSame($p->expected['status'], $payload['code'] ?? 0);
            $this->assertStringContainsString($p->expected['message'], $payload['error'] ?? '');
        } else {
            $payload = json_decode($res->getContent(), true);
            $this->assertSame(200, $payload['code'] ?? 0);
            $this->assertArrayHasKey('balance', $payload['data'] ?? []);
        }
    }
}