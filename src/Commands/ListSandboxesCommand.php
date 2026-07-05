<?php

declare(strict_types=1);

namespace Zeevx\LaravelBrimbleSandbox\Commands;

use Illuminate\Console\Command;
use Zeevx\BrimbleSandbox\Sandbox;
use Zeevx\BrimbleSandbox\Data\SandboxData;
use Zeevx\BrimbleSandbox\Exceptions\BrimbleException;

final class ListSandboxesCommand extends Command
{
    protected $signature = 'brimble:sandbox:list {--page=1 : Page number} {--limit=15 : Results per page}';

    protected $description = 'List your Brimble sandboxes';

    public function handle(): int
    {
        try {
            $client = $this->sandboxClient();
            $page = $client->sandboxes()->list(
                (int) $this->option('page'),
                (int) $this->option('limit'),
            );
        } catch (BrimbleException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($page->count() === 0) {
            $this->info('No sandboxes found.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Name', 'Template', 'Status', 'Created'],
            array_map(static fn (SandboxData $sb): array => [
                $sb->id,
                $sb->name,
                $sb->template,
                $sb->status->value,
                $sb->createdAt,
            ], $page->data),
        );

        $this->line(sprintf(
            'Page %d of %d, %d total.',
            $page->currentPage,
            $page->totalPages,
            $page->totalCount,
        ));

        return self::SUCCESS;
    }

    private function sandboxClient(): Sandbox
    {
        return $this->laravel->make(Sandbox::class);
    }
}
