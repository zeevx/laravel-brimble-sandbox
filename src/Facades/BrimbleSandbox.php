<?php

declare(strict_types=1);

namespace Zeevx\LaravelBrimbleSandbox\Facades;

use Zeevx\BrimbleSandbox\Sandbox;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \Zeevx\BrimbleSandbox\Resources\Sandboxes sandboxes()
 * @method static \Zeevx\BrimbleSandbox\Resources\Volumes volumes()
 * @method static \Zeevx\BrimbleSandbox\Resources\Snapshots snapshots()
 * @method static \Zeevx\BrimbleSandbox\Resources\Catalog catalog()
 * @method static \Zeevx\BrimbleSandbox\Resources\SandboxHandle sandbox(string $id)
 * @method static list<\Zeevx\BrimbleSandbox\Data\Region> regions()
 * @method static list<\Zeevx\BrimbleSandbox\Data\Template> templates()
 *
 * @see Sandbox
 */
final class BrimbleSandbox extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Sandbox::class;
    }
}
