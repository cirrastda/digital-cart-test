<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use Mockery;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Services\TransactionService;
use App\Models\User;
use App\Models\Transaction;
use Tests\Provider;

class TransactionServiceTest extends TestCase
{
    public static function provider_deposit(): array
    {
        $ok = new Provider();
        $ok->name = 'deposit sucesso';
        $ok->success = true;
        $ok->data = ['initial' => 0.0, 'amount' => 100.0];
        $ok->prepare = function() use ($ok) {
            DB::shouldReceive('beginTransaction')->once();
            DB::shouldReceive('commit')->zeroOrMoreTimes();
            DB::shouldReceive('rollBack')->zeroOrMoreTimes();

            $transaction = Mockery::mock();
            $transaction->shouldReceive('deposits->create')->andReturnTrue();

            $transactionsRel = Mockery::mock();
            $transactionsRel->shouldReceive('create')->andReturn($transaction);

            $user = Mockery::mock(User::class)->makePartial();
            $user->balance = $ok->data['initial'];
            $user->shouldReceive('transactions')->andReturn($transactionsRel);
            $user->shouldReceive('save')->andReturnTrue();
            return $user;
        };

        $saveErr = new Provider();
        $saveErr->name = 'deposit erro salvar usuário';
        $saveErr->success = false;
        $saveErr->data = ['initial' => 0.0, 'amount' => 100.0];
        $saveErr->errors = ['exception_class' => \Exception::class, 'message' => 'Falha ao salvar'];
        $saveErr->prepare = function() use ($saveErr) {
            DB::shouldReceive('beginTransaction')->once();
            DB::shouldReceive('commit')->zeroOrMoreTimes();
            DB::shouldReceive('rollBack')->zeroOrMoreTimes();

            $transaction = Mockery::mock();
            $transaction->shouldReceive('deposits->create')->andReturnTrue();

            $transactionsRel = Mockery::mock();
            $transactionsRel->shouldReceive('create')->andReturn($transaction);

            $user = Mockery::mock(User::class)->makePartial();
            $user->balance = $saveErr->data['initial'];
            $user->shouldReceive('transactions')->andReturn($transactionsRel);
            $user->shouldReceive('save')->andThrow(new \Exception('Falha ao salvar'));
            return $user;
        };

        return [[$ok], [$saveErr]];
    }

    /**
     * @dataProvider provider_deposit
     */
    public function test_deposit(Provider $p): void
    {
        $user = ($p->prepare)();
        $service = new TransactionService();
        try {
            $result = $service->depositMoney($user, $p->data['amount']);
            $this->assertTrue($p->success);
            $this->assertTrue($result);
        } catch (\Throwable $te) {
            $this->assertFalse($p->success);
            $this->assertInstanceOf($p->errors['exception_class'], $te);
            $this->assertSame($p->errors['message'], $te->getMessage());
        }
    }

    public static function provider_withdraw(): array
    {
        $ok = new Provider();
        $ok->name = 'withdraw sucesso';
        $ok->success = true;
        $ok->data = ['initial' => 100.0, 'amount' => 40.0];
        $ok->prepare = function() use ($ok) {
            DB::shouldReceive('beginTransaction')->once();
            DB::shouldReceive('commit')->zeroOrMoreTimes();
            DB::shouldReceive('rollBack')->zeroOrMoreTimes();

            $transaction = Mockery::mock();
            $transaction->shouldReceive('withdraws->create')->andReturnTrue();

            $transactionsRel = Mockery::mock();
            $transactionsRel->shouldReceive('create')->andReturn($transaction);

            $user = Mockery::mock(User::class)->makePartial();
            $user->balance = $ok->data['initial'];
            $user->shouldReceive('transactions')->andReturn($transactionsRel);
            $user->shouldReceive('save')->andReturnTrue();
            return $user;
        };

        $createErr = new Provider();
        $createErr->name = 'withdraw erro criar saque';
        $createErr->success = false;
        $createErr->data = ['initial' => 100.0, 'amount' => 40.0];
        $createErr->errors = ['exception_class' => \Exception::class, 'message' => 'Falha ao criar saque'];
        $createErr->prepare = function() use ($createErr) {
            DB::shouldReceive('beginTransaction')->once();
            DB::shouldReceive('commit')->zeroOrMoreTimes();
            DB::shouldReceive('rollBack')->zeroOrMoreTimes();

            $transaction = Mockery::mock();
            $transaction->shouldReceive('withdraws->create')->andThrow(new \Exception('Falha ao criar saque'));

            $transactionsRel = Mockery::mock();
            $transactionsRel->shouldReceive('create')->andReturn($transaction);

            $user = Mockery::mock(User::class)->makePartial();
            $user->balance = $createErr->data['initial'];
            $user->shouldReceive('transactions')->andReturn($transactionsRel);
            $user->shouldReceive('save')->andReturnTrue();
            return $user;
        };

        return [[$ok], [$createErr]];
    }

    /**
     * @dataProvider provider_withdraw
     */
    public function test_withdraw(Provider $p): void
    {
        $user = ($p->prepare)();
        $service = new TransactionService();
        try {
            $result = $service->withdrawMoney($user, $p->data['amount']);
            $this->assertTrue($p->success);
            $this->assertTrue($result);
        } catch (\Throwable $te) {
            $this->assertFalse($p->success);
            $this->assertInstanceOf($p->errors['exception_class'], $te);
            $this->assertSame($p->errors['message'], $te->getMessage());
        }
    }

    public static function provider_transfer(): array
    {
        $ok = new Provider();
        $ok->name = 'transfer sucesso';
        $ok->success = true;
        $ok->data = ['amount' => 30.0];
        $ok->prepare = function() use ($ok) {
            DB::shouldReceive('beginTransaction')->once();
            DB::shouldReceive('commit')->zeroOrMoreTimes();
            DB::shouldReceive('rollBack')->zeroOrMoreTimes();

            $transaction = Mockery::mock();
            $transaction->shouldReceive('transfers->create')->andReturnTrue();

            $transactionsRelSender = Mockery::mock();
            $transactionsRelSender->shouldReceive('create')->andReturn($transaction);

            $sender = Mockery::mock(User::class)->makePartial();
            $sender->id = 1;
            $sender->balance = 100.0;
            $sender->shouldReceive('transactions')->andReturn($transactionsRelSender);
            $sender->shouldReceive('save')->andReturnTrue();

            $receiver = Mockery::mock(User::class)->makePartial();
            $receiver->id = 2;
            $receiver->balance = 0.0;
            $receiver->shouldReceive('save')->andReturnTrue();

            return [$sender, $receiver];
        };

        $saveErr = new Provider();
        $saveErr->name = 'transfer erro salvar remetente';
        $saveErr->success = false;
        $saveErr->data = ['amount' => 30.0];
        $saveErr->errors = ['exception_class' => \Exception::class, 'message' => 'Falha ao salvar'];
        $saveErr->prepare = function() use ($saveErr) {
            DB::shouldReceive('beginTransaction')->once();
            DB::shouldReceive('commit')->zeroOrMoreTimes();
            DB::shouldReceive('rollBack')->zeroOrMoreTimes();

            $transaction = Mockery::mock();
            $transaction->shouldReceive('transfers->create')->andReturnTrue();

            $transactionsRelSender = Mockery::mock();
            $transactionsRelSender->shouldReceive('create')->andReturn($transaction);

            $sender = Mockery::mock(User::class)->makePartial();
            $sender->id = 1;
            $sender->balance = 100.0;
            $sender->shouldReceive('transactions')->andReturn($transactionsRelSender);
            $sender->shouldReceive('save')->andThrow(new \Exception('Falha ao salvar'));

            $receiver = Mockery::mock(User::class)->makePartial();
            $receiver->id = 2;
            $receiver->balance = 0.0;
            $receiver->shouldReceive('save')->andReturnTrue();

            return [$sender, $receiver];
        };

        return [[$ok], [$saveErr]];
    }

    /**
     * @dataProvider provider_transfer
     */
    public function test_transfer(Provider $p): void
    {
        [$sender, $receiver] = ($p->prepare)();
        $service = new TransactionService();
        try {
            $result = $service->transferMoney($sender, $receiver, $p->data['amount']);
            $this->assertTrue($p->success);
            $this->assertTrue($result);
        } catch (\Throwable $te) {
            $this->assertFalse($p->success);
            $this->assertInstanceOf($p->errors['exception_class'], $te);
            $this->assertSame($p->errors['message'], $te->getMessage());
        }
    }

    public static function provider_history(): array
    {
        $ok = new Provider();
        $ok->name = 'history sucesso';
        $ok->success = true;
        $ok->expected = [
            ['id' => 1, 'amount' => 10.0, 'type' => 'deposit'],
            ['id' => 2, 'amount' => 5.0, 'type' => 'withdraw'],
        ];
        $ok->prepare = function() use ($ok) {
            $query = Mockery::mock();
            $query->shouldReceive('with')->andReturnSelf();
            $item1 = new class { public function toList() { return ['id' => 1, 'amount' => 10.0, 'type' => 'deposit']; } };
            $item2 = new class { public function toList() { return ['id' => 2, 'amount' => 5.0, 'type' => 'withdraw']; } };
            $collection = new Collection([$item1, $item2]);
            $query->shouldReceive('get')->andReturn($collection);
            $user = Mockery::mock(User::class)->makePartial();
            $user->shouldReceive('transactions')->andReturn($query);
            return $user;
        };

        $err = new Provider();
        $err->name = 'history erro consulta';
        $err->success = false;
        $err->errors = ['exception_class' => \Exception::class, 'message' => 'Falha ao consultar'];
        $err->prepare = function() use ($err) {
            $query = Mockery::mock();
            $query->shouldReceive('with')->andThrow(new \Exception('Falha ao consultar'));
            $user = Mockery::mock(User::class)->makePartial();
            $user->shouldReceive('transactions')->andReturn($query);
            return $user;
        };

        return [[$ok], [$err]];
    }

    /**
     * @dataProvider provider_history
     */
    public function test_history(Provider $p): void
    {
        $user = ($p->prepare)();
        $service = new TransactionService();
        try {
            $result = $service->getTransactionHistory($user);
            $this->assertTrue($p->success);
            $this->assertInstanceOf(Collection::class, $result);
            $this->assertEquals($p->expected, $result->all());
        } catch (\Throwable $te) {
            $this->assertFalse($p->success);
            $this->assertInstanceOf($p->errors['exception_class'], $te);
            $this->assertSame($p->errors['message'], $te->getMessage());
        }
    }

    public static function provider_deposit_exceeds_daily_limit(): array
    {
        $under = new Provider();
        $under->name = 'depositExceedsDailyLimit false quando dentro do limite';
        $under->success = true;
        $under->data = ['current' => 200.0, 'amount' => 100.0];
        $under->expected = ['result' => false];
        $under->prepare = function() use ($under) {
            $service = \Mockery::mock(TransactionService::class)->makePartial();
            $user = \Mockery::mock(User::class)->makePartial();
            $service->shouldReceive('getCurrentDayDepositAmount')->with($user)->andReturn($under->data['current']);
            return [$service, $user];
        };

        $overAmount = new Provider();
        $overAmount->name = 'depositExceedsDailyLimit true quando valor excede limite';
        $overAmount->success = true;
        $overAmount->data = ['current' => 0.0, 'amount' => Transaction::DEPOSIT_LIMIT + 1];
        $overAmount->expected = ['result' => true];
        $overAmount->prepare = function() use ($overAmount) {
            $service = \Mockery::mock(TransactionService::class)->makePartial();
            $user = \Mockery::mock(User::class)->makePartial();
            $service->shouldReceive('getCurrentDayDepositAmount')->with($user)->andReturn($overAmount->data['current']);
            return [$service, $user];
        };

        $overSum = new Provider();
        $overSum->name = 'depositExceedsDailyLimit true quando soma excede limite';
        $overSum->success = true;
        $overSum->data = ['current' => Transaction::DEPOSIT_LIMIT - 100.0, 'amount' => 200.0];
        $overSum->expected = ['result' => true];
        $overSum->prepare = function() use ($overSum) {
            $service = \Mockery::mock(TransactionService::class)->makePartial();
            $user = \Mockery::mock(User::class)->makePartial();
            $service->shouldReceive('getCurrentDayDepositAmount')->with($user)->andReturn($overSum->data['current']);
            return [$service, $user];
        };

        return [[$under], [$overAmount], [$overSum]];
    }

    /**
     * @dataProvider provider_deposit_exceeds_daily_limit
     */
    public function test_deposit_exceeds_daily_limit(Provider $p): void
    {
        [$service, $user] = ($p->prepare)();
        $result = $service->depositExceedsDailyLimit($user, $p->data['amount']);
        $this->assertTrue($p->success);
        $this->assertSame($p->expected['result'], $result);
    }

    public static function provider_withdraw_exceeds_daily_limit(): array
    {
        $under = new Provider();
        $under->name = 'withdrawExceedsDailyLimit false quando dentro do limite';
        $under->success = true;
        $under->data = ['current' => 200.0, 'amount' => 100.0];
        $under->expected = ['result' => false];
        $under->prepare = function() use ($under) {
            $service = \Mockery::mock(TransactionService::class)->makePartial();
            $user = \Mockery::mock(User::class)->makePartial();
            $service->shouldReceive('getCurrentDayWithdrawAmount')->with($user)->andReturn($under->data['current']);
            return [$service, $user];
        };

        $overAmount = new Provider();
        $overAmount->name = 'withdrawExceedsDailyLimit true quando valor excede limite';
        $overAmount->success = true;
        $overAmount->data = ['current' => 0.0, 'amount' => Transaction::WITHDRAW_LIMIT + 1];
        $overAmount->expected = ['result' => true];
        $overAmount->prepare = function() use ($overAmount) {
            $service = \Mockery::mock(TransactionService::class)->makePartial();
            $user = \Mockery::mock(User::class)->makePartial();
            $service->shouldReceive('getCurrentDayWithdrawAmount')->with($user)->andReturn($overAmount->data['current']);
            return [$service, $user];
        };

        $overSum = new Provider();
        $overSum->name = 'withdrawExceedsDailyLimit true quando soma excede limite';
        $overSum->success = true;
        $overSum->data = ['current' => Transaction::WITHDRAW_LIMIT - 100.0, 'amount' => 200.0];
        $overSum->expected = ['result' => true];
        $overSum->prepare = function() use ($overSum) {
            $service = \Mockery::mock(TransactionService::class)->makePartial();
            $user = \Mockery::mock(User::class)->makePartial();
            $service->shouldReceive('getCurrentDayWithdrawAmount')->with($user)->andReturn($overSum->data['current']);
            return [$service, $user];
        };

        return [[$under], [$overAmount], [$overSum]];
    }

    /**
     * @dataProvider provider_withdraw_exceeds_daily_limit
     */
    public function test_withdraw_exceeds_daily_limit(Provider $p): void
    {
        [$service, $user] = ($p->prepare)();
        $result = $service->withdrawExceedsDailyLimit($user, $p->data['amount']);
        $this->assertTrue($p->success);
        $this->assertSame($p->expected['result'], $result);
    }

    public static function provider_get_current_day_deposit_amount(): array
    {
        $zero = new Provider();
        $zero->name = 'getCurrentDayDepositAmount retorna 0';
        $zero->success = true;
        $zero->expected = ['result' => 0.0];
        $zero->prepare = function() use ($zero) {
            $date = now()->toDateString();
            $query = \Mockery::mock();
            $query->shouldReceive('whereDate')->with('deposits.created_at', $date)->andReturnSelf();
            $query->shouldReceive('sum')->with('amount')->andReturn(0.0);
            $user = \Mockery::mock(User::class)->makePartial();
            $user->shouldReceive('deposits')->andReturn($query);
            return $user;
        };

        $some = new Provider();
        $some->name = 'getCurrentDayDepositAmount retorna soma positiva';
        $some->success = true;
        $some->expected = ['result' => 150.0];
        $some->prepare = function() use ($some) {
            $date = now()->toDateString();
            $query = \Mockery::mock();
            $query->shouldReceive('whereDate')->with('deposits.created_at', $date)->andReturnSelf();
            $query->shouldReceive('sum')->with('amount')->andReturn(150.0);
            $user = \Mockery::mock(User::class)->makePartial();
            $user->shouldReceive('deposits')->andReturn($query);
            return $user;
        };

        return [[$zero], [$some]];
    }

    /**
     * @dataProvider provider_get_current_day_deposit_amount
     */
    public function test_get_current_day_deposit_amount(Provider $p): void
    {
        $user = ($p->prepare)();
        $service = new TransactionService();
        $result = $service->getCurrentDayDepositAmount($user);
        $this->assertTrue($p->success);
        $this->assertSame($p->expected['result'], $result);
    }

    public static function provider_get_current_day_withdraw_amount(): array
    {
        $zero = new Provider();
        $zero->name = 'getCurrentDayWithdrawAmount retorna 0';
        $zero->success = true;
        $zero->expected = ['result' => 0.0];
        $zero->prepare = function() use ($zero) {
            $date = now()->toDateString();
            $query = \Mockery::mock();
            $query->shouldReceive('whereDate')->with('withdraws.created_at', $date)->andReturnSelf();
            $query->shouldReceive('sum')->with('amount')->andReturn(0.0);
            $user = \Mockery::mock(User::class)->makePartial();
            $user->shouldReceive('withdraws')->andReturn($query);
            return $user;
        };

        $some = new Provider();
        $some->name = 'getCurrentDayWithdrawAmount retorna soma positiva';
        $some->success = true;
        $some->expected = ['result' => 75.0];
        $some->prepare = function() use ($some) {
            $date = now()->toDateString();
            $query = \Mockery::mock();
            $query->shouldReceive('whereDate')->with('withdraws.created_at', $date)->andReturnSelf();
            $query->shouldReceive('sum')->with('amount')->andReturn(75.0);
            $user = \Mockery::mock(User::class)->makePartial();
            $user->shouldReceive('withdraws')->andReturn($query);
            return $user;
        };

        return [[$zero], [$some]];
    }

    /**
     * @dataProvider provider_get_current_day_withdraw_amount
     */
    public function test_get_current_day_withdraw_amount(Provider $p): void
    {
        $user = ($p->prepare)();
        $service = new TransactionService();
        $result = $service->getCurrentDayWithdrawAmount($user);
        $this->assertTrue($p->success);
        $this->assertSame($p->expected['result'], $result);
    }
}
