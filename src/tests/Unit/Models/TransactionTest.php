<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Tests\Provider;

class TransactionTest extends TestCase
{
    public static function provider_to_list(): array
    {
        $deposit = new Provider();
        $deposit->name = 'toList deposit';
        $deposit->success = true;
        $deposit->data = ['amount' => 100];
        $deposit->expected = ['amount' => 100];
        $deposit->prepare = function() use ($deposit) {
            $t = new Transaction();
            $t->id = 1;
            $t->type = 'deposit';
            $t->created_at = now();
            $dep = new class($deposit) { public $amount; public function __construct($p){ $this->amount = $p->data['amount']; } };
            $t->setRelation('deposits', new Collection([$dep]));
            return $t;
        };

        $withdraw = new Provider();
        $withdraw->name = 'toList withdraw';
        $withdraw->success = true;
        $withdraw->data = ['amount' => 40];
        $withdraw->expected = ['amount' => 40];
        $withdraw->prepare = function() use ($withdraw) {
            $t = new Transaction();
            $t->id = 1;
            $t->type = 'withdraw';
            $t->created_at = now();
            $w = new class($withdraw) { public $amount; public function __construct($p){ $this->amount = $p->data['amount']; } };
            $t->setRelation('withdraws', new Collection([$w]));
            return $t;
        };

        $transfer = new Provider();
        $transfer->name = 'toList transfer';
        $transfer->success = true;
        $transfer->data = ['amount' => -30, 'recipient' => 'Dest'];
        $transfer->expected = ['amount' => -30, 'recipient' => 'Dest'];
        $transfer->prepare = function() use ($transfer) {
            $t = new Transaction();
            $t->id = 1;
            $t->type = 'transfer';
            $t->created_at = now();
            $recipient = new class($transfer) { public $name; public function __construct($p){ $this->name = $p->data['recipient']; } };
            $tr = new class($transfer, $recipient) { public $amount; public $recipient; public function __construct($p, $r){ $this->amount = $p->data['amount']; $this->recipient = $r; } };
            $t->setRelation('transfers', new Collection([$tr]));
            return $t;
        };

        return [[$deposit], [$withdraw], [$transfer]];
    }

    /**
     * @dataProvider provider_to_list
     */
    public function test_to_list(Provider $p): void
    {
        $t = ($p->prepare)();
        $r = $t->toList();
        $this->assertSame($p->expected['amount'], $r['amount']);
        if (isset($p->expected['recipient'])) {
            $this->assertSame($p->expected['recipient'], $r['recipient']);
        }
    }
}