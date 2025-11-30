<?php

namespace Tests;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use stdClass;

class Provider extends stdClass{
    public string $name;
    public array $data;
    public bool $success;
    public array $errors;
    public array $expected;
    public \Closure $prepare;
}

