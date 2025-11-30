<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Mockery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Services\UserService;
use App\Models\User;
use Tests\Provider;

class UserServiceTest extends TestCase
{
    public static function provider_create_user(): array
    {
        $ok = new Provider();
        $ok->name = 'createUser sucesso';
        $ok->data = ['name' => 'Nome', 'email' => 'a@b.com', 'password' => 'pwd'];
        $ok->success = true;
        $ok->expected = ['type' => User::class];
        $ok->prepare = function() use ($ok) {
            DB::shouldReceive('beginTransaction')->once();
            DB::shouldReceive('commit')->zeroOrMoreTimes();
            DB::shouldReceive('rollBack')->zeroOrMoreTimes();
            Hash::shouldReceive('make')->andReturn('hashed');
            $over = Mockery::mock(User::class)->makePartial();
            $over->shouldReceive('save')->andReturnTrue();
            $service = Mockery::mock(UserService::class)->makePartial();
            $service->shouldAllowMockingProtectedMethods();
            $service->shouldReceive('userExistsByEmail')->with($ok->data['email'])->andReturn(false);
            $service->shouldReceive('createToken')->andReturn('tkn');
            $service->shouldReceive('newUser')->andReturn($over);
            return $service;
        };

        $exists = new Provider();
        $exists->name = 'createUser email existente';
        $exists->data = ['name' => 'Nome', 'email' => 'a@b.com', 'password' => 'pwd'];
        $exists->success = false;
        $exists->errors = ['exception_class' => \Exception::class, 'message' => 'Usuário já existe com este email.'];
        $exists->prepare = function() use ($exists) {
            DB::shouldReceive('beginTransaction')->once();
            DB::shouldReceive('commit')->zeroOrMoreTimes();
            DB::shouldReceive('rollBack')->zeroOrMoreTimes();
            Hash::shouldReceive('make')->andReturn('hashed');
            $service = Mockery::mock(UserService::class)->makePartial();
            $service->shouldAllowMockingProtectedMethods();
            $service->shouldReceive('userExistsByEmail')->with($exists->data['email'])->andReturn(true);
            $service->shouldReceive('createToken')->andReturn('tkn');
            return $service;
        };

        $tokenFailNotLoaded = new Provider();
        $tokenFailNotLoaded->name = 'createUser falha ao gerar token - usuário sem ID';
        $tokenFailNotLoaded->data = ['name' => 'Nome', 'email' => 'a@b.com', 'password' => 'pwd'];
        $tokenFailNotLoaded->success = false;
        $tokenFailNotLoaded->errors = ['exception_class' => \Exception::class, 'message' => 'Falha ao gerar token Sanctum: Usuário recém-criado não possui ID carregado para Sanctum.'];
        $tokenFailNotLoaded->prepare = function() use ($tokenFailNotLoaded) {
            DB::shouldReceive('beginTransaction')->once();
            DB::shouldReceive('commit')->zeroOrMoreTimes();
            DB::shouldReceive('rollBack')->zeroOrMoreTimes();
            Hash::shouldReceive('make')->andReturn('hashed');
            $over = Mockery::mock(User::class)->makePartial();
            $over->shouldReceive('save')->andReturnTrue();
            $service = Mockery::mock(UserService::class)->makePartial();
            $service->shouldAllowMockingProtectedMethods();
            $service->shouldReceive('userExistsByEmail')->with($tokenFailNotLoaded->data['email'])->andReturn(false);
            $service->shouldReceive('createToken')->andThrow(new \Exception('Falha ao gerar token Sanctum: Usuário recém-criado não possui ID carregado para Sanctum.'));
            $service->shouldReceive('newUser')->andReturn($over);
            return $service;
        };

        $saveFail = new Provider();
        $saveFail->name = 'createUser falha ao salvar';
        $saveFail->data = ['name' => 'Nome', 'email' => 'a@b.com', 'password' => 'pwd'];
        $saveFail->success = false;
        $saveFail->errors = ['exception_class' => \Exception::class, 'message' => 'Falha ao salvar'];
        $saveFail->prepare = function() use ($saveFail) {
            DB::shouldReceive('beginTransaction')->once();
            DB::shouldReceive('commit')->zeroOrMoreTimes();
            DB::shouldReceive('rollBack')->zeroOrMoreTimes();
            Hash::shouldReceive('make')->andReturn('hashed');
            $over = Mockery::mock(User::class)->makePartial();
            $over->shouldReceive('save')->andThrow(new \Exception('Falha ao salvar'));
            $service = Mockery::mock(UserService::class)->makePartial();
            $service->shouldAllowMockingProtectedMethods();
            $service->shouldReceive('userExistsByEmail')->with($saveFail->data['email'])->andReturn(false);
            $service->shouldReceive('createToken')->andReturn('tkn');
            $service->shouldReceive('newUser')->andReturn($over);
            return $service;
        };

        return [[$ok], [$exists], [$tokenFailNotLoaded], [$saveFail]];
    }

    /**
     * @dataProvider provider_create_user
     */
    public function test_create_user_saves_to_db(Provider $p): void
    {
        $service = ($p->prepare)();

        try {
            $result = $service->createUser($p->data['name'], $p->data['email'], $p->data['password']);
            $this->assertTrue($p->success);
            $this->assertInstanceOf($p->expected['type'], $result);
            $this->assertSame('Nome', $result->name);
            $this->assertSame('a@b.com', $result->email);
            $this->assertSame('hashed', $result->password);
        } catch (\Throwable $te) {
            $this->assertFalse($p->success);
            $this->assertInstanceOf($p->errors['exception_class'], $te);
            $this->assertSame($p->errors['message'], $te->getMessage());
        }
    }

    public static function provider_get_auth_user(): array
    {
        $ok = new Provider();
        $ok->name = 'getAuthUser sucesso';
        $ok->success = true;
        $ok->prepare = function() {
            $u = new User();
            Auth::shouldReceive('user')->andReturn($u);
            return new UserService();
        };

        $err = new Provider();
        $err->name = 'getAuthUser erro';
        $err->success = false;
        $err->errors = ['exception_class' => \Illuminate\Auth\AuthenticationException::class, 'message' => 'Usuário não autenticado.'];
        $err->prepare = function() use ($err) {
            Auth::shouldReceive('user')->andReturn(null);
            return new UserService();
        };

        return [[$ok], [$err]];
    }

    /**
     * @dataProvider provider_get_auth_user
     */
    public function test_get_auth_user(Provider $p): void
    {
        $service = ($p->prepare)();
        try {
            $result = $service->getAuthUser();
            $this->assertTrue($p->success);
            $this->assertInstanceOf(User::class, $result);
        } catch (\Throwable $te) {
            $this->assertFalse($p->success);
            $this->assertInstanceOf($p->errors['exception_class'], $te);
            $this->assertSame($p->errors['message'], $te->getMessage());
        }
    }

    public static function provider_find_user_by_id(): array
    {
        $ok = new Provider();
        $ok->name = 'findUserById sucesso';
        $ok->data = ['id' => 1];
        $ok->success = true;
        $ok->prepare = function() use ($ok) {
            $qb = Mockery::mock();
            $qb->shouldReceive('find')->with($ok->data['id'])->andReturn(new User());
            $service = Mockery::mock(UserService::class)->makePartial();
            $service->shouldAllowMockingProtectedMethods();
            $service->shouldReceive('userQuery')->andReturn($qb);
            return $service;
        };

        $err = new Provider();
        $err->name = 'findUserById erro';
        $err->data = ['id' => 999];
        $err->success = false;
        $err->errors = ['exception_class' => \Exception::class, 'message' => 'Usuário não encontrado.'];
        $err->prepare = function() use ($err) {
            $qb = Mockery::mock();
            $qb->shouldReceive('find')->with($err->data['id'])->andReturn(null);
            $service = Mockery::mock(UserService::class)->makePartial();
            $service->shouldAllowMockingProtectedMethods();
            $service->shouldReceive('userQuery')->andReturn($qb);
            return $service;
        };

        return [[$ok], [$err]];
    }

    /**
     * @dataProvider provider_find_user_by_id
     */
    public function test_find_user_by_id(Provider $p): void
    {
        $service = ($p->prepare)();
        try {
            $result = $service->findUserById($p->data['id']);
            $this->assertTrue($p->success);
            $this->assertInstanceOf(User::class, $result);
        } catch (\Throwable $te) {
            $this->assertFalse($p->success);
            $this->assertInstanceOf($p->errors['exception_class'], $te);
            $this->assertSame($p->errors['message'], $te->getMessage());
        }
    }

    public static function provider_find_user_by_email(): array
    {
        $ok = new Provider();
        $ok->name = 'findUserByEmail sucesso';
        $ok->data = ['email' => 'a@b.com'];
        $ok->success = true;
        $ok->prepare = function() use ($ok) {
            $qb = Mockery::mock();
            $qb->shouldReceive('where')->with('email', $ok->data['email'])->andReturn($qb);
            $qb->shouldReceive('first')->andReturn(new User());
            $service = Mockery::mock(UserService::class)->makePartial();
            $service->shouldAllowMockingProtectedMethods();
            $service->shouldReceive('userQuery')->andReturn($qb);
            return $service;
        };

        $err = new Provider();
        $err->name = 'findUserByEmail erro';
        $err->data = ['email' => 'x@y.com'];
        $err->success = false;
        $err->errors = ['exception_class' => \Exception::class, 'message' => 'Usuário não encontrado.'];
        $err->prepare = function() use ($err) {
            $qb = Mockery::mock();
            $qb->shouldReceive('where')->with('email', $err->data['email'])->andReturn($qb);
            $qb->shouldReceive('first')->andReturn(null);
            $service = Mockery::mock(UserService::class)->makePartial();
            $service->shouldAllowMockingProtectedMethods();
            $service->shouldReceive('userQuery')->andReturn($qb);
            return $service;
        };

        return [[$ok], [$err]];
    }

    /**
     * @dataProvider provider_find_user_by_email
     */
    public function test_find_user_by_email(Provider $p): void
    {
        $service = ($p->prepare)();
        try {
            $result = $service->findUserByEmail($p->data['email']);
            $this->assertTrue($p->success);
            $this->assertInstanceOf(User::class, $result);
        } catch (\Throwable $te) {
            $this->assertFalse($p->success);
            $this->assertInstanceOf($p->errors['exception_class'], $te);
            $this->assertSame($p->errors['message'], $te->getMessage());
        }
    }

    public static function provider_user_exists_by_email(): array
    {
        $exists = new Provider();
        $exists->name = 'userExistsByEmail true';
        $exists->data = ['email' => 'a@b.com'];
        $exists->success = true;
        $exists->expected = ['result' => true];
        $exists->prepare = function() use ($exists) {
            $qb = Mockery::mock();
            $qb->shouldReceive('where')->with('email', $exists->data['email'])->andReturn($qb);
            $qb->shouldReceive('exists')->andReturn(true);
            $service = Mockery::mock(UserService::class)->makePartial();
            $service->shouldAllowMockingProtectedMethods();
            $service->shouldReceive('userQuery')->andReturn($qb);
            return $service;
        };

        $notExists = new Provider();
        $notExists->name = 'userExistsByEmail false';
        $notExists->data = ['email' => 'x@y.com'];
        $notExists->success = true;
        $notExists->expected = ['result' => false];
        $notExists->prepare = function() use ($notExists) {
            $qb = Mockery::mock();
            $qb->shouldReceive('where')->with('email', $notExists->data['email'])->andReturn($qb);
            $qb->shouldReceive('exists')->andReturn(false);
            $service = Mockery::mock(UserService::class)->makePartial();
            $service->shouldAllowMockingProtectedMethods();
            $service->shouldReceive('userQuery')->andReturn($qb);
            return $service;
        };

        return [[$exists], [$notExists]];
    }

    /**
     * @dataProvider provider_user_exists_by_email
     */
    public function test_user_exists_by_email(Provider $p): void
    {
        $service = ($p->prepare)();
        $result = $service->userExistsByEmail($p->data['email']);
        $this->assertSame($p->expected['result'], $result);
    }

    public static function provider_create_token(): array
    {
        $ok = new Provider();
        $ok->name = 'createToken sucesso';
        $ok->data = ['email' => 'a@b.com', 'token' => 'abc123'];
        $ok->success = true;
        $ok->expected = ['result' => 'abc123'];
        $ok->prepare = function() use ($ok) {
            $u = Mockery::mock(User::class)->makePartial();
            $u->email = $ok->data['email'];
            $u->shouldReceive('getKey')->andReturn(1);
            $tokenable = Mockery::mock();
            $tokenable->plainTextToken = $ok->data['token'];
            $u->shouldReceive('createToken')->with('api')->andReturn($tokenable);
            $u->shouldReceive('save')->andReturnTrue();
            return $u;
        };

        $errNotLoaded = new Provider();
        $errNotLoaded->name = 'createToken erro usuário sem ID';
        $errNotLoaded->data = ['email' => 'a@b.com'];
        $errNotLoaded->success = false;
        $errNotLoaded->errors = ['exception_class' => \Exception::class, 'message' => 'Falha ao gerar token Sanctum: Usuário recém-criado não possui ID carregado para Sanctum.'];
        $errNotLoaded->prepare = function() use ($errNotLoaded) {
            $u = Mockery::mock(User::class)->makePartial();
            $u->email = $errNotLoaded->data['email'];
            $u->shouldReceive('getKey')->andReturn(null);
            return $u;
        };

        $errThrown = new Provider();
        $errThrown->name = 'createToken erro createToken lança';
        $errThrown->data = ['email' => 'a@b.com'];
        $errThrown->success = false;
        $errThrown->errors = ['exception_class' => \Exception::class, 'message' => 'Falha ao gerar token Sanctum: boom'];
        $errThrown->prepare = function() use ($errThrown) {
            $u = Mockery::mock(User::class)->makePartial();
            $u->email = $errThrown->data['email'];
            $u->shouldReceive('getKey')->andReturn(1);
            $u->shouldReceive('createToken')->with('api')->andThrow(new \Exception('boom'));
            return $u;
        };

        return [[$ok], [$errNotLoaded], [$errThrown]];
    }

    /**
     * @dataProvider provider_create_token
     */
    public function test_create_token(Provider $p): void
    {
        $service = new UserService();
        $u = ($p->prepare)();
        try {
            $t = $service->createToken($u);
            $this->assertTrue($p->success);
            $this->assertSame($p->expected['result'], $t);
        } catch (\Throwable $te) {
            $this->assertFalse($p->success);
            $this->assertInstanceOf($p->errors['exception_class'], $te);
            $this->assertSame($p->errors['message'], $te->getMessage());
        }
    }
}
