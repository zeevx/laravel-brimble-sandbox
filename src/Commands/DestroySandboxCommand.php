<?php

declare(strict_types=1);

namespace Zeevx\LaravelBrimbleSandbox\Commands;

use Illuminate\Console\Command;
use Zeevx\BrimbleSandbox\Sandbox;
use Zeevx\BrimbleSandbox\Exceptions\BrimbleException;

final class DestroySandboxCommand extends Command
{
    protected $signature = 'brimble:sandbox:destroy {id : The sandbox id} {--force : Skip the confirmation prompt}';

    protected $description = 'Destroy a Brimble sandbox';

    public function handle(): int
    {
        $id = (string) $this->argument('id');

        if (! $this->option('force') && ! $this->confirm("Destroy sandbox {$id}?")) {
            $this->line('Aborted.');

            return self::SUCCESS;
        }

        try {
            $client = $this->sandboxClient();
            $client->sandbox($id)->destroy();
        } catch (BrimbleException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info("Sandbox {$id} destroyed.");

        return self::SUCCESS;
    }

    private function sandboxClient(): Sandbox
    {
        return $this->laravel->make(Sandbox::class);
    }
}
