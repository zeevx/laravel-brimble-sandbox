<?php

declare(strict_types=1);

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Zeevx\BrimbleSandbox\Sandbox;
use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Console\Kernel;
use GuzzleHttp\Exception\ConnectException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

it('lists sandboxes via the artisan command', function () {
    $this->fakeSandbox([
        $this->envelope([
            'data' => [[
                'id' => 'aaaaaaaaaaaaaaaaaaaaaaaa',
                'name' => 'brave-otter',
                'template' => 'node-22',
                'status' => 'ready',
                'region' => [
                    'id' => 'r1', 'name' => 'eu-west', 'country' => 'France',
                    'continent' => 'Europe', 'enabled' => true, 'type' => 'sandbox',
                ],
                'specs' => ['cpu' => 1000, 'memory' => 512, 'disk' => 2],
                'auto_destroy' => false, 'one_shot' => false, 'block_outbound' => false,
                'egress' => ['mode' => 'open'], 'persistent' => false,
                'created_at' => '2026-07-04T10:00:00Z',
                'last_activity_at' => '2026-07-04T10:01:00Z',
                'expires_at' => '2026-07-04T12:00:00Z',
            ]],
            'totalCount' => 1, 'currentPage' => 1, 'totalPages' => 1, 'limit' => 15,
        ], 'Sandboxes fetched'),
    ]);

    $this->artisan('brimble:sandbox:list')
        ->assertSuccessful()
        ->expectsOutputToContain('brave-otter')
        ->expectsOutputToContain('Page 1 of 1, 1 total.');
});

it('reports when there are no sandboxes', function () {
    $this->fakeSandbox([
        $this->envelope(['data' => [], 'totalCount' => 0, 'currentPage' => 1, 'totalPages' => 0, 'limit' => 15]),
    ]);

    $this->artisan('brimble:sandbox:list')
        ->assertSuccessful()
        ->expectsOutput('No sandboxes found.');
});

it('destroys a sandbox with --force', function () {
    $history = [];
    $this->fakeSandbox([new Response(204)], $history);

    $this->artisan('brimble:sandbox:destroy', ['id' => 'aaaaaaaaaaaaaaaaaaaaaaaa', '--force' => true])
        ->assertSuccessful()
        ->expectsOutput('Sandbox aaaaaaaaaaaaaaaaaaaaaaaa destroyed.');

    expect($history[0]['request']->getMethod())->toBe('DELETE');
});

it('aborts destroy when the confirmation is declined', function () {
    $history = [];
    $this->fakeSandbox([new Response(204)], $history);

    $this->artisan('brimble:sandbox:destroy', ['id' => 'aaaaaaaaaaaaaaaaaaaaaaaa'])
        ->expectsConfirmation('Destroy sandbox aaaaaaaaaaaaaaaaaaaaaaaa?', 'no')
        ->assertSuccessful()
        ->expectsOutput('Aborted.');

    expect($history)->toBeEmpty();
});

it('runs a command inside a sandbox and returns its exit code', function () {
    $this->fakeSandbox([
        $this->envelope([
            'stdout' => "hello\n", 'stderr' => '', 'exit_code' => 0, 'duration_ms' => 12,
        ], 'Exec completed'),
    ]);

    $this->artisan('brimble:sandbox:exec', ['id' => 'aaaaaaaaaaaaaaaaaaaaaaaa', 'cmd' => 'echo hello'])
        ->assertSuccessful()
        ->expectsOutputToContain('hello');
});

it('prints buffered command output without interpreting console tags', function () {
    $this->fakeSandbox([
        $this->envelope([
            'stdout' => "</error><info>taggy</info>\n",
            'stderr' => '<fg=red>raw stderr</>',
            'exit_code' => 0,
            'duration_ms' => 12,
        ], 'Exec completed'),
    ]);

    $this->artisan('brimble:sandbox:exec', ['id' => 'aaaaaaaaaaaaaaaaaaaaaaaa', 'cmd' => 'echo hello'])
        ->assertSuccessful()
        ->expectsOutputToContain('</error><info>taggy</info>')
        ->expectsOutputToContain('<fg=red>raw stderr</>');
});

it('prints streamed command output without interpreting console tags', function () {
    $this->fakeSandbox([
        new Response(200, ['Content-Type' => 'text/event-stream'], "data: {\"type\":\"stdout\",\"data\":\"</error><info>taggy</info>\\n\"}\n\n"
            ."data: {\"type\":\"stderr\",\"data\":\"<fg=red>raw stderr</>\"}\n\n"
            ."data: {\"type\":\"done\",\"exit_code\":0,\"duration_ms\":12}\n\n"),
    ]);

    $this->artisan('brimble:sandbox:exec', ['id' => 'aaaaaaaaaaaaaaaaaaaaaaaa', 'cmd' => 'echo hello', '--stream' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('</error><info>taggy</info>')
        ->expectsOutputToContain('<fg=red>raw stderr</>');
});

it('keeps buffered stdout and stderr on separate streams', function () {
    $this->fakeSandbox([
        $this->envelope([
            'stdout' => "</error><info>taggy</info>\n",
            'stderr' => '<fg=red>raw stderr</>',
            'exit_code' => 0,
            'duration_ms' => 12,
        ], 'Exec completed'),
    ]);

    $output = new TestConsoleOutput;

    $exitCode = $this->app->make(Kernel::class)->call(
        'brimble:sandbox:exec',
        ['id' => 'aaaaaaaaaaaaaaaaaaaaaaaa', 'cmd' => 'echo hello'],
        new OutputStyle(new ArrayInput([]), $output),
    );

    $stdout = $output->fetch();
    $stderr = $output->fetchError();

    expect($exitCode)->toBe(0)
        ->and($stdout)->toContain('</error><info>taggy</info>')
        ->and($stdout)->not->toContain('<fg=red>raw stderr</>')
        ->and($stderr)->toContain('<fg=red>raw stderr</>')
        ->and($stderr)->not->toContain('</error><info>taggy</info>');
});

it('keeps streamed stdout and stderr on separate streams', function () {
    $this->fakeSandbox([
        new Response(200, ['Content-Type' => 'text/event-stream'], "data: {\"type\":\"stdout\",\"data\":\"</error><info>taggy</info>\\n\"}\n\n"
            ."data: {\"type\":\"stderr\",\"data\":\"<fg=red>raw stderr</>\"}\n\n"
            ."data: {\"type\":\"done\",\"exit_code\":0,\"duration_ms\":12}\n\n"),
    ]);

    $output = new TestConsoleOutput;

    $exitCode = $this->app->make(Kernel::class)->call(
        'brimble:sandbox:exec',
        ['id' => 'aaaaaaaaaaaaaaaaaaaaaaaa', 'cmd' => 'echo hello', '--stream' => true],
        new OutputStyle(new ArrayInput([]), $output),
    );

    $stdout = $output->fetch();
    $stderr = $output->fetchError();

    expect($exitCode)->toBe(0)
        ->and($stdout)->toContain('</error><info>taggy</info>')
        ->and($stdout)->not->toContain('<fg=red>raw stderr</>')
        ->and($stderr)->toContain('<fg=red>raw stderr</>')
        ->and($stderr)->not->toContain('</error><info>taggy</info>');
});

it('returns a failure exit code when the command fails', function () {
    $this->fakeSandbox([
        $this->envelope([
            'stdout' => '', 'stderr' => "boom\n", 'exit_code' => 1, 'duration_ms' => 3,
        ], 'Exec completed'),
    ]);

    $this->artisan('brimble:sandbox:exec', ['id' => 'aaaaaaaaaaaaaaaaaaaaaaaa', 'cmd' => 'false'])
        ->assertFailed();
});

it('surfaces API errors as command failures', function () {
    $this->fakeSandbox([
        new Response(404, ['Content-Type' => 'application/json'], (string) json_encode(['message' => 'Sandbox not found'])),
    ]);

    $this->artisan('brimble:sandbox:exec', ['id' => 'aaaaaaaaaaaaaaaaaaaaaaaa', 'cmd' => 'echo hi'])
        ->assertFailed()
        ->expectsOutputToContain('Sandbox not found');
});

it('surfaces transport errors as command failures', function () {
    $this->fakeSandbox([
        new ConnectException('boom', new Request('POST', 'sandboxes/aaaaaaaaaaaaaaaaaaaaaaaa/exec')),
    ]);

    $this->artisan('brimble:sandbox:exec', ['id' => 'aaaaaaaaaaaaaaaaaaaaaaaa', 'cmd' => 'echo hi'])
        ->assertFailed()
        ->expectsOutputToContain('Request to POST /sandboxes/aaaaaaaaaaaaaaaaaaaaaaaa/exec failed: boom');
});

it('surfaces configuration errors as command failures', function () {
    putenv('BRIMBLE_SANDBOX_KEY');
    unset($_SERVER['BRIMBLE_SANDBOX_KEY'], $_ENV['BRIMBLE_SANDBOX_KEY']);

    $this->app['config']->set('brimble-sandbox.api_key', null);
    $this->app->forgetInstance(Sandbox::class);

    $this->artisan('brimble:sandbox:list')
        ->assertFailed()
        ->expectsOutputToContain('No Brimble API key provided.');
});

final class TestConsoleOutput extends BufferedOutput implements ConsoleOutputInterface
{
    private OutputInterface $errorOutput;

    public function __construct()
    {
        parent::__construct();

        $this->errorOutput = new BufferedOutput;
    }

    public function getErrorOutput(): OutputInterface
    {
        return $this->errorOutput;
    }

    public function setErrorOutput(OutputInterface $error): void
    {
        $this->errorOutput = $error;
    }

    public function section(): ConsoleSectionOutput
    {
        throw new LogicException('Console sections are not needed in these tests.');
    }

    public function fetchError(): string
    {
        if (! $this->errorOutput instanceof BufferedOutput) {
            throw new LogicException('The test error output must be buffered.');
        }

        return $this->errorOutput->fetch();
    }
}
