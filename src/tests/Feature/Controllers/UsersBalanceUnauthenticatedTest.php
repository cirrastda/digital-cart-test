<?php

namespace Tests\Feature;

use Tests\TestCase;

class UsersBalanceUnauthenticatedTest extends TestCase
{
    public function test_users_balance_requires_authentication(): void
    {
        $response = $this->getJson('/users/balance');
        $response->assertStatus(401);
    }
}
