<?php

declare(strict_types=1);

namespace Zeevx\LaravelBrimbleSandbox;

use Zeevx\BrimbleSandbox\Config;
use Zeevx\BrimbleSandbox\Sandbox;
use Illuminate\Support\ServiceProvider;
use Illuminate\Contracts\Config\Repository;
use Zeevx\LaravelBrimbleSandbox\Commands\ExecSandboxCommand;
use Zeevx\LaravelBrimbleSandbox\Commands\ListSandboxesCommand;
use Zeevx\LaravelBrimbleSandbox\Commands\DestroySandboxCommand;

final class BrimbleSandboxServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/brimble-sandbox.php', 'brimble-sandbox');

        $this->app->singleton(Sandbox::class, function ($app): Sandbox {
            /** @var Repository $config */
            $config = $app['config'];

            return Sandbox::fromConfig(new Config(
                apiKey: $config->get('brimble-sandbox.api_key'),
                baseUrl: $config->get('brimble-sandbox.base_url'),
                timeout: (float) $config->get('brimble-sandbox.timeout', 90.0),
                maxRetries: (int) $config->get('brimble-sandbox.max_retries', 2),
            ));
        });

        $this->app->alias(Sandbox::class, 'brimble-sandbox');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/brimble-sandbox.php' => config_path('brimble-sandbox.php'),
            ], 'brimble-sandbox-config');

            $this->commands([
                ListSandboxesCommand::class,
                DestroySandboxCommand::class,
                ExecSandboxCommand::class,
            ]);
        }
    }

    /**
     * @return array<int, string>
     */
    public function provides(): array
    {
        return [Sandbox::class, 'brimble-sandbox'];
    }
}
