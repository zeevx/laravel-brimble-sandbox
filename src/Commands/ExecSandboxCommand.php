<?php

declare(strict_types=1);

namespace Zeevx\LaravelBrimbleSandbox\Commands;

use Illuminate\Console\Command;
use Zeevx\BrimbleSandbox\Sandbox;
use Zeevx\BrimbleSandbox\Requests\ExecInput;
use Zeevx\BrimbleSandbox\Resources\SandboxHandle;
use Symfony\Component\Console\Output\OutputInterface;
use Zeevx\BrimbleSandbox\Exceptions\BrimbleException;
use Symfony\Component\Console\Output\ConsoleOutputInterface;

final class ExecSandboxCommand extends Command
{
    protected $signature = 'brimble:sandbox:exec {id : The sandbox id} {cmd : The shell command to run} {--stream : Stream output as it arrives}';

    protected $description = 'Run a shell command inside a Brimble sandbox';

    public function handle(): int
    {
        try {
            $client = $this->sandboxClient();
            $handle = $client->sandbox((string) $this->argument('id'));
            $input = new ExecInput((string) $this->argument('cmd'));

            return $this->option('stream')
                ? $this->runStreaming($handle, $input)
                : $this->runBuffered($handle, $input);
        } catch (BrimbleException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    private function runBuffered(SandboxHandle $handle, ExecInput $input): int
    {
        $result = $handle->exec($input);

        if ($result->stdout !== '') {
            $this->writeStdout($result->stdout);
        }

        if ($result->stderr !== '') {
            $this->writeStderr($result->stderr);
        }

        return $result->succeeded() ? self::SUCCESS : self::FAILURE;
    }

    private function runStreaming(SandboxHandle $handle, ExecInput $input): int
    {
        $stream = $handle->execStream($input);

        foreach ($stream as $frame) {
            if ($frame->isStdout()) {
                $this->writeStdout($frame->data ?? '');
            } elseif ($frame->isStderr()) {
                $this->writeStderr($frame->data ?? '');
            }
        }

        return $stream->result()->succeeded() ? self::SUCCESS : self::FAILURE;
    }

    private function sandboxClient(): Sandbox
    {
        return $this->laravel->make(Sandbox::class);
    }

    private function writeStdout(string $message): void
    {
        $this->output->write($message, false, OutputInterface::OUTPUT_RAW);
    }

    private function writeStderr(string $message): void
    {
        $consoleOutput = $this->output->getOutput();

        if ($consoleOutput instanceof ConsoleOutputInterface) {
            $consoleOutput->getErrorOutput()->write($message, false, OutputInterface::OUTPUT_RAW);

            return;
        }

        $consoleOutput->write($message, false, OutputInterface::OUTPUT_RAW);
    }
}
