<?php

declare(strict_types=1);

namespace Zeevx\LaravelBrimbleSandbox\Tests;

use Throwable;
use GuzzleHttp\Client;
use GuzzleHttp\Middleware;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Zeevx\BrimbleSandbox\Config;
use Zeevx\BrimbleSandbox\Sandbox;
use GuzzleHttp\Handler\MockHandler;
use Orchestra\Testbench\TestCase as Orchestra;
use Zeevx\LaravelBrimbleSandbox\BrimbleSandboxServiceProvider;

abstract class TestCase extends Orchestra
{
    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [BrimbleSandboxServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('brimble-sandbox.api_key', 'test-key');
    }

    /**
     * Bind a Sandbox client backed by a Guzzle MockHandler queue and return
     * the request history array for assertions.
     *
     * @param  list<Response|Throwable>  $responses
     * @param  array<int, array<string, mixed>>  $history
     */
    protected function fakeSandbox(array $responses, array &$history = []): Sandbox
    {
        $stack = HandlerStack::create(new MockHandler($responses));
        $stack->push(Middleware::history($history));

        $config = new Config(apiKey: 'test-key', maxRetries: 0, retryBaseDelay: 0.0);
        $client = Sandbox::fromConfig($config, new Client(['handler' => $stack, 'http_errors' => false]));

        $this->app->instance(Sandbox::class, $client);

        return $client;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function envelope(array $data, string $message = 'ok', int $status = 200): Response
    {
        return new Response($status, ['Content-Type' => 'application/json'], (string) json_encode([
            'message' => $message,
            'data' => $data,
        ]));
    }
}
