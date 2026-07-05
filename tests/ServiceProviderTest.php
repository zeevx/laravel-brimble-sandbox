<?php

declare(strict_types=1);

use Zeevx\BrimbleSandbox\Sandbox;
use Illuminate\Contracts\Console\Kernel;
use Zeevx\LaravelBrimbleSandbox\Facades\BrimbleSandbox;

it('binds the Sandbox client as a singleton', function () {
    $first = $this->app->make(Sandbox::class);
    $second = $this->app->make(Sandbox::class);

    expect($first)->toBeInstanceOf(Sandbox::class)
        ->and($first)->toBe($second);
});

it('resolves the client through the string alias', function () {
    expect($this->app->make('brimble-sandbox'))->toBeInstanceOf(Sandbox::class);
});

it('merges the package config', function () {
    expect(config('brimble-sandbox.base_url'))->toBe('https://sandbox.brimble.io')
        ->and(config('brimble-sandbox.timeout'))->toBe(90.0)
        ->and(config('brimble-sandbox.max_retries'))->toBe(2);
});

it('resolves the facade root to the Sandbox client', function () {
    expect(BrimbleSandbox::getFacadeRoot())->toBeInstanceOf(Sandbox::class);
});

it('registers the artisan commands', function () {
    $commands = array_keys($this->app[Kernel::class]->all());

    expect($commands)->toContain('brimble:sandbox:list')
        ->toContain('brimble:sandbox:destroy')
        ->toContain('brimble:sandbox:exec');
});
