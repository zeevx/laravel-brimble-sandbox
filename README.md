# Laravel Brimble Sandbox

Laravel integration for [`zeevx/php-brimble-sandbox`](https://github.com/zeevx/php-brimble-sandbox): a config file, a container binding, a facade, and Artisan commands. All the API logic lives in the core package; this is the glue.

## Install

```bash
composer require zeevx/laravel-brimble-sandbox
```

The service provider and `BrimbleSandbox` facade are auto-discovered. Publish the config if you want to tweak defaults:

```bash
php artisan vendor:publish --tag=brimble-sandbox-config
```

## Configure

Set your key in `.env`:

```dotenv
BRIMBLE_SANDBOX_KEY=your-api-key
```

Optional overrides (shown with defaults):

```dotenv
BRIMBLE_SANDBOX_BASE_URL=https://sandbox.brimble.io
BRIMBLE_SANDBOX_TIMEOUT=90
BRIMBLE_SANDBOX_MAX_RETRIES=2
```

## Usage

Resolve the client from the container, inject it, or use the facade. All three give you the same configured `Zeevx\BrimbleSandbox\Sandbox` instance.

```php
use Zeevx\LaravelBrimbleSandbox\Facades\BrimbleSandbox;
use Zeevx\BrimbleSandbox\Requests\CreateSandbox;
use Zeevx\BrimbleSandbox\Requests\ExecInput;

$sb = BrimbleSandbox::sandboxes()->create(new CreateSandbox(template: 'node-22'));

echo $sb->exec(new ExecInput('node -v'))->stdout;

$sb->destroy();
```

Dependency injection:

```php
use Zeevx\BrimbleSandbox\Sandbox;

public function __construct(private readonly Sandbox $brimble) {}
```

See the [core package README](https://github.com/zeevx/php-brimble-sandbox) for the full API (exec/code, streaming, files, snapshots, volumes, pagination, error handling).

## Artisan commands

```bash
# List your sandboxes
php artisan brimble:sandbox:list --page=1 --limit=15

# Run a shell command inside a sandbox (add --stream for live output)
php artisan brimble:sandbox:exec {id} "npm test" --stream

# Destroy a sandbox (add --force to skip the prompt)
php artisan brimble:sandbox:destroy {id}
```

## Development

```bash
composer test      # Pest + Testbench
composer lint      # Pint
composer analyse   # PHPStan (Larastan)
```

## License

MIT
