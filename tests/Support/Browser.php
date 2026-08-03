<?php
declare(strict_types=1);

use Illuminate\Support\Env;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Orchestra\Testbench\Factories\UserFactory;
use Pest\Browser\Api\AwaitableWebpage;
use Pest\Browser\Api\PendingAwaitablePage;
use Pest\Browser\Api\Webpage;
use Workbench\App\Models\User;

/**
 * Retries browser assertions while asynchronous UI work settles.
 *
 * @param  Closure(): void  $assert
 * @param  (Closure(): void)|null  $between
 */
function retryUntil(Closure $assert, int $attempts = 20, int $sleepMicroseconds = 500_000, ?Closure $between = null): void
{
    foreach (range(1, $attempts) as $attempt) {
        try {
            $assert();

            return;
        } catch (Throwable $exception) {
            if ($attempt === $attempts) {
                throw $exception;
            }

            $between?->__invoke();

            \Amp\delay($sleepMicroseconds / 1_000_000);
        }
    }
}

function assertSeeEventually(AwaitableWebpage|PendingAwaitablePage|Webpage $page, string|int|float $text): void
{
    retryUntil(function () use ($page, $text): void {
        $page->assertSee($text);
    });
}

function assertDontSeeEventually(AwaitableWebpage|PendingAwaitablePage|Webpage $page, string|int|float $text): void
{
    retryUntil(function () use ($page, $text): void {
        $page->assertDontSee($text);
    });
}

function assertPresentEventually(AwaitableWebpage|PendingAwaitablePage|Webpage $page, string $selector): void
{
    retryUntil(function () use ($page, $selector): void {
        $page->assertPresent($selector);
    });
}

function rustfsIsReachable(): bool
{
    // CI provisions RustFS itself, so never skip there — an unreachable disk
    // must fail the suite loudly instead of passing green as "skipped".
    if (Env::get('CI') !== null) {
        return true;
    }

    $key = 'lattice-test-probes/'.Str::uuid().'.txt';

    try {
        $disk = Storage::disk('s3');

        if ($disk->put($key, 'ok') !== true) {
            return false;
        }

        $disk->delete($key);

        return true;
    } catch (Throwable) {
        return false;
    }
}

/**
 * @param  array<string, mixed>  $attributes
 */
function workbenchTestUser(array $attributes = []): User
{
    $user = UserFactory::new()->create([
        'name' => 'Authenticated Workbench User',
        'email' => 'workbench-test-'.Str::random(12).'@example.com',
        ...$attributes,
    ]);

    if (! $user instanceof User) {
        throw new RuntimeException('Expected the workbench auth model to be an instance of '.User::class);
    }

    return $user;
}
